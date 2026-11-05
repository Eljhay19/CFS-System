<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSurveyConfiguration extends Migration
{
    public function up()
    {
        Schema::create('survey_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('key', 40);
            $table->string('title', 100);
            $table->string('description', 300)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'key']);
        });
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('dashboard_notifications')->default(true);
            $table->boolean('feedback_email_alerts')->default(true);
            $table->boolean('contact_message_alerts')->default(true);
            $table->string('closed_survey_message', 500)->nullable();
        });
        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->foreignId('contact_message_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('admin_notifications', fn (Blueprint $table) => $table->dropConstrainedForeignId('contact_message_id'));
        Schema::table('businesses', fn (Blueprint $table) => $table->dropColumn(['dashboard_notifications', 'feedback_email_alerts', 'contact_message_alerts', 'closed_survey_message']));
        Schema::dropIfExists('survey_steps');
    }
}
