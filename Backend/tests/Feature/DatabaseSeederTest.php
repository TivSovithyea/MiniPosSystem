<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_catalog_has_expected_record_volumes(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 21);
        $this->assertDatabaseCount('categories', 15);
        $this->assertDatabaseCount('products', 264);
    }
}
