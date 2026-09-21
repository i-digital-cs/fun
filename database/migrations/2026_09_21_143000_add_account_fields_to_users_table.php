<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status', 32)->default('pending_documents')->after('email');
            $table->string('platform_role', 32)->nullable()->after('status');
            $table->string('google_id')->nullable()->unique()->after('platform_role');
            $table->string('locale', 5)->default('en')->after('google_id');
            $table->string('profile_image_path')->nullable()->after('locale');
            $table->timestamp('deactivated_at')->nullable()->after('profile_image_path');
            $table->foreignId('deactivated_by')->nullable()->after('deactivated_at')->constrained('users')->nullOnDelete();
            $table->string('deactivation_reason')->nullable()->after('deactivated_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deactivated_by');
            $table->dropColumn([
                'status',
                'platform_role',
                'google_id',
                'locale',
                'profile_image_path',
                'deactivated_at',
                'deactivation_reason',
            ]);
        });
    }
};
