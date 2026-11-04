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

class FreeBookingConfirmationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('Pengujian hanya boleh menggunakan gor_reservation_testing.');
        }
    }

    private function booking(bool $warga = true): array
    {
        $user = User::create([
            'no_hp_email' => 'free-' . Str::uuid() . '@example.com',
            'status_otp' => true,
        ]);
        $identity = Identity::create([
            'user_id' => $user->id,
            'nik_enc' => '3576010101900002',
            'nik_hash' => hash('sha256', (string) Str::uuid()),
            'tanggal_lahir_enc' => '1990-01-01',
            'status_warga_kota' => $warga,
            'sumber_validasi' => 'local',
            'divalidasi_at' => now(),
        ]);
        $slot = (string) Str::uuid();
        DB::table('regular_slots')->insert([
            'id' => $slot,
            'tanggal' => now()->addDays(7)->toDateString(),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kapasitas' => 5,
            'kuota_terpakai' => 1,
            'status' => 'active',
        ]);
        $tariff = TariffReference::create([
            'event_id' => null,
            'jenis_pengunjung' => 'UMUM',
            'nominal' => 10000,
            'berlaku_mulai' => now()->subDay()->toDateString(),
            'aktif' => true,
        ]);
        $booking = Booking::create([
            'user_id' => $user->id,
            'identity_id' => $identity->id,
            'jenis_booking' => 'REGULER',
            'regular_slot_id' => $slot,
            'tariff_id' => $tariff->id,
            'retribusi_diperlukan' => !$warga,
            'status_booking' => 'DRAFT',
        ]);
        return [$user, $booking, $slot];
    }

    public function test_warga_gratis_dapat_confirm_dan_qr_hanya_satu(): void
    {
        [$user, $booking, $slot] = $this->booking();
        Sanctum::actingAs($user, ['booking:create']);
        $url = "/api/bookings/{$booking->id}/confirm-free";
        $first = $this->postJson($url)->assertOk()
            ->assertJsonPath('data.status_booking', 'CONFIRMED');
        $second = $this->postJson($url)->assertOk();
        $this->assertSame($first->json('data.qr_ticket_id'), $second->json('data.qr_ticket_id'));
        $this->assertSame(1, DB::table('qr_tickets')->where('booking_id', $booking->id)->count());
        $this->assertSame('CONFIRMED', $booking->fresh()->status_booking);
        $this->assertSame(1, (int) DB::table('regular_slots')->where('id', $slot)->value('kuota_terpakai'));
    }

    public function test_booking_orang_lain_ditolak(): void
    {
        [, $booking] = $this->booking();
        $other = User::create([
            'no_hp_email' => 'other-' . Str::uuid() . '@example.com',
            'status_otp' => true,
        ]);
        Sanctum::actingAs($other, ['booking:create']);
        $this->postJson("/api/bookings/{$booking->id}/confirm-free")->assertForbidden();
        $this->assertSame('DRAFT', $booking->fresh()->status_booking);
    }

    public function test_booking_berbayar_ditolak(): void
    {
        [$user, $booking] = $this->booking(false);
        Sanctum::actingAs($user, ['booking:create']);
        $this->postJson("/api/bookings/{$booking->id}/confirm-free")->assertUnprocessable();
        $this->assertSame('DRAFT', $booking->fresh()->status_booking);
    }

    public function test_tanpa_token_ditolak(): void
    {
        [, $booking] = $this->booking();
        $this->postJson("/api/bookings/{$booking->id}/confirm-free")->assertUnauthorized();
    }
}
