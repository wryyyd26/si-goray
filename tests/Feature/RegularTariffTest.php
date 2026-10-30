<?php

namespace Tests\Feature;

use App\Models\TariffReference;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;
use Tests\Concerns\LogsInSiGorayAdmin;

class RegularTariffTest extends TestCase
{
    use LogsInSiGorayAdmin;

    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsSiGorayAdmin();

        // Fail closed: do not write to the developer's operational database.
        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new RuntimeException('RegularTariffTest hanya boleh dijalankan di gor_reservation_testing.');
        }
    }

    private function regularTariff(int $nominal): TariffReference
    {
        return TariffReference::create([
            'event_id' => null,
            'jenis_pengunjung' => 'UMUM',
            'nominal' => $nominal,
            'berlaku_mulai' => today()->subDays(1)->toDateString(),
            'aktif' => true,
        ]);
    }

    public function test_simpan_tarif_membuat_referensi_baru_tanpa_mengganti_nominal_lama(): void
    {
        $old = $this->regularTariff(15000);

        $this->from('/admin/reguler?month=2026-10&date=2026-10-30')
            ->put(route('admin.reguler.tarif.update'), ['non_warga_price' => 20000])
            ->assertRedirect('/admin/reguler?month=2026-10&date=2026-10-30')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tariff_reference', [
            'id' => $old->id,
            'nominal' => 15000,
            'aktif' => false,
        ]);

        $this->assertDatabaseHas('tariff_reference', [
            'jenis_pengunjung' => 'UMUM',
            'event_id' => null,
            'nominal' => 20000,
            'aktif' => true,
            'berlaku_mulai' => today()->toDateString(),
        ]);
    }

    public function test_simpan_tarif_reguler_tidak_mengubah_tarif_event(): void
    {
        $eventId = (string) Str::uuid();
        DB::table('events')->insert([
            'id' => $eventId,
            'nama_event' => 'Event Test Tarif',
            'deskripsi' => 'Hanya untuk pengujian',
            'status' => 'AKTIF',
            'created_at' => now(),
        ]);

        $eventTariff = TariffReference::create([
            'event_id' => $eventId,
            'jenis_pengunjung' => 'UMUM',
            'nominal' => 50000,
            'berlaku_mulai' => today()->subDay()->toDateString(),
            'aktif' => true,
        ]);

        $this->put(route('admin.reguler.tarif.update'), ['non_warga_price' => 22000])
            ->assertRedirect();

        $this->assertDatabaseHas('tariff_reference', [
            'id' => $eventTariff->id,
            'nominal' => 50000,
            'aktif' => true,
            'event_id' => $eventId,
        ]);
    }

    public function test_nominal_tidak_valid_ditolak_tanpa_membuat_tarif(): void
    {
        $before = DB::table('tariff_reference')->count();

        $this->from('/admin/reguler')
            ->put(route('admin.reguler.tarif.update'), ['non_warga_price' => 0])
            ->assertRedirect('/admin/reguler')
            ->assertSessionHasErrors('non_warga_price');

        $this->assertSame($before, DB::table('tariff_reference')->count());
    }
}
