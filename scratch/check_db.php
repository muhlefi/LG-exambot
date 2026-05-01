<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Question;

$lastQuestions = Question::with('options')->latest()->take(5)->get();

foreach ($lastQuestions as $q) {
    echo "ID: {$q->id} | Type: {$q->question_type}\n";
    echo "Text: " . substr($q->question_text, 0, 50) . "...\n";
    foreach ($q->options as $o) {
        echo "  - {$o->option_label}: {$o->option_text}\n";
    }
    echo "-------------------\n";
}
