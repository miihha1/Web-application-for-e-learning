<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $courses = DB::table('courses')
            ->where('is_public', false)
            ->whereNull('enroll_code')
            ->get(['id']);

        foreach ($courses as $course) {
            DB::table('courses')
                ->where('id', $course->id)
                ->update(['enroll_code' => Str::upper(Str::random(8))]);
        }
    }

    public function down(): void
    {
        // no-op
    }
};
