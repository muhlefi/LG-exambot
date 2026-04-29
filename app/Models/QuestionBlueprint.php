<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionBlueprint extends Model
{
    use HasFactory;

    protected $table = 'blueprints';

    protected $fillable = [
        'question_id',
        'competency',
        'indicator',
        'material',
        'cognitive_dimension',
        'question_type',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
