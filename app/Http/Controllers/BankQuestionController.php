<?php

namespace App\Http\Controllers;

use App\Models\BankQuestion;
use Illuminate\Support\Facades\Auth;

class BankQuestionController extends Controller
{
    public function __invoke()
    {
        $questions = BankQuestion::query()
            ->where(fn ($query) => $query->where('scope', 'public')->orWhere('user_id', Auth::id()))
            ->latest()
            ->paginate(12);

        return view('bank.index', compact('questions'));
    }
}
