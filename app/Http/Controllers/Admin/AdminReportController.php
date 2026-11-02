<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'tanggal_mulai' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_akhir' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai'],
            'jenis' => ['nullable', 'in:semua,registrasi,tiket,pembayaran'],
        ]);

        $tanggalMulai = $validated['tanggal_mulai']
            ?? now()->startOfMonth()->toDateString();

        $tanggalAkhir = $validated['tanggal_akhir']
            ?? now()->toDateString();

        $jenis = $validated['jenis'] ?? 'semua';

        $mulai = Carbon::parse($tanggalMulai)->startOfDay();
        $akhir = Carbon::parse($tanggalAkhir)->endOfDay();

        if ($akhir->lt($mulai)) {
            return back()->withErrors([
                'tanggal_akhir' => 'Tanggal akhir harus sama atau setelah tanggal mulai.',
            ])->withInput();
        }

        $laporan = collect();

        $ringkasan = [
            'registrasi' => 0,
            'tiket_digunakan' => 0,
            'pembayaran' => 0,
        ];

        // Registrasi akun: berdasarkan waktu akun dibuat.
        if (in_array($jenis, ['semua', 'registrasi'], true)) {
            $users = DB::table('users')
                ->whereBetween('created_at', [$mulai, $akhir])
                ->get(['id', 'created_at']);

            $ringkasan['registrasi'] = $users->count();

            foreach ($users as $user) {
                $laporan->push([
                    'tanggal' => $user->created_at,
                    'kode' => $user->id,
                    'jenis' => 'registrasi',
                    'status_registrasi' => 'Terdaftar',
                    'status_tiket' => '-',
                    'metode_pembayaran' => '-',
                    'status_pembayaran' => 'Tidak Ada',
                ]);
            }
        }

        // Tiket digunakan: hanya scan gerbang yang berhasil.
        if (in_array($jenis, ['semua', 'tiket'], true)) {
            $scans = DB::table('access_logs as al')
                ->join('qr_tickets as qt', 'qt.id', '=', 'al.qr_ticket_id')
                ->where('al.hasil_validasi', 'BERHASIL')
                ->whereBetween('al.waktu_scan', [$mulai, $akhir])
                ->get([
                    'al.id',
                    'al.waktu_scan',
                    'qt.booking_id',
                ]);

            $ringkasan['tiket_digunakan'] = $scans->count();

            foreach ($scans as $scan) {
                $laporan->push([
                    'tanggal' => $scan->waktu_scan,
                    'kode' => $scan->booking_id,
                    'jenis' => 'tiket',
                    'status_registrasi' => '-',
                    'status_tiket' => 'Sudah Digunakan',
                    'metode_pembayaran' => '-',
                    'status_pembayaran' => 'Tidak Ada',
                ]);
            }
        }

        // Pembayaran: periode berdasarkan created_at transaksi.
        if (in_array($jenis, ['semua', 'pembayaran'], true)) {
            $payments = DB::table('payment_transactions')
                ->whereBetween('created_at', [$mulai, $akhir])
                ->get([
                    'id',
                    'booking_id',
                    'metode_pembayaran',
                    'status_pembayaran',
                    'created_at',
                ]);

            $ringkasan['pembayaran'] = $payments
                ->where('status_pembayaran', 'SUCCESS')
                ->count();

            foreach ($payments as $payment) {
                $statusLabel = match ($payment->status_pembayaran) {
                    'SUCCESS' => 'Berhasil',
                    'PENDING' => 'Menunggu',
                    'FAILED' => 'Gagal',
                    'CANCELLED' => 'Dibatalkan',
                    'EXPIRED' => 'Kedaluwarsa',
                    default => 'Status Lainnya',
                };

                $laporan->push([
                    'tanggal' => $payment->created_at,
                    'kode' => $payment->booking_id,
                    'jenis' => 'pembayaran',
                    'status_registrasi' => '-',
                    'status_tiket' => '-',
                    'metode_pembayaran' => $payment->metode_pembayaran ?: '-',
                    'status_pembayaran' => $statusLabel,
                ]);
            }
        }

        $laporan = $laporan
            ->sortByDesc('tanggal')
            ->values();

        return view('admin.report.index', compact(
            'tanggalMulai',
            'tanggalAkhir',
            'jenis',
            'ringkasan',
            'laporan'
        ));
    }
}
