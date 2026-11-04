<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Retained for Stage E4 continuity; centralized coverage lives in FactoryPersistenceGuardTest.
 */
class UserFactoryProductionGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        config(['database.default' => 'sqlite']);
        parent::tearDown();
    }

    public function test_factory_create_is_allowed_on_isolated_sqlite(): void
    {
        $this->assertSame('sqlite', config('database.default'));

        $user = User::factory()->active()->create();

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_factory_create_is_forbidden_against_protected_database(): void
    {
        config([
            'database.connections.mysql.driver' => 'mysql',
            'database.connections.mysql.database' => 'd14098_fun',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('protected database [d14098_fun]');

        User::factory()->connection('mysql')->create();
    }
}
