<?php

namespace Tests\Feature;

use App\Services\QrTicketService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentCallbackQrValidationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new \RuntimeException(
                'Test hanya boleh menggunakan database gor_reservation_testing.'
            );
        }

        // Kunci khusus pengujian; tidak menggunakan kredensial Midtrans asli.
        config()->set('midtrans.server_key', 'sigoray-only-test-key');
    }

    public function test_callback_tanpa_field_wajib_ditolak(): void
    {
        $before = DB::table('payment_gateway_callbacks')->count();

        $this->postJson('/api/payments/callback', [])
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertSame(
            $before,
            DB::table('payment_gateway_callbacks')->count()
        );
    }

    public function test_callback_signature_palsu_ditolak_tanpa_mengubah_data(): void
    {
        $callbacksBefore = DB::table('payment_gateway_callbacks')->count();
        $qrBefore = DB::table('qr_tickets')->count();
        $confirmedBefore = DB::table('bookings')
            ->where('status_booking', 'CONFIRMED')->count();

        $this->postJson('/api/payments/callback', [
            'order_id' => 'ORDER-UJI-TIDAK-ADA',
            'status_code' => '200',
            'gross_amount' => '25000.00',
            'transaction_status' => 'settlement',
            'signature_key' => str_repeat('0', 128),
        ])->assertStatus(403)
          ->assertJsonPath('success', false);

        $this->assertSame($callbacksBefore, DB::table('payment_gateway_callbacks')->count());
        $this->assertSame($qrBefore, DB::table('qr_tickets')->count());
        $this->assertSame(
            $confirmedBefore,
            DB::table('bookings')->where('status_booking', 'CONFIRMED')->count()
        );
    }

    public function test_qr_dengan_format_tidak_valid_ditolak(): void
    {
        $result = app(QrTicketService::class)->validate('payload-tanpa-signature');

        $this->assertFalse($result['valid']);
        $this->assertSame('FORMAT_QR_TIDAK_VALID', $result['reason']);
    }

    public function test_qr_dengan_signature_dimodifikasi_ditolak(): void
    {
        $result = app(QrTicketService::class)->validate('YWJj.invalid_signature');

        $this->assertFalse($result['valid']);
        $this->assertSame('SIGNATURE_TIDAK_VALID', $result['reason']);
    }
}
