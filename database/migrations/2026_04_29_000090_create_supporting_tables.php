<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('school_name')->nullable();
            $table->string('logo_path')->nullable();
            $table->text('header_text')->nullable();
            $table->text('footer_text')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('export_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('exam_session_id')->nullable()->constrained('exam_sessions')->nullOnDelete();
            $table->string('document_type');
            $table->string('format');
            $table->string('path')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('exam_session_id')->nullable()->constrained('exam_sessions')->nullOnDelete();
            $table->string('provider')->default('local-draft');
            $table->unsignedInteger('tokens_used')->default(0);
            $table->string('status')->default('success');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('tutorial_lessons', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->default('Dasar');
            $table->string('video_url')->nullable();
            $table->text('body');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->string('scope')->default('private')->index();
            $table->string('title');
            $table->string('subject')->nullable();
            $table->string('topic')->nullable();
            $table->string('question_type');
            $table->longText('question_text');
            $table->string('answer_key')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_questions');
        Schema::dropIfExists('tutorial_lessons');
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('export_logs');
        Schema::dropIfExists('document_templates');
    }
};
