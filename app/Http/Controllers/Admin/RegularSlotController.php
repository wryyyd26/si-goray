<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class RegularSlotController extends Controller
{
    /**
     * Menampilkan daftar tiket reguler.
     */
    public function index()
    {
        $slots = DB::table('regular_slots')
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get()
            ->map(function ($slot) {
                return [
                    'id' => $slot->id,
                    'date' => date(
                        'Y-m-d',
                        strtotime($slot->tanggal)
                    ),
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
                    'quota' => (int) $slot->kapasitas,
                    'used' => (int) $slot->kuota_terpakai,
                    'status' => isset($slot->status)
                        ? $slot->status
                        : 'active',
                ];
            });

        // Menghitung total kuota dari seluruh slot.
        $totalQuota = $slots->sum('quota');

        // Menghitung total kuota yang sudah terpakai.
        $totalUsed = $slots->sum('used');

        // Menghitung sisa kuota yang tersedia.
        $totalAvailable = max(
            0,
            $totalQuota - $totalUsed
        );


$month = request()->query('month', now()->format('Y-m'));

if (!is_string($month) ||
    !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
    $month = now()->format('Y-m');
}

$calendarMonth = \Carbon\Carbon::createFromFormat('!Y-m', $month)
    ->startOfMonth();

$monthKey = $calendarMonth->format('Y-m');

// Mengelompokkan slot yang sudah diambil dari database.
$slotsByDate = $slots->groupBy('date');

// Membentuk kalender mulai Senin.
$calendarCells = [];

$leadingDays = $calendarMonth->dayOfWeekIso - 1;

for ($i = 0; $i < $leadingDays; $i++) {
    $calendarCells[] = null;
}

$daysInMonth = $calendarMonth->daysInMonth;

for ($day = 1; $day <= $daysInMonth; $day++) {
    $date = $calendarMonth->copy()
        ->day($day)
        ->format('Y-m-d');

    $calendarCells[] = [
        'date' => $date,
        'day' => $day,
        'slots' => $slotsByDate->get($date, collect())->values(),
    ];
}

// Tanggal yang dipilih dari kalender.
$selectedDate = request()->query('date');

if (
    $selectedDate &&
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)
) {
    $selectedDate = null;
}

if (
    $selectedDate &&
    !str_starts_with($selectedDate, $monthKey . '-')
) {
    $selectedDate = null;
}

$selectedSlots = $selectedDate
    ? $slotsByDate->get($selectedDate, collect())->values()
    : collect();


// Data navigasi kalender untuk tampilan Admin.
$month = request()->query('month', now()->format('Y-m'));

try {
    $calendarMonth = Carbon::createFromFormat(
        '!Y-m',
        $month
    )->startOfMonth();

    if ($calendarMonth->format('Y-m') !== $month) {
        throw new \InvalidArgumentException('Format bulan tidak valid.');
    }
} catch (\Throwable $e) {
    $calendarMonth = now()->startOfMonth();
}

$previousMonth = $calendarMonth->copy()
    ->subMonth()
    ->format('Y-m');

$nextMonth = $calendarMonth->copy()
    ->addMonth()
    ->format('Y-m');

$calendarMonthLabel = $calendarMonth
    ->locale('id')
    ->translatedFormat('F Y');


$activeRegularTariff = \App\Models\TariffReference::query()
    ->where('jenis_pengunjung', 'UMUM')
    ->whereNull('event_id')
    ->where('aktif', true)
    ->whereDate('berlaku_mulai', '<=', today())
    ->orderByDesc('berlaku_mulai')
    ->first();

$nonWargaPrice = $activeRegularTariff
    ? (int) $activeRegularTariff->nominal
    : null;


/*
 * PRIORITAS EVENT PER TANGGAL
 * Event AKTIF menggantikan tampilan reguler
 * untuk seluruh tanggal event.
 */
$awalBulan = $calendarMonth->copy()
    ->startOfMonth()
    ->toDateString();

$akhirBulan = $calendarMonth->copy()
    ->endOfMonth()
    ->toDateString();

$eventDays = DB::table('event_schedules as es')
    ->join('events as e', 'e.id', '=', 'es.event_id')
    ->where('e.status', 'AKTIF')
    ->whereBetween('es.tanggal', [
        $awalBulan,
        $akhirBulan,
    ])
    ->orderBy('es.tanggal')
    ->orderBy('es.jam_mulai')
    ->get([
        'es.tanggal',
        'es.event_id',
        'e.nama_event',
    ])
    ->groupBy('tanggal')
    ->map(function ($events) {
        $event = $events->first();

        return [
            'id' => (string) $event->event_id,
            'title' => $event->nama_event,
            'count' => $events->count(),
        ];
    });

foreach ($calendarCells as &$cell) {
    if ($cell === null) {
        continue;
    }

    $event = $eventDays->get($cell['date']);

    if ($event) {
        $cell['event'] = $event;
        $cell['type'] = 'EVENT';

        // Hanya menyembunyikan slot pada kalender.
        // Tidak menghapus data regular_slots.
        $cell['slots'] = [];
    }
}
unset($cell);

if ($selectedDate && $eventDays->has($selectedDate)) {
    $selectedSlots = collect();
}

return view('admin.reguler.tiket-reguler', [
    'slots' => $slots,
    'totalQuota' => $totalQuota,
    'totalUsed' => $totalUsed,
    'totalAvailable' => $totalAvailable,
    'totalRemaining' => $totalAvailable,
    'previousMonth' => $previousMonth,
    'nextMonth' => $nextMonth,
    'calendarMonthLabel' => $calendarMonthLabel,
    'monthKey' => $monthKey,
    'calendarCells' => $calendarCells,
    'selectedDate' => $selectedDate,
    'selectedSlots' => $selectedSlots,
    'nonWargaPrice' => $nonWargaPrice,
]);

    }

    /**
     * Menampilkan form pengaturan slot berdasarkan tanggal.
     */

	/**
 * Menyimpan slot reguler baru.
 */
