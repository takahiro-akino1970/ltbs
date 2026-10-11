<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    public function test_tests_use_an_isolated_in_memory_database(): void
    {
        $connection = DB::connection();

        $this->assertSame('sqlite', $connection->getDriverName());
        $this->assertSame(':memory:', $connection->getDatabaseName());
        $this->assertSame(1, $connection->selectOne('SELECT 1 AS value')->value);
    }
}
