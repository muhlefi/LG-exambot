<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TutorialLesson extends Model
{
    protected $fillable = [
        'title',
        'category',
        'video_url',
        'body',
        'sort_order',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }
}
