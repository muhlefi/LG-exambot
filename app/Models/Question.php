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
        $text = Str::markdown($this->question_text);
 
        return preg_replace_callback(
            ['/\[GAMBAR: (.*?)\]/i', '/\[DIAGRAM: (.*?)\]/i'],
            function ($matches) {
                $description = trim($matches[1]);
                $safeDescription = urlencode($description);
                $isDiagram = Str::contains($matches[0], 'DIAGRAM');
                
                $baseUrl = "https://image.pollinations.ai/prompt/";
                $context = $isDiagram ? "detailed educational diagram about " : "clear educational illustration of ";
                $url = $baseUrl . urlencode($context . $description) . "?width=600&height=400&nologo=true&seed=" . md5($description);
 
                $label = $isDiagram ? "📊 Struktur Diagram" : "🖼️ Ilustrasi Gambar";
                $colorClass = $isDiagram ? "bg-honey/10 border-honey/20 text-honey-dark" : "bg-limewash border-fern/20 text-fern";
 
                return '
                    <div class="my-6 overflow-hidden rounded-[2rem] border-2 ' . $colorClass . ' shadow-sm">
                        <div class="p-4 bg-white/50 border-b border-inherit">
                            <span class="text-[10px] font-black uppercase tracking-widest block">' . $label . '</span>
                            <p class="mt-1 text-[11px] font-bold opacity-70 italic">' . htmlspecialchars($description) . '</p>
                        </div>
                        <div class="p-2 bg-white">
                            <img src="' . $url . '" alt="' . htmlspecialchars($description) . '" class="w-full h-auto rounded-[1.5rem] shadow-inner" loading="lazy">
                        </div>
                    </div>';
            },
            $text
        );
    }

    public function getPdfFormattedTextAttribute()
    {
        // DomPDF needs simpler HTML and absolute URLs
        $text = Str::markdown($this->question_text);

        return preg_replace_callback(
            ['/\[GAMBAR: (.*?)\]/i', '/\[DIAGRAM: (.*?)\]/i'],
            function ($matches) {
                $description = trim($matches[1]);
                $isDiagram = Str::contains($matches[0], 'DIAGRAM');
                
                $baseUrl = "https://image.pollinations.ai/prompt/";
                $context = $isDiagram ? "detailed educational diagram about " : "clear educational illustration of ";
                $url = $baseUrl . urlencode($context . $description) . "?width=600&height=400&nologo=true";

                return '
                    <div style="margin: 20px 0; border: 1px solid #ddd; border-radius: 10px; padding: 10px; background: #f9f9f9;">
                        <div style="font-size: 9px; font-weight: bold; color: #666; margin-bottom: 5px;">' . ($isDiagram ? "DIAGRAM" : "ILUSTRASI") . ': ' . htmlspecialchars($description) . '</div>
                        <img src="' . $url . '" style="width: 100%; max-width: 500px; display: block; margin: 0 auto; border-radius: 5px;">
                    </div>';
            },
            $text
        );
    }
}
