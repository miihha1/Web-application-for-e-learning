<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->unsignedSmallInteger('time_limit_minutes')->nullable()->after('pass_percent');
            $table->unsignedSmallInteger('max_attempts')->nullable()->after('time_limit_minutes');
            $table->unsignedSmallInteger('cooldown_minutes')->default(0)->after('max_attempts');
            $table->boolean('randomize_questions')->default(false)->after('cooldown_minutes');
            $table->boolean('randomize_options')->default(false)->after('randomize_questions');
        });
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn([
                'time_limit_minutes',
                'max_attempts',
                'cooldown_minutes',
                'randomize_questions',
                'randomize_options',
            ]);
        });
    }
};
