<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
            $table->unsignedBigInteger('question_id')->nullable()->change();
            $table->foreign('question_id')->references('id')->on('questions')->nullOnDelete();
        });

        Schema::create('deleted_survey_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('field_key', 100);
            $table->timestamps();
            $table->unique(['business_id', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deleted_survey_questions');
        Schema::table('answers', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
            $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
        });
    }
};
