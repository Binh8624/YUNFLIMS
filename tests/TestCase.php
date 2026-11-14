<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUpTraits()
    {
        $this->assertIsolatedDatabaseConfiguration();
        $usesDatabase = array_intersect(
            [
                \Illuminate\Foundation\Testing\DatabaseMigrations::class,
                \Illuminate\Foundation\Testing\DatabaseTransactions::class,
                \Illuminate\Foundation\Testing\DatabaseTruncation::class,
                \Illuminate\Foundation\Testing\RefreshDatabase::class,
            ],
            array_values(class_uses_recursive(static::class))
        ) !== [];

        if ($usesDatabase) {
            $connection = DB::connection();
            if ($connection->getDatabaseName() !== 'yunflims_test') {
                throw new RuntimeException('Tests may only use the yunflims_test database.');
            }

            foreach (['users', 'bookings', 'booking_seats'] as $table) {
                if (Schema::hasTable($table) && DB::table($table)->exists()) {
                    throw new RuntimeException("Refusing to run tests: the yunflims_test database already contains rows in {$table}.");
                }
            }
        }

        return parent::setUpTraits();
    }

    private function assertIsolatedDatabaseConfiguration(): void
    {
        $connectionName = config('database.default');
        $connection = config("database.connections.{$connectionName}");

        if (
            env('APP_ENV') !== 'testing'
            || $connectionName !== 'mysql'
            || ($connection['database'] ?? null) !== 'yunflims_test'
            || ! empty($connection['url'])
        ) {
            throw new RuntimeException('Tests require APP_ENV=testing and the isolated yunflims_test MySQL database.');
        }
    }
}
