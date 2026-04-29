<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankQuestionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamSessionController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TutorialController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::get('/quiz/join', [QuizController::class, 'joinForm'])->name('quizzes.join.form');
Route::post('/quiz/join', [QuizController::class, 'join'])->name('quizzes.join');
Route::get('/quiz/play/{participant}', [QuizController::class, 'play'])->name('quizzes.play');
Route::post('/quiz/play/{participant}', [QuizController::class, 'submit'])->name('quizzes.submit');
Route::get('/quiz/{quiz}/leaderboard', [QuizController::class, 'leaderboard'])->name('quizzes.leaderboard');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/tutorial', TutorialController::class)->name('tutorial');
    Route::get('/bank-soal', BankQuestionController::class)->name('bank.index');
    Route::get('/settings', SettingsController::class)->name('settings');

    Route::get('/sessions', [ExamSessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/create', [ExamSessionController::class, 'create'])->name('sessions.create');
    Route::post('/sessions', [ExamSessionController::class, 'store'])->name('sessions.store');
    Route::get('/sessions/{examSession}', [ExamSessionController::class, 'show'])->name('sessions.show');
    Route::post('/sessions/{examSession}/structures', [ExamSessionController::class, 'addStructure'])->name('sessions.structures.store');
    Route::post('/sessions/{examSession}/structures/{structure}/duplicate', [ExamSessionController::class, 'duplicateStructure'])->name('sessions.structures.duplicate');
    Route::delete('/sessions/{examSession}/structures/{structure}', [ExamSessionController::class, 'destroyStructure'])->name('sessions.structures.destroy');
    Route::post('/sessions/{examSession}/generate', [ExamSessionController::class, 'generate'])->name('sessions.generate');
    Route::get('/sessions/{examSession}/results', [ExamSessionController::class, 'results'])->name('sessions.results');
    Route::get('/sessions/{examSession}/export/{documentType}/{format}', [ExamSessionController::class, 'export'])->name('sessions.export');

    Route::get('/quizzes', [QuizController::class, 'index'])->name('quizzes.index');
    Route::post('/sessions/{examSession}/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
    Route::get('/quizzes/{quiz}', [QuizController::class, 'show'])->name('quizzes.show');
});
