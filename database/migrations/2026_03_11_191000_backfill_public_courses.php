<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('courses')
            ->whereNull('teacher_id')
            ->update(['is_public' => true, 'enroll_code' => null]);
    }

    public function down(): void
    {
        // no-op
    }
};
