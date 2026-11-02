<?php

namespace Tests\Feature;

use App\Models\Identity;
use App\Models\User;
use App\Services\IdentityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class PengunjungIdentityApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (
            DB::connection()->getDatabaseName()
            !== 'gor_reservation_testing'
        ) {
            throw new \RuntimeException(
                'Pengujian hanya boleh menggunakan gor_reservation_testing.'
            );
        }
    }

    public function test_tanpa_token_ditolak(): void
    {
        $this->postJson('/api/pengunjung/identities', [
            'nik' => '3576020101010001',
            'tanggal_lahir' => '2001-01-01',
        ])->assertUnauthorized();
    }

    public function test_token_tanpa_izin_booking_ditolak(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['gate:scan']);

        $this->postJson('/api/pengunjung/identities', [
            'nik' => '3576020101010001',
            'tanggal_lahir' => '2001-01-01',
        ])->assertForbidden();
    }

    public function test_nik_tidak_valid_ditolak(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['booking:create']);

        $this->postJson('/api/pengunjung/identities', [
            'nik' => '12345',
            'tanggal_lahir' => '2001-01-01',
        ])->assertUnprocessable()
          ->assertJsonValidationErrors('nik');
    }

    public function test_identitas_berhasil_tanpa_membocorkan_data(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['booking:create']);

        $nik = '3576020101010001';
        $tanggalLahir = '2001-01-01';

        $identity = new Identity();

        $identity->forceFill([
            'id' => '11111111-1111-4111-8111-111111111111',
            'user_id' => $user->id,
            'status_warga_kota' => true,
        ]);

        $service = Mockery::mock(IdentityService::class);

        $service->shouldReceive('createIdentity')
            ->once()
            ->with(
                (string) $user->id,
                $nik,
                $tanggalLahir
            )
            ->andReturn($identity);

        $this->app->instance(IdentityService::class, $service);

        $this->postJson('/api/pengunjung/identities', [
            'nik' => $nik,
            'tanggal_lahir' => $tanggalLahir,
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.id',
                '11111111-1111-4111-8111-111111111111'
            )
            ->assertJsonPath('data.status_warga_kota', true)
            ->assertDontSee($nik)
            ->assertDontSee($tanggalLahir);
    }
}
