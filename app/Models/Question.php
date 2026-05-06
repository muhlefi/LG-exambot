<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    protected static function booted()
    {
        static::deleting(function ($question) {
            if ($question->question_image) {
                \Illuminate\Support\Facades\Storage::delete($question->question_image);
            }
        });
    }

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
 
    public function getFormattedTextAttribute()
    {
        $text = $this->question_text;
        
        // Ensure tables have a blank line before them
        $text = preg_replace('/([^\n])\n\|/', "$1\n\n|", $text);

        $converter = new \League\CommonMark\GithubFlavoredMarkdownConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);
        $text = $converter->convert($text)->getContent();
        $text = str_replace('<table>', '<table class="markdown-table">', $text);
 
        return preg_replace_callback(
            ['/\[GAMBAR: (.*?)\]/i', '/\[DIAGRAM: (.*?)\]/i'],
            function ($matches) {
                $description = trim($matches[1]);
                $isDiagram = Str::contains($matches[0], 'DIAGRAM');
                
                $baseUrl = "https://image.pollinations.ai/prompt/";
                // Menambahkan style agar hasil lebih edukatif dan bersih
                $style = ", professional educational style, flat vector illustration, minimalist, white background, clear lines, high resolution";
                $context = $isDiagram ? "detailed schematic diagram of " : "clear educational illustration of ";
                $url = $baseUrl . urlencode($context . $description . $style) . "?width=600&height=400&nologo=true&seed=" . md5($description);
 
                $label = $isDiagram ? "📊 Struktur Diagram" : "🖼️ Ilustrasi Gambar";
                $colorClass = $isDiagram ? "bg-honey/10 border-honey/20 text-honey-dark" : "bg-limewash border-fern/20 text-fern";
 
                return '
                    <div class="my-6 overflow-hidden rounded-[2rem] border-2 ' . $colorClass . ' shadow-sm">
                        <div class="p-4 bg-white/50 border-b border-inherit">
                            <span class="text-[10px] font-black uppercase tracking-widest block">' . $label . '</span>
                            <p class="mt-1 text-[11px] font-bold opacity-70 italic">' . htmlspecialchars($description) . '</p>
                        </div>
                        <div class="p-2 bg-white">
                            <img src="' . $url . '" alt="' . htmlspecialchars($description) . '" class="w-full h-auto rounded-[1.5rem] shadow-inner" loading="lazy" />
                        </div>
                    </div>';
            },
            $text
        );
    }

    public function getPdfFormattedTextAttribute()
    {
        $text = $this->question_text;

        // 1. Render LaTeX to Images BEFORE Markdown
        // Block Math
        $text = preg_replace_callback('/(?:\$\$(.*?)\$\$|\\\[(.*?)\\\]|\\\\begin\{(?:equation|gather|align)\}(.*?)\\\\end\{(?:equation|gather|align)\})/s', function ($matches) {
            $latex = trim($matches[1] ?: ($matches[2] ?: ($matches[3] ?? '')));
            if (empty($latex)) return '';
            $url = 'https://latex.codecogs.com/png.latex?\dpi{150}\bg_white ' . urlencode($latex);
            $base64 = $this->imageToBase64($url);
            if (empty($base64)) {
                return '<div style="text-align:center; margin:15px 0; font-family:serif;">$$' . htmlspecialchars($latex) . '$$</div>';
            }
            return '<div style="text-align:center; margin:15px 0;"><img src="' . $base64 . '" style="max-width:100%" /></div>';
        }, $text);

        // Inline Math
        $text = preg_replace_callback('/(?:\$([^\$]+)\$|\\\((.*?)\\\))/s', function ($matches) {
            $latex = trim($matches[1] ?: ($matches[2] ?? ''));
            if (empty($latex)) return '';
            $url = 'https://latex.codecogs.com/png.latex?\inline&space;\dpi{150}\bg_white ' . urlencode($latex);
            $base64 = $this->imageToBase64($url);
            if (empty($base64)) {
                return '<span style="font-family:serif; font-style:italic;">$' . htmlspecialchars($latex) . '$</span>';
            }
            return '<img src="' . $base64 . '" style="vertical-align:middle; margin:0 2px; height:14px" />';
        }, $text);

        // 2. Apply Markdown with GFM (Tables)
        $text = preg_replace('/([^\n])\n\|/', "$1\n\n|", $text);
        
        // Use the manual converter to ensure GFM (tables)
        $converter = new \League\CommonMark\GithubFlavoredMarkdownConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);
        $text = $converter->convert($text)->getContent();

        // 3. Render AI Images/Diagrams
        return preg_replace_callback(
            ['/\[GAMBAR: (.*?)\]/i', '/\[DIAGRAM: (.*?)\]/i'],
            function ($matches) {
                $description = trim($matches[1]);
                $isDiagram = Str::contains($matches[0], 'DIAGRAM');
                
                $baseUrl = "https://image.pollinations.ai/prompt/";
                $style = ", professional educational style, flat vector, minimalist, white background, clear lines";
                $context = $isDiagram ? "detailed schematic diagram of " : "clear educational illustration of ";
                $url = $baseUrl . urlencode($context . $description . $style) . "?width=600&height=400&nologo=true";
                $base64 = $this->imageToBase64($url);

                return '
                    <div style="margin: 20px 0; border: 1px solid #ddd; border-radius: 10px; padding: 10px; background: #f9f9f9;">
                        <div style="font-size: 9px; font-weight: bold; color: #666; margin-bottom: 5px;">' . ($isDiagram ? "DIAGRAM" : "ILUSTRASI") . ': ' . htmlspecialchars($description) . '</div>
                        <img src="' . $base64 . '" style="width: 100%; max-width: 500px; display: block; margin: 0 auto; border-radius: 5px;" />
                    </div>';
            },
            $text
        );
    }

    private function imageToBase64($url)
    {
        $filename = 'latex_' . md5($url) . '.png';
        if (str_contains($url, 'pollinations.ai')) $filename = 'ai_' . md5($url) . '.jpg';
        
        $storagePath = 'public/latex/' . $filename;
        $absolutePath = storage_path('app/' . $storagePath);

        // Pastikan direktori ada
        if (!file_exists(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0755, true);
        }

        // Gunakan cache agar tidak cek file_exists terus menerus (opsional, tapi aman)
        if (!file_exists($absolutePath)) {
            try {
                $response = \Illuminate\Support\Facades\Http::withoutVerifying()->timeout(20)->get($url);
                if ($response->successful()) {
                    file_put_contents($absolutePath, $response->body());
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to download image for Export: " . $url . " - " . $e->getMessage());
                return ''; 
            }
        }

        return file_exists($absolutePath) ? $absolutePath : '';
    }
}
