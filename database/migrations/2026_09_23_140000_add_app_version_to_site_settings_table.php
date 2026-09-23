<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('android_latest_version', 32)->default('1.0.0')->after('logo_disk');
            $table->string('android_min_version', 32)->default('1.0.0')->after('android_latest_version');
            $table->string('ios_latest_version', 32)->default('1.0.0')->after('android_min_version');
            $table->string('ios_min_version', 32)->default('1.0.0')->after('ios_latest_version');
            $table->boolean('force_update')->default(false)->after('ios_min_version');
            $table->string('android_store_url', 500)->nullable()->after('force_update');
            $table->string('ios_store_url', 500)->nullable()->after('android_store_url');
            $table->string('update_message_en', 500)->nullable()->after('ios_store_url');
            $table->string('update_message_ar', 500)->nullable()->after('update_message_en');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'android_latest_version',
                'android_min_version',
                'ios_latest_version',
                'ios_min_version',
                'force_update',
                'android_store_url',
                'ios_store_url',
                'update_message_en',
                'update_message_ar',
            ]);
        });
    }
};
