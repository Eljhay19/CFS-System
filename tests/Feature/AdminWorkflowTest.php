<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Feedback;
use App\Models\User;
use App\Mail\AdminFeedbackSubmitted;
use App\Mail\FeedbackActionUpdated;
use App\Services\FeedbackNotifications;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;
    private $owner;
    private $business;

    protected function setUp(): void
    {
        parent::setUp(); Mail::fake();
        $this->owner = User::factory()->create(['password'=>Hash::make('current-password')]);
        $this->business = Business::create(['user_id'=>$this->owner->id,'business_name'=>'Admin Cafe','owner_name'=>'Owner','business_type'=>'Cafe','address'=>'Manila','contact_number'=>'123','email'=>'contact@example.test','notification_email'=>'alerts@example.test']);
        $this->actingAs($this->owner);
    }

    private function custom(array $overrides = [])
    {
        return $this->business->questions()->create(array_merge(['question'=>'Extra question','question_type'=>'comment','required'=>false,'step'=>'final','field_key'=>'custom_' . Str::uuid(),'sort_order'=>100],$overrides));
    }

    private function record(string $status, string $date, string $comment, int $rating): Feedback
    {
        $feedback = $this->business->feedback()->create(['status'=>$status,'submitted_at'=>$date]);
        foreach (['cleanliness'=>(string) $rating,'problem_comment'=>$comment] as $key=>$value) {
            $question = $this->business->questions()->where('field_key',$key)->firstOrFail();
            $feedback->answers()->create(['question_id'=>$question->id,'question_text'=>$question->question,'question_type'=>$question->question_type,'field_key'=>$key,'answer'=>$value]);
        }
        return $feedback;
    }

    public function test_question_management_persists_section_hint_choices_and_archive_without_deletion(): void
    {
        $payload = ['question'=>'Preferred drink?','question_type'=>'multiple_choice','options'=>"Tea\nCoffee",'step'=>'order','required'=>'1','help_text'=>'Choose your drink','sort_order'=>20];
        $this->post(route('questions.store'),$payload)->assertRedirect(route('questions.index'));
        $question = $this->business->questions()->where('question','Preferred drink?')->firstOrFail();
        $this->assertSame(['Tea','Coffee'],$question->optionList());
        $this->assertSame('order',$question->step);
        $this->assertSame('Choose your drink',$question->help_text);
        $this->put(route('questions.update',$question),array_merge($payload,['question'=>'Favorite drink?','required'=>0]))->assertRedirect();
        $this->assertFalse($question->fresh()->required);
        $this->delete(route('questions.destroy',$question))->assertRedirect();
        $this->assertNotNull($question->fresh()->archived_at);
        $this->post(route('questions.restore',$question))->assertRedirect();
        $this->assertNull($question->fresh()->archived_at);
        $this->get(route('questions.index'))->assertOk()->assertSee('Favorite drink?');
        $this->delete(route('questions.permanently-destroy',$question))->assertRedirect();
        $this->assertDatabaseMissing('questions',['id'=>$question->id]);
    }

    public function test_permanent_core_question_deletion_keeps_feedback_and_does_not_recreate_question(): void
    {
        $feedback = $this->record('Submitted', now()->toDateTimeString(), 'Saved rating history', 8);
        $question = $this->business->questions()->where('field_key', 'cleanliness')->firstOrFail();
        $answer = $feedback->answers()->where('question_id', $question->id)->firstOrFail();

        $this->delete(route('questions.permanently-destroy', $question))->assertRedirect();

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseHas('answers', [
            'id' => $answer->id,
            'question_id' => null,
            'question_text' => $question->question,
            'question_type' => 'rating',
            'answer' => '8',
        ]);
        $this->get(route('questions.index'))->assertOk();
        $this->assertDatabaseMissing('questions', ['business_id' => $this->business->id, 'field_key' => 'cleanliness']);
        $this->assertNotContains('cleanliness', array_column(app(\App\Services\SurveyDefinition::class)->snapshot($this->business), 'key'));
        $this->assertSame('Saved rating history', $feedback->fresh()->answers->first(fn ($item) => $item->field_key === 'problem_comment')->answer);
    }

    public function test_archived_step_can_be_restored_and_permanently_deleted_with_its_questions(): void
    {
        $step = $this->business->surveySteps()->create(['key' => 'step_testing', 'title' => 'Test Step', 'description' => null, 'is_published' => true]);
        $question = $this->custom(['question' => 'Step question?', 'step' => $step->key, 'question_type' => 'comment']);
        $feedback = $this->business->feedback()->create(['status' => 'Submitted', 'submitted_at' => now()]);
        $answer = $feedback->answers()->create([
            'question_id' => $question->id, 'question_text' => $question->question,
            'question_type' => $question->question_type, 'field_key' => $question->field_key, 'answer' => 'Step answer',
        ]);

        $this->post(route('survey-steps.archive', $step))->assertRedirect();
        $this->assertNotContains($step->key, array_column(app(\App\Services\SurveyDefinition::class)->snapshot($this->business), 'step'));
        $this->post(route('survey-steps.archive', $step))->assertRedirect();
        $this->assertNull($step->fresh()->archived_at);
        $this->assertFalse($step->fresh()->is_published);
        $this->post(route('survey-steps.publish', $step))->assertRedirect();
        $this->assertContains($step->key, array_column(app(\App\Services\SurveyDefinition::class)->snapshot($this->business), 'step'));

        $this->post(route('survey-steps.archive', $step))->assertRedirect();
        $this->delete(route('survey-steps.destroy', $step))->assertRedirect();
        $this->assertDatabaseMissing('survey_steps', ['id' => $step->id]);
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseHas('answers', ['id' => $answer->id, 'question_id' => null, 'question_text' => 'Step question?', 'answer' => 'Step answer']);
    }

    public function test_choices_are_validated_and_core_semantics_cannot_be_changed(): void
    {
        $this->post(route('questions.store'),['question'=>'Invalid','question_type'=>'multiple_choice','options'=>" \n ",'step'=>'final'])->assertSessionHasErrors('options');
        $question = $this->business->questions()->where('field_key','cleanliness')->firstOrFail();
        $this->put(route('questions.update',$question),['question'=>'Updated cleanliness label','question_type'=>'comment','step'=>'order','required'=>0,'help_text'=>'Rate 1 to 10'])->assertRedirect();
        $question->refresh();
        $this->assertSame('rating',$question->question_type);
        $this->assertSame('service',$question->step);
        $this->assertTrue($question->required);
        $this->assertSame('Updated cleanliness label',$question->question);
        $this->delete(route('questions.permanently-destroy',$question))->assertRedirect();
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }

    public function test_other_admin_cannot_manage_questions_or_retry_email(): void
    {
        $question = $this->custom();
        $feedback = $this->record('Submitted',now()->toDateTimeString(),'Test',8);
        $this->actingAs(User::factory()->create());
        $this->put(route('questions.update',$question),[])->assertForbidden();
        $this->delete(route('questions.destroy',$question))->assertForbidden();
        $this->post(route('questions.restore',$question))->assertForbidden();
        $this->post(route('feedback.retry-email',$feedback))->assertForbidden();
    }

    public function test_notification_and_account_emails_are_separate_and_require_verification(): void
    {
        Notification::fake();
        $this->post(route('settings.update'),[
            'section'=>'notifications','notification_email'=>'new-alerts@example.test','use_account_email_for_feedback'=>0,
            'dashboard_notifications'=>1,'feedback_email_alerts'=>1,'contact_message_alerts'=>1,
        ])->assertRedirect();
        $this->assertSame('contact@example.test',$this->business->fresh()->email);
        $this->assertSame($this->owner->email,$this->business->fresh()->notificationRecipient());
        $this->assertSame('new-alerts@example.test',$this->business->fresh()->pending_notification_email);
        $notificationCode = null;
        Notification::assertSentOnDemand(\App\Notifications\NotificationEmailVerificationCode::class, function ($notification, $channels, $notifiable) use (&$notificationCode) {
            $notificationCode = $notification->code;
            return ($notifiable->routes['mail'] ?? null) === 'new-alerts@example.test';
        });
        $this->post(route('settings.notification-email.verify'),['code'=>$notificationCode])->assertRedirect();
        $this->assertSame('new-alerts@example.test',$this->business->fresh()->notificationRecipient());

        $originalAccountEmail = $this->owner->email;
        $this->post(route('settings.account'),['name'=>'New Owner','email'=>'new-login@example.test','current_password'=>'wrong','password'=>'changed-password','password_confirmation'=>'changed-password'])->assertSessionHasErrors('current_password');
        $this->post(route('settings.account'),['name'=>'New Owner','email'=>'new-login@example.test','current_password'=>'current-password','password'=>'changed-password','password_confirmation'=>'changed-password'])->assertRedirect();
        $this->assertSame($originalAccountEmail,$this->owner->fresh()->email);
        $accountCode = null;
        Notification::assertSentOnDemand(\App\Notifications\AccountEmailChangeCode::class, function ($notification, $channels, $notifiable) use (&$accountCode) {
            $accountCode = $notification->code;
            return ($notifiable->routes['mail'] ?? null) === 'new-login@example.test';
        });
        $this->post(route('settings.account-email.verify'),['code'=>$accountCode])->assertRedirect();
        $this->assertSame('new-login@example.test',$this->owner->fresh()->email);
        $this->assertTrue(Hash::check('changed-password',$this->owner->fresh()->password));
        $this->get(route('settings.index'))->assertOk()->assertSee('Send Test Email')->assertSee('Survey Link');
    }

    public function test_test_email_is_honest_about_log_mode_and_retry_updates_delivery_status(): void
    {
        config(['mail.default'=>'log']);
        Mail::shouldReceive('raw')->once()->andReturnNull();
        $this->post(route('settings.test-email'))->assertSessionHas('status',fn ($message)=>strpos($message,'development mail output') !== false);
        Mail::fake();
        $feedback = $this->record('Submitted',now()->toDateTimeString(),'Test',8);
        $this->post(route('feedback.retry-email',$feedback))->assertRedirect();
        $this->assertSame('logged',$feedback->fresh()->email_status);
        $this->assertNull($feedback->fresh()->email_sent_at);
    }

    public function test_saved_profile_uses_edit_mode_and_syncs_email_and_header_logo(): void
    {
        $this->get(route('business.edit'))->assertOk()->assertViewHas('editing', false)
            ->assertSee('Edit Information')->assertDontSee('Save Information');
        $this->get(route('business.edit', ['edit'=>1]))->assertOk()->assertViewHas('editing', true)->assertSee('Save Information');
        $payload = ['business_name'=>'CFS Business','owner_name'=>'Admin Owner','business_type'=>'Services','address'=>'Test address',
            'contact_number'=>'12345','email'=>'profile-alerts@example.test','logo'=>UploadedFile::fake()->image('test-logo.png')];
        $this->post(route('business.update'), $payload)->assertRedirect(route('business.edit'));
        $business = $this->business->fresh();
        try {
            $this->assertSame('alerts@example.test', $business->notification_email);
            $this->assertFileExists(public_path($business->logo));
            $this->get(route('business.edit'))->assertOk()->assertViewHas('editing', false)
                ->assertSee('Edit Information')->assertDontSee('Save Information')
                ->assertSee('<span class="profile-name">Admin Owner</span>', false)
                ->assertSee('<span class="profile-avatar"><img src="' . asset($business->logo) . '"', false);
            unset($payload['logo']);
            $this->post(route('business.update'), $payload)->assertRedirect(route('business.edit'));
            $this->assertSame($business->logo, $this->business->fresh()->logo);
            $feedback = $this->record('Submitted', now()->toDateTimeString(), 'Profile email test', 8);
            app(FeedbackNotifications::class)->sendEmail($feedback);
            Mail::assertSent(AdminFeedbackSubmitted::class, fn ($mail) => $mail->hasTo($this->owner->email));
            $this->post(route('dashboard.notification-email'), ['email'=>'quick-alerts@example.test'])->assertRedirect();
            $this->assertSame('profile-alerts@example.test', $this->business->fresh()->email);
            $this->assertSame('quick-alerts@example.test', $this->business->fresh()->pending_notification_email);
        } finally {
            if ($business->logo) unlink(public_path($business->logo));
        }
    }

    public function test_header_dashboard_link_is_only_on_customer_pages_for_logged_in_admin(): void
    {
        foreach (['dashboard', 'business.edit', 'feedback.index', 'questions.index', 'settings.index'] as $route) {
            $this->get(route($route))->assertOk()->assertDontSee('data-header-dashboard', false)->assertSee('admin-sidebar');
        }
        $this->get(route('home'))->assertOk()->assertSee('data-header-dashboard', false);
        $this->post(route('logout'));
        $this->get(route('home'))->assertOk()->assertDontSee('data-header-dashboard', false);
    }

    public function test_live_revision_and_metrics_follow_submissions_status_and_each_notification(): void
    {
        $initial = $this->getJson(route('dashboard.stats'))->assertOk()->json('revision');
        $first = $this->record('Submitted', now()->toDateTimeString(), 'First response', 8);
        $second = $this->record('Needs Action', now()->toDateTimeString(), 'Second concern', 4);
        $notifications = app(FeedbackNotifications::class);
        $notifications->record($first);
        $notifications->record($second);
        $stats = $this->getJson(route('dashboard.stats'))->assertOk()->assertJsonPath('total', 2)
            ->assertJsonPath('average', 6)->assertJsonPath('unread_notifications', 2)->json();
        $this->assertNotSame($initial, $stats['revision']);
        $this->get(route('dashboard'))->assertOk()->assertViewHas('liveRevision', $stats['revision']);
        $this->get(route('feedback.index'))->assertOk()->assertViewHas('liveRevision', $stats['revision'])
            ->assertViewHas('feedbackOverview', fn ($overview) => $overview['statusCounts']['Needs Action'] === 1);
        $ratingAnswer = $first->answers()->where('field_key', 'cleanliness')->firstOrFail();
        $ratingAnswer->update(['answer' => '10']);
        $updatedRatings = $this->getJson(route('dashboard.stats'))->assertOk()->assertJsonPath('average', 7)->json();
        $this->assertNotSame($stats['revision'], $updatedRatings['revision']);
        $stats = $updatedRatings;
        $this->getJson(route('dashboard.notifications'))->assertOk()->assertJsonPath('unread_count', 2)
            ->assertJsonPath('notifications.0.url', route('dashboard.notifications.open', $second))
            ->assertJsonPath('notifications.0.submitted_at', $second->submitted_at->format('M d, Y h:i A'))
            ->assertSee($second->displayCode())->assertSee('Anonymous')->assertSee('Needs Action');
        $this->get(route('feedback.detail', $first))->assertOk();
        $readRevision = $this->getJson(route('dashboard.stats'))->assertJsonPath('unread_notifications', 1)->json('revision');
        $this->assertNotSame($stats['revision'], $readRevision);
        $this->assertFalse($second->notification()->first()->is_read);
        $second->update(['status'=>'Resolved']);
        $this->getJson(route('dashboard.stats'))->assertJsonPath('resolved', 1)->assertJsonPath('response_rate', 50);
        $this->getJson(route('dashboard.notifications'))->assertSee('Resolved');
        $this->postJson(route('dashboard.notifications.read-all'))->assertJsonPath('unread_count', 0);
        $this->getJson(route('dashboard.stats'))->assertJsonPath('unread_notifications', 0);
    }

    public function test_opening_one_feedback_alert_marks_only_that_alert_read_and_targets_its_record(): void
    {
        $feedback = $this->record('Needs Action', now()->toDateTimeString(), 'Customer complaint', 4);
        $clicked = $this->owner->notifications()->create([
            'feedback_id' => $feedback->id,
            'title' => 'Customer concern needs action',
            'message' => 'Initial complaint alert.',
            'type' => 'warning',
            'is_read' => false,
        ]);
        $newer = $this->owner->notifications()->create([
            'feedback_id' => $feedback->id,
            'title' => 'Complaint action updated',
            'message' => 'Follow-up action alert.',
            'type' => 'warning',
            'is_read' => false,
        ]);

        $this->get(route('dashboard.notifications.open', $clicked))
            ->assertRedirect(route('feedback.detail', ['feedback' => $feedback->id, 'notification' => $clicked->id]));

        $this->assertTrue($clicked->fresh()->is_read);
        $this->assertFalse($newer->fresh()->is_read);
    }

    public function test_filters_charts_and_csv_match_and_all_export_is_explicit(): void
    {
        $matching = $this->record('Resolved','2026-01-12 12:00:00','Match this',10);
        $outside = $this->record('Resolved','2025-12-12 12:00:00','Outside range',2);
        $otherStatus = $this->record('Needs Action','2026-01-12 13:00:00','Different status',4);
        $filters = ['from'=>'2026-01-01','to'=>'2026-01-31','status'=>'Resolved','period'=>'monthly'];
        $this->get(route('feedback.index',$filters))->assertOk()->assertViewHas('feedbackOverview',fn ($overview)=>$overview['total'] === 1 && $overview['averageRating'] == 10)
            ->assertViewHas('feedbackItems',fn ($items)=>$items->total() === 1 && $items->first()->id === $matching->id);
        $this->get(route('dashboard',$filters))->assertOk()->assertViewHas('totalFeedback',1)->assertViewHas('averageRating',10);
        $csv = $this->get(route('dashboard.export',$filters))->assertOk()->streamedContent();
        $this->assertStringContainsString($matching->displayCode(),$csv);
        $this->assertStringNotContainsString($outside->displayCode(),$csv);
        $this->assertStringNotContainsString($otherStatus->displayCode(),$csv);
        $pdf = $this->get(route('dashboard.export', $filters + ['format'=>'pdf']))->assertOk()
            ->assertHeader('content-type', 'application/pdf')->getContent();
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString($matching->displayCode(), $pdf);
        $svg = $this->get(route('dashboard.export', $filters + ['format'=>'svg']))->assertOk()
            ->assertHeader('content-type', 'image/svg+xml; charset=UTF-8')->getContent();
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString($matching->displayCode(), $svg);
        $jpg = $this->get(route('dashboard.export', $filters + ['format'=>'jpg']))->assertOk()
            ->assertHeader('content-type', 'image/jpeg')->getContent();
        $this->assertStringStartsWith("\xFF\xD8\xFF", $jpg);
        $all = $this->get(route('dashboard.export',array_merge($filters,['all'=>1])))->assertOk()->streamedContent();
        $this->assertStringContainsString($outside->displayCode(),$all);
        $this->get(route('feedback.index',['to'=>'2026-01-31']))->assertOk();
        $this->get(route('feedback.index',['from'=>'2026-02-01','to'=>'2026-01-01']))->assertSessionHasErrors('to');
    }

    public function test_date_presets_filter_metrics_and_export_consistently(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-18 12:00:00'));
        $today = $this->record('Submitted', now()->toDateTimeString(), 'Today', 10);
        $week = $this->record('Resolved', now()->subDays(6)->toDateTimeString(), 'This week', 8);
        $old = $this->record('Submitted', now()->subDays(10)->toDateTimeString(), 'Old', 2);
        $this->get(route('dashboard', ['range'=>'last7']))->assertOk()->assertViewHas('totalFeedback', 2)
            ->assertViewHas('filters', fn ($filters) => $filters['from'] === '2026-10-12' && $filters['to'] === '2026-10-18');
        $this->get(route('dashboard', ['range'=>'today']))->assertOk()->assertViewHas('totalFeedback', 1);
        $csv = $this->get(route('dashboard.export', ['range'=>'last7']))->assertOk()->streamedContent();
        $this->assertStringContainsString($today->displayCode(), $csv);
        $this->assertStringContainsString($week->displayCode(), $csv);
        $this->assertStringNotContainsString($old->displayCode(), $csv);
        $this->get(route('dashboard', ['range'=>'custom', 'from'=>'2026-10-01', 'to'=>'2026-10-10']))->assertOk()->assertViewHas('totalFeedback', 1);
        $this->get(route('dashboard', ['range'=>'invalid']))->assertSessionHasErrors('range');
    }

    public function test_dashboard_notification_scope_and_contextual_export_links_match_filters(): void
    {
        $matching = $this->record('Submitted', '2026-01-12 12:00:00', 'Inside', 8);
        $outside = $this->record('Submitted', '2025-12-12 12:00:00', 'Outside', 4);
        foreach ([$matching, $outside] as $feedback) {
            $this->owner->notifications()->create(['feedback_id'=>$feedback->id,'title'=>'Feedback received','message'=>'New feedback','type'=>'info','is_read'=>false]);
        }
        $filters = ['from'=>'2026-01-01','to'=>'2026-01-31'];
        $this->get(route('dashboard', $filters))->assertOk()->assertViewHas('unreadNotifications', 1)
            ->assertViewHas('unreadNotificationItems', fn ($items) => $items->count() === 1 && $items->first()->feedback_id === $matching->id);
        $this->get(route('dashboard.section', ['section'=>'today'] + $filters))->assertOk()
            ->assertSee(route('dashboard.export', $filters + ['filter'=>'today']));
        $this->get(route('dashboard.section', ['section'=>'unread'] + $filters))->assertOk()
            ->assertSee(route('dashboard.export', $filters + ['unread'=>1]));
    }

    public function test_background_polling_does_not_consume_settings_feedback_messages(): void
    {
        $this->post(route('settings.update'), ['notification_email'=>'alerts@example.test','survey_enabled'=>1])->assertRedirect();
        $this->getJson(route('dashboard.notifications'))->assertOk();
        $this->get(route('settings.index'))->assertOk()->assertSee('Notification settings saved.');
        $this->getJson(route('dashboard.notifications'))->assertOk();
        $this->get(route('settings.index'))->assertOk()->assertDontSee('Notification settings saved.');
    }

    public function test_dashboard_charts_include_historical_data_and_follow_period_and_status_filters(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-19 12:00:00'));
        $this->record('Submitted', '2026-09-16 10:00:00', 'Historical first', 5);
        $this->record('Resolved', '2026-09-20 10:00:00', 'Historical second', 9);
        $this->record('Submitted', '2026-10-06 10:00:00', 'Historical third', 8);
        $this->business->feedback()->create(['status'=>'Submitted', 'submitted_at'=>'2026-10-12 10:00:00']);
        $this->get(route('dashboard'))->assertOk()->assertViewHas('dashboardSeries', function ($series) {
            return $series->pluck('date')->all() === ['2026-09-16', '2026-09-20', '2026-10-06', '2026-10-12']
                && $series->pluck('average')->all() === [5.0, 9.0, 8.0, null] && $series->sum('count') === 4;
        })->assertViewHas('dashboardDistribution', fn ($buckets) => $buckets->pluck('count')->all() === [0, 0, 1, 1, 1])
            ->assertSee('Feedback Volume')->assertSee('data-rated-count>3', false)->assertSee('5/10');
        $this->get(route('dashboard', ['period'=>'weekly']))->assertOk()->assertViewHas('dashboardSeries', function ($series) {
            return $series->first()['date'] === '2026-09-14' && $series->first()['count'] === 2 && $series->first()['average'] === 7.0;
        });
        $this->get(route('dashboard', ['period'=>'monthly', 'status'=>'Resolved']))->assertOk()
            ->assertViewHas('totalFeedback', 1)->assertViewHas('dashboardSeries', fn ($series) => $series->count() === 1 && $series->first()['date'] === '2026-09-01' && $series->first()['average'] === 9.0);
        $this->get(route('dashboard', ['from'=>'2026-10-18']))->assertOk()
            ->assertViewHas('dashboardSeries', fn ($series) => $series->isEmpty())->assertSee('No feedback for these filters');
    }

    public function test_presets_override_stale_manual_dates_but_custom_dates_still_validate(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-19 12:00:00'));
        foreach (app(\App\Services\FeedbackFilters::class)->dateRanges() as $range => $dates) {
            if ($range === 'all') continue;
            $this->get(route('dashboard', ['range'=>$range, 'from'=>'2026-12-31', 'to'=>'2026-01-01']))
                ->assertOk()->assertViewHas('filters', fn ($filters) => $filters['from'] === $dates['from'] && $filters['to'] === $dates['to']);
        }
        $this->get(route('dashboard', ['range'=>'custom', 'from'=>'2026-12-31', 'to'=>'2026-01-01']))->assertSessionHasErrors('to');
    }

    public function test_all_notification_inbox_keeps_read_items_and_excludes_other_owners(): void
    {
        $feedback = $this->record('Submitted', now()->toDateTimeString(), 'Notification test', 8);
        app(FeedbackNotifications::class)->record($feedback);
        $other = User::factory()->create();
        $other->notifications()->create(['title'=>'Other owner secret', 'message'=>'Hidden', 'type'=>'info', 'is_read'=>false]);
        $this->getJson(route('dashboard.notifications'))->assertOk()
            ->assertJsonPath('notifications.0.icon', 'clipboard-list')->assertJsonPath('notifications.0.rating', 8)
            ->assertJsonPath('notifications.0.feedback_code', $feedback->displayCode());
        $this->postJson(route('dashboard.notifications.read-all'))->assertJsonPath('unread_count', 0);
        $this->get(route('dashboard.section', ['section'=>'inbox']))->assertOk()->assertSee('All Notifications')
            ->assertSee($feedback->displayCode())->assertDontSee('Other owner secret');
        $this->assertFalse($other->notifications()->first()->is_read);
        foreach (range(1, 20) as $index) {
            $item = $this->record('Submitted', now()->toDateTimeString(), 'Inbox page ' . $index, 8);
            app(FeedbackNotifications::class)->record($item);
        }
        $this->get(route('dashboard.section', ['section'=>'inbox']))->assertOk()
            ->assertViewHas('notificationItems', fn ($items) => $items->total() === 21 && $items->count() === 20)->assertSee('Page 1 of 2');
        $this->get(route('dashboard.section', ['section'=>'inbox', 'page'=>2]))->assertOk()
            ->assertSee('Page 2 of 2')->assertSee($feedback->displayCode());
    }

    public function test_forgot_password_issues_single_use_token_and_resets_password(): void
    {
        $this->post(route('logout'));
        Notification::fake();
        $this->get(route('password.request'))->assertOk();
        $this->post(route('password.email'),['email'=>$this->owner->email])->assertRedirect();
        $code = null;
        Notification::assertSentTo($this->owner,\App\Notifications\PasswordResetCode::class,function ($notification) use (&$code) { $code = $notification->code; return true; });
        $verification = $this->post(route('password.verify'), ['code'=>$code])->assertRedirect();
        $token = basename(parse_url($verification->headers->get('Location'), PHP_URL_PATH));
        $data = ['token'=>$token,'email'=>$this->owner->email,'password'=>'reset-password','password_confirmation'=>'reset-password'];
        $this->post(route('password.update'),$data)->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('reset-password',$this->owner->fresh()->password));
        $this->post(route('password.update'),$data)->assertSessionHasErrors('email');
    }

    public function test_feedback_details_show_frozen_answers_and_honest_email_state_without_nested_panels(): void
    {
        $feedback = $this->record('Needs Action', now()->toDateTimeString(), 'Please review this concern.', 5);
        $feedback->update(['concern_level'=>'High', 'email_status'=>'logged']);
        $this->get(route('feedback.detail', $feedback))->assertOk()->assertSee('Customer Feedback')
            ->assertSee('Admin Action Required')->assertSee('Action History')->assertSee('Logged (development)')
            ->assertSee('5/10')->assertSee('Please review this concern.')->assertDontSee('class="panel"', false);
        $answer = $feedback->answers()->where('field_key', 'cleanliness')->first();
        $answer->question->update(['question'=>'New label for future responses']);
        $this->get(route('feedback.detail', $feedback))->assertOk()->assertSee($answer->question_text)->assertDontSee('New label for future responses');
    }

    public function test_admin_action_validation_retains_input_and_duplicate_saves_do_not_duplicate_history(): void
    {
        $feedback = $this->record('Needs Action', now()->toDateTimeString(), 'Action test', 4);
        $this->from(route('feedback.detail', $feedback))->post(route('feedback.action', $feedback), ['status'=>'Resolved', 'action_taken'=>''])
            ->assertSessionHasErrors('action_taken')->assertSessionHasInput('status', 'Resolved');
        $this->get(route('feedback.detail', $feedback))->assertOk()->assertSee('value="Resolved" selected', false);
        $payload = ['status'=>'Resolved', 'action_taken'=>'Concern handled and verified.'];
        $this->post(route('feedback.action', $feedback), $payload)->assertRedirect();
        $this->assertDatabaseHas('admin_notifications', [
            'feedback_id' => $feedback->id,
            'user_id' => $this->owner->id,
            'title' => 'Customer complaint resolved',
            'type' => 'success',
            'is_read' => false,
        ]);
        Mail::assertSent(FeedbackActionUpdated::class, fn ($mail) => $mail->hasTo($this->owner->email)
            && $mail->feedback->is($feedback)
            && $mail->action->status === 'Resolved'
            && str_contains($mail->build()->subject, $feedback->displayCode()));
        $resolvedAt = $feedback->fresh()->resolved_at;
        $this->travel(5)->minutes();
        $this->post(route('feedback.action', $feedback), $payload)->assertSessionHas('status', 'No changes to save.');
        $this->assertSame(1, $feedback->actions()->count());
        $this->assertTrue($resolvedAt->equalTo($feedback->fresh()->resolved_at));
        $this->post(route('feedback.action', $feedback), ['status'=>'Resolved', 'action_taken'=>'Updated resolution detail.'])->assertRedirect();
        $this->assertSame(2, $feedback->actions()->count());
        $this->assertTrue($resolvedAt->equalTo($feedback->fresh()->resolved_at));
        $this->assertSame('Updated resolution detail.', $feedback->fresh()->resolution_note);
        $this->post(route('feedback.action', $feedback), ['status'=>'In Progress', 'action_taken'=>'Reopened for another review.'])->assertRedirect();
        $this->assertNull($feedback->fresh()->resolved_at);
        $this->assertNull($feedback->fresh()->resolution_note);
    }

    public function test_failed_customer_email_is_not_presented_as_sent_in_action_history(): void
    {
        $feedback = $this->record('Needs Action', now()->toDateTimeString(), 'Please review this concern.', 4);
        $feedback->update([
            'follow_up_email' => 'customer@example.test',
            'follow_up_consent_at' => now(),
        ]);
        $this->business->update(['feedback_email_alerts' => false]);

        $pendingMail = \Mockery::mock();
        $pendingMail->shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP unavailable'));
        Mail::shouldReceive('to')->once()->with('customer@example.test')->andReturn($pendingMail);

        $this->post(route('feedback.action', $feedback), [
            'status' => 'In Progress',
            'action_taken' => 'Started reviewing the concern.',
            'customer_message' => 'We are looking into your concern.',
        ])->assertRedirect(route('feedback.detail', $feedback))
            ->assertSessionHas('status', fn ($message) => str_contains($message, 'customer update email failed'));

        $action = $feedback->actions()->latest('id')->firstOrFail();
        $this->assertSame('In Progress', $feedback->fresh()->status);
        $this->assertSame('failed', $action->customer_email_status);
        $this->assertSame('We are looking into your concern.', $action->customer_message);

        $this->get(route('feedback.detail', $feedback))->assertOk()
            ->assertSee('Message not delivered:')
            ->assertDontSee('Message sent to customer:')
            ->assertSee('Retry Customer Email');
    }

    public function test_other_admin_cannot_view_or_update_feedback_action_history(): void
    {
        $feedback = $this->record('Needs Action', now()->toDateTimeString(), 'Private concern', 4);
        $this->actingAs(User::factory()->create());
        $this->get(route('feedback.detail', $feedback))->assertForbidden();
        $this->post(route('feedback.action', $feedback), ['status'=>'Resolved', 'action_taken'=>'Unauthorized'])->assertForbidden();
        $this->assertSame(0, $feedback->actions()->count());
        $this->assertSame('Needs Action', $feedback->fresh()->status);
    }

    public function test_header_search_filters_dashboard_feedback_and_csv_within_the_owners_records(): void
    {
        $match = $this->record('Submitted', now()->toDateTimeString(), 'Unique search needle', 8);
        $other = $this->record('Resolved', now()->toDateTimeString(), 'Different comment', 6);
        $this->get(route('dashboard', ['search'=>'  needle  ']))->assertOk()->assertViewHas('totalFeedback', 1)
            ->assertSee('action="' . route('dashboard') . '"', false)->assertSee('aria-label="Run search"', false);
        $this->get(route('feedback.index', ['search'=>'needle']))->assertOk()
            ->assertViewHas('feedbackItems', fn ($items) => $items->total() === 1 && $items->first()->id === $match->id);
        $this->get(route('feedback.index', ['search'=>$other->displayCode(), 'status'=>'Resolved']))->assertOk()
            ->assertViewHas('feedbackItems', fn ($items) => $items->total() === 1 && $items->first()->id === $other->id);
        $csv = $this->get(route('dashboard.export', ['search'=>'needle']))->streamedContent();
        $this->assertStringContainsString($match->displayCode(), $csv);
        $this->assertStringNotContainsString($other->displayCode(), $csv);
        $this->get(route('dashboard', ['search'=>"' OR 1=1 --"]))->assertOk()->assertViewHas('totalFeedback', 0);
    }

    public function test_zero_search_archived_question_search_and_malformed_search_are_handled(): void
    {
        $this->record('Submitted', now()->toDateTimeString(), 'Plain response', 8)->update(['feedback_code'=>'WITHOUT-DIGITS']);
        $match = $this->record('Submitted', now()->toDateTimeString(), 'Response 0', 8);
        $this->get(route('dashboard', ['search'=>'0']))->assertOk()->assertViewHas('totalFeedback', 1);
        $this->get(route('questions.index', ['search'=>'clean']))->assertOk()->assertViewHas('questions', fn ($items) => $items->isNotEmpty());
        $question = $this->business->questions()->where('field_key', 'cleanliness')->first();
        $question->update(['archived_at'=>now()]);
        $this->get(route('questions.index', ['search'=>'clean', 'archived'=>1]))->assertOk()->assertSee('name="archived" value="1"', false)
            ->assertViewHas('questions', fn ($items) => $items->contains('id', $question->id));
        $this->get(route('questions.index', ['search'=>['invalid']]))->assertSessionHasErrors('search');
        $this->get(route('business.edit', ['search'=>['invalid']]))->assertOk();
        $this->get(route('dashboard', ['search'=>['invalid']]))->assertSessionHasErrors('search');
    }
}
