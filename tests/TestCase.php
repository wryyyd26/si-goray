<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
  protected function setUp(): void
{
    parent::setUp();

    if (
        config('database.default') !== 'mysql' ||
        \Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'gor_reservation_testing'
    ) {
        throw new \RuntimeException(
            'Pengujian dihentikan: database bukan gor_reservation_testing.'
        );
    }
}  //
}
