<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Concerns\LogsInSiGorayAdmin;

class DailyRegularQuotaTest extends TestCase
{
    use LogsInSiGorayAdmin;

    use DatabaseTransactions;

    private const DATE = '2035-03-17';

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsSiGorayAdmin();

        if (
            DB::connection()->getDatabaseName()
            !== 'gor_reservation_testing'
        ) {
            throw new \RuntimeException(
                'Pengujian hanya boleh memakai gor_reservation_testing.'
            );
        }
    }

    private function payload(
        array $quotas = [200, 200, 200, 200, 200, 200]
    ): array {
        return [
            'date' => self::DATE,
            'capacity_per_session' => 200,
            'quotas' => $quotas,
        ];
    }

    private function slots()
    {
        return DB::table('regular_slots')
            ->whereDate('tanggal', self::DATE)
            ->orderBy('jam_mulai')
            ->get();
    }

    public function test_enam_sesi_masing_masing_kuota_200(): void
    {
        $response = $this->post(
            route('admin.reguler.kuota-harian.store'),
            $this->payload()
        );

        $response->assertRedirect(
            route('admin.reguler.index', [
                'month' => '2035-03',
                'date' => self::DATE,
            ])
        );

        $slots = $this->slots();

        $this->assertCount(6, $slots);

        $expected = [
            '08:00',
            '10:00',
            '12:00',
            '14:00',
            '16:00',
            '18:00',
        ];

        foreach ($slots as $index => $slot) {
            $this->assertSame(
                $expected[$index],
                substr($slot->jam_mulai, 0, 5)
            );

            $this->assertSame(200, (int) $slot->kapasitas);
            $this->assertSame(0, (int) $slot->kuota_terpakai);
        }
    }

    public function test_kuota_per_sesi_dapat_berbeda(): void
    {
        $this->post(
            route('admin.reguler.kuota-harian.store'),
            $this->payload([200, 150, 200, 100, 200, 200])
        );

        $actual = $this->slots()
            ->pluck('kapasitas')
            ->map(fn ($value) => (int) $value)
            ->all();

        $this->assertSame(
            [200, 150, 200, 100, 200, 200],
            $actual
        );
    }

    public function test_simpan_ulang_tidak_menggandakan_sesi(): void
    {
        $url = route('admin.reguler.kuota-harian.store');

        $this->post($url, $this->payload());

        $idsBefore = $this->slots()
            ->pluck('id')
            ->all();

        $this->post(
            $url,
            $this->payload([200, 175, 200, 200, 200, 200])
        );

        $idsAfter = $this->slots()
            ->pluck('id')
            ->all();

        $this->assertCount(6, $idsAfter);
        $this->assertSame($idsBefore, $idsAfter);

        $this->assertSame(
            175,
            (int) $this->slots()[1]->kapasitas
        );
    }

    public function test_kuota_tidak_boleh_di_bawah_tiket_terpakai(): void
    {
        $url = route('admin.reguler.kuota-harian.store');

        $this->post($url, $this->payload());

        $slot = $this->slots()->first();

        DB::table('regular_slots')
            ->where('id', $slot->id)
            ->update([
                'kuota_terpakai' => 100,
            ]);

        $response = $this->from(
            route('admin.reguler.index')
        )->post(
            $url,
            $this->payload([50, 200, 200, 200, 200, 200])
        );

        $response->assertSessionHasErrors('quotas.0');

        $this->assertDatabaseHas('regular_slots', [
            'id' => $slot->id,
            'kapasitas' => 200,
            'kuota_terpakai' => 100,
        ]);
    }

    public function test_kuota_melebihi_kapasitas_ditolak(): void
    {
        $response = $this->post(
            route('admin.reguler.kuota-harian.store'),
            $this->payload([250, 200, 200, 200, 200, 200])
        );

        $response->assertSessionHasErrors('quotas.0');

        $this->assertCount(0, $this->slots());
    }

    public function test_kuota_tanggal_lampau_ditolak(): void
    {
        $data = $this->payload();
        $data['date'] = '2020-01-01';

        $response = $this->post(
            route('admin.reguler.kuota-harian.store'),
            $data
        );

        $response->assertSessionHasErrors('date');
    }
}
