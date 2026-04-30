<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'option_label',
        'option_text',
        'option_image',
        'is_correct',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
    
    public function getPdfFormattedTextAttribute()
    {
        $text = $this->option_text;

        // Render LaTeX to Images for PDF
        // Inline Math $ ... $ (options usually don't have block math)
        return preg_replace_callback('/\$([^\$]+)\$/', function ($matches) {
            $latex = urlencode(trim($matches[1]));
            return '<img src="https://latex.codecogs.com/png.latex?\inline&space;\dpi{150}\bg_white ' . $latex . '" style="vertical-align:middle; margin:0 2px; height:12px;">';
        }, $text);
    }
}
