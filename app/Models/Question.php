<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id',
        'question_structure_id',
        'question_type',
        'question_text',
        'question_image',
        'explanation',
        'difficulty',
        'cognitive_level',
        'answer_key',
        'sort_order',
    ];

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function structure()
    {
        return $this->belongsTo(QuestionStructure::class, 'question_structure_id');
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort_order');
    }

    public function blueprint()
    {
        return $this->hasOne(QuestionBlueprint::class);
    }
}
