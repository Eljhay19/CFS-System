<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdminEmailDeliveryToFeedbackActions extends Migration
{
    public function up()
    {
        Schema::table('feedback_actions', function (Blueprint $table) {
            $table->string('admin_email_status')->nullable()->after('customer_email_error');
            $table->text('admin_email_error')->nullable()->after('admin_email_status');
        });
    }

    public function down()
    {
        Schema::table('feedback_actions', function (Blueprint $table) {
            $table->dropColumn(['admin_email_status', 'admin_email_error']);
        });
    }
}
