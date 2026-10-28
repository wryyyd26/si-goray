<?php

namespace Tests\Feature;

use App\Models\Identity;
use App\Models\TariffReference;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class BookingApiIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private Identity $identity;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('Test hanya boleh memakai gor_reservation_testing.');
        }

        $this->user = User::create([
            'no_hp_email' => 'booking-http-' . Str::uuid() . '@example.com',
            'status_otp' => true,
        ]);

        $this->identity = $this->makeIdentity($this->user);

        TariffReference::create([
            'event_id' => null,
            'jenis_pengunjung' => 'UMUM',
            'nominal' => 10000,
            'berlaku_mulai' => now()->subDay()->toDateString(),
            'aktif' => true,
        ]);
    }

    private function makeIdentity(User $user): Identity
    {
        return Identity::create([
            'user_id' => $user->id,
            'nik_enc' => '3576010101900002',
            'nik_hash' => hash('sha256', (string) Str::uuid()),
            'tanggal_lahir_enc' => '1990-01-01',
            'status_warga_kota' => true,
            'sumber_validasi' => 'local',
            'divalidasi_at' => now(),
        ]);
    }

    private function makeSlot(int $capacity = 3, int $used = 0, string $status = 'active'): string
    {
        $id = (string) Str::uuid();
        DB::table('regular_slots')->insert([
            'id' => $id,
            'tanggal' => now()->addDays(7)->toDateString(),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kapasitas' => $capacity,
            'kuota_terpakai' => $used,
            'status' => $status,
        ]);
        return $id;
    }

    private function makeEventSchedule(): string
    {
        $eventId = (string) Str::uuid();
        $scheduleId = (string) Str::uuid();
        DB::table('events')->insert([
            'id' => $eventId,
            'nama_event' => 'Event HTTP Test',
            'deskripsi' => 'Pengujian booking',
            'status' => 'AKTIF',
            'created_at' => now(),
        ]);
        DB::table('event_schedules')->insert([
            'id' => $scheduleId,
            'event_id' => $eventId,
            'tanggal' => now()->addDays(7)->toDateString(),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kapasitas' => 3,
            'kuota_terpakai' => 0,
        ]);
        return $scheduleId;
    }

    private function regularPayload(string $slotId): array
    {
        return [
            'identity_id' => $this->identity->id,
            'jenis_booking' => 'REGULER',
            'regular_slot_id' => $slotId,
        ];
    }

    public function test_pengunjung_tanpa_token_tidak_bisa_booking(): void
    {
        $slotId = $this->makeSlot();
        $this->postJson('/api/bookings', $this->regularPayload($slotId))
            ->assertUnauthorized();
        $this->assertDatabaseHas('regular_slots', ['id' => $slotId, 'kuota_terpakai' => 0]);
    }

    public function test_token_tanpa_izin_booking_create_ditolak(): void
    {
        $slotId = $this->makeSlot();
        Sanctum::actingAs($this->user, ['booking:read']);
        $this->postJson('/api/bookings', $this->regularPayload($slotId))
            ->assertForbidden();
        $this->assertDatabaseHas('regular_slots', ['id' => $slotId, 'kuota_terpakai' => 0]);
    }

    public function test_booking_reguler_http_berhasil_dan_kuota_naik(): void
    {
        $slotId = $this->makeSlot();
        Sanctum::actingAs($this->user, ['booking:create']);

        $this->postJson('/api/bookings', $this->regularPayload($slotId))
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', $this->user->id)
            ->assertJsonPath('data.regular_slot_id', $slotId)
            ->assertJsonPath('data.status_booking', 'DRAFT');

        $this->assertDatabaseHas('regular_slots', ['id' => $slotId, 'kuota_terpakai' => 1]);
        $this->assertDatabaseHas('bookings', [
            'user_id' => $this->user->id,
            'identity_id' => $this->identity->id,
            'regular_slot_id' => $slotId,
            'jenis_booking' => 'REGULER',
        ]);
    }

    public function test_user_id_palsu_dari_frontend_tidak_dipercaya(): void
    {
        $slotId = $this->makeSlot();
        $otherUser = User::create([
            'no_hp_email' => 'other-http-' . Str::uuid() . '@example.com',
            'status_otp' => true,
        ]);
        Sanctum::actingAs($this->user, ['booking:create']);

        $this->postJson('/api/bookings', $this->regularPayload($slotId) + [
            'user_id' => $otherUser->id,
            'retribusi_diperlukan' => true,
            'status_booking' => 'CONFIRMED',
        ])->assertCreated()
            ->assertJsonPath('data.user_id', $this->user->id)
            ->assertJsonPath('data.status_booking', 'DRAFT')
            ->assertJsonPath('data.retribusi_diperlukan', false);
    }

    public function test_slot_penuh_ditolak_dan_kuota_tidak_berubah(): void
    {
        $slotId = $this->makeSlot(1, 1);
        Sanctum::actingAs($this->user, ['booking:create']);
        $this->postJson('/api/bookings', $this->regularPayload($slotId))
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
        $this->assertDatabaseHas('regular_slots', ['id' => $slotId, 'kuota_terpakai' => 1]);
    }

    public function test_identitas_user_lain_ditolak(): void
    {
        $slotId = $this->makeSlot();
        $otherUser = User::create([
            'no_hp_email' => 'foreign-http-' . Str::uuid() . '@example.com',
            'status_otp' => true,
        ]);
        $foreignIdentity = $this->makeIdentity($otherUser);
        Sanctum::actingAs($this->user, ['booking:create']);

        $this->postJson('/api/bookings', [
            'identity_id' => $foreignIdentity->id,
            'jenis_booking' => 'REGULER',
            'regular_slot_id' => $slotId,
        ])->assertUnprocessable()->assertJsonValidationErrors('identity_id');
        $this->assertDatabaseHas('regular_slots', ['id' => $slotId, 'kuota_terpakai' => 0]);
    }

    public function test_validasi_id_slot_dan_jenis_booking(): void
    {
        Sanctum::actingAs($this->user, ['booking:create']);
        $this->postJson('/api/bookings', [
            'identity_id' => $this->identity->id,
            'jenis_booking' => 'REGULER',
        ])->assertUnprocessable()->assertJsonValidationErrors('regular_slot_id');

        $this->postJson('/api/bookings', [
            'identity_id' => $this->identity->id,
            'jenis_booking' => 'TIDAK_VALID',
        ])->assertUnprocessable()->assertJsonValidationErrors('jenis_booking');
    }

    public function test_booking_event_http_berhasil_dan_kuota_event_naik(): void
    {
        $scheduleId = $this->makeEventSchedule();
        Sanctum::actingAs($this->user, ['booking:create']);
        $this->postJson('/api/bookings', [
            'identity_id' => $this->identity->id,
            'jenis_booking' => 'EVENT',
            'event_schedule_id' => $scheduleId,
        ])->assertCreated()
            ->assertJsonPath('data.jenis_booking', 'EVENT')
            ->assertJsonPath('data.event_schedule_id', $scheduleId)
            ->assertJsonPath('data.status_booking', 'DRAFT');
        $this->assertDatabaseHas('event_schedules', [
            'id' => $scheduleId,
            'kuota_terpakai' => 1,
        ]);
    }
}
