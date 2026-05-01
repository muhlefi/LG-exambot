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

        // 1. Render LaTeX to Images
        $text = preg_replace_callback('/(?:\$([^\$]+)\$|\\\((.*?)\\\))/s', function ($matches) {
            $latex = trim($matches[1] ?: ($matches[2] ?? ''));
            if (empty($latex)) return '';
            $url = 'https://latex.codecogs.com/png.latex?\inline&space;\dpi{150}\bg_white ' . urlencode($latex);
            $base64 = $this->imageToBase64($url);
            return '<img src="' . $base64 . '" style="vertical-align:middle; margin:0 2px; height:12px;">';
        }, $text);

        // 2. Render Markdown
        // Ensure blank line before tables
        $text = preg_replace('/([^\n])\n\|/', "$1\n\n|", $text);
        $converter = new \League\CommonMark\GithubFlavoredMarkdownConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);
        $text = $converter->convert($text)->getContent();

        // 3. Render AI Images (if any in options)
        return preg_replace_callback(
            ['/\[GAMBAR: (.*?)\]/i', '/\[DIAGRAM: (.*?)\]/i'],
            function ($matches) {
                $description = trim($matches[1]);
                $url = "https://image.pollinations.ai/prompt/" . urlencode($description) . "?width=300&height=200&nologo=true";
                $base64 = $this->imageToBase64($url);
                return '<img src="' . $base64 . '" style="max-height: 100px; display: block; margin: 5px 0;">';
            },
            $text
        );
    }

    private function imageToBase64($url)
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()->timeout(15)->get($url);
            if ($response->successful()) {
                $type = 'image/png';
                if (str_contains($url, 'pollinations.ai')) $type = 'image/jpeg';
                return 'data:' . $type . ';base64,' . base64_encode($response->body());
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to fetch image for PDF: " . $url . " - " . $e->getMessage());
        }
        return $url;
    }
}
