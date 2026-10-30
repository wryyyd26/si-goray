<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\TariffReference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class EventController extends Controller
{
    private const DEFAULT_QUOTA = 200;
    private const MAX_QUOTA = 200;

    /**
     * Menampilkan daftar event.
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
                    $subQuery
                        ->where('nama_event', 'like', "%{$search}%")
                        ->orWhere('deskripsi', 'like', "%{$search}%");
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

                    'quota' => $schedule?->kapasitas
                        ?? self::DEFAULT_QUOTA,

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
     * Form tambah event.
     */
    public function create()
    {
        return view('admin.event.create', [
            'defaultQuota' => self::DEFAULT_QUOTA,
        ]);
    }

    /**
     * Simpan event, jadwal, dan tarif.
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
            DB::transaction(function () use ($data, $posterPath) {

                $this->assertNoScheduleConflict(
                    $data['date'],
                    $data['start_time'],
                    $data['end_time']
                );

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
                    'kapasitas' => (int) $data['quota'],
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
                'Event berhasil disimpan dengan kuota '
                . $data['quota']
                . ' tiket.'
            );
    }

    /**
     * Form edit event.
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

                'quota' => $schedule?->kapasitas
                    ?? self::DEFAULT_QUOTA,

                'sold' => $schedule?->kuota_terpakai ?? 0,

                'tariff' => $tariff?->nominal ?? 0,
            ],
        ]);
    }

    /**
     * Update event tanpa mengganggu booking yang ada.
     */
    public function update(Request $request, Event $event)
    {
        $data = $this->validateEvent($request, false);

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
                $newPosterPath
            ) {
                $schedule = $event->schedules()
                    ->orderBy('tanggal')
                    ->orderBy('jam_mulai')
                    ->lockForUpdate()
                    ->first();

                if ($schedule) {
                    // Jangan menurunkan kapasitas di bawah tiket terpakai.
                    if (
                        (int) $data['quota']
                        < (int) $schedule->kuota_terpakai
                    ) {
                        throw ValidationException::withMessages([
                            'quota' => 'Kuota tidak boleh lebih kecil '
                                . 'daripada jumlah tiket yang sudah terpakai.',
                        ]);
                    }

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

                    $scheduleChanged = !(
                        $sameDate && $sameStart && $sameEnd
                    );

                    // Jadwal yang sudah memiliki booking tidak boleh digeser.
                    if (
                        $scheduleChanged
                        && $schedule->bookings()->exists()
                    ) {
                        throw ValidationException::withMessages([
                            'date' => 'Tanggal atau jam event tidak dapat '
                                . 'diubah karena sudah memiliki booking.',
                        ]);
                    }
                } else {
                    $scheduleChanged = true;
                }

                // Konflik hanya perlu diperiksa jika jadwal berubah.
                if ($scheduleChanged) {
                    $this->assertNoScheduleConflict(
                        $data['date'],
                        $data['start_time'],
                        $data['end_time'],
                        $schedule?->id
                    );
                }

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
                        'kapasitas' => (int) $data['quota'],
                    ]);
                } else {
                    $event->schedules()->create([
                        'tanggal' => $data['date'],
                        'jam_mulai' => $data['start_time'],
                        'jam_selesai' => $data['end_time'],
                        'kapasitas' => (int) $data['quota'],
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
                'Informasi dan kuota event berhasil diperbarui.'
            );
    }

    /**
     * Validasi form event.
     */
    private function validateEvent(
        Request $request,
        bool $requireFutureDate
    ): array {
        $dateRules = ['required', 'date_format:Y-m-d'];

        if ($requireFutureDate) {
            $dateRules[] = 'after_or_equal:today';
        }

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
            ],

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

            'quota' => [
                'required',
                'integer',
                'min:1',
                'max:' . self::MAX_QUOTA,
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
     * Periksa konflik jadwal dengan sesi reguler dan event lain.
     *
     * Dua rentang bertabrakan jika:
     * existing.start < requested.end
     * dan existing.end > requested.start.
     *
     * Jadwal yang selesai tepat saat jadwal baru dimulai
     * tidak dianggap bertabrakan.
     */
    private function assertNoScheduleConflict(
        string $date,
        string $startTime,
        string $endTime,
        ?string $excludeScheduleId = null
    ): void {
        $start = $startTime . ':00';
        $end = $endTime . ':00';

        // Sesi reguler aktif atau yang sudah memiliki tiket terpakai.
        $regularConflict = DB::table('regular_slots')
            ->whereDate('tanggal', $date)
            ->where('jam_mulai', '<', $end)
            ->where('jam_selesai', '>', $start)
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhere('kuota_terpakai', '>', 0);
            })
            ->exists();

        if ($regularConflict) {
            throw ValidationException::withMessages([
                'date' => 'Jadwal event bertabrakan dengan '
                    . 'sesi tiket reguler pada tanggal tersebut.',
            ]);
        }

        // Event lain yang berstatus AKTIF.
        $eventQuery = DB::table('event_schedules as es')
            ->join(
                'events as e',
                'e.id',
                '=',
                'es.event_id'
            )
            ->where('e.status', 'AKTIF')
            ->whereDate('es.tanggal', $date)
            ->where('es.jam_mulai', '<', $end)
            ->where('es.jam_selesai', '>', $start);

        // Saat edit, abaikan jadwal yang sedang diedit.
        if ($excludeScheduleId !== null) {
            $eventQuery->where(
                'es.id',
                '!=',
                $excludeScheduleId
            );
        }

        if ($eventQuery->exists()) {
            throw ValidationException::withMessages([
                'date' => 'Jadwal event bertabrakan dengan '
                    . 'event aktif lainnya.',
            ]);
        }
    }

    /**
     * Tarif khusus event yang masih berlaku.
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
     * Pertahankan referensi tarif booking sebelumnya.
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
