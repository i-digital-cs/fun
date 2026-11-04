<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 64);
            $table->boolean('is_enabled')->default(true);
            $table->json('capabilities_json')->nullable();
            $table->timestamps();
        });

        Schema::create('marketing_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_id')->constrained('marketing_platforms')->cascadeOnDelete();
            $table->string('external_account_id', 64);
            $table->string('name');
            $table->char('currency', 3)->default('USD');
            $table->string('timezone', 64)->nullable();
            $table->string('status', 32)->default('disabled');
            $table->timestamp('last_synced_at')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->unique(['platform_id', 'external_account_id'], 'marketing_accounts_platform_external_unique');
            $table->index(['status']);
            $table->index(['platform_id', 'status']);
        });

        Schema::create('marketing_integration_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_account_id')
                ->nullable()
                ->constrained('marketing_accounts')
                ->nullOnDelete();
            $table->foreignId('platform_id')->constrained('marketing_platforms')->cascadeOnDelete();
            $table->string('credential_type', 32);
            $table->text('encrypted_payload');
            $table->json('scopes_json')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('rotated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // MariaDB identifier limit is 64 chars; avoid auto-generated long names.
            $table->index(['platform_id', 'credential_type'], 'mkt_cred_platform_type_idx');
        });

        Schema::create('marketing_budget_policies', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type', 32);
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->decimal('max_daily_spend', 14, 2)->nullable();
            $table->decimal('max_monthly_spend', 14, 2)->nullable();
            $table->decimal('max_budget_increase_pct', 5, 2)->nullable();
            $table->decimal('max_budget_decrease_pct', 5, 2)->nullable();
            $table->boolean('allow_status_pause')->default(true);
            $table->boolean('allow_status_resume')->default(true);
            $table->boolean('allow_create')->default(false);
            $table->char('currency', 3)->default('USD');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['scope_type', 'scope_id']);
            $table->index(['active']);
        });

        Schema::create('marketing_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64);
            $table->string('subject_type', 64)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('correlation_id', 64)->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['created_at']);
            $table->index(['actor_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_audit_logs');
        Schema::dropIfExists('marketing_budget_policies');
        Schema::dropIfExists('marketing_integration_credentials');
        Schema::dropIfExists('marketing_accounts');
        Schema::dropIfExists('marketing_platforms');
    }
};
