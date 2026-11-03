<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegularSlotCalendarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2026,2100'],
        ]);

        $firstDate = CarbonImmutable::create(
            (int) $data['year'],
            (int) $data['month'],
            1,
            0,
            0,
            0
        );

        $lastDate = $firstDate->endOfMonth();
        $today = CarbonImmutable::today()->toDateString();

        $startDate = max(
            $firstDate->toDateString(),
            $today
        );

        $endDate = $lastDate->toDateString();

        /*
         * 1. Ambil jadwal event AKTIF pada bulan terpilih.
         *
         * Semua event pada satu tanggal dikembalikan,
         * bukan hanya event pertama.
         */
        $eventSchedules = DB::table('event_schedules as es')
            ->join('events as e', 'e.id', '=', 'es.event_id')
            ->where('e.status', 'AKTIF')
            ->whereBetween('es.tanggal', [
                $startDate,
                $endDate,
            ])
            ->orderBy('es.tanggal')
            ->orderBy('es.jam_mulai')
            ->get([
                'es.id as schedule_id',
                'es.event_id',
                'es.tanggal',
                'es.jam_mulai',
                'es.jam_selesai',
                'es.kapasitas',
                'es.kuota_terpakai',
                'e.nama_event',
            ]);

        $eventDays = $eventSchedules
            ->groupBy(function ($schedule) {
                return CarbonImmutable::parse(
                    $schedule->tanggal
                )->toDateString();
            })
            ->map(function ($schedules, $date) {
                return [
                    'date' => $date,
                    'events' => $schedules
                        ->map(function ($schedule) {
                            $remaining = max(
                                0,
                                (int) $schedule->kapasitas
                                - (int) $schedule->kuota_terpakai
                            );

                            return [
                                'id' => (string) $schedule->event_id,
                                'schedule_id' =>
                                    (string) $schedule->schedule_id,
                                'title' => $schedule->nama_event,
                                'start_time' => substr(
                                    (string) $schedule->jam_mulai,
                                    0,
                                    5
                                ),
                                'end_time' => substr(
                                    (string) $schedule->jam_selesai,
                                    0,
                                    5
                                ),
                                'remaining' => $remaining,
                                'is_full' => $remaining === 0,
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values();

        /*
         * 2. Ambil slot reguler.
         *
         * NOT EXISTS mencegah semua sesi reguler
         * pada tanggal event AKTIF ditawarkan kepada
         * pengunjung, sekalipun jamnya berbeda.
         *
         * Data regular_slots tidak dihapus.
         */
        $slots = DB::table('regular_slots')
            ->whereBetween('tanggal', [
                $startDate,
                $endDate,
            ])
            ->where('status', 'active')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('event_schedules as es')
                    ->join(
                        'events as e',
                        'e.id',
                        '=',
                        'es.event_id'
                    )
                    ->where(
                        'e.status',
                        'AKTIF'
                    )
                    ->whereColumn(
                        'es.tanggal',
                        'regular_slots.tanggal'
                    );
            })
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get()
            ->map(function ($slot) {
                $quota = (int) $slot->kapasitas;
                $used = (int) $slot->kuota_terpakai;
                $available = max(0, $quota - $used);

                return [
                    'id' => $slot->id,
                    'date' => $slot->tanggal,
                    'start_time' => substr(
                        (string) $slot->jam_mulai,
                        0,
                        5
                    ),
                    'end_time' => substr(
                        (string) $slot->jam_selesai,
                        0,
                        5
                    ),
                    'quota' => $quota,
                    'used' => $used,
                    'available' => $available,
                    'status' => $available > 0
                        ? 'available'
                        : 'full',
                ];
            })
            ->values();

        /*
         * 3. Respons kompatibel dengan API sebelumnya.
         *
         * Frontend lama tetap dapat membaca slots.
         * Frontend baru dapat memakai event_days
         * untuk menampilkan tombol Lihat Event.
         */
        return response()->json([
            'month' => (int) $data['month'],
            'year' => (int) $data['year'],
            'total_slots' => $slots->count(),
            'slots' => $slots,
            'event_days' => $eventDays,
        ]);
    }
}
