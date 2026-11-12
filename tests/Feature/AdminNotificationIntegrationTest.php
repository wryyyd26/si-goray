<?php

namespace Tests\Feature;

use App\Services\AdminNotificationRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminNotificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // Verifikasi target SEBELUM parent::setUp agar migrasi test tidak mengenai database utama.
        $xml = file_get_contents(base_path('phpunit.xml')) ?: '';
        if (! str_contains($xml, 'gor_reservation_testing')) {
            throw new \RuntimeException('phpunit.xml belum dipastikan memakai DB testing.');
        }
        parent::setUp();
        if (DB::connection()->getDatabaseName() !== 'gor_reservation_testing') {
            throw new \RuntimeException('Database bukan gor_reservation_testing.');
        }
        Config::set('sigoray_admin.username', 'test-admin');
        Config::set('sigoray_admin.password_hash', Hash::make('test-password'));
        Config::set('sigoray_admin.session_minutes', 120);
    }

    private function adminSession(): array
    {
        return [
            'sigoray_admin_authenticated' => true,
            'sigoray_admin_username' => 'test-admin',
            'sigoray_admin_password_version' => hash('sha256', (string) config('sigoray_admin.password_hash')),
            'sigoray_admin_since' => now()->timestamp,
        ];
    }

    public function test_feed_tidak_bisa_diakses_tanpa_login_admin(): void
    {
        $this->getJson('/admin/notifications/feed')->assertUnauthorized();
    }

    public function test_notifikasi_tidak_duplikat_dan_bisa_ditandai_dibaca(): void
    {
        $service = app(AdminNotificationRecorder::class);
        $service->record('booking:test-1:created', 'BOOKING_NEW', 'Pemesanan Baru', 'Booking uji', 'receipt_long', '/admin/booking');
        $service->record('booking:test-1:created', 'BOOKING_NEW', 'Pemesanan Baru', 'Booking uji', 'receipt_long', '/admin/booking');
        $this->assertDatabaseCount('admin_notifications', 1);

        $feed = $this->withSession($this->adminSession())->getJson('/admin/notifications/feed')
            ->assertOk()->assertJsonPath('unread_count', 1);
        $id = $feed->json('notifications.0.id');
        $this->withSession($this->adminSession())->postJson('/admin/notifications/' . $id . '/read')
            ->assertOk();
        $this->withSession($this->adminSession())->getJson('/admin/notifications/feed')
            ->assertOk()->assertJsonPath('unread_count', 0);
    }

    public function test_tandai_semua_dibaca(): void
    {
        $service = app(AdminNotificationRecorder::class);
        foreach ([1, 2] as $id) {
            $service->record('feedback:test-' . $id, 'FEEDBACK_NEW', 'Pesan Baru', 'Pesan uji', 'mail', '/admin/saran-masukan');
        }
        $this->withSession($this->adminSession())->postJson('/admin/notifications/read-all')->assertOk();
        $this->withSession($this->adminSession())->getJson('/admin/notifications/feed')
            ->assertOk()->assertJsonPath('unread_count', 0);
    }
}
