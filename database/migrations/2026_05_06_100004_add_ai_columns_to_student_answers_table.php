<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_answers', function (Blueprint $table) {
            $table->text('answer_text')->nullable()->after('match_selections');
            $table->text('ai_feedback')->nullable()->after('answer_text');
            $table->boolean('ai_evaluated')->default(false)->after('ai_feedback');
        });
    }

    public function down(): void
    {
        Schema::table('student_answers', function (Blueprint $table) {
            $table->dropColumn(['answer_text', 'ai_feedback', 'ai_evaluated']);
        });
    }
};
