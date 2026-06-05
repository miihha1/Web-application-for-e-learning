<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_results', function (Blueprint $table) {
            $table->dropUnique('test_results_test_id_user_id_unique');
            $table->unsignedInteger('attempt')->default(1)->after('user_id');
            $table->decimal('percent', 5, 2)->unsigned()->nullable()->after('max_score');
            $table->boolean('passed')->default(false)->after('percent');
        });
    }

    public function down(): void
    {
        Schema::table('test_results', function (Blueprint $table) {
            $table->unique(['test_id', 'user_id']);
            $table->dropColumn(['attempt', 'percent', 'passed']);
        });
    }
};
