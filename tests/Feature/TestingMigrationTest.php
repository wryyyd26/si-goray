<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TestingMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_database_mysql_berhasil(): void
    {
        $this->assertSame(
            'gor_reservation_testing',
            DB::connection()->getDatabaseName()
        );

        $this->assertTrue(
            Schema::hasTable('users')
        );

        $this->assertTrue(
            Schema::hasTable('events')
        );

        $this->assertTrue(
            Schema::hasTable('event_schedules')
        );

        $this->assertTrue(
            Schema::hasTable('bookings')
        );

        $this->assertTrue(
            Schema::hasTable('payment_transactions')
        );

        $this->assertTrue(
            Schema::hasTable('personal_access_tokens')
        );
    }
}
