<?php

namespace Tests\Feature;

use App\Models\Identity;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class BookingJourneyHttpTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;
    private User $other;
    private Identity $identity;
    private string $slotId;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('Test hanya boleh memakai gor_reservation_testing.');
        }

        $this->owner = $this->makeUser();
        $this->other = $this->makeUser();
        $this->identity = Identity::create([
            'user_id' => $this->owner->id,
            'nik_enc' => '3576010101900002',
            'nik_hash' => hash('sha256', (string) Str::uuid()),
            'tanggal_lahir_enc' => '1990-01-01',
            'status_warga_kota' => true,
            'sumber_validasi' => 'local',
            'divalidasi_at' => now(),
        ]);

        DB::table('tariff_reference')->insert([
            'id' => (string) Str::uuid(),
            'event_id' => null,
            'jenis_pengunjung' => 'UMUM',
            'nominal' => 10000,
            'berlaku_mulai' => now()->subDay()->toDateString(),
            'aktif' => true,
        ]);

        $this->slotId = (string) Str::uuid();
        DB::table('regular_slots')->insert([
            'id' => $this->slotId,
            'tanggal' => now()->addDays(7)->toDateString(),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kapasitas' => 5,
            'kuota_terpakai' => 0,
            'status' => 'active',
        ]);
    }

    private function makeUser(): User
    {
        return User::create([
            'no_hp_email' => 'journey-' . Str::uuid() . '@example.com',
            'status_otp' => true,
        ]);
    }

    private function makeBooking(): string
    {
        Sanctum::actingAs($this->owner, ['booking:create', 'booking:read']);
        $response = $this->postJson('/api/bookings', [
            'identity_id' => $this->identity->id,
            'jenis_booking' => 'REGULER',
            'regular_slot_id' => $this->slotId,
        ]);
        $response->assertCreated();
        return (string) $response->json('data.id');
    }

    public function test_riwayat_memerlukan_token_dengan_izin_baca(): void
    {
        $this->getJson('/api/bookings/history')->assertUnauthorized();

        Sanctum::actingAs($this->owner, ['booking:create']);
        $this->getJson('/api/bookings/history')->assertForbidden();
    }

    public function test_booking_baru_muncul_di_riwayat_tanpa_qr(): void
    {
        $id = $this->makeBooking();

        $response = $this->getJson('/api/bookings/history')
            ->assertOk()
            ->assertJsonPath('success', true);

        $item = collect($response->json('data'))->firstWhere('id', $id);
        $this->assertNotNull($item);
        $this->assertSame('DRAFT', $item['status_booking']);
        $this->assertNull($item['active_qr_ticket']);
    }

    public function test_riwayat_pengguna_tidak_membocorkan_booking_pengguna_lain(): void
    {
        $id = $this->makeBooking();
        Sanctum::actingAs($this->other, ['booking:read']);

        $response = $this->getJson('/api/bookings/history')->assertOk();
        $this->assertNotContains($id, collect($response->json('data'))->pluck('id')->all());
    }

    public function test_qr_booking_draft_ditolak(): void
    {
        $id = $this->makeBooking();
        $this->postJson("/api/bookings/{$id}/qr-ticket")
            ->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertDatabaseMissing('qr_tickets', ['booking_id' => $id]);
    }

    public function test_pembayaran_dan_qr_booking_orang_lain_ditolak(): void
    {
        $id = $this->makeBooking();
        Sanctum::actingAs($this->other, ['booking:read']);

        $this->postJson("/api/bookings/{$id}/payment")->assertForbidden();
        $this->postJson("/api/bookings/{$id}/qr-ticket")->assertForbidden();
    }

    public function test_qr_confirmed_muncul_di_riwayat_dan_tidak_digandakan(): void
    {
        $id = $this->makeBooking();

        // Simulasi KONFIRMASI hanya pada database testing,
        // bukan simulasi sukses callback gateway pembayaran.
        DB::table('bookings')->where('id', $id)->update(['status_booking' => 'CONFIRMED']);

        $first = $this->postJson("/api/bookings/{$id}/qr-ticket")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.booking_id', $id)
            ->assertJsonPath('data.status_qr', 'AKTIF');

        $qrId = $first->json('data.id');
        $this->assertNotEmpty($first->json('data.qr_payload'));

        $this->postJson("/api/bookings/{$id}/qr-ticket")
            ->assertOk()
            ->assertJsonPath('data.id', $qrId);

        $history = $this->getJson('/api/bookings/history')->assertOk();
        $item = collect($history->json('data'))->firstWhere('id', $id);
        $this->assertNotNull($item);
        $this->assertSame($qrId, $item['active_qr_ticket']['id']);
        $this->assertSame(1, DB::table('qr_tickets')->where('booking_id', $id)->count());
    }
}
