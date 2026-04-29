<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id',
        'title',
        'quiz_code',
        'duration',
        'is_random_question',
        'is_random_answer',
        'visibility',
        'password',
        'max_participants',
        'status',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'is_random_question' => 'boolean',
            'is_random_answer' => 'boolean',
        ];
    }

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function participants()
    {
        return $this->hasMany(QuizParticipant::class)->orderBy('rank')->orderByDesc('score');
    }
}
