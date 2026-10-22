<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Identity;
use App\Models\TariffReference;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class RegularBookingIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private Identity $identity;

    protected function setUp(): void
    {
        parent::setUp();

        // Jangan pernah menjalankan test ini pada database utama.
        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException(
                'Pengujian hanya boleh memakai gor_reservation_testing.'
            );
        }

        $nik = '3576010101900002';

        $this->user = User::create([
            'no_hp_email' => 'booking-' . Str::uuid() . '@example.com',
            'status_otp' => true,
        ]);

        $this->identity = Identity::create([
            'user_id' => $this->user->id,
            'nik_enc' => $nik,
            'nik_hash' => hash('sha256', $nik . Str::uuid()),
            'tanggal_lahir_enc' => '1990-01-01',
            'status_warga_kota' => true,
            'sumber_validasi' => 'local',
            'divalidasi_at' => now(),
        ]);

        // BookingService mencari tarif UMUM, bukan LUAR_KOTA.
        TariffReference::create([
            'event_id' => null,
            'jenis_pengunjung' => 'UMUM',
            'nominal' => 10000,
            'berlaku_mulai' => now()->subDay()->toDateString(),
            'aktif' => true,
        ]);
    }

    private function buatSlot(
        int $kapasitas = 100,
        int $terpakai = 0,
        string $status = 'active'
    ): string {
        $id = (string) Str::uuid();

        DB::table('regular_slots')->insert([
            'id' => $id,
            'tanggal' => now()->addDays(7)->toDateString(),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kapasitas' => $kapasitas,
            'kuota_terpakai' => $terpakai,
            'status' => $status,
        ]);

        return $id;
    }

    private function pesanSlot(
        string $slotId,
        ?string $identityId = null
    ): Booking {
        return app(BookingService::class)->createBooking([
            'user_id' => $this->user->id,
            'identity_id' => $identityId ?? $this->identity->id,
            'jenis_booking' => 'REGULER',
            'regular_slot_id' => $slotId,
            'event_schedule_id' => null,
        ]);
    }

    public function test_slot_aktif_dapat_dibooking(): void
    {
        $slotId = $this->buatSlot();

        $booking = $this->pesanSlot($slotId);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertSame('REGULER', $booking->jenis_booking);
        $this->assertSame($slotId, $booking->regular_slot_id);
        $this->assertSame('DRAFT', $booking->status_booking);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'regular_slot_id' => $slotId,
        ]);

        $this->assertDatabaseHas('regular_slots', [
            'id' => $slotId,
            'kuota_terpakai' => 1,
        ]);
    }

    public function test_slot_penuh_ditolak(): void
    {
        $slotId = $this->buatSlot(
            kapasitas: 100,
            terpakai: 100
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Kuota booking REGULER sudah penuh.');

        $this->pesanSlot($slotId);
    }

    public function test_slot_nonaktif_ditolak(): void
    {
        $slotId = $this->buatSlot(
            kapasitas: 100,
            terpakai: 0,
            status: 'inactive'
        );

        // Test ini kemungkinan GAGAL pada BookingService saat ini.
        // Kegagalan merupakan temuan yang harus diperbaiki.
        try {
            $this->pesanSlot($slotId);

            $this->fail(
                'BUG: BookingService menerima booking slot inactive.'
            );
        } catch (InvalidArgumentException $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function test_uuid_slot_tidak_ditemukan_ditolak(): void
    {
        $slotId = (string) Str::uuid();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Regular slot tidak ditemukan.');

        $this->pesanSlot($slotId);
    }

    public function test_identitas_bukan_milik_user_ditolak(): void
    {
        $slotId = $this->buatSlot();

        $identityLain = (string) Str::uuid();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Identity tidak ditemukan atau bukan milik user.'
        );

        $this->pesanSlot($slotId, $identityLain);
    }
}
