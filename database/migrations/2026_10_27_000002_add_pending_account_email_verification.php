<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pending_email')->nullable();
            $table->string('email_change_code_hash')->nullable();
            $table->timestamp('email_change_code_expires_at')->nullable();
            $table->timestamp('email_change_code_sent_at')->nullable();
            $table->unsignedTinyInteger('email_change_code_attempts')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pending_email', 'email_change_code_hash', 'email_change_code_expires_at', 'email_change_code_sent_at', 'email_change_code_attempts']);
        });
    }
};
