<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\LogsInSiGorayAdmin;
use Tests\TestCase;

class AdminSaranMasukanTest extends TestCase
{
    use LogsInSiGorayAdmin;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new \RuntimeException(
                'Pengujian hanya boleh menggunakan gor_reservation_testing.'
            );
        }

        if (!DB::getSchemaBuilder()->hasTable('saran_masukan')) {
            throw new \RuntimeException(
                'Tabel saran_masukan belum tersedia pada database testing.'
            );
        }

        $this->loginAsSiGorayAdmin();
    }

    private function buatPesan(
        string $category = 'feedback',
        string $status = 'baru'
    ): string {
        $id = (string) Str::uuid();

        DB::table('saran_masukan')->insert([
            'id' => $id,
            'name' => 'Pengguna Pengujian',
            'email' => 'pengujian@example.test',
            'whatsapp' => null,
            'category' => $category,
            'subject' => 'Pesan pengujian SI-GORAY',
            'message' => 'Isi pesan untuk pengujian otomatis.',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    public function test_halaman_daftar_dapat_dibuka(): void
    {
        $this->get(route('admin.saran-masukan.index'))
            ->assertOk()
            ->assertViewIs('admin.saran-masukan.index')
            ->assertViewHas('pesan')
            ->assertViewHas('ringkasan');
    }

    public function test_pesan_database_muncul_pada_daftar(): void
    {
        $this->buatPesan();

        $this->get(route('admin.saran-masukan.index'))
            ->assertOk()
            ->assertSee('Pesan pengujian SI-GORAY')
            ->assertSee('pengujian@example.test');
    }

    public function test_filter_kategori_dan_status(): void
    {
        $id = $this->buatPesan('reservasi', 'diproses');

        $this->get(route('admin.saran-masukan.index', [
            'category' => 'reservasi',
            'status' => 'diproses',
        ]))
            ->assertOk()
            ->assertViewHas('pesan', function ($pesan) use ($id) {
                return $pesan->contains('id', $id);
            });
    }

    public function test_pencarian_subjek(): void
    {
        $id = $this->buatPesan();

        $this->get(route('admin.saran-masukan.index', [
            'search' => 'Pesan pengujian SI-GORAY',
        ]))
            ->assertOk()
            ->assertViewHas('pesan', function ($pesan) use ($id) {
                return $pesan->contains('id', $id);
            });
    }

    public function test_detail_pesan_dapat_dibuka(): void
    {
        $id = $this->buatPesan();

        $this->get(route('admin.saran-masukan.detail', $id))
            ->assertOk()
            ->assertViewIs('admin.saran-masukan.detail')
            ->assertSee('Isi pesan untuk pengujian otomatis.');
    }

    public function test_status_pesan_dapat_diperbarui(): void
    {
        $id = $this->buatPesan();

        $this->patch(
            route('admin.saran-masukan.update-status', $id),
            ['status' => 'selesai']
        )->assertRedirect();

        $this->assertDatabaseHas('saran_masukan', [
            'id' => $id,
            'status' => 'selesai',
        ]);
    }

    public function test_status_tidak_valid_ditolak(): void
    {
        $id = $this->buatPesan();

        $this->patch(
            route('admin.saran-masukan.update-status', $id),
            ['status' => 'tidak-valid']
        )->assertSessionHasErrors('status');

        $this->assertDatabaseHas('saran_masukan', [
            'id' => $id,
            'status' => 'baru',
        ]);
    }

    public function test_pesan_dapat_dihapus(): void
    {
        $id = $this->buatPesan();

        $this->delete(
            route('admin.saran-masukan.destroy', $id)
        )->assertRedirect(route('admin.saran-masukan.index'));

        $this->assertDatabaseMissing('saran_masukan', [
            'id' => $id,
        ]);
    }

    public function test_detail_pesan_tidak_ditemukan(): void
    {
        $this->get(route(
            'admin.saran-masukan.detail',
            '00000000-0000-4000-8000-000000000000'
        ))->assertNotFound();
    }

public function test_pengunjung_tidak_bisa_melihat_daftar_pesan(): void
{
    $this->flushSession();

    $response = $this->get(
        route('admin.saran-masukan.index')
    );

    $this->assertContains(
        $response->getStatusCode(),
        [302, 401, 403]
    );
}

public function test_pengunjung_tidak_bisa_menghapus_pesan(): void
{
    $id = $this->buatPesan();

    $this->flushSession();

    $response = $this->delete(
        route('admin.saran-masukan.destroy', $id)
    );

    $this->assertContains(
        $response->getStatusCode(),
        [302, 401, 403]
    );

    $this->assertDatabaseHas('saran_masukan', [
        'id' => $id,
    ]);
}
}
