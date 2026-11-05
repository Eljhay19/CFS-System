<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (! Schema::hasColumn('businesses', 'use_account_email_for_feedback')) {
                $table->boolean('use_account_email_for_feedback')->default(true);
            }
            if (! Schema::hasColumn('businesses', 'notification_email_verified_at')) {
                $table->timestamp('notification_email_verified_at')->nullable();
            }
            if (! Schema::hasColumn('businesses', 'pending_notification_email')) {
                $table->string('pending_notification_email')->nullable();
            }
        });

        Schema::create('notification_email_verification_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_email_verification_codes');
        $columns = array_values(array_filter(
            ['use_account_email_for_feedback', 'notification_email_verified_at', 'pending_notification_email'],
            fn ($column) => Schema::hasColumn('businesses', $column)
        ));
        if ($columns) {
            Schema::table('businesses', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
