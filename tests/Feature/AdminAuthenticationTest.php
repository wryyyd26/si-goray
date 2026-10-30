<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('sigoray_admin.username', 'admin-test');
        config()->set('sigoray_admin.password_hash', Hash::make('PasswordTestingSangatPanjang!123'));
        config()->set('sigoray_admin.session_minutes', 120);
        RateLimiter::clear('sigoray-admin-login:' . hash('sha256', '127.0.0.1'));
    }

    public function test_pengunjung_tanpa_login_dialihkan_ke_login_admin(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
        $this->get(route('admin.user.index'))
            ->assertRedirect(route('admin.login'));
        $this->get(route('admin.events.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_mutasi_tanpa_login_ditolak(): void
    {
        $this->post(route('admin.events.store'), [])->assertForbidden();
    }

    public function test_password_salah_tidak_dapat_login(): void
    {
        $this->post(route('admin.login.submit'), [
            'username' => 'admin-test',
            'password' => 'password-yang-salah',
        ])->assertSessionHasErrors('login');

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_login_logout_admin_berhasil(): void
    {
        $this->get(route('admin.login'))->assertOk();

        $this->post(route('admin.login.submit'), [
            'username' => 'admin-test',
            'password' => 'PasswordTestingSangatPanjang!123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))->assertOk();
        $this->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_rotasi_password_mengakhiri_sesi_lama(): void
    {
        $this->post(route('admin.login.submit'), [
            'username' => 'admin-test',
            'password' => 'PasswordTestingSangatPanjang!123',
        ])->assertRedirect(route('admin.dashboard'));

        config()->set('sigoray_admin.password_hash', Hash::make('PasswordBaruYangSangatPanjang!123'));

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }
}
