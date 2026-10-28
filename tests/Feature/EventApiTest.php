<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (app()->environment() !== 'testing' ||
            DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('Test hanya boleh menggunakan gor_reservation_testing.');
        }
    }

    private function buatEvent(string $nama, string $status = 'AKTIF', int $jarakHari = 7): string
    {
        $id = (string) Str::uuid();

        DB::table('events')->insert([
            'id' => $id,
            'nama_event' => $nama,
            'deskripsi' => 'Deskripsi pengujian event',
            'status' => $status,
            'created_at' => now(),
        ]);

        DB::table('event_schedules')->insert([
            'id' => (string) Str::uuid(),
            'event_id' => $id,
            'tanggal' => now()->addDays($jarakHari)->toDateString(),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'kapasitas' => 100,
            'kuota_terpakai' => 25,
        ]);

        return $id;
    }

    private function buatTarif(?string $eventId, int $nominal): void
    {
        DB::table('tariff_reference')->insert([
            'id' => (string) Str::uuid(),
            'event_id' => $eventId,
            'jenis_pengunjung' => 'UMUM',
            'nominal' => $nominal,
            'berlaku_mulai' => now()->subDay()->toDateString(),
            'aktif' => true,
        ]);
    }

    public function test_daftar_hanya_event_aktif_dengan_jadwal_mendatang(): void
    {
        $aktif = $this->buatEvent('Turnamen Aktif');
        $this->buatEvent('Turnamen Nonaktif', 'NONAKTIF');
        $this->buatEvent('Turnamen Lampau', 'AKTIF', -7);

        $this->getJson('/api/events')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.0.id', $aktif)
            ->assertJsonPath('data.0.remaining', 75);
    }

    public function test_pencarian_event_dan_validasi_parameter(): void
    {
        $this->buatEvent('Turnamen Badminton');
        $this->buatEvent('Kompetisi Futsal');

        $this->getJson('/api/events?q=Badminton')
            ->assertOk()
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.0.title', 'Turnamen Badminton');

        $this->getJson('/api/events?q=' . str_repeat('a', 101))
            ->assertStatus(422);
    }

    public function test_detail_event_memuat_jadwal_dan_tarif_khusus(): void
    {
        $id = $this->buatEvent('Event Berbayar');
        $this->buatTarif(null, 10000);
        $this->buatTarif($id, 25000);

        $this->getJson('/api/events/' . $id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.price_amount', 25000)
            ->assertJsonPath('data.schedules.0.capacity', 100)
            ->assertJsonPath('data.schedules.0.remaining', 75);
    }

    public function test_tarif_umum_digunakan_jika_tidak_ada_tarif_event(): void
    {
        $id = $this->buatEvent('Event Tarif Umum');
        $this->buatTarif(null, 10000);

        $this->getJson('/api/events/' . $id)
            ->assertOk()
            ->assertJsonPath('data.price_amount', 10000);
    }

    public function test_event_nonaktif_dan_lampau_tidak_bisa_dibuka(): void
    {
        $nonaktif = $this->buatEvent('Event Nonaktif', 'NONAKTIF');
        $lampau = $this->buatEvent('Event Lampau', 'AKTIF', -7);

        $this->getJson('/api/events/' . $nonaktif)->assertNotFound();
        $this->getJson('/api/events/' . $lampau)->assertNotFound();
    }
}
