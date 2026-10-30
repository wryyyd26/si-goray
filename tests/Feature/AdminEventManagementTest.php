<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Concerns\LogsInSiGorayAdmin;

class AdminEventManagementTest extends TestCase
{
    use LogsInSiGorayAdmin;

    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsSiGorayAdmin();

        if (
            DB::connection()->getDatabaseName()
            !== 'gor_reservation_testing'
        ) {
            throw new \RuntimeException(
                'Pengujian hanya boleh menggunakan gor_reservation_testing.'
            );
        }
    }

    public function test_halaman_daftar_event_dapat_dibuka(): void
    {
        $this->get(route('admin.events.index'))
            ->assertOk()
            ->assertViewIs('admin.event.index');
    }

    public function test_halaman_tambah_event_dapat_dibuka(): void
    {
        $this->get(route('admin.events.create'))
            ->assertOk()
            ->assertViewIs('admin.event.create');
    }

    public function test_filter_pencarian_event_berjalan(): void
    {
        $this->get(route('admin.events.index', [
            'search' => 'Event Pengujian',
        ]))->assertOk();
    }

    public function test_tambah_event_tanpa_data_ditolak(): void
    {
        $this->post(route('admin.events.store'), [])
            ->assertSessionHasErrors([
                'name',
                'date',
                'start_time',
                'end_time',
                'tariff',
            ]);
    }

    public function test_jam_event_tidak_valid_ditolak(): void
    {
        $this->post(route('admin.events.store'), [
            'name' => 'Event Pengujian',
            'date' => '2035-11-15',
            'start_time' => '14:00',
            'end_time' => '10:00',
            'tariff' => 25000,
        ])->assertSessionHasErrors('end_time');
    }

    public function test_tanggal_lampau_ditolak(): void
    {
        $this->post(route('admin.events.store'), [
            'name' => 'Event Pengujian',
            'date' => '2020-01-01',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'tariff' => 25000,
        ])->assertSessionHasErrors('date');
    }

    public function test_edit_event_tidak_ditemukan(): void
    {
        $this->get(route('admin.events.edit', [
            'event' => '00000000-0000-4000-8000-000000000000',
        ]))->assertNotFound();
    }

    public function test_mengubah_event_tidak_ditemukan(): void
    {
        $this->put(route('admin.events.update', [
            'event' => '00000000-0000-4000-8000-000000000000',
        ]), [
            'name' => 'Event Pengujian',
            'date' => '2035-11-15',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'tariff' => 25000,
        ])->assertNotFound();
    }
}
