<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\TariffReference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class EventController extends Controller
{
    /**
     * Menampilkan daftar event dari database.
     */
    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) $request->query('search', ''));

        $eventModels = Event::query()
            ->with([
                'schedules' => function ($query) {
                    $query->orderBy('tanggal')
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

        $tariffsByEvent = TariffReference::query()
            ->whereIn('event_id', $eventModels->pluck('id'))
            ->where('jenis_pengunjung', 'UMUM')
            ->where('aktif', true)
            ->whereDate('berlaku_mulai', '<=', today())
            ->orderByDesc('berlaku_mulai')
            ->get()
            ->groupBy('event_id');

        $tarifUmumDefault = TariffReference::query()
            ->whereNull('event_id')
            ->where('jenis_pengunjung', 'UMUM')
            ->where('aktif', true)
            ->whereDate('berlaku_mulai', '<=', today())
            ->orderByDesc('berlaku_mulai')
            ->first();

        $events = $eventModels->map(
            function (Event $event) use (
                $tariffsByEvent,
                $tarifUmumDefault
            ) {
                $schedule = $event->schedules->first();

                $tariff = $tariffsByEvent
                    ->get($event->id, collect())
                    ->first();

                $statusLabel = 'Tidak Aktif';

                if ($event->status === 'AKTIF') {
                    $statusLabel = $schedule
                        && $schedule->tanggal->gt(today())
                            ? 'Mendatang'
                            : 'Aktif';
                }

                return [
                    'id' => $event->id,
                    'name' => $event->nama_event,
                    'description' => $event->deskripsi,
                    'poster' => $event->gambar,
                    'date' => $schedule?->tanggal
                        ?->format('Y-m-d')
                        ?? today()->toDateString(),
                    'start_time' => $schedule
                        ? substr((string) $schedule->jam_mulai, 0, 5)
                        : '--:--',
                    'end_time' => $schedule
                        ? substr((string) $schedule->jam_selesai, 0, 5)
                        : '--:--',
                    'tariff' => $tariff?->nominal
                        ?? $tarifUmumDefault?->nominal
                        ?? 0,
                    'sold' => $event->schedules->sum(
                        'kuota_terpakai'
                    ),
                    'status' => $statusLabel,
                ];
            }
        );

        return view('admin.event.index', [
            'events' => $events,
            'search' => $search,
            'totalEvents' => Event::count(),
            'totalActiveEvents' => Event::where(
                'status',
                'AKTIF'
            )->count(),
            'totalUpcomingEvents' => Event::where(
                'status',
                'AKTIF'
            )->whereHas('schedules', function ($query) {
                $query->whereDate('tanggal', '>', today());
            })->count(),
            'totalTicketsSold' => (int) EventSchedule::sum(
                'kuota_terpakai'
            ),
        ]);
    }

    /**
     * Menampilkan form tambah event.
     */
    public function create()
    {
        return view('admin.event.create');
    }

    /**
     * Menyimpan event, jadwal berkapasitas 200 orang,
     * dan tarif pengunjung umum.
     */
    public function store(Request $request)
    {
        $data = $this->validateEvent($request, true);

        $posterPath = $request->hasFile('poster')
            ? $request->file('poster')->store(
                'event-posters',
                'public'
            )
            : null;

        try {
            DB::transaction(function () use (
                $data,
                $posterPath
            ) {
                $event = Event::create([
                    'nama_event' => $data['name'],
                    'deskripsi' => $data['description'] ?? null,
                    'gambar' => $posterPath,
                    'status' => 'AKTIF',
                ]);

                $event->schedules()->create([
                    'tanggal' => $data['date'],
                    'jam_mulai' => $data['start_time'],
                    'jam_selesai' => $data['end_time'],
                    'kapasitas' => 200,
                    'kuota_terpakai' => 0,
                ]);

                TariffReference::create([
                    'event_id' => $event->id,
                    'jenis_pengunjung' => 'UMUM',
                    'nominal' => $data['tariff'],
                    'berlaku_mulai' => today()->toDateString(),
                    'aktif' => true,
                ]);
            });
        } catch (Throwable $exception) {
            if ($posterPath) {
                Storage::disk('public')->delete($posterPath);
            }

            throw $exception;
        }

        return redirect()
            ->route('admin.events.index')
            ->with(
                'success',
                'Event berhasil disimpan. Kapasitas jadwal: 200 orang.'
            );
    }

    /**
     * Menampilkan form edit event.
     */
    public function edit(Event $event)
    {
        $schedule = $event->schedules()
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->first();

        $tariff = $this->activeEventTariff($event);

        if (!$tariff) {
            $tariff = TariffReference::query()
                ->whereNull('event_id')
                ->where('jenis_pengunjung', 'UMUM')
                ->where('aktif', true)
                ->whereDate('berlaku_mulai', '<=', today())
                ->orderByDesc('berlaku_mulai')
                ->first();
        }

        return view('admin.event.edit', [
            'event' => [
                'id' => $event->id,
                'name' => $event->nama_event,
                'description' => $event->deskripsi,
                'poster' => $event->gambar,
                'date' => $schedule?->tanggal
                    ?->format('Y-m-d')
                    ?? today()->toDateString(),
                'start_time' => $schedule
                    ? substr((string) $schedule->jam_mulai, 0, 5)
                    : '08:00',
                'end_time' => $schedule
                    ? substr((string) $schedule->jam_selesai, 0, 5)
                    : '16:00',
                'tariff' => $tariff?->nominal ?? 0,
            ],
        ]);
    }

    /**
     * Memperbarui event tanpa mengubah tanggal atau jam
     * jadwal yang sudah memiliki booking.
     */
    public function update(Request $request, Event $event)
    {
        $data = $this->validateEvent($request, false);

        $schedule = $event->schedules()
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->first();

        if ($schedule && $schedule->bookings()->exists()) {
            $sameDate = $schedule->tanggal->format('Y-m-d')
                === $data['date'];

            $sameStart = substr(
                (string) $schedule->jam_mulai,
                0,
                5
            ) === $data['start_time'];

            $sameEnd = substr(
                (string) $schedule->jam_selesai,
                0,
                5
            ) === $data['end_time'];

            if (!$sameDate || !$sameStart || !$sameEnd) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'date' => 'Tanggal atau jam tidak dapat '
                            . 'diubah karena jadwal sudah memiliki booking.',
                    ]);
            }
        }

        $oldPosterPath = $event->gambar;

        $newPosterPath = $request->hasFile('poster')
            ? $request->file('poster')->store(
                'event-posters',
                'public'
            )
            : null;

        try {
            DB::transaction(function () use (
                $event,
                $data,
                $newPosterPath,
                $schedule
            ) {
                $event->update([
                    'nama_event' => $data['name'],
                    'deskripsi' => $data['description'] ?? null,
                    'gambar' => $newPosterPath ?: $event->gambar,
                ]);

                if ($schedule) {
                    $schedule->update([
                        'tanggal' => $data['date'],
                        'jam_mulai' => $data['start_time'],
                        'jam_selesai' => $data['end_time'],
                    ]);
                } else {
                    $event->schedules()->create([
                        'tanggal' => $data['date'],
                        'jam_mulai' => $data['start_time'],
                        'jam_selesai' => $data['end_time'],
                        'kapasitas' => 200,
                        'kuota_terpakai' => 0,
                    ]);
                }

                $this->saveEventTariff(
                    $event,
                    $data['tariff']
                );
            });
        } catch (Throwable $exception) {
            if ($newPosterPath) {
                Storage::disk('public')->delete($newPosterPath);
            }

            throw $exception;
        }

        if (
            $newPosterPath
            && $oldPosterPath
            && Storage::disk('public')->exists($oldPosterPath)
        ) {
            Storage::disk('public')->delete($oldPosterPath);
        }

        return redirect()
            ->route('admin.events.index')
            ->with(
                'success',
                'Informasi event berhasil diperbarui.'
            );
    }

    /**
     * Validasi input dari form frontend.
     */
    private function validateEvent(
        Request $request,
        bool $requireFutureDate
    ): array {
        $dateRules = ['required', 'date'];

        if ($requireFutureDate) {
            $dateRules[] = 'after_or_equal:today';
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'poster' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'date' => $dateRules,
            'start_time' => [
                'required',
                'date_format:H:i',
            ],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
            ],
            'tariff' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999',
            ],
        ]);
    }

    /**
     * Mengambil tarif event yang sedang aktif.
     */
    private function activeEventTariff(
        Event $event
    ): ?TariffReference {
        return TariffReference::query()
            ->where('event_id', $event->id)
            ->where('jenis_pengunjung', 'UMUM')
            ->where('aktif', true)
            ->whereDate('berlaku_mulai', '<=', today())
            ->orderByDesc('berlaku_mulai')
            ->first();
    }

    /**
     * Mempertahankan riwayat tarif yang sudah digunakan
     * oleh booking sebelumnya.
     */
    private function saveEventTariff(
        Event $event,
        int|float|string $amount
    ): void {
        $tariff = $this->activeEventTariff($event);

        if ($tariff && $tariff->bookings()->exists()) {
            $tariff->update([
                'aktif' => false,
            ]);

            $tariff = null;
        }

        if ($tariff) {
            $tariff->update([
                'nominal' => $amount,
                'berlaku_mulai' => today()->toDateString(),
                'aktif' => true,
            ]);

            return;
        }

        TariffReference::create([
            'event_id' => $event->id,
            'jenis_pengunjung' => 'UMUM',
            'nominal' => $amount,
            'berlaku_mulai' => today()->toDateString(),
            'aktif' => true,
        ]);
    }
}
