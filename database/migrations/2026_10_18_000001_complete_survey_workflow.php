<?php

use App\Models\Business;
use App\Services\SurveyDefinition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CompleteSurveyWorkflow extends Migration
{
    public function up()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('notification_email')->nullable();
            $table->string('survey_slug')->nullable()->unique();
            $table->boolean('survey_enabled')->default(true);
            $table->boolean('is_default')->default(false);
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->constrained('businesses');
            $table->string('field_key')->nullable();
            $table->string('step')->default('final');
            $table->text('help_text')->nullable();
            $table->boolean('is_core')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->unique(['business_id', 'field_key']);
        });
        Schema::table('answers', function (Blueprint $table) {
            $table->text('question_text')->nullable();
            $table->string('question_type')->nullable();
            $table->text('question_options')->nullable();
            $table->string('field_key')->nullable();
        });
        Schema::table('feedback', function (Blueprint $table) {
            $table->timestamp('consent_at')->nullable();
            $table->string('policy_version')->nullable();
            $table->string('email_status')->default('pending');
            $table->text('email_error')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
        });
        Schema::create('feedback_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_id')->constrained('feedback')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('previous_status');
            $table->string('status');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        $defaultId = DB::table('businesses')->orderBy('id')->value('id');
        foreach (DB::table('businesses')->orderBy('id')->get() as $business) {
            DB::table('businesses')->where('id', $business->id)->update([
                'survey_slug' => (string) Str::uuid(), 'notification_email' => $business->email,
                'is_default' => $business->id === $defaultId,
            ]);
        }
        DB::table('questions')->whereNull('business_id')->update(['business_id' => $defaultId]);
        foreach (DB::table('answers')->orderBy('id')->cursor() as $answer) {
            $question = DB::table('questions')->find($answer->question_id);
            if ($question) {
                DB::table('answers')->where('id', $answer->id)->update([
                    'question_text' => $question->question, 'question_type' => $question->question_type,
                    'question_options' => $question->options,
                ]);
            }
        }
        foreach (Business::all() as $business) {
            app(SurveyDefinition::class)->initialize($business);
        }
        foreach (DB::table('questions')->whereNotNull('field_key')->get() as $question) {
            DB::table('answers')->where('question_id', $question->id)->update(['field_key' => $question->field_key]);
        }
        DB::table('feedback')->whereNotNull('email_sent_at')->update(['email_status' => 'sent']);
    }

    public function down()
    {
        Schema::dropIfExists('feedback_actions');
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn(['consent_at', 'policy_version', 'email_status', 'email_error', 'resolved_at', 'resolution_note']);
        });
        Schema::table('answers', function (Blueprint $table) {
            $table->dropColumn(['question_text', 'question_type', 'question_options', 'field_key']);
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropUnique(['business_id', 'field_key']);
            $table->dropColumn(['business_id', 'field_key', 'step', 'help_text', 'is_core', 'archived_at']);
        });
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['notification_email', 'survey_slug', 'survey_enabled', 'is_default']);
        });
    }
}
