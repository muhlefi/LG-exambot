<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'teacher_name',
        'school_name',
        'logo_path',
        'education_level',
        'learning_phase',
        'class_level',
        'semester',
        'academic_year',
        'subject',
        'topic',
        'subtopic',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function structures()
    {
        return $this->hasMany(QuestionStructure::class)->orderBy('sort_order');
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    public function exportLogs()
    {
        return $this->hasMany(ExportLog::class);
    }

    public function totalPlannedQuestions(): int
    {
        return (int) $this->structures->sum(fn (QuestionStructure $structure) => $structure->total_questions);
    }
}
