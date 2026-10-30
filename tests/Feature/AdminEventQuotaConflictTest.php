<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\LogsInSiGorayAdmin;
use Tests\TestCase;

class AdminEventQuotaConflictTest extends TestCase
{
    use DatabaseTransactions, LogsInSiGorayAdmin;

    private const DATE = '2035-06-18';

    protected function setUp(): void
    {
        parent::setUp();

        // Pastikan pengujian tidak menggunakan database utama.
        if (
            DB::connection()->getDatabaseName()
            !== 'gor_reservation_testing'
        ) {
            throw new \RuntimeException(
                'Pengujian hanya boleh menggunakan gor_reservation_testing.'
            );
        }

        // Login sebagai Admin khusus lingkungan testing.
        $this->loginAsSiGorayAdmin();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Event Uji Kuota SI-GORAY',
            'description' => 'Pengujian event',
            'date' => self::DATE,
            'start_time' => '10:00',
            'end_time' => '12:00',
            'quota' => 150,
            'tariff' => 25000,
        ], $overrides);
    }

    public function test_kuota_150_disimpan_bersama_event_dan_tarif(): void
    {
        $response = $this->post(
            route('admin.events.store'),
            $this->payload()
        );

        $response->assertRedirect(
            route('admin.events.index')
        );

        $event = DB::table('events')
            ->where('nama_event', 'Event Uji Kuota SI-GORAY')
            ->first();

        $this->assertNotNull($event);

        $schedule = DB::table('event_schedules')
            ->where('event_id', $event->id)
            ->first();

        $this->assertNotNull($schedule);
        $this->assertSame(150, (int) $schedule->kapasitas);
        $this->assertSame(0, (int) $schedule->kuota_terpakai);

        $this->assertDatabaseHas('tariff_reference', [
            'event_id' => $event->id,
            'jenis_pengunjung' => 'UMUM',
            'nominal' => 25000,
        ]);
    }

    public function test_kuota_melebihi_200_ditolak(): void
    {
        $response = $this->post(
            route('admin.events.store'),
            $this->payload([
                'name' => 'Event Kuota Berlebih',
                'quota' => 250,
            ])
        );

        $response->assertSessionHasErrors('quota');

        $this->assertDatabaseMissing('events', [
            'nama_event' => 'Event Kuota Berlebih',
        ]);
    }

    public function test_event_bentrok_dengan_slot_reguler_ditolak(): void
    {
        DB::table('regular_slots')->insert([
            'id' => (string) Str::uuid(),
            'tanggal' => self::DATE,
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '11:00:00',
            'kapasitas' => 200,
            'kuota_terpakai' => 0,
            'status' => 'active',
        ]);

        $response = $this->post(
            route('admin.events.store'),
            $this->payload([
                'name' => 'Event Bentrok Reguler',
            ])
        );

        $response->assertSessionHasErrors('date');

        $this->assertDatabaseMissing('events', [
            'nama_event' => 'Event Bentrok Reguler',
        ]);
    }

    public function test_event_bentrok_dengan_event_lain_ditolak(): void
    {
        // Membuat event pertama melalui controller asli.
        $this->post(
            route('admin.events.store'),
            $this->payload([
                'name' => 'Event Pertama',
            ])
        )->assertRedirect(route('admin.events.index'));

        // Event kedua menggunakan jadwal yang bertabrakan.
        $response = $this->post(
            route('admin.events.store'),
            $this->payload([
                'name' => 'Event Kedua',
                'start_time' => '11:00',
                'end_time' => '13:00',
            ])
        );

        $response->assertSessionHasErrors('date');

        $this->assertDatabaseMissing('events', [
            'nama_event' => 'Event Kedua',
        ]);
    }

    public function test_jadwal_tanpa_bentrok_diperbolehkan(): void
    {
        DB::table('regular_slots')->insert([
            'id' => (string) Str::uuid(),
            'tanggal' => self::DATE,
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kapasitas' => 200,
            'kuota_terpakai' => 0,
            'status' => 'active',
        ]);

        $this->post(
            route('admin.events.store'),
            $this->payload([
                'name' => 'Event Tidak Bentrok',
                'start_time' => '10:00',
                'end_time' => '12:00',
            ])
        )->assertRedirect(route('admin.events.index'));

        $this->assertDatabaseHas('events', [
            'nama_event' => 'Event Tidak Bentrok',
        ]);
    }
}
