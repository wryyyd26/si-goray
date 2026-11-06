<?php

namespace Tests\Feature;

use Tests\TestCase;

class VisitorRegularLoginReturnTest extends TestCase
{
    public function test_reguler_login_keeps_safe_return_url(): void
    {
        $this->get('/pengunjung/login?next=%2Factivity-reguler')
            ->assertOk()
            ->assertSee('data-next="/activity-reguler"', false);
    }

    public function test_login_rejects_external_redirect(): void
    {
        $this->get('/pengunjung/login?next=https%3A%2F%2Fevil.example')
            ->assertOk()
            ->assertSee('data-next="/events"', false);
    }

    public function test_navbar_guest_uses_otp_login_and_return_path(): void
    {
        $this->get('/activity-reguler')
            ->assertOk()
            ->assertSee('Login / Daftar')
            ->assertSee('/pengunjung/login?next=', false);
    }

    public function test_reguler_form_uses_backend_field_name(): void
    {
        $this->get('/activity-reguler')
            ->assertOk()
            ->assertSee('name="nama_lengkap"', false)
            ->assertSee('name="tanggal_lahir"', false);
    }
}
