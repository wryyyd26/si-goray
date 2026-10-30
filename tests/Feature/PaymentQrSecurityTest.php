<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Identity;
use App\Models\TariffReference;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class PaymentQrSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('Pengujian hanya boleh memakai gor_reservation_testing.');
        }
    }

    private function draftBooking(): Booking
    {
        $user = User::create([
            'no_hp_email' => 'payment-qr-' . Str::uuid() . '@example.com',
            'status_otp' => true,
        ]);

        $identity = Identity::create([
            'user_id' => $user->id,
            'nik_enc' => '3576010101900002',
            'nik_hash' => hash('sha256', (string) Str::uuid()),
            'tanggal_lahir_enc' => '1990-01-01',
            'status_warga_kota' => false,
            'sumber_validasi' => 'local',
            'divalidasi_at' => now(),
        ]);

        $tariff = TariffReference::create([
            'event_id' => null,
            'jenis_pengunjung' => 'UMUM',
            'nominal' => 10000,
            'berlaku_mulai' => now()->subDay()->toDateString(),
            'aktif' => true,
        ]);

        $slotId = (string) Str::uuid();
        DB::table('regular_slots')->insert([
            'id' => $slotId,
            'tanggal' => now()->addDays(7)->toDateString(),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kapasitas' => 200,
            'kuota_terpakai' => 0,
            'status' => 'active',
        ]);

        return Booking::create([
            'user_id' => $user->id,
            'identity_id' => $identity->id,
            'jenis_booking' => 'REGULER',
            'regular_slot_id' => $slotId,
            'event_schedule_id' => null,
            'tariff_id' => $tariff->id,
            'retribusi_diperlukan' => true,
            'status_booking' => 'DRAFT',
        ]);
    }

    public function test_pembayaran_tanpa_token_ditolak(): void
    {
        $booking = $this->draftBooking();
        $this->postJson("/api/bookings/{$booking->id}/payment")->assertUnauthorized();
        $this->assertSame(0, DB::table('payment_transactions')->where('booking_id', $booking->id)->count());
    }

    public function test_pembayaran_booking_orang_lain_ditolak(): void
    {
        $booking = $this->draftBooking();
        $other = User::create([
            'no_hp_email' => 'other-payment-' . Str::uuid() . '@example.com',
            'status_otp' => true,
        ]);
        Sanctum::actingAs($other, ['booking:read']);
        $this->postJson("/api/bookings/{$booking->id}/payment")
            ->assertForbidden()
            ->assertJsonPath('success', false);
        $this->assertSame(0, DB::table('payment_transactions')->where('booking_id', $booking->id)->count());
    }

    public function test_qr_booking_draft_ditolak(): void
    {
        $booking = $this->draftBooking();
        Sanctum::actingAs($booking->user, ['booking:read']);
        $this->postJson("/api/bookings/{$booking->id}/qr-ticket")
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
        $this->assertSame(0, DB::table('qr_tickets')->where('booking_id', $booking->id)->count());
    }

    public function test_qr_booking_orang_lain_ditolak(): void
    {
        $booking = $this->draftBooking();
        $other = User::create([
            'no_hp_email' => 'other-qr-' . Str::uuid() . '@example.com',
            'status_otp' => true,
        ]);
        Sanctum::actingAs($other, ['booking:read']);
        $this->postJson("/api/bookings/{$booking->id}/qr-ticket")
            ->assertForbidden()
            ->assertJsonPath('success', false);
        $this->assertSame(0, DB::table('qr_tickets')->where('booking_id', $booking->id)->count());
    }
}
