<?php

namespace App\Http\Controllers;

use App\Models\TutorialLesson;

class TutorialController extends Controller
{
    public function __invoke()
    {
        $lessons = TutorialLesson::whereNotNull('published_at')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        return view('tutorial.index', compact('lessons'));
    }
}
