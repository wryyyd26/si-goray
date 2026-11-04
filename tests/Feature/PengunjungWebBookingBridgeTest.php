<?php

namespace Tests\Feature;

use App\Models\Identity;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class PengunjungWebBookingBridgeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('Test hanya boleh memakai gor_reservation_testing.');
        }
    }

    private function user(): User
    {
        return User::create([
            'no_hp_email' => 'web-bridge-' . Str::uuid() . '@example.com',
            'status_otp' => true,
        ]);
    }

    private function identity(User $owner): Identity
    {
        return Identity::create([
            'user_id' => $owner->id,
            'nik_enc' => '3576010101900002',
            'nik_hash' => hash('sha256', (string) Str::uuid()),
            'tanggal_lahir_enc' => '1990-01-01',
            'status_warga_kota' => true,
            'sumber_validasi' => 'local',
            'divalidasi_at' => now(),
        ]);
    }

    private function slot(): string
    {
        $id = (string) Str::uuid();
        DB::table('regular_slots')->insert([
            'id' => $id,
            'tanggal' => now()->addDays(7)->toDateString(),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kapasitas' => 3,
            'kuota_terpakai' => 0,
            'status' => 'active',
        ]);
        return $id;
    }

    public function test_tanpa_login_web_tidak_bisa_membaca_identitas_atau_booking(): void
    {
        $this->getJson('/pengunjung/web-booking/identities')->assertUnauthorized();
        $this->postJson('/pengunjung/web-booking/identities', [])->assertUnauthorized();
        $this->postJson('/pengunjung/web-booking/bookings', [])->assertUnauthorized();
    }

    public function test_daftar_identitas_hanya_milik_pengguna_yang_login(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $mine = $this->identity($owner);
        $notMine = $this->identity($other);

        $this->actingAs($owner, 'web')
            ->getJson('/pengunjung/web-booking/identities')
            ->assertOk()->assertJsonPath('data.0.id', (string) $mine->id)
            ->assertJsonMissing(['id' => (string) $notMine->id])
            ->assertDontSee('nik_enc')->assertDontSee('nik_hash');
    }

    public function test_booking_web_menolak_identitas_orang_lain(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $identity = $this->identity($other);
        $slot = $this->slot();

        $this->actingAs($owner, 'web')
            ->postJson('/pengunjung/web-booking/bookings', [
                'identity_id' => $identity->id,
                'jenis_booking' => 'REGULER',
                'regular_slot_id' => $slot,
            ])->assertUnprocessable();
        $this->assertDatabaseHas('regular_slots', ['id' => $slot, 'kuota_terpakai' => 0]);
    }

    public function test_booking_web_membuat_draft_dan_mengabaikan_user_id_palsu(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $identity = $this->identity($owner);
        $slot = $this->slot();
        DB::table('tariff_reference')->insert([
            'id' => (string) Str::uuid(),
            'event_id' => null,
            'jenis_pengunjung' => 'UMUM',
            'nominal' => 10000,
            'berlaku_mulai' => now()->subDay()->toDateString(),
            'aktif' => true,
        ]);

        $this->actingAs($owner, 'web')
            ->postJson('/pengunjung/web-booking/bookings', [
                'identity_id' => $identity->id,
                'jenis_booking' => 'REGULER',
                'regular_slot_id' => $slot,
                'user_id' => $other->id,
                'status_booking' => 'CONFIRMED',
                'retribusi_diperlukan' => true,
            ])->assertCreated()
              ->assertJsonPath('data.status_booking', 'DRAFT')
              ->assertJsonPath('data.retribusi_diperlukan', false);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $owner->id,
            'identity_id' => $identity->id,
            'status_booking' => 'DRAFT',
        ]);
        $this->assertDatabaseHas('regular_slots', ['id' => $slot, 'kuota_terpakai' => 1]);
    }

    public function test_pendaftaran_identitas_menolak_input_tidak_valid(): void
    {
        $this->actingAs($this->user(), 'web')
            ->postJson('/pengunjung/web-booking/identities', [
                'nik' => '123',
                'tanggal_lahir' => 'besok',
            ])->assertUnprocessable()->assertJsonValidationErrors(['nik', 'tanggal_lahir']);
    }
}
