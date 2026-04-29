<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignId('question_structure_id')->nullable()->constrained('question_structures')->nullOnDelete();
            $table->string('question_type');
            $table->longText('question_text');
            $table->string('question_image')->nullable();
            $table->longText('explanation')->nullable();
            $table->string('difficulty')->index();
            $table->string('cognitive_level')->nullable();
            $table->string('answer_key')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
