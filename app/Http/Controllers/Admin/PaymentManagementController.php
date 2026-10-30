<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentManagementController extends Controller
{
    private function ensureLocalAccess(): void
    {
        // Sementara, sampai autentikasi dan otorisasi Admin tersedia.
        abort_unless(
            app()->environment(['local', 'testing']),
            403,
            'Otorisasi Admin belum dikonfigurasi.'
        );
    }

    public function index(Request $request)
    {
        $this->ensureLocalAccess();

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
            'method' => ['nullable', 'string', 'max:100'],
        ]);

        $filters = array_merge([
            'search' => '',
            'status' => '',
            'method' => '',
        ], $filters);

        $query = DB::table('payment_transactions as pt')
            ->leftJoin(
                'bookings as b',
                'b.id',
                '=',
                'pt.booking_id'
            )
            ->select([
                'pt.id',
                'pt.booking_id',
                'pt.kode_bayar',
                'pt.metode_pembayaran',
                'pt.nominal',
                'pt.status_pembayaran',
                'pt.jumlah_retry',
                'pt.referensi_gateway',
                'pt.expired_at',
                'pt.created_at',
                'b.jenis_booking',
                'b.status_booking',
            ]);

        if ($filters['search'] !== '') {
            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('pt.kode_bayar', 'like', "%{$search}%")
                    ->orWhere('pt.booking_id', 'like', "%{$search}%")
                    ->orWhere('pt.id', 'like', "%{$search}%");
            });
        }

        if ($filters['status'] !== '') {
            $query->where(
                'pt.status_pembayaran',
                $filters['status']
            );
        }

        if ($filters['method'] !== '') {
            $query->where(
                'pt.metode_pembayaran',
                $filters['method']
            );
        }

        $payments = $query
            ->orderByDesc('pt.created_at')
            ->orderByDesc('pt.id')
            ->paginate(15)
            ->withQueryString();

        // Statistik menggunakan nilai status asli dari database.
        // Jangan menganggap semua status gateway sudah diketahui.
        $statusCounts = DB::table('payment_transactions')
            ->select('status_pembayaran')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status_pembayaran')
            ->pluck('total', 'status_pembayaran');

        $statistics = [
            'total' => DB::table('payment_transactions')->count(),
            'by_status' => $statusCounts,
        ];

        $statuses = DB::table('payment_transactions')
            ->whereNotNull('status_pembayaran')
            ->distinct()
            ->orderBy('status_pembayaran')
            ->pluck('status_pembayaran');

        $methods = DB::table('payment_transactions')
            ->whereNotNull('metode_pembayaran')
            ->distinct()
            ->orderBy('metode_pembayaran')
            ->pluck('metode_pembayaran');

        return view('admin.payment.pembayaran', [
            'payments' => $payments,
            'statistics' => $statistics,
            'statuses' => $statuses,
            'methods' => $methods,
            'filters' => $filters,
        ]);
    }

    public function show(string $payment)
    {
        $this->ensureLocalAccess();

        $record = DB::table('payment_transactions')
            ->where('id', $payment)
            ->first();

        abort_if(!$record, 404);

        $booking = DB::table('bookings')
            ->where('id', $record->booking_id)
            ->first();

        // Tidak mengirim snap_token ataupun data sensitif gateway.
        return view('admin.payment.show', [
            'payment' => $record,
            'booking' => $booking,
        ]);
    }
}
