<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'participant_id',
        'question_id',
        'selected_answer',
        'is_correct',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    public function participant()
    {
        return $this->belongsTo(QuizParticipant::class, 'participant_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
