<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id',
        'name',
        'question_type',
        'option_count',
        'easy_count',
        'medium_count',
        'hard_count',
        'cognitive_levels',
        'has_question_image',
        'has_option_image',
        'has_diagram',
        'has_table',
        'sort_order',
    ];

    protected $appends = ['total_questions'];

    protected function casts(): array
    {
        return [
            'cognitive_levels' => 'array',
            'has_question_image' => 'boolean',
            'has_option_image' => 'boolean',
            'has_diagram' => 'boolean',
            'has_table' => 'boolean',
        ];
    }

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function getTotalQuestionsAttribute(): int
    {
        return (int) $this->easy_count + (int) $this->medium_count + (int) $this->hard_count;
    }
}
