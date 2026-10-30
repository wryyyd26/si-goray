<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DailyRegularQuotaController extends Controller
{
    private const SESSIONS = [
        ['08:00:00', '10:00:00'],
        ['10:00:00', '12:00:00'],
        ['12:00:00', '14:00:00'],
        ['14:00:00', '16:00:00'],
        ['16:00:00', '18:00:00'],
        ['18:00:00', '20:00:00'],
    ];

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
            'capacity_per_session' => [
                'required',
                'integer',
                'between:1,100000',
            ],
            'quotas' => [
                'required',
                'array',
                'size:6',
            ],
            'quotas.*' => [
                'required',
                'integer',
                'min:0',
                'max:100000',
            ],
        ]);

        $date = CarbonImmutable::createFromFormat(
            '!Y-m-d',
            $data['date']
        )->toDateString();

        if (array_keys($data['quotas']) !== range(0, 5)) {
            throw ValidationException::withMessages([
                'quotas' => 'Enam kuota sesi wajib dikirim secara lengkap.',
            ]);
        }

        $capacity = (int) $data['capacity_per_session'];

        foreach ($data['quotas'] as $index => $quota) {
            if ((int) $quota > $capacity) {
                throw ValidationException::withMessages([
                    'quotas.' . $index =>
                        'Kuota sesi tidak boleh melebihi kapasitas tribun.',
                ]);
            }
        }

        // Kunci per tanggal untuk mencegah perubahan bersamaan
        // pada MySQL, termasuk saat membuat slot pertama.
        $mysql = DB::getDriverName() === 'mysql';
        $lockName = 'sigoray_daily_quota_' .
            str_replace('-', '', $date);

        if ($mysql) {
            $result = DB::selectOne(
                'SELECT GET_LOCK(?, 10) AS acquired',
                [$lockName]
            );

            if ((int) ($result->acquired ?? 0) !== 1) {
                throw ValidationException::withMessages([
                    'date' => 'Tanggal ini sedang diproses. Coba lagi.',
                ]);
            }
        }

        try {
            DB::transaction(function () use ($date, $data) {
                $slots = DB::table('regular_slots')
                    ->whereDate('tanggal', $date)
                    ->lockForUpdate()
                    ->get();

                $normalize = fn ($time) =>
                    substr((string) $time, 0, 8);

                // Jangan diam-diam menimpa sesi kustom.
                foreach ($slots as $slot) {
                    $valid = collect(self::SESSIONS)->contains(
                        fn ($s) =>
                            $normalize($slot->jam_mulai) === $s[0]
                            && $normalize($slot->jam_selesai) === $s[1]
                    );

                    if (!$valid) {
                        throw ValidationException::withMessages([
                            'date' =>
                                'Ada jadwal sesi kustom pada tanggal ini. ' .
                                'Periksa jadwal tersebut terlebih dahulu.',
                        ]);
                    }
                }

                // Periksa event untuk slot baru.
                $events = DB::table('event_schedules as es')
                    ->join('events as e', 'e.id', '=', 'es.event_id')
                    ->whereDate('es.tanggal', $date)
                    ->where('e.status', 'AKTIF')
                    ->select('es.jam_mulai', 'es.jam_selesai')
                    ->get();

                foreach (self::SESSIONS as $i => [$start, $end]) {
                    $quota = (int) $data['quotas'][$i];

                    $matches = $slots->filter(
                        fn ($slot) =>
                            $normalize($slot->jam_mulai) === $start
                            && $normalize($slot->jam_selesai) === $end
                    );

                    if ($matches->count() > 1) {
                        throw ValidationException::withMessages([
                            'date' => 'Terdapat slot duplikat.',
                        ]);
                    }

                    $existing = $matches->first();

                    if ($existing) {
                        $used = (int) $existing->kuota_terpakai;

                        if ($quota < $used) {
                            throw ValidationException::withMessages([
                                'quotas.' . $i =>
                                    'Kuota sesi ' . ($i + 1) .
                                    ' lebih kecil dari tiket terpakai.',
                            ]);
                        }

                        DB::table('regular_slots')
                            ->where('id', $existing->id)
                            ->update([
                                'kapasitas' => $quota,
                                'status' => $quota === 0
                                    ? 'inactive'
                                    : $existing->status,
                            ]);

                        continue;
                    }

                    // Kuota nol berarti slot baru tidak dibuat.
                    if ($quota === 0) {
                        continue;
                    }

                    foreach ($events as $event) {
                        $eventStart = $normalize($event->jam_mulai);
                        $eventEnd = $normalize($event->jam_selesai);

                        if ($eventStart < $end && $eventEnd > $start) {
                            throw ValidationException::withMessages([
                                'quotas.' . $i =>
                                    'Sesi ' . ($i + 1) .
                                    ' bertabrakan dengan jadwal event.',
                            ]);
                        }
                    }

                    DB::table('regular_slots')->insert([
                        'id' => (string) Str::uuid(),
                        'tanggal' => $date,
                        'jam_mulai' => $start,
                        'jam_selesai' => $end,
                        'kapasitas' => $quota,
                        'kuota_terpakai' => 0,
                        'status' => 'active',
                    ]);
                }
            }, 3);
        } finally {
            if ($mysql) {
                DB::selectOne(
                    'SELECT RELEASE_LOCK(?) AS released',
                    [$lockName]
                );
            }
        }

        return redirect()
            ->route('admin.reguler.index', [
                'month' => substr($date, 0, 7),
                'date' => $date,
            ])
            ->with(
                'success',
                'Kuota enam sesi untuk tanggal ini berhasil disimpan.'
            );
    }
}