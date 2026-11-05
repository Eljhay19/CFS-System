<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (! $app->environment('testing') || config('database.default') !== 'sqlite' || getenv('CFS_BROWSER_FIXTURE') !== '1') {
    fwrite(STDERR, 'This fixture is only available to isolated browser tests.');
    exit(1);
}
$business = App\Models\Business::firstOrFail();
$feedback = Illuminate\Support\Facades\DB::transaction(function () use ($business, $argv) {
    $status = $argv[1] ?? 'Submitted';
    $feedback = $business->feedback()->create(['status'=>$status, 'submitted_at'=>now()]);
    foreach (['cleanliness'=>'4', 'problem_comment'=>'Live browser feedback'] as $key=>$value) {
        $question = $business->questions()->where('field_key', $key)->firstOrFail();
        $feedback->answers()->create(['question_id'=>$question->id, 'question_text'=>$question->question,
            'question_type'=>$question->question_type, 'field_key'=>$key, 'answer'=>$value]);
    }
    app(App\Services\FeedbackNotifications::class)->record($feedback);
    return $feedback;
});
echo json_encode(['id'=>$feedback->id, 'code'=>$feedback->displayCode()]);
