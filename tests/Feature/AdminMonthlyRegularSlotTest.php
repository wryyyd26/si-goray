<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\MonthlyRegularSlotController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminMonthlyRegularSlotTest extends TestCase
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
                'Test hanya boleh berjalan pada gor_reservation_testing.'
            );
        }

        // Route sementara yang hanya didaftarkan saat testing.
        Route::post(
            '/testing/admin/reguler/generate-month',
            [MonthlyRegularSlotController::class, 'store']
        )->middleware('web');
    }

    // TEST 1: Menolak data kosong.
    public function test_generator_menolak_input_kosong(): void
    {
        $response = $this->post(
            '/testing/admin/reguler/generate-month',
            []
        );

        $response->assertSessionHasErrors([
            'month',
            'year',
            'start_time',
            'end_time',
            'quota',
        ]);
    }

    // TEST 2: Menolak rentang bukan kelipatan 2 jam.
    public function test_generator_menolak_rentang_bukan_kelipatan_dua_jam(): void
    {
        $response = $this->post(
            '/testing/admin/reguler/generate-month',
            [
                'month' => 11,
                'year' => 2030,
                'start_time' => '08:00',
                'end_time' => '11:00',
                'quota' => 100,
            ]
        );

        $response->assertSessionHasErrors('end_time');
    }

    // TEST 3: Membuat dua sesi untuk setiap tanggal.
    public function test_generator_membuat_beberapa_sesi_per_hari(): void
    {
        $response = $this->post(
            '/testing/admin/reguler/generate-month',
            [
                'month' => 11,
                'year' => 2030,
                'start_time' => '08:00',
                'end_time' => '12:00',
                'quota' => 100,
            ]
        );

        $response->assertRedirect(
            route('admin.reguler.index')
        );

        $jumlahSlot = DB::table('regular_slots')
            ->whereBetween('tanggal', [
                '2030-11-01',
                '2030-11-30',
            ])
            ->count();

        $this->assertSame(60, $jumlahSlot);

        $jumlahKuotaBenar = DB::table('regular_slots')
            ->whereBetween('tanggal', [
                '2030-11-01',
                '2030-11-30',
            ])
            ->where('kapasitas', 100)
            ->count();

        $this->assertSame(60, $jumlahKuotaBenar);
    }

    // TEST 4: Generate ulang tidak menggandakan data.
    public function test_generator_tidak_menggandakan_slot(): void
    {
        $data = [
            'month' => 11,
            'year' => 2031,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'quota' => 100,
        ];

        $this->post(
            '/testing/admin/reguler/generate-month',
            $data
        )->assertRedirect(route('admin.reguler.index'));

        $jumlahPertama = DB::table('regular_slots')
            ->whereBetween('tanggal', [
                '2031-11-01',
                '2031-11-30',
            ])
            ->count();

        $this->post(
            '/testing/admin/reguler/generate-month',
            $data
        )->assertRedirect(route('admin.reguler.index'));

        $jumlahKedua = DB::table('regular_slots')
            ->whereBetween('tanggal', [
                '2031-11-01',
                '2031-11-30',
            ])
            ->count();

        $this->assertSame($jumlahPertama, $jumlahKedua);
        $this->assertSame(60, $jumlahKedua);
    }

    // TEST 5: Melewati sesi yang bentrok dengan event aktif.
    public function test_generator_melewati_slot_yang_bertabrakan_dengan_event(): void
    {
        $eventId = (string) Str::uuid();

        DB::table('events')->insert([
            'id' => $eventId,
            'nama_event' => 'Event Pengujian Konflik',
            'deskripsi' => 'Event khusus pengujian',
            'status' => 'AKTIF',
        ]);

        DB::table('event_schedules')->insert([
            'id' => (string) Str::uuid(),
            'event_id' => $eventId,
            'tanggal' => '2032-11-15',
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '11:00:00',
            'kapasitas' => 200,
            'kuota_terpakai' => 0,
        ]);

        $response = $this->post(
            '/testing/admin/reguler/generate-month',
            [
                'month' => 11,
                'year' => 2032,
                'start_time' => '08:00',
                'end_time' => '12:00',
                'quota' => 100,
            ]
        );

        $response->assertRedirect(
            route('admin.reguler.index')
        );

        // Kedua sesi pada tanggal 15 harus dilewati.
        $jumlahTanggalKonflik = DB::table('regular_slots')
            ->whereDate('tanggal', '2032-11-15')
            ->count();

        $this->assertSame(0, $jumlahTanggalKonflik);

        // 30 hari x 2 sesi = 60.
        // Dua sesi bertabrakan, sehingga tersisa 58.
        $jumlahSlot = DB::table('regular_slots')
            ->whereBetween('tanggal', [
                '2032-11-01',
                '2032-11-30',
            ])
            ->count();

        $this->assertSame(58, $jumlahSlot);
    }
}
