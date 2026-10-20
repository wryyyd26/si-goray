<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TestingDatabaseConnectionTest extends TestCase
{
    public function test_database_testing_menggunakan_mysql_khusus(): void
    {
        $this->assertSame('testing', app()->environment());

        $this->assertSame('mysql', config('database.default'));

        $this->assertSame(
            'gor_reservation_testing',
            DB::connection()->getDatabaseName()
        );
    }
}
