<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\City;
use App\Models\User;
use App\Services\Auth\GoogleAuthService;
use App\Services\Auth\RegisterUserService;
use App\Support\Factories\FactoryPersistenceGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;

class FactoryPersistenceGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Ensure later tests always see the forced sqlite default.
        config(['database.default' => 'sqlite']);
        app()['env'] = 'testing';

        parent::tearDown();
    }

    public function test_user_category_and_city_factories_persist_on_sqlite(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));

        $user = User::factory()->active()->create();
        $category = Category::factory()->create();
        $city = City::factory()->create();

        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('cities', ['id' => $city->id]);
    }

    public function test_factory_make_does_not_persist_and_is_allowed(): void
    {
        $user = User::factory()->make(['email' => 'make-only@example.com']);
        $category = Category::factory()->make();
        $city = City::factory()->make();

        $this->assertFalse($user->exists);
        $this->assertFalse($category->exists);
        $this->assertFalse($city->exists);
        $this->assertDatabaseMissing('users', ['email' => 'make-only@example.com']);
    }

    public function test_guard_blocks_protected_database_name_without_writing(): void
    {
        config([
            'database.connections.mysql.driver' => 'mysql',
            'database.connections.mysql.database' => 'd14098_fun',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('protected database [d14098_fun]');

        FactoryPersistenceGuard::assertPersistenceAllowed('mysql');
    }

    public function test_user_factory_create_blocked_when_connection_targets_protected_db(): void
    {
        config([
            'database.connections.mysql.driver' => 'mysql',
            'database.connections.mysql.database' => 'd14098_fun',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('protected database [d14098_fun]');

        // Uses factory connection override — never switches the test default off sqlite.
        User::factory()->connection('mysql')->create();
    }

    public function test_category_and_city_factory_create_blocked_against_protected_db(): void
    {
        config([
            'database.connections.mysql.driver' => 'mysql',
            'database.connections.mysql.database' => 'd14098_fun',
        ]);

        try {
            Category::factory()->connection('mysql')->create();
            $this->fail('CategoryFactory create should have been blocked');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('protected database [d14098_fun]', $e->getMessage());
        }

        try {
            City::factory()->connection('mysql')->create();
            $this->fail('CityFactory create should have been blocked');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('protected database [d14098_fun]', $e->getMessage());
        }
    }

    public function test_production_env_blocks_non_sqlite_even_if_db_renamed(): void
    {
        app()['env'] = 'production';

        config([
            'database.connections.mysql.driver' => 'mysql',
            'database.connections.mysql.database' => 'renamed_prod_db',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('forbidden in the production environment');

        FactoryPersistenceGuard::assertPersistenceAllowed('mysql');
    }

    public function test_sqlite_allowed_even_when_app_env_is_production(): void
    {
        app()['env'] = 'production';

        FactoryPersistenceGuard::assertPersistenceAllowed('sqlite');

        $user = User::factory()->make();
        $this->assertNotNull($user->email);
    }

    public function test_fails_closed_when_database_name_missing(): void
    {
        config([
            'database.connections.broken_factory' => [
                'driver' => 'mysql',
                'database' => '',
                'url' => null,
            ],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('database name for [broken_factory] could not be established');

        FactoryPersistenceGuard::assertPersistenceAllowed('broken_factory');
    }

    public function test_fails_closed_when_connection_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not configured');

        FactoryPersistenceGuard::assertPersistenceAllowed('definitely_missing_connection');
    }

    public function test_register_user_service_does_not_use_factories(): void
    {
        $source = file_get_contents((new ReflectionClass(RegisterUserService::class))->getFileName());
        $this->assertIsString($source);
        $this->assertStringNotContainsString('::factory(', $source);
        $this->assertStringContainsString('new User(', $source);
    }

    public function test_google_auth_service_does_not_use_factories(): void
    {
        $source = file_get_contents((new ReflectionClass(GoogleAuthService::class))->getFileName());
        $this->assertIsString($source);
        $this->assertStringNotContainsString('::factory(', $source);
        $this->assertStringContainsString('new User(', $source);
    }
}
