<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Concerns\LogsInSiGorayAdmin;


class DailyRegularQuotaConflictTest extends TestCase
{
    use LogsInSiGorayAdmin;

    use DatabaseTransactions;

    private const DATE = '2035-05-15';

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsSiGorayAdmin();

        // Perlindungan database kerja.
        if (
            DB::connection()->getDatabaseName()
            !== 'gor_reservation_testing'
        ) {
            throw new \RuntimeException(
                'Test hanya boleh berjalan pada gor_reservation_testing.'
            );
        }
    }

    private function createEvent(
        string $start,
        string $end
    ): void {
        $eventId = (string) Str::uuid();

        DB::table('events')->insert([
            'id' => $eventId,
            'nama_event' => 'Event Pengujian Kuota Harian',
            'deskripsi' => 'Pengujian bentrok jadwal',
            'status' => 'AKTIF',
        ]);

        DB::table('event_schedules')->insert([
            'id' => (string) Str::uuid(),
            'event_id' => $eventId,
            'tanggal' => self::DATE,
            'jam_mulai' => $start,
            'jam_selesai' => $end,
            'kapasitas' => 200,
            'kuota_terpakai' => 0,
        ]);
    }

    private function payload(): array
    {
        return [
            'date' => self::DATE,
            'capacity_per_session' => 200,
            'quotas' => [
                200, 200, 200, 200, 200, 200,
            ],
        ];
    }

    private function countSlots(): int
    {
        return DB::table('regular_slots')
            ->whereDate('tanggal', self::DATE)
            ->count();
    }

    public function test_bentrok_event_membatalkan_semua_slot(): void
    {
        // Event 09.00–11.00 bertabrakan dengan
        // sesi 1 dan sesi 2.
        $this->createEvent(
            '09:00:00',
            '11:00:00'
        );

        $response = $this->from(
            route('admin.reguler.index', [
                'month' => '2035-05',
                'date' => self::DATE,
            ])
        )->post(
            route('admin.reguler.kuota-harian.store'),
            $this->payload()
        );

        $response->assertSessionHasErrors('quotas.0');

        // Transaksi harus rollback sepenuhnya.
        $this->assertSame(
            0,
            $this->countSlots()
        );
    }

    public function test_event_di_luar_jam_sesi_tidak_menghalangi(): void
    {
        // Event selesai sebelum sesi pertama dimulai.
        $this->createEvent(
            '06:00:00',
            '08:00:00'
        );

        $response = $this->post(
            route('admin.reguler.kuota-harian.store'),
            $this->payload()
        );

        $response->assertRedirect(
            route('admin.reguler.index', [
                'month' => '2035-05',
                'date' => self::DATE,
            ])
        );

        $this->assertSame(
            6,
            $this->countSlots()
        );

        $this->assertSame(
            6,
            DB::table('regular_slots')
                ->whereDate('tanggal', self::DATE)
                ->where('kapasitas', 200)
                ->count()
        );
    }
}
