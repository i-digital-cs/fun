<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat V2 extensions — idempotency, replies, media, DM emoji reactions.
 *
 * Do NOT run on production until the owner explicitly approves activation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_messages', function (Blueprint $table) {
            $table->string('client_uuid', 36)->nullable()->after('id');
            $table->foreignId('reply_to_id')->nullable()->after('user_id')
                ->constrained('event_messages')->nullOnDelete();
            $table->string('message_type', 16)->default('text')->after('body');
            $table->string('media_path')->nullable()->after('message_type');
            $table->string('media_mime', 64)->nullable()->after('media_path');
            $table->unsignedInteger('media_bytes')->nullable()->after('media_mime');
            $table->unsignedInteger('media_duration_ms')->nullable()->after('media_bytes');
            $table->unique(['event_id', 'user_id', 'client_uuid'], 'event_messages_client_uuid_unique');
        });

        Schema::table('direct_messages', function (Blueprint $table) {
            $table->string('client_uuid', 36)->nullable()->after('id');
            $table->foreignId('reply_to_id')->nullable()->after('user_id')
                ->constrained('direct_messages')->nullOnDelete();
            $table->string('message_type', 16)->default('text')->after('body');
            $table->string('media_path')->nullable()->after('message_type');
            $table->string('media_mime', 64)->nullable()->after('media_path');
            $table->unsignedInteger('media_bytes')->nullable()->after('media_mime');
            $table->unsignedInteger('media_duration_ms')->nullable()->after('media_bytes');
            $table->unique(['conversation_id', 'user_id', 'client_uuid'], 'direct_messages_client_uuid_unique');
        });

        Schema::create('direct_message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('direct_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('emoji', 16);
            $table->timestamps();
            $table->unique(['message_id', 'user_id', 'emoji']);
            $table->index(['message_id', 'emoji']);
        });

        // Expand event reactions beyond like/dislike while keeping legacy rows.
        Schema::table('message_reactions', function (Blueprint $table) {
            $table->string('emoji', 16)->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('message_reactions', function (Blueprint $table) {
            $table->dropColumn('emoji');
        });

        Schema::dropIfExists('direct_message_reactions');

        Schema::table('direct_messages', function (Blueprint $table) {
            $table->dropUnique('direct_messages_client_uuid_unique');
            $table->dropConstrainedForeignId('reply_to_id');
            $table->dropColumn([
                'client_uuid',
                'message_type',
                'media_path',
                'media_mime',
                'media_bytes',
                'media_duration_ms',
            ]);
        });

        Schema::table('event_messages', function (Blueprint $table) {
            $table->dropUnique('event_messages_client_uuid_unique');
            $table->dropConstrainedForeignId('reply_to_id');
            $table->dropColumn([
                'client_uuid',
                'message_type',
                'media_path',
                'media_mime',
                'media_bytes',
                'media_duration_ms',
            ]);
        });
    }
};
