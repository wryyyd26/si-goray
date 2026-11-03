<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\TariffReference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    /**
     * GET /api/events
     *
     * Mengambil event aktif dengan jadwal hari ini
     * atau jadwal mendatang.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));

        $events = Event::query()
            ->where('status', 'AKTIF')
            ->whereHas('schedules', function ($query) {
                $query->whereDate('tanggal', '>=', today());
            })
            ->with([
                'schedules' => function ($query) {
                    $query->whereDate('tanggal', '>=', today())
                        ->orderBy('tanggal')
                        ->orderBy('jam_mulai');
                },
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where(
                        'nama_event',
                        'like',
                        "%{$search}%"
                    )->orWhere(
                        'deskripsi',
                        'like',
                        "%{$search}%"
                    );
                });
            })
            ->orderBy('nama_event')
            ->get();

        // Ambil tarif event yang aktif.
        $eventTariffs = TariffReference::query()
            ->whereIn('event_id', $events->pluck('id'))
            ->where('jenis_pengunjung', 'UMUM')
            ->where('aktif', true)
            ->whereDate('berlaku_mulai', '<=', today())
            ->orderByDesc('berlaku_mulai')
            ->get()
            ->groupBy('event_id')
            ->map(function ($tariffs) {
                return $tariffs->first();
            });

        // Tarif umum menjadi fallback jika event tidak
        // memiliki tarif khusus yang aktif.
        $defaultTariff = TariffReference::query()
            ->whereNull('event_id')
            ->where('jenis_pengunjung', 'UMUM')
            ->where('aktif', true)
            ->whereDate('berlaku_mulai', '<=', today())
            ->orderByDesc('berlaku_mulai')
            ->first();

        $data = $events->map(function (Event $event) use (
            $eventTariffs,
            $defaultTariff
        ) {
            $tariff = $eventTariffs->get($event->id)
                ?? $defaultTariff;

            return $this->formatEvent($event, $tariff);
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Daftar event berhasil diambil.',
            'data' => $data,
            'meta' => [
                'count' => $data->count(),
                'search' => $search,
            ],
        ]);
    }

    /**
     * GET /api/events/{event}
     *
     * Mengambil detail event dan seluruh jadwal
     * yang masih relevan untuk pemesanan.
     */
    public function show(Event $event): JsonResponse
    {
        if ($event->status !== 'AKTIF') {
            return response()->json([
                'success' => false,
                'message' => 'Event tidak tersedia.',
                'data' => null,
            ], 404);
        }

        $event->load([
            'schedules' => function ($query) {
                $query->whereDate('tanggal', '>=', today())
                    ->orderBy('tanggal')
                    ->orderBy('jam_mulai');
            },
        ]);

        if ($event->schedules->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Event tidak memiliki jadwal yang tersedia.',
                'data' => null,
            ], 404);
        }

        $tariff = TariffReference::query()
            ->where('event_id', $event->id)
            ->where('jenis_pengunjung', 'UMUM')
            ->where('aktif', true)
            ->whereDate('berlaku_mulai', '<=', today())
            ->orderByDesc('berlaku_mulai')
            ->first();

        if (!$tariff) {
            $tariff = TariffReference::query()
                ->whereNull('event_id')
                ->where('jenis_pengunjung', 'UMUM')
                ->where('aktif', true)
                ->whereDate('berlaku_mulai', '<=', today())
                ->orderByDesc('berlaku_mulai')
                ->first();
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail event berhasil diambil.',
            'data' => $this->formatEvent($event, $tariff),
        ]);
    }

    /**
     * Menyeragamkan format data untuk frontend.
     */
    private function formatEvent(
        Event $event,
        ?TariffReference $tariff
    ): array {
        $schedules = $event->schedules->map(function ($schedule) {
            $capacity = (int) $schedule->kapasitas;
            $used = (int) $schedule->kuota_terpakai;
            $remaining = max(0, $capacity - $used);

            return [
                'id' => (string) $schedule->id,
                'event_id' => (string) $schedule->event_id,
                'date' => $schedule->tanggal?->format('Y-m-d'),
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
                'capacity' => $capacity,
                'used' => $used,
                'remaining' => $remaining,
                'is_full' => $remaining === 0,
            ];
        })->values();

        $total = (int) $schedules->sum('capacity');

        $remaining = (int) $schedules->sum('remaining');

        $priceAmount = $tariff
            ? (float) $tariff->nominal
            : null;

        return [
            'id' => (string) $event->id,
            'title' => $event->nama_event,
            'description' => $event->deskripsi,
            'image' => $this->imageUrl($event->gambar),

            // Belum ada pemetaan kategori event yang
            // tersimpan dan digunakan secara konsisten.
            'category' => $event->kategori ?? 'Event',

            'remaining' => $remaining,
            'total' => $total,

            'price' => $priceAmount === null
                ? 'Tarif belum tersedia'
                : 'Rp ' . number_format(
                    $priceAmount,
                    0,
                    ',',
                    '.'
                ),

            'price_amount' => $priceAmount,
            'schedules' => $schedules->all(),
        ];
    }

    /**
     * Mengubah lokasi gambar menjadi URL yang bisa
     * digunakan frontend.
     */
    private function imageUrl(?string $path): string
    {
        if (!$path) {
            return asset('images/events/futsal.jpg');
        }

        $path = trim($path);

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (
            str_starts_with($path, 'images/') ||
            str_starts_with($path, 'storage/')
        ) {
            return asset($path);
        }

        return asset(Storage::disk('public')->url($path));
    }
}
