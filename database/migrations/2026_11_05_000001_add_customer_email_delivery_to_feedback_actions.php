<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerEmailDeliveryToFeedbackActions extends Migration
{
    public function up()
    {
        Schema::table('feedback_actions', function (Blueprint $table) {
            $table->string('customer_email_status')->nullable()->after('note');
            $table->text('customer_email_error')->nullable()->after('customer_email_status');
        });
    }

    public function down()
    {
        Schema::table('feedback_actions', function (Blueprint $table) {
            $table->dropColumn(['customer_email_status', 'customer_email_error']);
        });
    }
}
