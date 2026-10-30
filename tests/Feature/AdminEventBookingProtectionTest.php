<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Identity;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\LogsInSiGorayAdmin;
use Tests\TestCase;

class AdminEventBookingProtectionTest extends TestCase
{
    use DatabaseTransactions, LogsInSiGorayAdmin;

    private const DATE = '2035-09-18';

    protected function setUp(): void
    {
        parent::setUp();

        if (
            DB::connection()->getDatabaseName()
            !== 'gor_reservation_testing'
        ) {
            throw new \RuntimeException(
                'Test hanya boleh menggunakan gor_reservation_testing.'
            );
        }

        $this->loginAsSiGorayAdmin();
    }

    private function payload(array $changes = []): array
    {
        return array_merge([
            'name' => 'Event Proteksi Booking',
            'description' => 'Pengujian perlindungan data',
            'date' => self::DATE,
            'start_time' => '10:00',
            'end_time' => '12:00',
            'quota' => 150,
            'tariff' => 25000,
        ], $changes);
    }

    private function createEvent(): array
    {
        $this->post(
            route('admin.events.store'),
            $this->payload()
        )->assertRedirect(route('admin.events.index'));

        $event = DB::table('events')
            ->where('nama_event', 'Event Proteksi Booking')
            ->first();

        $this->assertNotNull($event);

        $schedule = DB::table('event_schedules')
            ->where('event_id', $event->id)
            ->first();

        $tariff = DB::table('tariff_reference')
            ->where('event_id', $event->id)
            ->where('aktif', true)
            ->first();

        $this->assertNotNull($schedule);
        $this->assertNotNull($tariff);

        return [
            'event_id' => $event->id,
            'schedule_id' => $schedule->id,
            'tariff_id' => $tariff->id,
        ];
    }

    private function createBooking(array $event): string
    {
        $user = User::factory()->create();

        $identity = Identity::create([
            'user_id' => $user->id,
            'nik_enc' => '3576010101900002',
            'nik_hash' => hash(
                'sha256',
                (string) Str::uuid()
            ),
            'tanggal_lahir_enc' => '1990-01-01',
            'status_warga_kota' => false,
            'sumber_validasi' => 'local',
            'divalidasi_at' => now(),
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'identity_id' => $identity->id,
            'jenis_booking' => 'EVENT',
            'regular_slot_id' => null,
            'event_schedule_id' => $event['schedule_id'],
            'tariff_id' => $event['tariff_id'],
            'retribusi_diperlukan' => true,
            'status_booking' => 'DRAFT',
        ]);

        return $booking->id;
    }

    public function test_jadwal_yang_memiliki_booking_tidak_boleh_dipindah(): void
    {
        $event = $this->createEvent();
        $this->createBooking($event);

        $response = $this->put(
            route('admin.events.update', [
                'event' => $event['event_id'],
            ]),
            $this->payload([
                'start_time' => '13:00',
                'end_time' => '15:00',
            ])
        );

        $response->assertSessionHasErrors('date');

        $schedule = DB::table('event_schedules')
            ->where('id', $event['schedule_id'])
            ->first();

        $this->assertSame(
            '10:00',
            substr((string) $schedule->jam_mulai, 0, 5)
        );

        $this->assertSame(
            '12:00',
            substr((string) $schedule->jam_selesai, 0, 5)
        );
    }

    public function test_tarif_booking_lama_tidak_berubah(): void
    {
        $event = $this->createEvent();
        $bookingId = $this->createBooking($event);

        $this->put(
            route('admin.events.update', [
                'event' => $event['event_id'],
            ]),
            $this->payload([
                'tariff' => 35000,
            ])
        )->assertRedirect(route('admin.events.index'));

        $oldTariff = DB::table('tariff_reference')
            ->where('id', $event['tariff_id'])
            ->first();

        $this->assertSame(
            25000,
            (int) $oldTariff->nominal
        );

        $this->assertSame(
            0,
            (int) $oldTariff->aktif
        );

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'tariff_id' => $event['tariff_id'],
        ]);

        $newTariff = DB::table('tariff_reference')
            ->where('event_id', $event['event_id'])
            ->where('aktif', true)
            ->first();

        $this->assertNotNull($newTariff);

        $this->assertNotSame(
            $event['tariff_id'],
            $newTariff->id
        );

        $this->assertSame(
            35000,
            (int) $newTariff->nominal
        );
    }

    public function test_ubah_kuota_tanpa_memindah_booking(): void
    {
        $event = $this->createEvent();
        $bookingId = $this->createBooking($event);

        $this->put(
            route('admin.events.update', [
                'event' => $event['event_id'],
            ]),
            $this->payload([
                'quota' => 180,
            ])
        )->assertRedirect(route('admin.events.index'));

        $this->assertDatabaseHas('event_schedules', [
            'id' => $event['schedule_id'],
            'kapasitas' => 180,
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'event_schedule_id' => $event['schedule_id'],
            'tariff_id' => $event['tariff_id'],
        ]);
    }
}
