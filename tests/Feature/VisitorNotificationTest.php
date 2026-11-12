<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VisitorNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_read_notifications(): void
    {
        $this->getJson(route('pengunjung.notifications.index'))->assertUnauthorized();
    }

    public function test_notifications_are_isolated_by_user_and_read_status_persists(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $mine = VisitorNotification::create([
            'user_id' => $a->id, 'event_key' => 'test:a', 'category' => 'all',
            'title' => 'A', 'message' => 'Pesan A',
        ]);
        $other = VisitorNotification::create([
            'user_id' => $b->id, 'event_key' => 'test:b', 'category' => 'all',
            'title' => 'B', 'message' => 'Pesan B',
        ]);

        $this->actingAs($a, 'web')
            ->getJson(route('pengunjung.notifications.index'))
            ->assertOk()->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.id', (string) $mine->id);

        $this->postJson(route('pengunjung.notifications.read', $other->id))->assertNotFound();
        $this->postJson(route('pengunjung.notifications.read', $mine->id))->assertOk();
        $this->getJson(route('pengunjung.notifications.index'))->assertJsonPath('unread_count', 0);
        $this->assertDatabaseHas('visitor_notifications', ['id' => $other->id, 'read_at' => null]);
    }

    public function test_welcome_notification_is_created_once_after_first_otp_verification(): void
    {
        $user = User::factory()->create(['status_otp' => false]);
        $user->status_otp = true;
        $user->save();
        $user->refresh();
        $user->email_verified_at = now();
        $user->save();
        $this->assertSame(1, VisitorNotification::where('user_id', $user->id)
            ->where('event_key', 'welcome:' . $user->id)->count());
    }
}
