<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['test_id', 'text', 'order'];

    public function test() { return $this->belongsTo(Test::class); }
    public function options() { return $this->hasMany(AnswerOption::class); }

}
