<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (! $app->environment('testing') || config('database.default') !== 'sqlite' || getenv('CFS_BROWSER_FIXTURE') !== '1') {
    fwrite(STDERR, 'This fixture is only available to isolated browser tests.');
    exit(1);
}
$business = App\Models\Business::firstOrFail();
foreach ([[45, 5, 'Needs Action'], [42, 9, 'Resolved'], [14, 8, 'Submitted'], [7, 5, 'Needs Action'], [7, 7, 'In Progress']] as [$days, $rating, $status]) {
    $feedback = $business->feedback()->create(['submitted_at'=>now()->subDays($days)->setTime(10, 0), 'status'=>$status]);
    foreach (['cleanliness'=>(string) $rating, 'problem_comment'=>'Dashboard browser check ' . $days . ' days ago'] as $key=>$value) {
        $question = $business->questions()->where('field_key', $key)->firstOrFail();
        $feedback->answers()->create(['question_id'=>$question->id, 'question_text'=>$question->question,
            'question_type'=>$question->question_type, 'field_key'=>$key, 'answer'=>$value]);
    }
    app(App\Services\FeedbackNotifications::class)->record($feedback);
}
$items = $business->feedback()->with('answers.question')->get();
echo json_encode(['records'=>$items->map(fn ($feedback) => ['code'=>$feedback->displayCode(), 'date'=>$feedback->submitted_at->toDateString(),
    'status'=>$feedback->status, 'rating'=>$feedback->ratingAverage()])->values(), 'ranges'=>app(App\Services\FeedbackFilters::class)->dateRanges()]);
