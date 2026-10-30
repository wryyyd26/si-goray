<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\LogsInSiGorayAdmin;
use Tests\TestCase;

class AdminUserIntegrationTest extends TestCase
{
    use LogsInSiGorayAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new \RuntimeException('Hanya gor_reservation_testing.');
        }
        $this->loginAsSiGorayAdmin();
    }

    public function test_daftar_user_dapat_dibuka(): void
    {
        $this->get(route('admin.user.index'))->assertOk()->assertViewIs('admin.user.index');
    }

    public function test_detail_tidak_ditemukan(): void
    {
        $this->get(route('admin.user.detail', ['user' => '00000000-0000-4000-8000-000000000000']))->assertNotFound();
    }

    public function test_pencarian_terlalu_panjang_ditolak(): void
    {
        $this->get(route('admin.user.index', ['q' => str_repeat('x', 101)]))->assertSessionHasErrors('q');
    }
}
