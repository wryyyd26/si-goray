<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;

class RegularFormBookingWebBridgeTest extends TestCase
{
    public function test_web_booking_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('reguler.booking.store'));
        $this->assertTrue(Route::has('reguler.booking.draft'));
    }

    public function test_regular_calendar_contains_birth_date_and_real_form_actions(): void
    {
        $this->get(route('activity.reguler'))
            ->assertOk()
            ->assertSee('name="tanggal_lahir"', false)
            ->assertSee('name="regular_slot_id"', false)
            ->assertSee(route('reguler.booking.store'), false);
    }

    public function test_guest_cannot_submit_a_regular_booking(): void
    {
        $this->post(route('reguler.booking.store'), [
            'regular_slot_id' => '00000000-0000-4000-8000-000000000001',
        ])->assertRedirect();
    }
}
