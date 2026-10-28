<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Identity;
use App\Models\QrTicket;
use App\Models\TariffReference;
use App\Models\User;
use App\Services\QrTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class QrTicketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // Sebelum memakai RefreshDatabase, pastikan phpunit.xml
        // mengarahkan koneksi testing ke gor_reservation_testing.
        parent::setUp();

        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('Database testing tidak sesuai.');
        }
    }

    private function buatBooking(string $status = 'CONFIRMED'): Booking
    {
        $user = User::create([
            'no_hp_email' => 'qr-' . Str::uuid() . '@example.com',
            'status_otp' => true,
        ]);

        $nik = '3576010101900001';
        $identity = Identity::create([
            'user_id' => $user->id,
            'nik_enc' => $nik,
            'nik_hash' => hash('sha256', $nik),
            'tanggal_lahir_enc' => '1990-01-01',
            'status_warga_kota' => false,
            'sumber_validasi' => 'local',
            'divalidasi_at' => now(),
        ]);

        $slotId = (string) Str::uuid();
        DB::table('regular_slots')->insert([
            'id' => $slotId,
            'tanggal' => now()->addDays(7)->toDateString(),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kapasitas' => 100,
            'kuota_terpakai' => 0,
        ]);

        $tariff = TariffReference::create([
            'jenis_pengunjung' => 'LUAR_KOTA',
            'nominal' => 50000,
            'berlaku_mulai' => now()->subDay()->toDateString(),
            'aktif' => true,
        ]);

        return Booking::create([
            'user_id' => $user->id,
            'identity_id' => $identity->id,
            'jenis_booking' => 'REGULER',
            'regular_slot_id' => $slotId,
            'event_schedule_id' => null,
            'tariff_id' => $tariff->id,
            'retribusi_diperlukan' => true,
            'status_booking' => $status,
        ]);
    }

    public function test_booking_confirmed_berhasil_membuat_qr_valid(): void
    {
        $booking = $this->buatBooking();
        $service = app(QrTicketService::class);

        $qr = $service->generate($booking);
        $hasil = $service->validate($qr->payload_signed);

        $this->assertSame('AKTIF', $qr->status_qr);
        $this->assertSame($booking->id, $qr->booking_id);
        $this->assertTrue($hasil['valid']);
        $this->assertSame('QR_VALID', $hasil['reason']);
        $this->assertSame($qr->id, $hasil['qr_ticket']->id);
    }

    public function test_booking_draft_tidak_boleh_menerbitkan_qr(): void
    {
        $booking = $this->buatBooking('DRAFT');

        try {
            app(QrTicketService::class)->generate($booking);
            $this->fail('Booking DRAFT seharusnya ditolak.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('CONFIRMED', $e->getMessage());
        }

        $this->assertDatabaseMissing('qr_tickets', ['booking_id' => $booking->id]);
    }

    public function test_payload_dengan_signature_diubah_ditolak(): void
    {
        $qr = app(QrTicketService::class)->generate($this->buatBooking());
        [$payload, $signature] = explode('.', $qr->payload_signed, 2);
        $signaturePalsu = ($signature[0] === 'a' ? 'b' : 'a') . substr($signature, 1);

        $hasil = app(QrTicketService::class)->validate($payload . '.' . $signaturePalsu);

        $this->assertFalse($hasil['valid']);
        $this->assertSame('SIGNATURE_TIDAK_VALID', $hasil['reason']);
    }

    public function test_qr_kedaluwarsa_ditolak(): void
    {
        $service = app(QrTicketService::class);
        $qr = $service->generate($this->buatBooking());

        $this->travelTo($qr->waktu_expired->copy()->addSecond());
        try {
            $hasil = $service->validate($qr->payload_signed);
            $this->assertFalse($hasil['valid']);
            $this->assertSame('QR_EXPIRED', $hasil['reason']);
        } finally {
            $this->travelBack();
        }
    }

    public function test_regenerasi_menginvalidkan_qr_lama_dan_membuat_qr_baru(): void
    {
        $booking = $this->buatBooking();
        $service = app(QrTicketService::class);
        $lama = $service->generate($booking);
        $baru = $service->regenerate($booking);

        $this->assertNotSame($lama->id, $baru->id);
        $this->assertSame($lama->id, $baru->regenerated_from_qr_id);
        $this->assertSame('INVALID', $lama->fresh()->status_qr);
        $this->assertSame('AKTIF', $baru->fresh()->status_qr);
        $this->assertFalse($service->validate($lama->payload_signed)['valid']);
        $this->assertTrue($service->validate($baru->payload_signed)['valid']);
    }

    public function test_generate_kedua_ditolak_dan_tetap_satu_qr_aktif(): void
    {
        $booking = $this->buatBooking();
        $service = app(QrTicketService::class);
        $service->generate($booking);

        try {
            $service->generate($booking);
            $this->fail('Penerbitan QR kedua seharusnya ditolak.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('riwayat QR', $e->getMessage());
        }

        $this->assertSame(1, QrTicket::where('booking_id', $booking->id)->count());
        $this->assertSame(1, QrTicket::where('booking_id', $booking->id)
            ->where('status_qr', 'AKTIF')->count());
    }

    public function test_qr_yang_sudah_dipakai_tidak_dapat_diregenerasi(): void
    {
        $booking = $this->buatBooking();
        $service = app(QrTicketService::class);
        $qr = $service->generate($booking);
        $qr->update(['status_qr' => 'SUDAH_DIPAKAI']);

        try {
            $service->regenerate($booking);
            $this->fail('QR yang sudah dipakai seharusnya tidak dapat diregenerasi.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Tidak ada QR AKTIF', $e->getMessage());
        }

        $this->assertSame('SUDAH_DIPAKAI', $qr->fresh()->status_qr);
        $this->assertSame(1, QrTicket::where('booking_id', $booking->id)->count());
    }

    public function test_booking_yang_dibatalkan_tidak_bisa_memakai_qr_lama(): void
    {
        $booking = $this->buatBooking();
        $service = app(QrTicketService::class);
        $qr = $service->generate($booking);
        $booking->update(['status_booking' => 'CANCELLED']);

        $hasil = $service->validate($qr->payload_signed);

        $this->assertFalse($hasil['valid']);
        $this->assertSame('BOOKING_BELUM_CONFIRMED', $hasil['reason']);
    }

    public function test_format_qr_tidak_valid_ditolak(): void
    {
        $hasil = app(QrTicketService::class)->validate('bukan-format-qr');

        $this->assertFalse($hasil['valid']);
        $this->assertSame('FORMAT_QR_TIDAK_VALID', $hasil['reason']);
    }
}
