<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Identity;
use App\Models\PetugasGerbang;
use App\Models\User;
use App\Services\GateAccessService;
use App\Services\QrTicketService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\LogsInSiGorayAdmin;
use Tests\TestCase;

class GateSingleUseFlowTest extends TestCase
{
    use DatabaseTransactions, LogsInSiGorayAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new \RuntimeException('Tes hanya boleh dijalankan pada gor_reservation_testing.');
        }

        $this->loginAsSiGorayAdmin();
    }

    private function prepareQrAndOfficer(): array
    {
        // Gunakan controller Event yang sudah diuji; jangan menebak kolom events/tariff_reference.
        $eventName = 'Gate QR Test ' . Str::random(10);
        $date = now()->addDays(5)->toDateString();

        $this->post(route('admin.events.store'), [
            'name' => $eventName,
            'description' => 'Fixture pengujian scan QR',
            'date' => $date,
            'start_time' => '10:00',
            'end_time' => '12:00',
            'quota' => 150,
            'tariff' => 25000,
        ])->assertRedirect(route('admin.events.index'));

        $event = DB::table('events')->where('nama_event', $eventName)->first();
        $this->assertNotNull($event);

        $schedule = DB::table('event_schedules')->where('event_id', $event->id)->first();
        $tariff = DB::table('tariff_reference')
            ->where('event_id', $event->id)->where('aktif', true)->first();
        $this->assertNotNull($schedule);
        $this->assertNotNull($tariff);

        $user = User::factory()->create();
        $identity = Identity::create([
            'user_id' => $user->id,
            'nik_enc' => '3576010101900002',
            'nik_hash' => hash('sha256', (string) Str::uuid()),
            'tanggal_lahir_enc' => '1990-01-01',
            'status_warga_kota' => false,
            'sumber_validasi' => 'local',
            'divalidasi_at' => now(),
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'identity_id' => $identity->id,
            'jenis_booking' => 'EVENT',
            'regular_slot_id' => null,
            'event_schedule_id' => $schedule->id,
            'tariff_id' => $tariff->id,
            'retribusi_diperlukan' => true,
            'status_booking' => 'CONFIRMED',
        ]);

        $petugas = PetugasGerbang::create([
            'nama' => 'Petugas Test',
            'username' => 'gate_' . Str::lower(Str::random(12)),
            'aktif' => true,
        ]);

        $qr = app(QrTicketService::class)->generate($booking);

        return [$booking, $qr, $petugas];
    }

    public function test_scan_pertama_berhasil_dan_scan_kedua_ditolak(): void
    {
        [$booking, $qr, $petugas] = $this->prepareQrAndOfficer();
        $gate = app(GateAccessService::class);

        $first = $gate->scan($qr->payload_signed, $petugas);
        $this->assertTrue($first['success']);
        $this->assertSame('BERHASIL', $first['hasil']);
        $this->assertDatabaseHas('qr_tickets', [
            'id' => $qr->id,
            'booking_id' => $booking->id,
            'status_qr' => 'SUDAH_DIPAKAI',
        ]);

        $second = $gate->scan($qr->payload_signed, $petugas);
        $this->assertFalse($second['success']);
        $this->assertSame('GAGAL', $second['hasil']);
        $this->assertSame('QR_TIDAK_AKTIF', $second['reason']);

        $this->assertSame(1, DB::table('access_logs')
            ->where('qr_ticket_id', $qr->id)
            ->where('hasil_validasi', 'BERHASIL')
            ->count());
        $this->assertSame(1, DB::table('access_logs')
            ->where('qr_ticket_id', $qr->id)
            ->where('hasil_validasi', 'GAGAL')
            ->count());
    }

    public function test_petugas_tidak_aktif_ditolak_tanpa_memakai_qr(): void
    {
        [, $qr, $petugas] = $this->prepareQrAndOfficer();
        $petugas->update(['aktif' => false]);

        $result = app(GateAccessService::class)->scan($qr->payload_signed, $petugas->fresh());
        $this->assertFalse($result['success']);
        $this->assertSame('PETUGAS_TIDAK_AKTIF', $result['reason']);
        $this->assertDatabaseHas('qr_tickets', [
            'id' => $qr->id,
            'status_qr' => 'AKTIF',
        ]);
        $this->assertSame(0, DB::table('access_logs')->where('qr_ticket_id', $qr->id)->count());
    }
}
