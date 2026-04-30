<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Support\Facades\Auth;

class BankQuestionController extends Controller
{
    public function __invoke()
    {
        $questions = Question::whereHas('examSession', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->with(['examSession', 'options'])
            ->latest()
            ->paginate(15);

        return view('bank.index', compact('questions'));
    }
}
