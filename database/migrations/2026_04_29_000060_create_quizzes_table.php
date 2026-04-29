<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->string('title');
            $table->string('quiz_code')->unique();
            $table->unsignedSmallInteger('duration')->default(30);
            $table->boolean('is_random_question')->default(true);
            $table->boolean('is_random_answer')->default(true);
            $table->string('visibility')->default('private');
            $table->string('password')->nullable();
            $table->unsignedSmallInteger('max_participants')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
