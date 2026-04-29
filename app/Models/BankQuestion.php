<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankQuestion extends Model
{
    protected $fillable = [
        'user_id',
        'source_question_id',
        'scope',
        'title',
        'subject',
        'topic',
        'question_type',
        'question_text',
        'answer_key',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
