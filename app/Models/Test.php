<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'description',
        'pass_percent',
        'time_limit_minutes',
        'max_attempts',
        'cooldown_minutes',
        'randomize_questions',
        'randomize_options',
        'questions',
    ];

    protected $casts = [
        'questions' => 'array',
        'randomize_questions' => 'boolean',
        'randomize_options' => 'boolean',
    ];

    public function course() { return $this->belongsTo(Course::class); }
    public function questions() { return $this->hasMany(Question::class)->orderBy('order'); }
    public function results() { return $this->hasMany(TestResult::class); }
}
