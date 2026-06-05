<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestResult extends Model
{
    protected $fillable = [
        'test_id',
        'user_id',
        'attempt',
        'score',
        'max_score',
        'percent',
        'passed',
        'answers',
    ];

    protected $casts = [
        'answers' => 'array',
        'passed' => 'boolean',
    ];

    public function test() { return $this->belongsTo(Test::class); }
    public function user() { return $this->belongsTo(User::class); }
}