public function store(Request $request)
{
    $data = $request->validate([
        'date' => [
            'required',
            'date',
            'after_or_equal:today',
        ],
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
        ],
    ]);

    // Pastikan slot reguler tidak bertabrakan
    // dengan jadwal event yang aktif.
    $eventConflict = DB::table('event_schedules as es')
        ->join('events as e', 'e.id', '=', 'es.event_id')
        ->where('e.status', 'AKTIF')
        ->whereDate('es.tanggal', $data['date'])
        ->where(
            'es.jam_mulai',
            '<',
            $data['end_time'] . ':00'
        )
        ->where(
            'es.jam_selesai',
            '>',
            $data['start_time'] . ':00'
        )
        ->exists();

    if ($eventConflict) {
        return back()
            ->withInput()
            ->withErrors([
                'start_time' =>
                    'Jadwal reguler tidak boleh bertabrakan '
                    . 'dengan jadwal event aktif.',
            ]);
    }

    // Hindari slot reguler yang waktunya bertumpang tindih.
    $regularConflict = DB::table('regular_slots')
        ->whereDate('tanggal', $data['date'])
        ->where(
            'jam_mulai',
            '<',
            $data['end_time'] . ':00'
        )
        ->where(
            'jam_selesai',
            '>',
            $data['start_time'] . ':00'
        )
        ->exists();

    if ($regularConflict) {
        return back()
            ->withInput()
            ->withErrors([
                'start_time' =>
                    'Jadwal reguler bertabrakan '
                    . 'dengan slot yang sudah tersedia.',
            ]);
    }

    DB::table('regular_slots')->insert([
        'id' => (string) Str::uuid(),
        'tanggal' => $data['date'],
        'jam_mulai' => $data['start_time'] . ':00',
        'jam_selesai' => $data['end_time'] . ':00',
        'kapasitas' => $data['quota'],
        'kuota_terpakai' => 0,
        'status' => 'active',
    ]);

    return redirect()
        ->route('admin.reguler.index', [
        'month' => substr($data['date'], 0, 7),
        'date' => $data['date'],
    ])
    ->with(
        'success',
        'Slot reguler berhasil ditambahkan.'
        );
}

    public function edit(string $date)
    {
        $slots = DB::table('regular_slots')
            ->whereDate('tanggal', $date)
            ->orderBy('jam_mulai')
            ->get();

        if ($slots->isEmpty()) {
            abort(404, 'Jadwal tiket reguler tidak ditemukan.');
        }

        if ($slots->count() > 1) {
            abort(
                409,
                'Tanggal ini memiliki beberapa slot. '
                . 'Form saat ini dirancang untuk satu slot per tanggal.'
            );
        }

        $record = $slots->first();

        $slot = [
            'id' => $record->id,
            'date' => date(
                'Y-m-d',
                strtotime($record->tanggal)
            ),
            'start_time' => substr(
                (string) $record->jam_mulai,
                0,
                5
            ),
            'end_time' => substr(
                (string) $record->jam_selesai,
                0,
                5
            ),
            'quota' => (int) $record->kapasitas,
            'used' => (int) $record->kuota_terpakai,
            'status' => isset($record->status)
                ? $record->status
                : 'active',
        ];

        return view('admin.reguler.update-slot', [
            'slot' => $slot,
        ]);
    }

    /**
     * Menyimpan perubahan jadwal dan kuota.
     */
    public function update(Request $request, string $date)
    {
        $slot = DB::table('regular_slots')
            ->whereDate('tanggal', $date)
            ->first();

        if (!$slot) {
            abort(404, 'Jadwal tiket reguler tidak ditemukan.');
        }

        $data = $request->validate([
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
                'min:0',
            ],
            'status' => [
                'required',
                'in:active,inactive',
            ],
        ]);

        if ((int) $data['quota'] < (int) $slot->kuota_terpakai) {
            return back()
                ->withInput()
                ->withErrors([
                    'quota' => 'Kuota tidak boleh lebih kecil '
                        . 'daripada jumlah pengunjung terdaftar.',
                ]);
        }

        $hasBookings = DB::table('bookings')
            ->where('regular_slot_id', $slot->id)
            ->exists();

        $startChanged = substr(
            (string) $slot->jam_mulai,
            0,
            5
        ) !== $data['start_time'];

        $endChanged = substr(
            (string) $slot->jam_selesai,
            0,
            5
        ) !== $data['end_time'];

        if ($hasBookings && ($startChanged || $endChanged)) {
            return back()
                ->withInput()
                ->withErrors([
                    'start_time' => 'Jam kunjungan tidak dapat '
                        . 'diubah karena jadwal sudah memiliki booking.',
                ]);
        }

        DB::table('regular_slots')
            ->where('id', $slot->id)
            ->update([
                'jam_mulai' => $data['start_time'],
                'jam_selesai' => $data['end_time'],
                'kapasitas' => $data['quota'],
                'status' => $data['status'],
            ]);

        return redirect()
            ->route('admin.reguler.index', ['month' => substr($date, 0, 7), 'date' => $date])
            ->with(
                'success',
                'Pengaturan tiket reguler berhasil diperbarui.'
            );
    }

