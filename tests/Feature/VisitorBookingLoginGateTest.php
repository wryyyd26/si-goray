<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VisitorBookingLoginGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_warga_form_redirects_to_login_and_keeps_event(): void
    {
        $event = (string) Str::uuid();
        $this->get(route('events.pesan-tiket', ['event' => $event]))
            ->assertRedirect(route('pengunjung.login', [
                'next' => route('events.pesan-tiket', ['event' => $event], false),
            ]));
    }

    public function test_guest_luar_form_redirects_to_login_and_keeps_event(): void
    {
        $event = (string) Str::uuid();
        $this->get(route('events.pesan-tiket-luar', ['event' => $event]))
            ->assertRedirect(route('pengunjung.login', [
                'next' => route('events.pesan-tiket-luar', ['event' => $event], false),
            ]));
    }

    public function test_guest_cannot_submit_warga_form(): void
    {
        $event = (string) Str::uuid();
        $this->post(route('events.pesan-tiket.simpan'), ['event_id' => $event])
            ->assertRedirect(route('pengunjung.login', [
                'next' => route('events.pesan-tiket', ['event' => $event], false),
            ]));
    }

    public function test_guest_cannot_submit_luar_form(): void
    {
        $event = (string) Str::uuid();
        $this->post(route('events.pesan-tiket-luar.simpan'), ['event_id' => $event])
            ->assertRedirect(route('pengunjung.login', [
                'next' => route('events.pesan-tiket-luar', ['event' => $event], false),
            ]));
    }

    public function test_admin_session_flag_does_not_count_as_visitor_login(): void
    {
        $this->withSession(['sigoray_admin_authenticated' => true])
            ->get(route('events.pesan-tiket'))
            ->assertRedirect(route('pengunjung.login', [
                'next' => route('events.pesan-tiket', [], false),
            ]));
    }
}
