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

        // Tampilkan slot aktif mulai hari ini.
        // Kalender bulan lampau akan menghasilkan daftar kosong.
        $today = CarbonImmutable::today()->toDateString();

        $slots = DB::table('regular_slots')
            ->whereBetween('tanggal', [
                $firstDate->toDateString(),
                $lastDate->toDateString(),
            ])
            ->whereDate('tanggal', '>=', $today)
            ->where('status', 'active')
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
                    'start_time' => substr($slot->jam_mulai, 0, 5),
                    'end_time' => substr($slot->jam_selesai, 0, 5),
                    'quota' => $quota,
                    'used' => $used,
                    'available' => $available,
                    'status' => $available > 0 ? 'available' : 'full',
                ];
            })
            ->values();

        return response()->json([
            'month' => (int) $data['month'],
            'year' => (int) $data['year'],
            'total_slots' => $slots->count(),
            'slots' => $slots,
        ]);
    }
}
