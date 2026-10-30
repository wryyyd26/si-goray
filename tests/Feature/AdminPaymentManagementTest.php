<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Concerns\LogsInSiGorayAdmin;

class AdminPaymentManagementTest extends TestCase
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
                'Pengujian hanya boleh menggunakan gor_reservation_testing.'
            );
        }
    }

    public function test_halaman_pembayaran_dapat_dibuka(): void
    {
        $this->get(route('admin.payment.pembayaran'))
            ->assertOk()
            ->assertViewIs('admin.payment.pembayaran')
            ->assertSee('Kelola Pembayaran');
    }

    public function test_filter_status_pending_dapat_digunakan(): void
    {
        $this->get(route('admin.payment.pembayaran', [
            'status' => 'PENDING',
        ]))->assertOk();
    }

    public function test_filter_status_success_dapat_digunakan(): void
    {
        $this->get(route('admin.payment.pembayaran', [
            'status' => 'SUCCESS',
        ]))->assertOk();
    }

    public function test_filter_input_tidak_valid_ditolak(): void
    {
        $this->get(route('admin.payment.pembayaran', [
            'search' => str_repeat('X', 101),
        ]))->assertSessionHasErrors('search');
    }

    public function test_detail_pembayaran_tidak_ditemukan(): void
    {
        $this->get(route('admin.payment.show', [
            'payment' => '00000000-0000-4000-8000-000000000000',
        ]))->assertNotFound();
    }
}
