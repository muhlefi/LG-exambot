<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('teacher_name');
            $table->string('school_name');
            $table->string('logo_path')->nullable();
            $table->string('education_level');
            $table->string('learning_phase')->nullable();
            $table->string('class_level');
            $table->string('semester');
            $table->string('academic_year');
            $table->string('subject');
            $table->string('topic');
            $table->string('subtopic')->nullable();
            $table->string('status')->default('draft')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};
