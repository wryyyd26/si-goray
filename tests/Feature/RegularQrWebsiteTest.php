<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Identity;
use App\Models\QrTicket;
use App\Models\User;
use App\Services\QrTicketService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class RegularQrWebsiteTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('Tes QR hanya boleh dijalankan di gor_reservation_testing.');
        }
    }

    private function user(): User
    {
        return User::create([
            'no_hp_email' => 'qr-web-' . Str::uuid() . '@example.com',
            'status_otp' => true,
        ]);
    }

    private function booking(string $status = 'CONFIRMED'): Booking
    {
        $user = $this->user();
        $identity = Identity::create([
            'user_id' => $user->id,
            'nik_enc' => '3576010101900002',
            'nik_hash' => hash('sha256', (string) Str::uuid()),
            'tanggal_lahir_enc' => '1990-01-01',
            'status_warga_kota' => true,
            'sumber_validasi' => 'local',
            'divalidasi_at' => now(),
        ]);
        $slotId = (string) Str::uuid();
        DB::table('regular_slots')->insert([
            'id' => $slotId,
            'tanggal' => now()->addDays(7)->toDateString(),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kapasitas' => 10,
            'kuota_terpakai' => 1,
            'status' => 'active',
        ]);
        // BookingService pada aplikasi selalu mengisi tariff_id; fixture tes harus sama.
        // Buat tarif UMUM lokal di DB testing, tanpa bergantung pada data seed lain.
        $tariffId = (string) Str::uuid();
        DB::table('tariff_reference')->insert([
            'id' => $tariffId,
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
            'regular_slot_id' => $slotId,
            'event_schedule_id' => null,
            'tariff_id' => $tariffId,
            'retribusi_diperlukan' => false,
            'status_booking' => $status,
        ]);
        if ($status === 'CONFIRMED') {
            app(QrTicketService::class)->generate($booking);
        }
        return $booking;
    }

    public function test_guest_tidak_dapat_melihat_gambar_qr(): void
    {
        $booking = $this->booking();
        $this->get(route('reguler.booking.qr-image', $booking))
            ->assertRedirect();
    }

    public function test_pemilik_dapat_melihat_svg_dan_tidak_menerbitkan_qr_baru(): void
    {
        $booking = $this->booking();
        $this->actingAs($booking->user, 'web');
        $before = QrTicket::where('booking_id', $booking->id)->count();

        $response = $this->get(route('reguler.booking.qr-image', $booking));
        $response->assertOk();
        $this->assertStringContainsString('image/svg+xml', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('<svg', $response->getContent());
        $this->assertSame($before, QrTicket::where('booking_id', $booking->id)->count());
    }

    public function test_orang_lain_tidak_dapat_melihat_qr(): void
    {
        $booking = $this->booking();
        $this->actingAs($this->user(), 'web');
        $this->get(route('reguler.booking.qr-image', $booking))->assertForbidden();
    }

    public function test_booking_draft_tidak_memiliki_gambar_qr(): void
    {
        $booking = $this->booking('DRAFT');
        $this->actingAs($booking->user, 'web');
        $this->get(route('reguler.booking.qr-image', $booking))->assertNotFound();
    }

    public function test_tiket_kedaluwarsa_tidak_ditampilkan(): void
    {
        $booking = $this->booking();
        $booking->activeQrTicket()->firstOrFail()->update(['waktu_expired' => now()->subMinute()]);
        $this->actingAs($booking->user, 'web');
        $this->get(route('reguler.booking.qr-image', $booking))->assertStatus(410);
    }

    public function test_tiket_sudah_dipakai_tidak_ditampilkan(): void
    {
        $booking = $this->booking();
        $booking->activeQrTicket()->firstOrFail()->update(['status_qr' => 'SUDAH_DIPAKAI']);
        $this->actingAs($booking->user, 'web');
        $this->get(route('reguler.booking.qr-image', $booking))->assertStatus(410);
    }

    public function test_payload_signature_diubah_tidak_ditampilkan(): void
    {
        $booking = $this->booking();
        $ticket = $booking->activeQrTicket()->firstOrFail();
        $ticket->update(['payload_signed' => (string) $ticket->payload_signed . 'palsu']);
        $this->actingAs($booking->user, 'web');
        $this->get(route('reguler.booking.qr-image', $booking))->assertStatus(410);
    }
}
