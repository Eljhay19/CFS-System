<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOptionalFollowUpToFeedback extends Migration
{
    public function up()
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->text('follow_up_email')->nullable();
            $table->timestamp('follow_up_consent_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn(['follow_up_email', 'follow_up_consent_at']);
        });
    }
}
