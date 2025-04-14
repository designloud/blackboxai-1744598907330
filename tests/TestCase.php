<?php

namespace VendorName\Conversa\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use VendorName\Conversa\ConversaServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Additional test setup
    }

    protected function getPackageProviders($app)
    {
        return [
            ConversaServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Import the package migrations
        $migrations = [
            __DIR__ . '/../database/migrations/2024_01_01_000001_create_conversa_spaces_table.php',
            __DIR__ . '/../database/migrations/2024_01_01_000002_create_conversa_messages_table.php',
            __DIR__ . '/../database/migrations/2024_01_01_000003_create_conversa_threads_table.php',
        ];

        foreach ($migrations as $migration) {
            $instance = include $migration;
            $instance->up();
        }
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
    }
}
