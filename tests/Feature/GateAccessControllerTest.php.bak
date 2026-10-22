<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Identity;
use App\Models\PetugasGerbang;
use App\Models\QrTicket;
use App\Models\TariffReference;
use App\Models\User;
use App\Services\QrTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class GateAccessControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Pengamanan utama tetap melalui phpunit.xml / kredensial database khusus testing.
        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('Database testing tidak sesuai.');
        }
    }

    private function buatUser(): User
    {
        return User::create([
            'no_hp_email' => 'gate-api-' . Str::uuid() . '@example.com',
            'status_otp' => true,
        ]);
    }

    private function buatPetugas(User $user, bool $aktif = true): PetugasGerbang
    {
        $petugas = new PetugasGerbang();
        $petugas->user_id = $user->id;
        $petugas->nama = 'Petugas Pengujian';
        $petugas->username = 'gate_' . Str::lower(Str::random(12));
        $petugas->no_hp = '081234567890';
        $petugas->aktif = $aktif;
        $petugas->save();

        // Pastikan relasi benar-benar tersimpan, bukan hanya ada di array input.
        $this->assertDatabaseHas('petugas_gerbang', [
            'id' => $petugas->id,
            'user_id' => $user->id,
        ]);

        return $petugas;
    }

    private function buatQrValid(): QrTicket
    {
        $user = $this->buatUser();
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

        $booking = Booking::create([
            'user_id' => $user->id,
            'identity_id' => $identity->id,
            'jenis_booking' => 'REGULER',
            'regular_slot_id' => $slotId,
            'event_schedule_id' => null,
            'tariff_id' => $tariff->id,
            'retribusi_diperlukan' => true,
            'status_booking' => 'CONFIRMED',
        ]);

        return app(QrTicketService::class)->generate($booking);
    }

    public function test_tanpa_login_ditolak(): void
    {
        $this->postJson('/api/gate/scan', ['qr_payload' => 'tidak-valid'])
            ->assertUnauthorized();

        $this->assertDatabaseCount('access_logs', 0);
    }

    public function test_user_biasa_ditolak(): void
    {
        Sanctum::actingAs($this->buatUser(), ['gate:scan']);

        $this->postJson('/api/gate/scan', ['qr_payload' => 'tidak-valid'])
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_petugas_tidak_aktif_ditolak(): void
    {
        $user = $this->buatUser();
        $this->buatPetugas($user, false);
        Sanctum::actingAs($user, ['gate:scan']);

        $this->postJson('/api/gate/scan', ['qr_payload' => 'tidak-valid'])
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_token_tanpa_izin_scan_ditolak(): void
    {
        $user = $this->buatUser();
        $this->buatPetugas($user);
        Sanctum::actingAs($user, ['booking:read']);

        $this->postJson('/api/gate/scan', ['qr_payload' => 'tidak-valid'])
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_petugas_aktif_dengan_izin_bisa_scan(): void
    {
        $qr = $this->buatQrValid();
        $user = $this->buatUser();
        $petugas = $this->buatPetugas($user);
        Sanctum::actingAs($user, ['gate:scan']);

        $response = $this->postJson('/api/gate/scan', [
    'qr_payload' => $qr->payload_signed,
]);


$response
    ->assertOk()
    ->assertJsonPath('success', true)
    ->assertJsonPath('hasil', 'BERHASIL');

        $this->assertSame('SUDAH_DIPAKAI', $qr->fresh()->status_qr);
        $this->assertDatabaseHas('access_logs', [
            'qr_ticket_id' => $qr->id,
            'petugas_id' => $petugas->id,
            'hasil_validasi' => 'BERHASIL',
        ]);
    }

    public function test_petugas_id_palsu_di_request_diabaikan(): void
    {
        $qr = $this->buatQrValid();
        $user = $this->buatUser();
        $petugasAsli = $this->buatPetugas($user);
        $petugasLain = $this->buatPetugas($this->buatUser());
        Sanctum::actingAs($user, ['gate:scan']);

        $this->postJson('/api/gate/scan', [
            'qr_payload' => $qr->payload_signed,
            'petugas_id' => $petugasLain->id,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('access_logs', [
            'qr_ticket_id' => $qr->id,
            'petugas_id' => $petugasAsli->id,
            'hasil_validasi' => 'BERHASIL',
        ]);
        $this->assertDatabaseMissing('access_logs', [
            'qr_ticket_id' => $qr->id,
            'petugas_id' => $petugasLain->id,
        ]);
    }

    public function test_payload_kosong_ditolak_validasi(): void
    {
        $user = $this->buatUser();
        $this->buatPetugas($user);
        Sanctum::actingAs($user, ['gate:scan']);

        $this->postJson('/api/gate/scan', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qr_payload');
    }
}
