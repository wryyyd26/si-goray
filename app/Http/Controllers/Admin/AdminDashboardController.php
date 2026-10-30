<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth();
        $nextMonthStart = $monthStart->copy()->addMonth();
        $year = now()->year;

        $totalUsers = DB::table('users')->count();
        $newUsersThisMonth = DB::table('users')
            ->where('created_at', '>=', $monthStart)
            ->where('created_at', '<', $nextMonthStart)
            ->count();

        // Enam sesi (atau jumlah sesi sebenarnya) dijumlahkan, bukan diasumsikan 200/hari.
        $regularToday = DB::table('regular_slots')
            ->whereDate('tanggal', $today)
            ->where('status', 'active')
            ->selectRaw('COALESCE(SUM(kapasitas), 0) as capacity, COALESCE(SUM(kuota_terpakai), 0) as used')
            ->first();

        $regularCapacity = (int) ($regularToday->capacity ?? 0);
        $regularUsed = (int) ($regularToday->used ?? 0);

        // Angka ini mencakup seluruh jadwal event, bukan hanya event aktif.
        $eventUsed = (int) DB::table('event_schedules')->sum('kuota_terpakai');

        // Hanya nilai pembayaran sukses yang tercatat bulan ini.
        // Tanggal yang tersedia adalah created_at, bukan waktu settlement.
        $revenueThisMonth = (float) DB::table('payment_transactions')
            ->where('status_pembayaran', 'SUCCESS')
            ->where('created_at', '>=', $monthStart)
            ->where('created_at', '<', $nextMonthStart)
            ->sum('nominal');

        $paymentsByStatus = DB::table('payment_transactions')
            ->selectRaw('UPPER(status_pembayaran) as payment_status, COUNT(*) as total')
            ->groupByRaw('UPPER(status_pembayaran)')
            ->pluck('total', 'payment_status');

        $paymentCounts = [
            'success' => (int) ($paymentsByStatus['SUCCESS'] ?? 0),
            'pending' => (int) ($paymentsByStatus['PENDING'] ?? 0),
            'failed' => (int) ($paymentsByStatus['FAILED'] ?? 0)
                + (int) ($paymentsByStatus['EXPIRED'] ?? 0)
                + (int) ($paymentsByStatus['CANCELLED'] ?? 0)
                + (int) ($paymentsByStatus['CANCELED'] ?? 0),
        ];

        $bookingRows = DB::table('bookings')
            ->selectRaw('MONTH(created_at) as month_number, UPPER(jenis_booking) as booking_type, COUNT(*) as total')
            ->where('created_at', '>=', Carbon::create($year, 1, 1)->startOfDay())
            ->where('created_at', '<', Carbon::create($year + 1, 1, 1)->startOfDay())
            ->groupByRaw('MONTH(created_at), UPPER(jenis_booking)')
            ->get();

        $bookingChart = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            'regular' => array_fill(0, 12, 0),
            'event' => array_fill(0, 12, 0),
            'year' => $year,
        ];

        foreach ($bookingRows as $row) {
            $index = (int) $row->month_number - 1;
            if ($index < 0 || $index > 11) {
                continue;
            }
            if ($row->booking_type === 'REGULER') {
                $bookingChart['regular'][$index] = (int) $row->total;
            } elseif ($row->booking_type === 'EVENT') {
                $bookingChart['event'][$index] = (int) $row->total;
            }
        }

        $latestTransactions = DB::table('payment_transactions as p')
            ->join('bookings as b', 'b.id', '=', 'p.booking_id')
            ->join('users as u', 'u.id', '=', 'b.user_id')
            ->leftJoin('regular_slots as rs', 'rs.id', '=', 'b.regular_slot_id')
            ->leftJoin('event_schedules as es', 'es.id', '=', 'b.event_schedule_id')
            ->select([
                'p.id', 'p.kode_bayar', 'p.nominal', 'p.status_pembayaran',
                'p.created_at', 'b.jenis_booking', 'u.no_hp_email',
            ])
            ->selectRaw('COALESCE(rs.tanggal, es.tanggal) as tanggal_kunjungan')
            ->orderByDesc('p.created_at')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $contact = (string) $row->no_hp_email;
                if (str_contains($contact, '@')) {
                    [$name, $domain] = explode('@', $contact, 2);
                    $row->user_label = mb_substr($name, 0, 1).'***@'.$domain;
                } else {
                    $row->user_label = '***'.mb_substr($contact, -3);
                }
                return $row;
            });

        return view('admin.dashboard', compact(
            'totalUsers', 'newUsersThisMonth', 'regularCapacity', 'regularUsed',
            'eventUsed', 'revenueThisMonth', 'paymentCounts', 'bookingChart',
            'latestTransactions'
        ));
    }
}
