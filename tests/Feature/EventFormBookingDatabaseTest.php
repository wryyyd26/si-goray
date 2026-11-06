<?php

namespace Tests\Feature;

use App\Models\Identity;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class EventFormBookingDatabaseTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private string $eventId;
    private string $scheduleId;
    private string $nik = '3576010101900002';

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('Test hanya boleh memakai gor_reservation_testing.');
        }
        config(['identity.hash_key' => 'test-form-booking-secret']);
        $this->user = User::create(['no_hp_email' => 'form-' . Str::uuid() . '@example.com', 'status_otp' => true]);
        $this->eventId = (string) Str::uuid();
        $this->scheduleId = (string) Str::uuid();
        DB::table('events')->insert([
            'id' => $this->eventId,
            'nama_event' => 'Event Form Test',
            'deskripsi' => 'Test',
            'status' => 'AKTIF',
            'created_at' => now(),
        ]);
        DB::table('event_schedules')->insert([
            'id' => $this->scheduleId,
            'event_id' => $this->eventId,
            'tanggal' => now()->addDays(7)->toDateString(),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kapasitas' => 3,
            'kuota_terpakai' => 0,
        ]);
        DB::table('tariff_reference')->insert([
            'id' => (string) Str::uuid(),
            'event_id' => null,
            'jenis_pengunjung' => 'UMUM',
            'nominal' => 10000,
            'berlaku_mulai' => now()->subDay()->toDateString(),
            'aktif' => true,
        ]);
        $this->makeIdentity($this->user);
    }

    private function makeIdentity(User $user): Identity
    {
        return Identity::create([
            'user_id' => $user->id,
            'nik_enc' => $this->nik,
            'nik_hash' => hash_hmac('sha256', $this->nik, config('identity.hash_key')),
            'tanggal_lahir_enc' => '1990-01-01',
            'status_warga_kota' => true,
            'sumber_validasi' => 'local',
            'divalidasi_at' => now(),
        ]);
    }

    private function payload(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_schedule_id' => $this->scheduleId,
            'nama_lengkap' => 'Pengunjung Test',
            'nik' => $this->nik,
            'tanggal_lahir' => '1990-01-01',
            'email' => $this->user->no_hp_email,
            'nomor_hp' => '081234567890',
        ];
    }

    public function test_tidak_login_diarahkan_ke_otp(): void
    {
        $this->post(route('events.booking.submit.warga'), $this->payload())
            ->assertRedirect();
        $this->assertSame(0, DB::table('bookings')->where('event_schedule_id', $this->scheduleId)->count());
    }

    public function test_form_menciptakan_draft_dan_menambah_kuota(): void
    {
        $this->actingAs($this->user, 'web')
            ->post(route('events.booking.submit.warga'), $this->payload())
            ->assertRedirect();
        $booking = DB::table('bookings')->where('user_id', $this->user->id)->first();
        $this->assertNotNull($booking);
        $this->assertSame('DRAFT', $booking->status_booking);
        $this->assertSame($this->scheduleId, $booking->event_schedule_id);
        $this->assertSame(1, (int) DB::table('event_schedules')->where('id', $this->scheduleId)->value('kuota_terpakai'));
        $this->assertDatabaseMissing('qr_tickets', ['booking_id' => $booking->id]);
    }

    public function test_jadwal_event_lain_ditolak(): void
    {
        $other = (string) Str::uuid();
        DB::table('events')->insert(['id' => $other, 'nama_event' => 'Other', 'deskripsi' => 'Other', 'status' => 'AKTIF', 'created_at' => now()]);
        $this->actingAs($this->user, 'web')
            ->post(route('events.booking.submit.warga'), array_merge($this->payload(), ['event_id' => $other]))
            ->assertSessionHasErrors('event_schedule_id');
        $this->assertSame(0, DB::table('bookings')->where('event_schedule_id', $this->scheduleId)->count());
    }

    public function test_tanpa_pilihan_jadwal_ditolak(): void
    {
        $data = $this->payload();
        unset($data['event_schedule_id']);
        $this->actingAs($this->user, 'web')
            ->post(route('events.booking.submit.warga'), $data)
            ->assertSessionHasErrors('event_schedule_id');
        $this->assertSame(0, DB::table('bookings')->where('event_schedule_id', $this->scheduleId)->count());
    }

    public function test_email_berbeda_dengan_akun_ditolak(): void
    {
        $this->actingAs($this->user, 'web')
            ->post(route('events.booking.submit.warga'), array_merge($this->payload(), ['email' => 'oranglain@example.com']))
            ->assertSessionHasErrors('email');
        $this->assertSame(0, DB::table('bookings')->where('event_schedule_id', $this->scheduleId)->count());
    }

    public function test_kategori_luar_kota_tidak_boleh_memakai_identitas_warga(): void
    {
        $this->actingAs($this->user, 'web')
            ->post(route('events.booking.submit.luar'), $this->payload())
            ->assertSessionHas('error');
        $this->assertSame(0, DB::table('bookings')->where('event_schedule_id', $this->scheduleId)->count());
    }
}
