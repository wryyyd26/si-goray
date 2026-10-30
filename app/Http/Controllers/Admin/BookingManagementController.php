<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingManagementController extends Controller
{
    private const PAID = [
        'SUCCESS', 'SETTLEMENT', 'PAID', 'BERHASIL',
    ];

    private const PENDING = [
        'PENDING', 'MENUNGGU',
    ];

    private const QR_ACTIVE = [
        'AKTIF', 'ACTIVE',
    ];

    private const QR_USED = [
        'USED', 'DIGUNAKAN', 'TERPAKAI',
    ];

    // Pembatas sementara sampai login dan otorisasi Admin tersedia.
    private function checkAccess(): void
    {
        abort_unless(
            in_array(app()->environment(), ['local', 'testing'], true),
            403,
            'Halaman Admin belum memiliki otorisasi produksi.'
        );
    }

    public function index(Request $request)
    {
        $this->checkAccess();

        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:all,regular,event'],
            'payment_status' => [
                'nullable',
                'in:all,free,paid,pending,failed',
            ],
            'qr_status' => [
                'nullable',
                'in:all,active,used,none',
            ],
            'visit_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'type' => $request->query('type', 'all'),
            'payment_status' => $request->query('payment_status', 'all'),
            'qr_status' => $request->query('qr_status', 'all'),
            'visit_date' => $request->query('visit_date', ''),
        ];

        $query = Booking::query()->with([
            'user',
            'identity',
            'regularSlot',
            'eventSchedule.event',
            'tariff',
            'paymentTransactions',
            'qrTickets',
        ]);

        // PENCARIAN
        if ($filters['search'] !== '') {
            $term = $filters['search'];

            $query->where(function ($q) use ($term) {
                $q->where('id', 'like', "%{$term}%")
                    ->orWhereHas('user', function ($user) use ($term) {
                        $user->where(
                            'no_hp_email',
                            'like',
                            "%{$term}%"
                        );
                    });
            });
        }

        // JENIS BOOKING
        if ($filters['type'] !== 'all') {
            $type = $filters['type'] === 'regular'
                ? 'REGULER'
                : 'EVENT';

            $query->where('jenis_booking', $type);
        }

        // TANGGAL KUNJUNGAN
        if ($filters['visit_date'] !== '') {
            $date = $filters['visit_date'];

            $query->where(function ($q) use ($date) {
                $q->whereHas('regularSlot', function ($slot) use ($date) {
                    $slot->whereDate('tanggal', $date);
                })->orWhereHas('eventSchedule', function ($schedule) use ($date) {
                    $schedule->whereDate('tanggal', $date);
                });
            });
        }

        // STATUS PEMBAYARAN
        $payment = $filters['payment_status'];

        if ($payment === 'free') {
            $query->where('retribusi_diperlukan', false);

        } elseif ($payment === 'paid') {
            $query->where('retribusi_diperlukan', true)
                ->whereHas('paymentTransactions', function ($q) {
                    $q->whereIn('status_pembayaran', self::PAID);
                });

        } elseif ($payment === 'pending' || $payment === 'failed') {

            $query->where('retribusi_diperlukan', true)
                ->whereDoesntHave('paymentTransactions', function ($q) {
                    $q->whereIn('status_pembayaran', self::PAID);
                });

            if ($payment === 'pending') {
                $query->where(function ($q) {
                    $q->whereHas('paymentTransactions', function ($p) {
                        $p->whereIn('status_pembayaran', self::PENDING);
                    })->orWhereDoesntHave('paymentTransactions');
                });
            } else {
                $query->whereHas('paymentTransactions')
                    ->whereDoesntHave('paymentTransactions', function ($q) {
                        $q->whereIn('status_pembayaran', self::PENDING);
                    });
            }
        }

        // STATUS QR
        $qr = $filters['qr_status'];

        if ($qr === 'active') {
            $query->whereHas('qrTickets', function ($q) {
                $q->whereIn('status_qr', self::QR_ACTIVE);
            });

        } elseif ($qr === 'used') {
            $query->whereDoesntHave('qrTickets', function ($q) {
                $q->whereIn('status_qr', self::QR_ACTIVE);
            })->whereHas('qrTickets', function ($q) {
                $q->whereIn('status_qr', self::QR_USED);
            });

        } elseif ($qr === 'none') {
            $statuses = array_merge(
                self::QR_ACTIVE,
                self::QR_USED
            );

            $query->whereDoesntHave('qrTickets', function ($q) use ($statuses) {
                $q->whereIn('status_qr', $statuses);
            });
        }

        // AMBIL DATA ASLI, 15 PEMESANAN PER HALAMAN
        $bookings = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $bookings->getCollection()->transform(
            fn ($booking) => $this->formatBooking($booking)
        );

        // STATISTIK
        $statistics = [
            'total' => Booking::count(),

            'regular' => Booking::query()
                ->where('jenis_booking', 'REGULER')
                ->count(),

            'event' => Booking::query()
                ->where('jenis_booking', 'EVENT')
                ->count(),

            'paid' => Booking::query()
                ->where('retribusi_diperlukan', true)
                ->whereHas('paymentTransactions', function ($q) {
                    $q->whereIn('status_pembayaran', self::PAID);
                })
                ->count(),
        ];

        return view(
            'admin.booking.pemesanan',
            compact('bookings', 'statistics', 'filters')
        );
    }

    public function show(string $booking)
    {
        $this->checkAccess();

        $record = Booking::with([
            'user',
            'identity',
            'regularSlot',
            'eventSchedule.event',
            'tariff',
            'paymentTransactions',
            'qrTickets',
        ])->findOrFail($booking);

        $details = $this->formatBooking($record);

        $details['user_id'] = $record->user_id;
        $details['regular_slot_id'] = $record->regular_slot_id;
        $details['event_schedule_id'] = $record->event_schedule_id;

        // Jangan membuka NIK atau data identitas terenkripsi.
        $details['nik_masked'] = 'Tidak ditampilkan';

        // RIWAYAT PEMBAYARAN
        $details['payment_transactions'] = $record
            ->paymentTransactions
            ->sortByDesc('created_at')
            ->values()
            ->map(function ($payment) {
                $status = strtoupper(
                    (string) $payment->status_pembayaran
                );

                return [
                    'code' => $payment->kode_bayar,
                    'method' => $payment->metode_pembayaran,
                    'amount' => (float) $payment->nominal,
                    'status' => in_array($status, self::PAID, true)
                        ? 'paid'
                        : (
                            in_array($status, self::PENDING, true)
                                ? 'pending'
                                : 'failed'
                        ),
                    'created_at' => $payment->created_at,
                ];
            })
            ->all();

        // RIWAYAT QR
        $details['qr_tickets'] = $record->qrTickets
            ->map(function ($qr) {
                $status = strtoupper((string) $qr->status_qr);

                return [
                    // ID record, bukan payload QR rahasia.
                    'code' => $qr->id,

                    'valid_until' => $qr->waktu_expired
                        ?->format('d M Y H:i') ?? '-',

                    'regenerated_from' => $qr->regenerated_from_qr_id,

                    'status' => in_array(
                        $status,
                        self::QR_ACTIVE,
                        true
                    ) ? 'active' : (
                        in_array($status, self::QR_USED, true)
                            ? 'used'
                            : 'invalid'
                    ),
                ];
            })->all();

        // RIWAYAT AKSES GERBANG
        $details['access_logs'] = DB::table('access_logs as a')
            ->join(
                'qr_tickets as q',
                'q.id',
                '=',
                'a.qr_ticket_id'
            )
            ->where('q.booking_id', $record->id)
            ->orderByDesc('a.waktu_scan')
            ->select(
                'a.waktu_scan',
                'a.hasil_validasi',
                'a.petugas_id'
            )
            ->get()
            ->map(fn ($log) => [
                'scanned_at' => $log->waktu_scan,
                'officer' => $log->petugas_id,
                'result' => $log->hasil_validasi,
            ])
            ->all();

        return view('admin.booking.show', [
            'booking' => $details,
        ]);
    }

    private function formatBooking(Booking $booking): array
    {
        $transactions = $booking->paymentTransactions
            ->sortByDesc('created_at');

        $latest = $transactions->first();

        $hasPaid = $transactions->contains(
            fn ($p) => in_array(
                strtoupper((string) $p->status_pembayaran),
                self::PAID,
                true
            )
        );

        $hasPending = $transactions->contains(
            fn ($p) => in_array(
                strtoupper((string) $p->status_pembayaran),
                self::PENDING,
                true
            )
        );

        if (!$booking->retribusi_diperlukan) {
            $paymentStatus = 'free';
        } elseif ($hasPaid) {
            $paymentStatus = 'paid';
        } elseif ($hasPending || !$latest) {
            $paymentStatus = 'pending';
        } else {
            $paymentStatus = 'failed';
        }

        $hasActiveQr = $booking->qrTickets->contains(
            fn ($qr) => in_array(
                strtoupper((string) $qr->status_qr),
                self::QR_ACTIVE,
                true
            )
        );

        $hasUsedQr = $booking->qrTickets->contains(
            fn ($qr) => in_array(
                strtoupper((string) $qr->status_qr),
                self::QR_USED,
                true
            )
        );

        $qrStatus = $hasActiveQr
            ? 'active'
            : ($hasUsedQr ? 'used' : 'none');

        $isRegular = strtoupper(
            (string) $booking->jenis_booking
        ) === 'REGULER';

        $schedule = $isRegular
            ? $booking->regularSlot
            : $booking->eventSchedule;

        $title = $isRegular
            ? 'Tiket Reguler'
            : (
                $booking->eventSchedule?->event?->nama_event
                ?? 'Event'
            );

        $date = $schedule?->tanggal;
        $start = $schedule?->jam_mulai;
        $end = $schedule?->jam_selesai;

        $detail = $date
            ? \Carbon\Carbon::parse($date)->format('d/m/Y')
            : 'Jadwal tidak tersedia';

        if ($start) {
            $detail .= ' · ' . substr((string) $start, 0, 5);

            if ($end) {
                $detail .= '–' . substr((string) $end, 0, 5);
            }

            $detail .= ' WIB';
        }

        return [
            // Tidak ada kolom booking_code; gunakan UUID asli.
            'booking_code' => $booking->id,
            'created_at' => $booking->created_at,

            // Data user memiliki kontak akun, bukan kolom nama.
            'user_name' => $booking->user?->no_hp_email
                ?? 'Pengguna tidak ditemukan',

            'is_warga_kota' =>
                $booking->identity?->status_warga_kota,

            'type' => $isRegular ? 'regular' : 'event',
            'schedule_title' => $title,
            'schedule_detail' => $detail,

            'payment_status' => $paymentStatus,
            'qr_status' => $qrStatus,

            'amount' => !$booking->retribusi_diperlukan
                ? 0
                : (float) (
                    $latest?->nominal
                    ?? $booking->tariff?->nominal
                    ?? 0
                ),
        ];
    }
}
