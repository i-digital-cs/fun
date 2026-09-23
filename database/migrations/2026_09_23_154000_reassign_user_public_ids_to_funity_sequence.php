<?php

use App\Support\UserPublicId;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $number = UserPublicId::START_NUMBER;

        $users = DB::table('users')->orderBy('id')->get(['id']);

        foreach ($users as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'public_id' => UserPublicId::format($number),
            ]);
            $number++;
        }
    }

    public function down(): void
    {
        // Irreversible reassignment of sequential public IDs.
    }
};
