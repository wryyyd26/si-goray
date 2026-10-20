<?php

namespace Tests\Feature;

use App\Models\AccessLog;
use App\Models\Booking;
use App\Models\Identity;
use App\Models\PetugasGerbang;
use App\Models\TariffReference;
use App\Models\User;
use App\Services\GateAccessService;
use App\Services\QrTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class GateAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // PASTIKAN phpunit.xml sudah mengarahkan database ke gor_reservation_testing.
        // Pemeriksaan ini dilakukan setelah parent::setUp(), jadi bukan pengganti
        // isolasi database melalui konfigurasi/izin pengguna MySQL.
        parent::setUp();

        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('Database testing tidak sesuai.');
        }
    }

    private function buatBooking(string $status = 'CONFIRMED'): Booking
    {
        $user = User::create([
            'no_hp_email' => 'gate-' . Str::uuid() . '@example.com',
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

    private function buatPetugas(bool $aktif = true): PetugasGerbang
    {
        return PetugasGerbang::create([
            'nama' => 'Petugas Pengujian',
            'username' => 'gate_' . Str::lower(Str::random(12)),
            'no_hp' => '081234567890',
            'aktif' => $aktif,
        ]);
    }

    private function buatQrValid(): \App\Models\QrTicket
    {
        return app(QrTicketService::class)->generate($this->buatBooking());
    }

    public function test_scan_valid_memberikan_akses_dan_menghabiskan_qr(): void
    {
        $qr = $this->buatQrValid();
        $petugas = $this->buatPetugas();

        $hasil = app(GateAccessService::class)->scan($qr->payload_signed, $petugas);

        $this->assertTrue($hasil['success']);
        $this->assertSame('BERHASIL', $hasil['hasil']);
        $this->assertSame('SUDAH_DIPAKAI', $qr->fresh()->status_qr);
        $this->assertDatabaseHas('access_logs', [
            'qr_ticket_id' => $qr->id,
            'petugas_id' => $petugas->id,
            'hasil_validasi' => 'BERHASIL',
        ]);
    }

    public function test_scan_kedua_ditolak_dan_tidak_membuat_dua_log_berhasil(): void
    {
        $qr = $this->buatQrValid();
        $petugas = $this->buatPetugas();
        $service = app(GateAccessService::class);

        $pertama = $service->scan($qr->payload_signed, $petugas);
        $kedua = $service->scan($qr->payload_signed, $petugas);

        $this->assertTrue($pertama['success']);
        $this->assertFalse($kedua['success']);
        $this->assertSame('QR_TIDAK_AKTIF', $kedua['reason']);
        $this->assertSame(1, AccessLog::where('qr_ticket_id', $qr->id)
            ->where('hasil_validasi', 'BERHASIL')->count());
        $this->assertSame(1, AccessLog::where('qr_ticket_id', $qr->id)
            ->where('hasil_validasi', 'GAGAL')->count());
    }

    public function test_qr_expired_ditolak_tanpa_akses_berhasil(): void
    {
        $qr = $this->buatQrValid();
        $petugas = $this->buatPetugas();
        $qr->update(['waktu_expired' => now()->subMinute()]);

        $hasil = app(GateAccessService::class)->scan($qr->payload_signed, $petugas);

        $this->assertFalse($hasil['success']);
        $this->assertSame('QR_EXPIRED', $hasil['reason']);
        $this->assertSame(0, AccessLog::where('hasil_validasi', 'BERHASIL')->count());
    }

    public function test_signature_palsu_ditolak(): void
    {
        $qr = $this->buatQrValid();
        $petugas = $this->buatPetugas();
        [$encoded, $signature] = explode('.', $qr->payload_signed, 2);
        $signaturePalsu = ($signature[0] === 'a' ? 'b' : 'a') . substr($signature, 1);

        $hasil = app(GateAccessService::class)->scan(
            $encoded . '.' . $signaturePalsu,
            $petugas
        );

        $this->assertFalse($hasil['success']);
        $this->assertSame('SIGNATURE_TIDAK_VALID', $hasil['reason']);
        $this->assertDatabaseCount('access_logs', 0);
    }

    public function test_petugas_tidak_aktif_ditolak(): void
    {
        $qr = $this->buatQrValid();
        $petugas = $this->buatPetugas(false);

        $hasil = app(GateAccessService::class)->scan($qr->payload_signed, $petugas);

        $this->assertFalse($hasil['success']);
        $this->assertSame('PETUGAS_TIDAK_AKTIF', $hasil['reason']);
        $this->assertSame('AKTIF', $qr->fresh()->status_qr);
        $this->assertDatabaseCount('access_logs', 0);
    }

    public function test_booking_cancelled_ditolak(): void
    {
        $qr = $this->buatQrValid();
        $petugas = $this->buatPetugas();
        $qr->booking->update(['status_booking' => 'CANCELLED']);

        $hasil = app(GateAccessService::class)->scan($qr->payload_signed, $petugas);

        $this->assertFalse($hasil['success']);
        $this->assertSame('BOOKING_BELUM_CONFIRMED', $hasil['reason']);
        $this->assertSame('AKTIF', $qr->fresh()->status_qr);
        $this->assertDatabaseMissing('access_logs', [
            'qr_ticket_id' => $qr->id,
            'hasil_validasi' => 'BERHASIL',
        ]);
    }

    public function test_qr_lama_setelah_regenerasi_ditolak(): void
    {
        $qrLama = $this->buatQrValid();
        $qrBaru = app(QrTicketService::class)->regenerate($qrLama->booking);
        $petugas = $this->buatPetugas();

        $hasilLama = app(GateAccessService::class)->scan($qrLama->payload_signed, $petugas);
        $hasilBaru = app(GateAccessService::class)->scan($qrBaru->payload_signed, $petugas);

        $this->assertFalse($hasilLama['success']);
        $this->assertSame('QR_TIDAK_AKTIF', $hasilLama['reason']);
        $this->assertTrue($hasilBaru['success']);
        $this->assertSame('SUDAH_DIPAKAI', $qrBaru->fresh()->status_qr);
    }

    public function test_format_qr_tidak_valid_ditolak(): void
    {
        $petugas = $this->buatPetugas();

        $hasil = app(GateAccessService::class)->scan('bukan-qr-yang-valid', $petugas);

        $this->assertFalse($hasil['success']);
        $this->assertSame('FORMAT_QR_TIDAK_VALID', $hasil['reason']);
        $this->assertDatabaseCount('access_logs', 0);
    }
}
