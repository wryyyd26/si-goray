<?php

namespace Tests\Feature;

use Tests\TestCase;

class BookingQrEndpointSecurityTest extends TestCase
{
    /**
     * Endpoint regenerasi QR harus menolak
     * permintaan tanpa autentikasi.
     */
    public function test_regenerasi_qr_menolak_pengguna_tanpa_autentikasi(): void
    {
        $response = $this->postJson(
            '/api/bookings/00000000-0000-0000-0000-000000000000/qr-ticket/regenerate'
        );

        $response->assertUnauthorized();
    }
}
