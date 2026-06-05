<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('courses', 'cover_image_path')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->string('cover_image_path')->nullable()->after('enroll_code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('courses', 'cover_image_path')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('cover_image_path');
            });
        }
    }
};
