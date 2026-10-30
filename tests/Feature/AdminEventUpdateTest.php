<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\LogsInSiGorayAdmin;
use Tests\TestCase;

class AdminEventUpdateTest extends TestCase
{
    use DatabaseTransactions, LogsInSiGorayAdmin;

    private const DATE = '2035-07-20';

    protected function setUp(): void
    {
        parent::setUp();

        if (
            DB::connection()->getDatabaseName()
            !== 'gor_reservation_testing'
        ) {
            throw new \RuntimeException(
                'Test hanya boleh berjalan pada gor_reservation_testing.'
            );
        }

        $this->loginAsSiGorayAdmin();
    }

    private function payload(array $changes = []): array
    {
        return array_merge([
            'name' => 'Event Update Test',
            'description' => 'Pengujian perubahan event',
            'date' => self::DATE,
            'start_time' => '10:00',
            'end_time' => '12:00',
            'quota' => 150,
            'tariff' => 25000,
        ], $changes);
    }

    private function createEvent(): string
    {
        $this->post(
            route('admin.events.store'),
            $this->payload()
        )->assertRedirect(route('admin.events.index'));

        return DB::table('events')
            ->where('nama_event', 'Event Update Test')
            ->value('id');
    }

    public function test_edit_event_menampilkan_kuota_tersimpan(): void
    {
        $id = $this->createEvent();

        $response = $this->get(
            route('admin.events.edit', ['event' => $id])
        );

        $response->assertOk();

        $response->assertViewHas('event', function ($event) {
            return (int) $event['quota'] === 150
                && (int) $event['sold'] === 0;
        });
    }

    public function test_admin_dapat_memperbarui_kuota_event(): void
    {
        $id = $this->createEvent();

        $this->put(
            route('admin.events.update', ['event' => $id]),
            $this->payload([
                'quota' => 180,
                'tariff' => 30000,
            ])
        )->assertRedirect(route('admin.events.index'));

        $this->assertSame(
            180,
            (int) DB::table('event_schedules')
                ->where('event_id', $id)
                ->value('kapasitas')
        );
    }

    public function test_kuota_tidak_boleh_kurang_dari_tiket_terpakai(): void
    {
        $id = $this->createEvent();

        DB::table('event_schedules')
            ->where('event_id', $id)
            ->update(['kuota_terpakai' => 120]);

        $response = $this->put(
            route('admin.events.update', ['event' => $id]),
            $this->payload(['quota' => 100])
        );

        $response->assertSessionHasErrors('quota');

        $this->assertSame(
            150,
            (int) DB::table('event_schedules')
                ->where('event_id', $id)
                ->value('kapasitas')
        );
    }

    public function test_edit_jadwal_bentrok_ditolak(): void
    {
        $firstId = $this->createEvent();

        $this->post(
            route('admin.events.store'),
            $this->payload([
                'name' => 'Event Lain',
                'start_time' => '13:00',
                'end_time' => '15:00',
            ])
        )->assertRedirect(route('admin.events.index'));

        $response = $this->put(
            route('admin.events.update', ['event' => $firstId]),
            $this->payload([
                'start_time' => '14:00',
                'end_time' => '16:00',
            ])
        );

        $response->assertSessionHasErrors('date');

        $this->assertSame(
            '10:00',
            substr(
                (string) DB::table('event_schedules')
                    ->where('event_id', $firstId)
                    ->value('jam_mulai'),
                0,
                5
            )
        );
    }
}
