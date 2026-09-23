<?php

use App\Support\UserPublicId;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('public_id', 32)->nullable()->unique()->after('id');
        });

        $number = UserPublicId::START_NUMBER;
        $users = DB::table('users')->whereNull('public_id')->orderBy('id')->get(['id']);
        foreach ($users as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'public_id' => UserPublicId::format($number),
            ]);
            $number++;
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
