<?php

namespace App\Http\Controllers;

use App\Models\AiUsageLog;
use App\Models\ExamSession;
use App\Models\ExportLog;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        $sessionQuery = ExamSession::query()->where('user_id', $user->id);

        return view('dashboard.index', [
            'sessionCount' => (clone $sessionQuery)->count(),
            'questionCount' => Question::whereIn('exam_session_id', (clone $sessionQuery)->select('id'))->count(),
            'quizCount' => Quiz::whereIn('exam_session_id', (clone $sessionQuery)->select('id'))->count(),
            'exportCount' => ExportLog::where('user_id', $user->id)->count(),
            'aiUsageCount' => AiUsageLog::where('user_id', $user->id)->count(),
            'recentSessions' => ExamSession::where('user_id', $user->id)->latest()->limit(5)->get(),
        ]);
    }
}
