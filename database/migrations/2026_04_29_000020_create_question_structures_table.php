<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('question_type');
            $table->unsignedTinyInteger('option_count')->default(4);
            $table->unsignedSmallInteger('easy_count')->default(0);
            $table->unsignedSmallInteger('medium_count')->default(0);
            $table->unsignedSmallInteger('hard_count')->default(0);
            $table->json('cognitive_levels')->nullable();
            $table->boolean('has_question_image')->default(false);
            $table->boolean('has_option_image')->default(false);
            $table->boolean('has_diagram')->default(false);
            $table->boolean('has_table')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_structures');
    }
};
