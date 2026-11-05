<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSubmissionTokenToFeedbackTable extends Migration
{
    public function up()
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->uuid('submission_token')->nullable()->unique();
        });

        DB::table('feedback')->whereNull('feedback_code')->orderBy('id')->chunkById(100, function ($records) {
            foreach ($records as $record) {
                DB::table('feedback')->where('id', $record->id)->update([
                    'feedback_code' => 'FB-' . str_pad((string) $record->id, 6, '0', STR_PAD_LEFT),
                ]);
            }
        });
        DB::table('feedback')->where('status', 'New')->update(['status' => 'Submitted']);
    }

    public function down()
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropColumn('submission_token');
        });
    }
}
