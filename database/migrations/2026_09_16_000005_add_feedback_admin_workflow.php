<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFeedbackAdminWorkflow extends Migration
{
    public function up()
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->string('status')->default('New')->after('submitted_at');
            $table->string('concern_level')->default('Normal')->after('status');
            $table->text('action_taken')->nullable()->after('concern_level');
            $table->timestamp('email_sent_at')->nullable()->after('action_taken');
        });

        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feedback_id')->nullable()->constrained('feedback')->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('info');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_notifications');

        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn(['status', 'concern_level', 'action_taken', 'email_sent_at']);
        });
    }
}
