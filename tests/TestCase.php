<?php

namespace Tests;

use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * When true, setUp will not seed RoleAndPermissionSeeder.
     * Set by RoleAndPermissionSeederSecurityTest to get a clean DB for production-behavior tests.
     */
    public static bool $skipRoleAndPermissionSeeder = false;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $dbPath = dirname(__DIR__).'/database/testing.sqlite';
        if (file_exists(dirname($dbPath))) {
            touch($dbPath);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (
            in_array(RefreshDatabase::class, class_uses_recursive(static::class))
            && ! static::$skipRoleAndPermissionSeeder
        ) {
            $this->seed(RoleAndPermissionSeeder::class);
        }
    }
}
