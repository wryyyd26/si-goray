<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Concerns\LogsInSiGorayAdmin;

class AdminNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loginAsSiGorayAdmin();
    }

    use LogsInSiGorayAdmin;

    public function test_dashboard_membuka_halaman_dashboard(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewIs('admin.dashboard');
    }

    public function test_kelola_user_membuka_halaman_user(): void
    {
        $this->get(route('admin.user.index'))
            ->assertOk()
            ->assertViewIs('admin.user.index');
    }

    public function test_route_dashboard_tidak_redirect_ke_event(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertDontSee('Location: /admin/events');
    }
}
