<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Concerns\LogsInSiGorayAdmin;

class AdminBookingManagementTest extends TestCase
{
    use LogsInSiGorayAdmin;

    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsSiGorayAdmin();

        if (
            DB::connection()->getDatabaseName()
            !== 'gor_reservation_testing'
        ) {
            throw new \RuntimeException(
                'Pengujian hanya boleh dijalankan pada database testing.'
            );
        }
    }

    public function test_halaman_kelola_pemesanan_dapat_dibuka(): void
    {
        $response = $this->get(
            route('admin.booking.pemesanan')
        );

        $response->assertOk();
        $response->assertViewIs(
            'admin.booking.pemesanan'
        );
        $response->assertSee('Kelola Pemesanan');
    }

    public function test_filter_pemesanan_dapat_digunakan(): void
    {
        $response = $this->get(
            route('admin.booking.pemesanan', [
                'type' => 'regular',
                'payment_status' => 'pending',
                'qr_status' => 'none',
            ])
        );

        $response->assertOk();
    }

    public function test_detail_booking_tidak_ditemukan(): void
    {
        $response = $this->get(
            route('admin.booking.show', [
                'booking' =>
                    '00000000-0000-4000-8000-000000000000',
            ])
        );

        $response->assertNotFound();
    }

    public function test_filter_tidak_valid_ditolak(): void
    {
        $response = $this->get(
            route('admin.booking.pemesanan', [
                'type' => 'invalid',
            ])
        );

        $response->assertSessionHasErrors('type');
    }
}
