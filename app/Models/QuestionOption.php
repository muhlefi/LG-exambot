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

        // Render LaTeX to Images for PDF (Base64 approach)
        return preg_replace_callback('/\$([^\$]+)\$/', function ($matches) {
            $latex = rawurlencode(trim($matches[1]));
            $url = "https://latex.codecogs.com/png.latex?\dpi{150}\bg_white " . $latex;
            
            try {
                $ctx = stream_context_create(['http' => ['timeout' => 3]]);
                $data = @file_get_contents($url, false, $ctx);
                if ($data) {
                    return '<img src="data:image/png;base64,' . base64_encode($data) . '" style="vertical-align:middle; margin:0 2px; height:12px;">';
                }
            } catch (\Exception $e) {}

            return '$' . $matches[1] . '$';
        }, $text);
    }
}
