<?php

namespace Tests\Feature;

use App\Models\Identity;
use App\Models\TariffReference;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class EventBookingGuardTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private Identity $identity;

    protected function setUp(): void
    {
        parent::setUp();

        if (app()->environment() !== 'testing' ||
            DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('Test hanya boleh menggunakan gor_reservation_testing.');
        }

        $this->user = User::create([
            'no_hp_email' => 'event-guard-' . Str::uuid() . '@example.com',
            'status_otp' => true,
        ]);

        $this->identity = Identity::create([
            'user_id' => $this->user->id,
            'nik_enc' => '3576010101900002',
            'nik_hash' => hash('sha256', (string) Str::uuid()),
            'tanggal_lahir_enc' => '1990-01-01',
            'status_warga_kota' => true,
            'sumber_validasi' => 'local',
            'divalidasi_at' => now(),
        ]);

        TariffReference::create([
            'event_id' => null,
            'jenis_pengunjung' => 'UMUM',
            'nominal' => 10000,
            'berlaku_mulai' => now()->subDay()->toDateString(),
            'aktif' => true,
        ]);

        Sanctum::actingAs($this->user, ['booking:create']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createEventSchedule(
        string $status = 'AKTIF',
        ?string $date = null,
        string $endTime = '10:00:00'
    ): string {
        $eventId = (string) Str::uuid();
        $scheduleId = (string) Str::uuid();

        DB::table('events')->insert([
            'id' => $eventId,
            'nama_event' => 'Event Guard Test',
            'deskripsi' => 'Pengujian pengamanan booking Event',
            'status' => $status,
            'created_at' => now(),
        ]);

        DB::table('event_schedules')->insert([
            'id' => $scheduleId,
            'event_id' => $eventId,
            'tanggal' => $date ?? now()->addDays(7)->toDateString(),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => $endTime,
            'kapasitas' => 3,
            'kuota_terpakai' => 0,
        ]);

        return $scheduleId;
    }

    private function submitBooking(string $scheduleId)
    {
        return $this->postJson('/api/bookings', [
            'identity_id' => $this->identity->id,
            'jenis_booking' => 'EVENT',
            'event_schedule_id' => $scheduleId,
        ]);
    }

    private function assertNotBooked(string $scheduleId): void
    {
        $this->assertDatabaseHas('event_schedules', [
            'id' => $scheduleId,
            'kuota_terpakai' => 0,
        ]);

        $this->assertDatabaseMissing('bookings', [
            'identity_id' => $this->identity->id,
            'event_schedule_id' => $scheduleId,
        ]);
    }

    public function test_event_nonaktif_ditolak_tanpa_mengurangi_kuota(): void
    {
        $scheduleId = $this->createEventSchedule('NONAKTIF');

        $this->submitBooking($scheduleId)
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Event tidak aktif atau tidak tersedia.');

        $this->assertNotBooked($scheduleId);
    }

    public function test_event_tanggal_lampau_ditolak_tanpa_mengurangi_kuota(): void
    {
        $scheduleId = $this->createEventSchedule(
            'AKTIF',
            now()->subDays(2)->toDateString()
        );

        $this->submitBooking($scheduleId)
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Jadwal event sudah berakhir dan tidak dapat dipesan.');

        $this->assertNotBooked($scheduleId);
    }

    public function test_event_hari_ini_yang_sudah_berakhir_ditolak(): void
    {
        Carbon::setTestNow(now()->setTime(12, 0, 0));

        $scheduleId = $this->createEventSchedule(
            'AKTIF',
            now()->toDateString(),
            '10:00:00'
        );

        $this->submitBooking($scheduleId)
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Jadwal event sudah berakhir dan tidak dapat dipesan.');

        $this->assertNotBooked($scheduleId);
    }

    public function test_event_aktif_mendatang_tetap_dapat_dibooking(): void
    {
        $scheduleId = $this->createEventSchedule();

        $this->submitBooking($scheduleId)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.event_schedule_id', $scheduleId)
            ->assertJsonPath('data.status_booking', 'DRAFT');

        $this->assertDatabaseHas('event_schedules', [
            'id' => $scheduleId,
            'kuota_terpakai' => 1,
        ]);
    }
}
