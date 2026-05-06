<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_sub_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->foreignId('sub_item_id')->constrained('question_sub_items')->cascadeOnDelete();
            $table->text('answer_text')->nullable();
            $table->decimal('marks_awarded', 5, 2)->default(0);
            $table->text('ai_feedback')->nullable();
            $table->boolean('ai_evaluated')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_sub_answers');
    }
};