/**
 * Menampilkan pengaturan satu slot berdasarkan UUID.
 */
public function editById(string $slotId)
{
    $record = DB::table('regular_slots')
        ->where('id', $slotId)
        ->first();

    if (!$record) {
        abort(404, 'Slot reguler tidak ditemukan.');
    }

    $slot = [
        'id' => $record->id,
        'date' => substr((string) $record->tanggal, 0, 10),
        'start_time' => substr((string) $record->jam_mulai, 0, 5),
        'end_time' => substr((string) $record->jam_selesai, 0, 5),
        'quota' => (int) $record->kapasitas,
        'used' => (int) $record->kuota_terpakai,
        'status' => $record->status,
    ];

    return view('admin.reguler.update-slot', [
        'slot' => $slot,
    ]);
}

/**
 * Memperbarui satu slot berdasarkan UUID.
 */
public function updateById(Request $request, string $slotId)
{
    $data = $request->validate([
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
            'min:0',
        ],
        'status' => [
            'required',
            'in:active,inactive',
        ],
    ]);

    // Setiap sesi reguler harus berdurasi 2 jam.
    $start = explode(':', $data['start_time']);
    $end = explode(':', $data['end_time']);

    $startMinutes = ((int) $start[0] * 60)
        + (int) $start[1];

    $endMinutes = ((int) $end[0] * 60)
        + (int) $end[1];

    if ($endMinutes - $startMinutes !== 120) {
        throw ValidationException::withMessages([
            'end_time' => 'Durasi setiap slot harus tepat 2 jam.',
        ]);
    }

    DB::transaction(function () use ($slotId, $data) {

        $record = DB::table('regular_slots')
            ->where('id', $slotId)
            ->lockForUpdate()
            ->first();

        if (!$record) {
            abort(404, 'Slot reguler tidak ditemukan.');
        }

        // Kuota tidak boleh di bawah jumlah terpakai.
        if ((int) $data['quota'] < (int) $record->kuota_terpakai) {
            throw ValidationException::withMessages([
                'quota' => 'Kuota tidak boleh lebih kecil dari jumlah pengunjung terdaftar.',
            ]);
        }

        $oldStart = substr((string) $record->jam_mulai, 0, 5);
        $oldEnd = substr((string) $record->jam_selesai, 0, 5);

        $timeChanged = $oldStart !== $data['start_time']
            || $oldEnd !== $data['end_time'];

        // Pertahankan aturan lama:
        // jam tidak boleh berubah jika sudah ada booking.
        if ($timeChanged) {
            $hasBookings = DB::table('bookings')
                ->where('regular_slot_id', $record->id)
                ->exists();

            if ($hasBookings) {
                throw ValidationException::withMessages([
                    'start_time' => 'Jam tidak dapat diubah karena slot sudah memiliki booking.',
                ]);
            }
        }

        // Cegah benturan dengan slot reguler lain.
        $conflict = DB::table('regular_slots')
            ->where('id', '!=', $record->id)
            ->whereDate('tanggal', $record->tanggal)
            ->where('jam_mulai', '<', $data['end_time'] . ':00')
            ->where('jam_selesai', '>', $data['start_time'] . ':00')
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'start_time' => 'Jam bertabrakan dengan slot reguler lain pada tanggal yang sama.',
            ]);
        }

        DB::table('regular_slots')
            ->where('id', $record->id)
            ->update([
                'jam_mulai' => $data['start_time'] . ':00',
                'jam_selesai' => $data['end_time'] . ':00',
                'kapasitas' => $data['quota'],
                'status' => $data['status'],
            ]);

    }, 3);

    return redirect()
        ->route('admin.reguler.index', ['month' => \Carbon\Carbon::parse(\Illuminate\Support\Facades\DB::table('regular_slots')->where('id', $slotId)->value('tanggal'))->format('Y-m'), 'date' => \Carbon\Carbon::parse(\Illuminate\Support\Facades\DB::table('regular_slots')->where('id', $slotId)->value('tanggal'))->toDateString()])
        ->with('success', 'Pengaturan slot berhasil diperbarui.');
}

}
