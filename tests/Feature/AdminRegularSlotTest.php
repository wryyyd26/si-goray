<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminRegularSlotTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Pastikan pengujian tidak menulis ke database utama.
     */
    private function pastikanDatabaseTesting(): void
    {
        $database = DB::connection()->getDatabaseName();

        if ($database !== 'gor_reservation_testing') {
            $this->fail(
                'Pengujian dihentikan. Database aktif: '
                . $database
                . '. Harus menggunakan gor_reservation_testing.'
            );
        }
    }

    public function test_tambah_slot_dengan_data_kosong_ditolak(): void
    {
        $response = $this->post(
            route('admin.reguler.store'),
            []
        );

        $response->assertSessionHasErrors([
            'date',
            'start_time',
            'end_time',
            'quota',
        ]);
    }

    public function test_slot_dengan_uuid_tidak_ditemukan(): void
    {
        $response = $this->get(
            route('admin.reguler.slots.edit', [
                'slotId' => '00000000-0000-4000-8000-000000000000',
            ])
        );

        $response->assertNotFound();
    }

    public function test_update_kuota_satu_slot_tidak_mengubah_slot_lain(): void
    {
        $this->pastikanDatabaseTesting();

        $slotA = (string) Str::uuid();
        $slotB = (string) Str::uuid();

        DB::table('regular_slots')->insert([
            [
                'id' => $slotA,
                'tanggal' => '2030-11-01',
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '10:00:00',
                'kapasitas' => 100,
                'kuota_terpakai' => 0,
                'status' => 'active',
            ],
            [
                'id' => $slotB,
                'tanggal' => '2030-11-01',
                'jam_mulai' => '10:00:00',
                'jam_selesai' => '12:00:00',
                'kapasitas' => 100,
                'kuota_terpakai' => 0,
                'status' => 'active',
            ],
        ]);

        $response = $this->put(
            route('admin.reguler.slots.update', [
                'slotId' => $slotA,
            ]),
            [
                'start_time' => '08:00',
                'end_time' => '10:00',
                'quota' => 150,
                'status' => 'active',
            ]
        );

        $response->assertRedirect(
            route('admin.reguler.index')
        );

        $this->assertDatabaseHas('regular_slots', [
            'id' => $slotA,
            'kapasitas' => 150,
        ]);

        $this->assertDatabaseHas('regular_slots', [
            'id' => $slotB,
            'kapasitas' => 100,
        ]);
    }

    public function test_durasi_slot_harus_dua_jam(): void
    {
        $this->pastikanDatabaseTesting();

        $slotId = (string) Str::uuid();

        DB::table('regular_slots')->insert([
            'id' => $slotId,
            'tanggal' => '2030-11-02',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kapasitas' => 100,
            'kuota_terpakai' => 0,
            'status' => 'active',
        ]);

        $response = $this->put(
            route('admin.reguler.slots.update', [
                'slotId' => $slotId,
            ]),
            [
                'start_time' => '08:00',
                'end_time' => '11:00',
                'quota' => 100,
                'status' => 'active',
            ]
        );

        $response->assertSessionHasErrors('end_time');

        $this->assertDatabaseHas('regular_slots', [
            'id' => $slotId,
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
        ]);
    }
}
