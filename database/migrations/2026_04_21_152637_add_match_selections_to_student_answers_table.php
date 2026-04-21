<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_answers', function (Blueprint $table) {
            // Stores per-option selections for match questions: { optionId: submittedValue, ... }
            $table->json('match_selections')->nullable()->after('selected_option_id');
        });
    }

    public function down(): void
    {
        Schema::table('student_answers', function (Blueprint $table) {
            $table->dropColumn('match_selections');
        });
    }
};
