<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankQuestionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamSessionController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\TutorialController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    // Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    // Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/tutorial', TutorialController::class)->name('tutorial');
    Route::get('/bank-soal', BankQuestionController::class)->name('bank.index');

    // Exam Sessions CRUD
    Route::get('/sessions', [ExamSessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/create', [ExamSessionController::class, 'create'])->name('sessions.create');
    Route::post('/sessions', [ExamSessionController::class, 'store'])->name('sessions.store');
    Route::get('/sessions/{examSession}', [ExamSessionController::class, 'show'])->name('sessions.show');
    Route::get('/sessions/{examSession}/edit', [ExamSessionController::class, 'edit'])->name('sessions.edit');
    Route::put('/sessions/{examSession}', [ExamSessionController::class, 'update'])->name('sessions.update');
    Route::delete('/sessions/{examSession}', [ExamSessionController::class, 'destroy'])->name('sessions.destroy');

    // Question Structure within Session
    Route::post('/sessions/{examSession}/structures', [ExamSessionController::class, 'addStructure'])->name('sessions.structures.store');
    Route::post('/sessions/{examSession}/structures/{structure}/duplicate', [ExamSessionController::class, 'duplicateStructure'])->name('sessions.structures.duplicate');
    Route::delete('/sessions/{examSession}/structures/{structure}', [ExamSessionController::class, 'destroyStructure'])->name('sessions.structures.destroy');
    
    // AI Generation & Results
    Route::post('/sessions/{examSession}/generate', [ExamSessionController::class, 'generate'])->name('sessions.generate');
    Route::patch('/sessions/{examSession}/model', [ExamSessionController::class, 'updateModel'])->name('sessions.update-model');
    Route::get('/sessions/{examSession}/results', [ExamSessionController::class, 'results'])->name('sessions.results');
    Route::get('/sessions/{examSession}/export/{documentType}/{format}', [ExamSessionController::class, 'export'])->name('sessions.export');

    // Individual Question CRUD
    Route::get('/questions/{question}/edit', [ExamSessionController::class, 'editQuestion'])->name('questions.edit');
    Route::put('/questions/{question}', [ExamSessionController::class, 'updateQuestion'])->name('questions.update');
    Route::delete('/questions/{question}', [ExamSessionController::class, 'destroyQuestion'])->name('questions.destroy');
    Route::delete('/sessions/{examSession}/questions/batch', [ExamSessionController::class, 'batchDestroyQuestions'])->name('sessions.questions.batch-destroy');

    // Quizzes / Presentation Mode
    Route::get('/quizzes', [QuizController::class, 'index'])->name('quizzes.index');
    Route::post('/sessions/{examSession}/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
    Route::get('/quizzes/{quiz}', [QuizController::class, 'show'])->name('quizzes.show');
    Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');
});
