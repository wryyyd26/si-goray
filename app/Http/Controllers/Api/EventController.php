<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\TariffReference;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $events = Event::query()
            ->with([
                'schedules' => function ($query) {
                    $query->whereDate('tanggal', '>=', today())
                        ->orderBy('tanggal')
                        ->orderBy('jam_mulai');
                },
            ])
            ->where('status', 'AKTIF')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('nama_event', 'like', "%{$search}%")
                        ->orWhere('deskripsi', 'like', "%{$search}%");
                });
            })
            ->orderBy('nama_event')
            ->get();

        $tariffsByEvent = TariffReference::query()
            ->whereIn('event_id', $events->pluck('id'))
            ->where('jenis_pengunjung', 'UMUM')
            ->where('aktif', true)
            ->whereDate('berlaku_mulai', '<=', today())
            ->orderByDesc('berlaku_mulai')
            ->get()
            ->groupBy('event_id');

        $defaultTariff = TariffReference::query()
            ->whereNull('event_id')
            ->where('jenis_pengunjung', 'UMUM')
            ->where('aktif', true)
            ->whereDate('berlaku_mulai', '<=', today())
            ->orderByDesc('berlaku_mulai')
            ->first();

        $data = $events->map(function ($event) use (
            $tariffsByEvent,
            $defaultTariff
        ) {
            $tariff = $tariffsByEvent
                ->get($event->id, collect())
                ->first();

            $total = $event->schedules->sum('kapasitas');
            $used = $event->schedules->sum('kuota_terpakai');

            return [
                'id' => $event->id,
                'title' => $event->nama_event,
                'description' => $event->deskripsi,
                'image' => $event->gambar,
                'image_url' => $event->gambar
                    ? asset('storage/' . $event->gambar)
                    : null,
                'category' => 'Event',
                'remaining' => max(0, $total - $used),
                'total' => $total,
                'price' => (float) (
                    $tariff?->nominal
                    ?? $defaultTariff?->nominal
                    ?? 0
                ),
                'schedules' => $event->schedules->map(
                    function ($schedule) {
                        return [
                            'id' => $schedule->id,
                            'date' => $schedule->tanggal->format('Y-m-d'),
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
                            'capacity' => (int) $schedule->kapasitas,
                            'used' => (int) $schedule->kuota_terpakai,
                            'remaining' => max(
                                0,
                                (int) $schedule->kapasitas
                                - (int) $schedule->kuota_terpakai
                            ),
                        ];
                    }
                )->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
