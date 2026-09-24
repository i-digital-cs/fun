<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('slug', 180)->nullable()->after('title');
        });

        $used = [];

        foreach (DB::table('events')->select(['id', 'title'])->orderBy('id')->cursor() as $event) {
            $base = Str::slug((string) $event->title);
            if ($base === '') {
                $base = 'event';
            }
            $base = Str::limit($base, 140, '');

            $slug = $base;
            if (isset($used[$slug])) {
                $slug = Str::limit($base, 130, '').'-'.$event->id;
            }
            $n = 2;
            while (isset($used[$slug])) {
                $slug = Str::limit($base, 120, '').'-'.$event->id.'-'.$n;
                $n++;
            }

            $used[$slug] = true;
            DB::table('events')->where('id', $event->id)->update(['slug' => $slug]);
        }

        Schema::table('events', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
