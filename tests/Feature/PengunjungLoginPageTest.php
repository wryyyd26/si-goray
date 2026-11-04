<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class PengunjungLoginPageTest extends TestCase
{
    public function test_halaman_login_dapat_dibuka_tanpa_autentikasi(): void
    {
        $this->get('/pengunjung/login')
            ->assertOk()
            ->assertSee('Login Pengunjung')
            ->assertSee('visitor-login-form')
            ->assertSee('js/pengunjung-login.js');
    }

    public function test_form_menggunakan_endpoint_otp_web_yang_ada(): void
    {
        $this->get('/pengunjung/login')
            ->assertOk()
            ->assertSee(route('pengunjung.web-auth.request-otp'))
            ->assertSee(route('pengunjung.web-auth.verify-otp'));
    }

    public function test_redirect_tujuan_internal_pemesanan_dipertahankan(): void
    {
        $target = '/event/futsal/pesan-tiket?event=abc';
        $this->get('/pengunjung/login?next=' . urlencode($target))
            ->assertOk()
            ->assertSee('data-next="' . $target . '"', false);
    }

    public function test_redirect_eksternal_ditolak(): void
    {
        $this->get('/pengunjung/login?next=' . urlencode('https://evil.example/'))
            ->assertOk()
            ->assertSee('data-next="/events"', false);
    }

    public function test_pengunjung_sudah_login_langsung_ke_tujuan_internal(): void
    {
        $user = new User();
        $user->id = '00000000-0000-4000-8000-000000000001';
        $user->exists = true;
        $this->actingAs($user, 'web')
            ->get('/pengunjung/login?next=' . urlencode('/events/detail?event=abc'))
            ->assertRedirect('/events/detail?event=abc');
    }
}
