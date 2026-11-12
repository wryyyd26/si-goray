<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

class AdminReportController extends Controller
{
    private const HEADINGS = [
        'Tanggal', 'Kode', 'Jenis', 'Status registrasi',
        'Status tiket', 'Metode pembayaran', 'Status pembayaran',
    ];

    public function index(Request $request)
    {
        [$tanggalMulai, $tanggalAkhir, $jenis] = $this->filters($request);

        $mulai = Carbon::parse($tanggalMulai)->startOfDay();
        $akhir = Carbon::parse($tanggalAkhir)->endOfDay();

        if ($akhir->lt($mulai)) {
            return back()->withErrors([
                'tanggal_akhir' => 'Tanggal akhir harus sama atau setelah tanggal mulai.',
            ])->withInput();
        }

        [$laporan, $ringkasan] = $this->buildReport($mulai, $akhir, $jenis);

        return view('admin.report.index', compact(
            'tanggalMulai',
            'tanggalAkhir',
            'jenis',
            'ringkasan',
            'laporan'
        ));
    }

    /** Dipanggil tombol Ekspor Excel / Ekspor PDF di halaman laporan. */
    public function export(Request $request, string $format)
    {
        [$tanggalMulai, $tanggalAkhir, $jenis] = $this->filters($request);

        [$laporan] = $this->buildReport(
            Carbon::parse($tanggalMulai)->startOfDay(),
            Carbon::parse($tanggalAkhir)->endOfDay(),
            $jenis
        );

        if ($laporan->isEmpty()) {
            $message = 'Tidak ada data untuk diekspor pada filter ini.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message);
        }

        $rows = $laporan->map(fn (array $r) => [
            Carbon::parse($r['tanggal'])->format('d/m/Y H:i'),
            $r['kode'],
            ucfirst($r['jenis']),
            $r['status_registrasi'],
            $r['status_tiket'],
            $r['metode_pembayaran'],
            $r['status_pembayaran'],
        ])->values();

        $filename = "laporan-{$tanggalMulai}-sd-{$tanggalAkhir}-{$jenis}";

        if ($format === 'excel') {
            return Excel::download(
                new class($rows, self::HEADINGS) implements FromCollection, WithHeadings, ShouldAutoSize {
                    public function __construct(private Collection $rows, private array $headings) {}

                    public function collection(): Collection
                    {
                        return $this->rows;
                    }

                    public function headings(): array
                    {
                        return $this->headings;
                    }
                },
                $filename . '.xlsx'
            );
        }

        return Pdf::loadView('admin.report.pdf', [
            'rows' => $rows,
            'headings' => self::HEADINGS,
            'tanggalMulai' => $tanggalMulai,
            'tanggalAkhir' => $tanggalAkhir,
            'jenis' => $jenis,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->download($filename . '.pdf');
    }

    /** Validasi filter, dipakai bersama oleh index() dan export(). */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'tanggal_mulai' => ['nullable', 'date_format:Y-m-d'],
            'tanggal_akhir' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai'],
            'jenis' => ['nullable', 'in:semua,registrasi,tiket,pembayaran'],
        ]);

        return [
            $validated['tanggal_mulai'] ?? now()->startOfMonth()->toDateString(),
            $validated['tanggal_akhir'] ?? now()->toDateString(),
            $validated['jenis'] ?? 'semua',
        ];
    }

    /**
     * Isinya sama dengan logika index() sebelumnya, hanya dipindah ke sini
     * supaya data di layar dan data di file ekspor selalu sama.
     */
    private function buildReport(Carbon $mulai, Carbon $akhir, string $jenis): array
    {
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

        return [$laporan->sortByDesc('tanggal')->values(), $ringkasan];
    }
}
