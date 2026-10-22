<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegularSlotCalendarTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new \RuntimeException(
                'Test hanya boleh menggunakan gor_reservation_testing.'
            );
        }
    }

    private function buatSlot(
        string $tanggal,
        string $mulai,
        string $selesai,
        int $kapasitas,
        int $terpakai,
        string $status = 'active'
    ): string {
        $id = (string) Str::uuid();

        DB::table('regular_slots')->insert([
            'id' => $id,
            'tanggal' => $tanggal,
            'jam_mulai' => $mulai,
            'jam_selesai' => $selesai,
            'kapasitas' => $kapasitas,
            'kuota_terpakai' => $terpakai,
            'status' => $status,
        ]);

        return $id;
    }

    public function test_kalender_menampilkan_beberapa_sesi_satu_tanggal(): void
    {
        $idPertama = $this->buatSlot(
            '2035-11-15', '08:00:00', '10:00:00', 100, 25
        );

        $idKedua = $this->buatSlot(
            '2035-11-15', '10:00:00', '12:00:00', 100, 40
        );

        $response = $this->getJson(
            '/api/reguler/slots?month=11&year=2035'
        );

        $response->assertOk();

        $response->assertJsonFragment([
            'id' => $idPertama,
            'date' => '2035-11-15',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'quota' => 100,
            'used' => 25,
            'available' => 75,
            'status' => 'available',
        ]);

        $response->assertJsonFragment([
            'id' => $idKedua,
            'start_time' => '10:00',
            'end_time' => '12:00',
            'quota' => 100,
            'used' => 40,
            'available' => 60,
            'status' => 'available',
        ]);
    }

    public function test_slot_penuh_diberi_status_full(): void
    {
        $id = $this->buatSlot(
            '2035-12-15', '08:00:00', '10:00:00', 100, 100
        );

        $response = $this->getJson(
            '/api/reguler/slots?month=12&year=2035'
        );

        $response->assertOk();

        $response->assertJsonFragment([
            'id' => $id,
            'available' => 0,
            'status' => 'full',
        ]);
    }

    public function test_slot_nonaktif_tidak_ditampilkan(): void
    {
        $idNonaktif = $this->buatSlot(
            '2036-01-15', '08:00:00', '10:00:00',
            100, 0, 'inactive'
        );

        $idAktif = $this->buatSlot(
            '2036-01-15', '10:00:00', '12:00:00',
            100, 0, 'active'
        );

        $response = $this->getJson(
            '/api/reguler/slots?month=1&year=2036'
        );

        $response->assertOk();

        $response->assertJsonFragment([
            'id' => $idAktif,
        ]);

        $response->assertJsonMissing([
            'id' => $idNonaktif,
        ]);
    }

    public function test_parameter_bulan_tidak_valid_ditolak(): void
    {
        $response = $this->getJson(
            '/api/reguler/slots?month=13&year=2035'
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors('month');
    }
}
