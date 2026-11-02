<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\LogsInSiGorayAdmin;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use LogsInSiGorayAdmin;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new \RuntimeException(
                'Pengujian hanya boleh menggunakan gor_reservation_testing.'
            );
        }

        $this->loginAsSiGorayAdmin();
    }

    public function test_halaman_laporan_dapat_dibuka(): void
    {
        $this->get(route('admin.report.index'))
            ->assertOk()
            ->assertViewIs('admin.report.index')
            ->assertViewHas('ringkasan')
            ->assertViewHas('laporan')
            ->assertSee('Laporan');
    }

    public function test_filter_jenis_laporan_dapat_digunakan(): void
    {
        foreach (['semua', 'registrasi', 'tiket', 'pembayaran'] as $jenis) {
            $this->get(route('admin.report.index', [
                'jenis' => $jenis,
            ]))
                ->assertOk()
                ->assertViewHas('jenis', $jenis);
        }
    }

    public function test_filter_periode_dapat_digunakan(): void
    {
        $this->get(route('admin.report.index', [
            'tanggal_mulai' => '2026-10-01',
            'tanggal_akhir' => '2026-10-31',
            'jenis' => 'semua',
        ]))
            ->assertOk()
            ->assertViewHas('tanggalMulai', '2026-10-01')
            ->assertViewHas('tanggalAkhir', '2026-10-31');
    }

    public function test_periode_terbalik_ditolak(): void
    {
        $this->get(route('admin.report.index', [
            'tanggal_mulai' => '2026-11-02',
            'tanggal_akhir' => '2026-10-01',
        ]))
            ->assertSessionHasErrors('tanggal_akhir');
    }

    public function test_jenis_laporan_tidak_valid_ditolak(): void
    {
        $this->get(route('admin.report.index', [
            'jenis' => 'tidak-valid',
        ]))
            ->assertSessionHasErrors('jenis');
    }

    public function test_ringkasan_menggunakan_data_database(): void
    {
        $response = $this->get(route('admin.report.index', [
            'tanggal_mulai' => '2026-01-01',
            'tanggal_akhir' => '2026-12-31',
            'jenis' => 'semua',
        ]));

        $response->assertOk();

        $response->assertViewHas('ringkasan', function ($ringkasan) {
            return is_array($ringkasan)
                && isset(
                    $ringkasan['registrasi'],
                    $ringkasan['tiket_digunakan'],
                    $ringkasan['pembayaran']
                );
        });
    }
}

