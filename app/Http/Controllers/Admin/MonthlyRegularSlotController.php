<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MonthlyRegularSlotController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2026,2100'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
            ],
            'quota' => ['required', 'integer', 'min:1'],
        ]);

        $toMinutes = function (string $time): int {
            [$hour, $minute] = array_map(
                'intval',
                explode(':', $time)
            );

            return $hour * 60 + $minute;
        };

        $startMinutes = $toMinutes($data['start_time']);
        $endMinutes = $toMinutes($data['end_time']);

        // Setiap sesi harus tepat dua jam.
        // Rentang operasional harus bisa dibagi menjadi
        // beberapa sesi penuh tanpa sisa waktu.
        if (($endMinutes - $startMinutes) % 120 !== 0) {
            throw ValidationException::withMessages([
                'end_time' =>
                    'Rentang jam harus merupakan kelipatan 2 jam.',
            ]);
        }

        $firstDate = CarbonImmutable::create(
            (int) $data['year'],
            (int) $data['month'],
            1,
            0,
            0,
            0
        );

        $lastDate = $firstDate->endOfMonth();

        $today = CarbonImmutable::today();

        if ($lastDate->lt($today)) {
            throw ValidationException::withMessages([
                'month' => 'Tidak dapat membuat slot untuk bulan yang sudah berlalu.',
            ]);
        }

        // Cegah dua proses generator untuk bulan
        // yang sama berjalan bersamaan di MySQL.
        $lockName = sprintf(
            'sigoray_regular_%04d_%02d',
            $data['year'],
            $data['month']
        );

        $lock = DB::selectOne(
            'SELECT GET_LOCK(?, 10) AS acquired',
            [$lockName]
        );

        if ((int) ($lock->acquired ?? 0) !== 1) {
            throw ValidationException::withMessages([
                'month' => 'Generator sedang digunakan. Silakan coba lagi.',
            ]);
        }

        try {
            $result = DB::transaction(function () use (
                $data,
                $firstDate,
                $lastDate,
                $today,
                $startMinutes,
                $endMinutes
            ) {
                $created = 0;
                $skippedExisting = 0;
                $skippedEvent = 0;

                $events = DB::table('event_schedules as es')
                    ->join('events as e', 'e.id', '=', 'es.event_id')
                    ->where('e.status', 'AKTIF')
                    ->whereBetween('es.tanggal', [
                        $firstDate->toDateString(),
                        $lastDate->toDateString(),
                    ])
                    ->select(
                        'es.tanggal',
                        'es.jam_mulai',
                        'es.jam_selesai'
                    )
                    ->get()
                    ->groupBy('tanggal');

                $existingSlots = DB::table('regular_slots')
                    ->whereBetween('tanggal', [
                        $firstDate->toDateString(),
                        $lastDate->toDateString(),
                    ])
                    ->select(
                        'tanggal',
                        'jam_mulai',
                        'jam_selesai'
                    )
                    ->get()
                    ->groupBy('tanggal');

                $newRows = [];

                for (
                    $date = $firstDate;
                    $date->lte($lastDate);
                    $date = $date->addDay()
                ) {
                    if ($date->lt($today)) {
                        continue;
                    }

                    $dateString = $date->toDateString();

                    for (
                        $start = $startMinutes;
                        $start + 120 <= $endMinutes;
                        $start += 120
                    ) {
                        $end = $start + 120;

                        $startTime = sprintf(
                            '%02d:%02d:00',
                            intdiv($start, 60),
                            $start % 60
                        );

                        $endTime = sprintf(
                            '%02d:%02d:00',
                            intdiv($end, 60),
                            $end % 60
                        );

                        // Hindari tabrakan dengan event aktif.
                        $eventConflict = (
                            $events->get($dateString) ?? collect()
                        )->contains(function ($event) use (
                            $startTime,
                            $endTime
                        ) {
                            return $event->jam_mulai < $endTime
                                && $event->jam_selesai > $startTime;
                        });

                        if ($eventConflict) {
                            $skippedEvent++;
                            continue;
                        }

                        // Jangan mengubah ataupun menggandakan
                        // slot reguler yang sudah tersimpan.
                        $existingConflict = (
                            $existingSlots->get($dateString) ?? collect()
                        )->contains(function ($slot) use (
                            $startTime,
                            $endTime
                        ) {
                            return $slot->jam_mulai < $endTime
                                && $slot->jam_selesai > $startTime;
                        });

                        if ($existingConflict) {
                            $skippedExisting++;
                            continue;
                        }

                        $newRows[] = [
                            'id' => (string) Str::uuid(),
                            'tanggal' => $dateString,
                            'jam_mulai' => $startTime,
                            'jam_selesai' => $endTime,
                            'kapasitas' => (int) $data['quota'],
                            'kuota_terpakai' => 0,
                            'status' => 'active',
                        ];

                        $created++;
                    }
                }

                foreach (array_chunk($newRows, 100) as $chunk) {
                    DB::table('regular_slots')->insert($chunk);
                }

                return [
                    'created' => $created,
                    'skipped_existing' => $skippedExisting,
                    'skipped_event' => $skippedEvent,
                ];
            }, 3);
        } finally {
            DB::selectOne(
                'SELECT RELEASE_LOCK(?) AS released',
                [$lockName]
            );
        }

        return redirect()
            ->route('admin.reguler.index')
            ->with(
                'success',
                "Generator selesai. {$result['created']} slot dibuat, "
                . "{$result['skipped_existing']} dilewati karena sudah ada, "
                . "{$result['skipped_event']} dilewati karena jadwal event."
            );
    }
}
