<?php

namespace Tests;

use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
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

        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class))) {
            $this->seed(RoleAndPermissionSeeder::class);
        }
    }
}
