<?php

use App\Models\EventMember;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_members', function (Blueprint $table) {
            $table->string('ticket_code', 16)->nullable()->after('role');
        });

        EventMember::query()->orderBy('id')->each(function (EventMember $member) {
            if (filled($member->ticket_code)) {
                return;
            }

            $member->forceFill([
                'ticket_code' => EventMember::generateUniqueTicketCode(),
            ])->saveQuietly();
        });

        Schema::table('event_members', function (Blueprint $table) {
            $table->string('ticket_code', 16)->nullable(false)->change();
            $table->unique('ticket_code');
        });
    }

    public function down(): void
    {
        Schema::table('event_members', function (Blueprint $table) {
            $table->dropUnique(['ticket_code']);
            $table->dropColumn('ticket_code');
        });
    }
};
