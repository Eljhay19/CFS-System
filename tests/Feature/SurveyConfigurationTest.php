<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\ContactMessage;
use App\Models\Feedback;
use App\Models\SurveyStep;
use App\Models\User;
use App\Jobs\SendContactMessageNotification;
use App\Services\FeedbackNotifications;
use App\Services\SurveyDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class SurveyConfigurationTest extends TestCase
{
    use RefreshDatabase;
    private $owner;
    private $business;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => Hash::make('password')]);
        $this->business = Business::create(['user_id' => $this->owner->id, 'business_name' => 'Test Business', 'owner_name' => 'Owner', 'business_type' => 'Services', 'email' => 'alerts@example.test', 'address' => 'Test', 'contact_number' => '123']);
        $this->actingAs($this->owner);
    }

    private function step(string $title = 'Follow-up', string $type = 'comment'): array
    {
        $step = $this->business->surveySteps()->create(['key' => 'step_' . Str::lower(Str::random(12)), 'title' => $title]);
        $question = $this->business->questions()->create(['field_key' => 'custom_' . Str::uuid(), 'step' => $step->key, 'question' => $title . ' question', 'question_type' => $type, 'required' => true, 'is_core' => false, 'sort_order' => 100]);
        return [$step, $question];
    }

    private function draft(): array
    {
        $snapshot = app(SurveyDefinition::class)->draft($this->business);
        return $snapshot + [
            'agreed' => true, 'token' => (string) Str::uuid(), 'business_id' => $this->business->id,
            'consent_at' => now()->toIso8601String(), 'policy_version' => SurveyDefinition::POLICY_VERSION,
            'expires_at' => now()->addHour()->toIso8601String(),
            'visit' => ['date_of_visit' => now()->subDay()->toDateString(), 'time_of_order' => '10:00'],
            'order' => ['order_type' => 'Dine In', 'order_details' => 'Test order'],
            'store_rating' => ['cleanliness' => 8, 'speed_of_service' => 8, 'staff' => 'N/A'],
            'food_rating' => ['taste' => 8, 'freshness' => 8, 'temperature' => 8, 'presentation' => 8, 'value_for_money' => 8],
            'final' => ['order_accurate' => 'Like Up', 'food_satisfied' => 'Like Up', 'problem_today' => 'No', 'problem_comment' => ''],
        ];
    }

    public function test_default_steps_stay_five_and_additional_steps_require_explicit_publication(): void
    {
        $this->assertCount(5, app(SurveyDefinition::class)->stepSnapshot($this->business));
        $this->post(route('survey-steps.store'), ['title' => 'Extra Form', 'description' => 'Additional questions'])->assertRedirect(route('questions.index'));
        $step = SurveyStep::firstOrFail();
        $this->assertFalse($step->is_published);
        $this->post(route('survey-steps.publish', $step))->assertSessionHasErrors('step');
        $this->post(route('questions.store'), ['question' => 'Extra answer?', 'question_type' => 'comment', 'step' => $step->key, 'required' => 1])->assertRedirect();
        $this->assertCount(5, app(SurveyDefinition::class)->stepSnapshot($this->business));
        $this->post(route('survey-steps.publish', $step))->assertRedirect();
        $this->assertCount(6, app(SurveyDefinition::class)->stepSnapshot($this->business));
        $this->assertSame(array_keys(SurveyDefinition::STEPS), array_slice(array_keys(app(SurveyDefinition::class)->stepSnapshot($this->business)), 0, 5));
        $this->post(route('survey-steps.archive', $step))->assertRedirect();
        $this->assertCount(5, app(SurveyDefinition::class)->stepSnapshot($this->business));
        $this->post(route('survey-steps.archive', $step))->assertRedirect();
        $this->assertFalse($step->fresh()->is_published);
    }

    public function test_additional_steps_validate_in_order_and_save_review_and_export_all_answers(): void
    {
        [$first, $comment] = $this->step('Additional Comments');
        [$second, $rating] = $this->step('Extra Rating', 'rating');
        $first->update(['is_published' => true]);
        $second->update(['is_published' => true]);
        $this->withSession(['survey' => $this->draft()]);
        $this->get(route('feedback.review'))->assertRedirect(route('feedback.additional', $first->key));
        $this->get(route('feedback.additional', $second->key))->assertRedirect(route('feedback.additional', $first->key));
        $this->get(route('feedback.additional', $first->key))->assertOk()->assertSee('Step 6 of 7');
        $this->post(route('feedback.additional.save', $first->key), [])->assertSessionHasErrors('extra.' . $comment->id);
        $answer = '=HYPERLINK("test")';
        $this->post(route('feedback.additional.save', $first->key), ['extra' => [$comment->id => $answer]])->assertRedirect(route('feedback.additional', $second->key));
        $this->post(route('feedback.additional.save', $second->key), ['extra' => [$rating->id => 5]])->assertSessionHasErrors('extra_selected.' . $rating->id);
        $this->post(route('feedback.additional.save', $second->key), ['extra' => [$rating->id => 7], 'extra_selected' => [$rating->id => 1]])->assertRedirect(route('feedback.review'));
        $this->get(route('feedback.review'))->assertOk()->assertSee('Additional Comments');
        $this->post(route('feedback.store'))->assertRedirect(route('feedback.thank-you'));
        $feedback = Feedback::firstOrFail();
        $this->assertSame($answer, $feedback->answers()->where('question_id', $comment->id)->value('answer'));
        $this->assertSame('7', $feedback->answers()->where('question_id', $rating->id)->value('answer'));
        $csv = $this->get(route('dashboard.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Additional Comments question', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString('Anonymous', $csv);
        $this->assertStringContainsString('Test order', $csv);
    }

    public function test_published_step_edits_and_archives_do_not_change_existing_customer_drafts(): void
    {
        [$step, $question] = $this->step('Original Step');
        $step->update(['is_published' => true]);
        $draft = $this->draft();
        $this->withSession(['survey' => $draft]);
        $this->put(route('survey-steps.update', $step), ['title' => 'Renamed Step'])->assertRedirect();
        $this->post(route('survey-steps.archive', $step))->assertRedirect();
        $this->delete(route('questions.destroy', $question))->assertRedirect();
        $this->get(route('feedback.additional', $step->key))->assertOk()->assertSee('Original Step');
        $this->post(route('feedback.additional.save', $step->key), ['extra' => [$question->id => 'Retained answer']])->assertRedirect(route('feedback.review'));
        $this->get(route('feedback.continue'))->assertRedirect(route('feedback.additional', $step->key));
        $this->get(route('feedback.review'))->assertOk();
        $this->post(route('feedback.store'))->assertRedirect();
        $this->assertDatabaseHas('answers', ['question_id' => $question->id, 'question_text' => 'Original Step question', 'answer' => 'Retained answer']);
        $this->assertCount(5, app(SurveyDefinition::class)->stepSnapshot($this->business));
    }

    public function test_steps_are_owner_scoped_and_unknown_steps_cannot_be_used(): void
    {
        [$step] = $this->step();
        $other = User::create(['name' => 'Other', 'email' => 'other@example.test', 'password' => Hash::make('password')]);
        $this->actingAs($other)->put(route('survey-steps.update', $step), ['title' => 'Wrong owner'])->assertForbidden();
        $this->post(route('survey-steps.publish', $step))->assertForbidden();
        $this->post(route('survey-steps.archive', $step))->assertForbidden();
        $this->actingAs($this->owner)->withSession(['survey' => $this->draft()])->get(route('feedback.additional', 'unknown'))->assertNotFound();
        $this->post(route('questions.store'), ['question' => 'Wrong section', 'question_type' => 'comment', 'step' => 'unknown'])->assertSessionHasErrors('step');
    }

    public function test_archiving_the_last_question_unpublishes_a_step_without_deleting_it(): void
    {
        [$step, $question] = $this->step();
        $step->update(['is_published' => true]);
        $this->delete(route('questions.destroy', $question))->assertRedirect();
        $this->assertFalse($step->fresh()->is_published);
        $this->assertDatabaseHas('survey_steps', ['id' => $step->id]);
        $this->post(route('questions.restore', $question))->assertRedirect();
        $this->assertCount(5, app(SurveyDefinition::class)->stepSnapshot($this->business));
    }

    public function test_settings_sections_save_independently_and_closed_message_reaches_home(): void
    {
        Notification::fake();
        $this->post(route('settings.update'), ['section' => 'notifications', 'notification_email' => 'new@example.test', 'use_account_email_for_feedback' => 0, 'dashboard_notifications' => 0, 'feedback_email_alerts' => 0, 'contact_message_alerts' => 1])->assertRedirect();
        $business = $this->business->fresh();
        $this->assertTrue($business->survey_enabled);
        $this->assertFalse($business->dashboard_notifications);
        $this->assertSame('owner@example.test', $business->notificationRecipient());
        $this->assertSame('new@example.test', $business->pending_notification_email);
        $verificationCode = null;
        Notification::assertSentOnDemand(\App\Notifications\NotificationEmailVerificationCode::class, function ($notification, $channels, $notifiable) use (&$verificationCode) {
            $verificationCode = $notification->code;
            return ($notifiable->routes['mail'] ?? null) === 'new@example.test';
        });
        $this->post(route('settings.notification-email.verify'), ['code' => $verificationCode])->assertRedirect();
        $this->post(route('settings.update'), ['section' => 'access', 'survey_enabled' => 0, 'closed_survey_message' => "Temporarily closed.\nPlease return later."])->assertRedirect();
        $this->assertSame('new@example.test', $this->business->fresh()->notificationRecipient());
        $this->get(route('home'))->assertOk()->assertSee('Temporarily closed.');
        $this->assertFalse($this->business->fresh()->feedback_email_alerts);
        $this->post(route('settings.update'), ['section' => 'access', 'survey_enabled' => 1])->assertRedirect();
        $this->assertTrue($this->business->fresh()->survey_enabled);
    }

    public function test_feedback_notification_preferences_are_real_and_never_delete_feedback(): void
    {
        $this->business->update(['dashboard_notifications' => false, 'feedback_email_alerts' => false]);
        $feedback = $this->business->feedback()->create(['submitted_at' => now(), 'status' => 'Submitted']);
        $feedback->load('business.user', 'answers');
        app(FeedbackNotifications::class)->record($feedback);
        app(FeedbackNotifications::class)->sendEmail($feedback);
        $this->assertDatabaseCount('feedback', 1);
        $this->assertDatabaseCount('admin_notifications', 0);
        $this->assertSame('disabled', $feedback->fresh()->email_status);
        Mail::assertNothingSent();
    }

    public function test_contact_alert_toggle_hides_the_notification_but_keeps_the_message_and_email(): void
    {
        $oldContactMessage = ContactMessage::create([
            'business_id' => $this->business->id,
            'submission_token' => (string) Str::uuid(),
            'email' => 'previous@example.test',
            'message' => 'An earlier saved Contact Us message.',
            'recipient_email' => $this->business->notificationRecipient(),
        ]);
        $this->owner->notifications()->create([
            'contact_message_id' => $oldContactMessage->id,
            'title' => 'New contact message received',
            'message' => $oldContactMessage->message,
            'type' => 'info',
        ]);
        $this->getJson(route('dashboard.notifications'))->assertJsonPath('unread_count', 1);
        $this->business->update(['contact_message_alerts' => false]);
        $this->owner->unsetRelation('business');
        $this->get(route('contact'))->assertOk();
        $this->post(route('contact.store'), ['submission_token' => session('contact.token'), 'email' => 'visitor@example.test', 'message' => 'Please assist with the survey.'])->assertRedirect();
        $message = ContactMessage::where('email', 'visitor@example.test')->firstOrFail();
        SendContactMessageNotification::dispatchSync($message->id);
        $transport = config('mail.mailers.' . config('mail.default') . '.transport');
        $this->assertSame(in_array($transport, ['log', 'array'], true) ? 'logged' : 'sent', $message->email_status);
        Mail::assertSent(\App\Mail\ContactMessageReceived::class, fn ($mail) => $mail->hasTo($message->recipient_email));
        $feed = $this->getJson(route('dashboard.notifications'))->assertOk()->json();
        $this->assertSame(0, $feed['unread_count']);
        $this->assertSame([], $feed['notifications']);
        $this->get(route('dashboard'))->assertOk()->assertSee(route('dashboard.section', ['section' => 'unread']));
        $this->get(route('dashboard.section', ['section' => 'unread']))->assertOk()
            ->assertViewHas('unreadNotificationItems', fn ($items) => ! $items->contains('contact_message_id', $message->id));
        $this->get(route('messages.show', $message))->assertOk()->assertSee('Please assist with the survey.');
        $this->assertSame(0, $this->owner->notificationCenterItems()->where('is_read', false)->count());
        $this->assertSame(1, $this->owner->notifications()->where('contact_message_id', $oldContactMessage->id)->count());
        $other = User::create(['name' => 'Other', 'email' => 'second@example.test', 'password' => Hash::make('password')]);
        $this->actingAs($other)->get(route('messages.show', $message))->assertForbidden();
    }
}
