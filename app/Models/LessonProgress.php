<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    // Dôležité: uveďte tu polia, ktoré reálne existujú v migrácii lesson_progress.
    // Ak sú tam zatiaľ len user_id a lesson_id, nechajte to tak.
    protected $fillable = ['user_id', 'lesson_id', 'progress', 'completed_at'];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
