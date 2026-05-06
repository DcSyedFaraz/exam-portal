<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend enum to include new question types (MySQL/MariaDB/PostgreSQL only)
        // SQLite stores enum as VARCHAR and does not enforce constraints — new values are accepted as-is.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE questions MODIFY COLUMN question_type ENUM('mcq','true_false','match','picture','fill_blank','word_bank','ai_evaluated') NOT NULL");
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('order');
            $table->text('correct_answer_text')->nullable()->after('image_path');
            $table->text('word_bank_items')->nullable()->after('correct_answer_text');
            $table->integer('ai_max_marks')->nullable()->after('word_bank_items');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'correct_answer_text', 'word_bank_items', 'ai_max_marks']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE questions MODIFY COLUMN question_type ENUM('mcq','true_false','match') NOT NULL");
        }
    }
};
