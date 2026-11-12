<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Identity;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class PaymentWebBridgeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('Tes pembayaran hanya boleh berjalan di gor_reservation_testing.');
        }
    }

    private function user(): User
    {
        return User::create(['no_hp_email' => 'pay-' . Str::uuid() . '@example.com', 'status_otp' => true]);
    }

    private function booking(bool $paid = true): Booking
    {
        $user = $this->user();
        $identity = Identity::create([
            'user_id' => $user->id, 'nik_enc' => '3576010101900002',
            'nik_hash' => hash('sha256', (string) Str::uuid()),
            'tanggal_lahir_enc' => '1990-01-01', 'status_warga_kota' => !$paid,
            'sumber_validasi' => 'local', 'divalidasi_at' => now(),
        ]);
        $slotId = (string) Str::uuid();
        DB::table('regular_slots')->insert([
            'id' => $slotId, 'tanggal' => now()->addDays(7)->toDateString(),
            'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00',
            'kapasitas' => 10, 'kuota_terpakai' => 1, 'status' => 'active',
        ]);
        $tariffId = (string) Str::uuid();
        DB::table('tariff_reference')->insert([
            'id' => $tariffId, 'event_id' => null, 'jenis_pengunjung' => 'UMUM',
            'nominal' => 10000, 'berlaku_mulai' => now()->subDay()->toDateString(), 'aktif' => true,
        ]);
        return Booking::create([
            'user_id' => $user->id, 'identity_id' => $identity->id,
            'jenis_booking' => 'REGULER', 'regular_slot_id' => $slotId,
            'tariff_id' => $tariffId, 'retribusi_diperlukan' => $paid,
            'status_booking' => 'DRAFT',
        ]);
    }

    public function test_guest_diarahkan_login(): void
    {
        $booking = $this->booking();
        $this->get(route('booking.payment.show', ['booking' => $booking->id]))->assertRedirect();
    }

    public function test_pemilik_melihat_nominal_tarif_database(): void
    {
        $booking = $this->booking();
        $this->actingAs($booking->user, 'web')
            ->get(route('booking.payment.show', ['booking' => $booking->id]))
            ->assertOk()->assertSee('10.000')->assertSee('Lanjutkan ke Pembayaran');
    }

    public function test_orang_lain_dilarang_melihat_checkout(): void
    {
        $booking = $this->booking();
        $this->actingAs($this->user(), 'web')
            ->get(route('booking.payment.show', ['booking' => $booking->id]))->assertForbidden();
    }

    public function test_booking_gratis_tidak_dapat_masuk_checkout(): void
    {
        $booking = $this->booking(false);
        $this->actingAs($booking->user, 'web')
            ->get(route('booking.payment.show', ['booking' => $booking->id]))->assertNotFound();
    }

    public function test_guest_tidak_bisa_memulai_pembayaran(): void
    {
        $booking = $this->booking();
        $this->post(route('booking.payment.start', ['booking' => $booking->id]))->assertRedirect();
        $this->assertDatabaseMissing('payment_transactions', ['booking_id' => $booking->id]);
    }
}
