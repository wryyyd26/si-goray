<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RegularFreeConfirmationWebRouteTest extends TestCase
{
    public function test_post_route_for_regular_free_confirmation_is_registered(): void
    {
        $route = Route::getRoutes()->getByName('reguler.booking.confirm-free');

        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
        $this->assertSame('pengunjung/booking-reguler/{booking}/confirm-free', $route->uri());
        $this->assertContains(\App\Http\Middleware\EnsureVisitorBookingLogin::class, $route->gatherMiddleware());
    }

    public function test_regular_draft_view_contains_csrf_protected_free_confirmation_form(): void
    {
        $view = file_get_contents(resource_path('views/regular-booking-draft.blade.php'));

        $this->assertStringContainsString("@csrf", $view);
        $this->assertStringContainsString("route('reguler.booking.confirm-free'", $view);
        $this->assertStringContainsString("$" . "booking->retribusi_diperlukan", $view);
    }
}
