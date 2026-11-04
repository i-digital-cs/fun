<?php

namespace Tests\Feature\Marketing;

use App\Enums\Marketing\MarketingCredentialType;
use App\Enums\Marketing\MarketingPlatformCode;
use App\Models\Marketing\MarketingIntegrationCredential;
use App\Models\Marketing\MarketingPlatform;
use App\Models\User;
use App\Repositories\Marketing\MarketingCredentialRepository;
use Database\Seeders\MarketingPlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MarketingFoundationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase1_tables_exist_after_migrations(): void
    {
        foreach ([
            'marketing_platforms',
            'marketing_accounts',
            'marketing_integration_credentials',
            'marketing_budget_policies',
            'marketing_audit_logs',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table {$table}");
        }

        $this->assertFalse(Schema::hasTable('marketing_campaigns'));
        $this->assertFalse(Schema::hasTable('marketing_recommendations'));
    }

    public function test_platform_seeder_is_idempotent(): void
    {
        $this->seed(MarketingPlatformSeeder::class);
        $this->seed(MarketingPlatformSeeder::class);

        $this->assertSame(2, MarketingPlatform::query()->count());
        $this->assertNotNull(MarketingPlatform::query()->where('code', MarketingPlatformCode::Meta->value)->first());
        $this->assertNotNull(MarketingPlatform::query()->where('code', MarketingPlatformCode::TikTok->value)->first());
    }

    public function test_credentials_are_encrypted_at_rest_and_hidden(): void
    {
        $this->seed(MarketingPlatformSeeder::class);
        $admin = User::factory()->superAdmin()->create();
        $platform = MarketingPlatform::query()->where('code', 'meta')->firstOrFail();

        /** @var MarketingCredentialRepository $repo */
        $repo = app(MarketingCredentialRepository::class);
        $credential = $repo->storeEncrypted(
            platformId: (int) $platform->id,
            type: MarketingCredentialType::OauthToken,
            payload: ['access_token' => 'secret-token-value', 'refresh_token' => 'refresh-secret'],
            actor: $admin,
            scopes: ['ads_read'],
        );

        $raw = DB::table('marketing_integration_credentials')->where('id', $credential->id)->value('encrypted_payload');
        $this->assertIsString($raw);
        $this->assertStringNotContainsString('secret-token-value', $raw);

        $fresh = MarketingIntegrationCredential::query()->findOrFail($credential->id);
        $this->assertSame('secret-token-value', $fresh->encrypted_payload['access_token']);
        $this->assertArrayNotHasKey('encrypted_payload', $fresh->toArray());
    }

    public function test_account_external_id_unique_per_platform(): void
    {
        $this->seed(MarketingPlatformSeeder::class);
        $platform = MarketingPlatform::query()->where('code', 'meta')->firstOrFail();

        DB::table('marketing_accounts')->insert([
            'platform_id' => $platform->id,
            'external_account_id' => 'act_1',
            'name' => 'A',
            'currency' => 'USD',
            'status' => 'disabled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('marketing_accounts')->insert([
            'platform_id' => $platform->id,
            'external_account_id' => 'act_1',
            'name' => 'B',
            'currency' => 'USD',
            'status' => 'disabled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
