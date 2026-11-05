<?php

namespace Tests\Feature;

use App\Mail\AdminFeedbackSubmitted;
use App\Jobs\SendFeedbackNotification;
use App\Models\Business;
use App\Models\Feedback;
use App\Models\User;
use App\Services\FeedbackNotifications;
use App\Services\SurveyDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SurveySubmissionTest extends TestCase
{
    use RefreshDatabase;
    protected $business;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->business = Business::create(['user_id'=>User::factory()->create()->id, 'business_name'=>'Test Cafe', 'owner_name'=>'Owner',
            'business_type'=>'Food & Beverage','address'=>'Quezon City','contact_number'=>'09123456789','email'=>'contact@example.test','notification_email'=>'admin@example.test']);
    }

    private function survey(): array
    {
        return [
            'agreed'=>true,'token'=>(string) Str::uuid(),'business_id'=>$this->business->id,
            'questions'=>app(SurveyDefinition::class)->snapshot($this->business),
            'consent_at'=>now()->toIso8601String(),'policy_version'=>SurveyDefinition::POLICY_VERSION,
            'expires_at'=>now()->addHours(2)->toIso8601String(),
            'visit'=>['date_of_visit'=>now()->toDateString(),'time_of_order'=>'00:00'],
            'order'=>['order_type'=>'Dine In','order_details'=>'Coffee and sandwich'],
            'store_rating'=>['cleanliness'=>9,'speed_of_service'=>8,'staff'=>'N/A'],
            'food_rating'=>['taste'=>9,'freshness'=>8,'temperature'=>9,'presentation'=>8,'value_for_money'=>9],
        ];
    }

    private function finalAnswers(array $overrides = []): array
    {
        return array_merge(['order_accurate'=>'Like Up','food_satisfied'=>'Like Up','problem_today'=>'No','problem_comment'=>''], $overrides);
    }

    private function prepare(array $answers = [], ?array $survey = null): void
    {
        $this->withSession(['survey'=>$survey ?? $this->survey()])->post(route('feedback.final.save'), $this->finalAnswers($answers))
            ->assertRedirect(route('feedback.review'));
        $this->get(route('feedback.review'))->assertOk();
    }

    private function confirmTerms(): void
    {
        $terms = $this->get(route('terms'))->assertOk();
        $this->post(route('feedback.terms-confirm'), ['agreement'=>'1', 'terms_token'=>$terms->viewData('termsToken')])
            ->assertRedirect(route('home'))->assertSessionHas('consent_confirmation')->assertSessionMissing('survey');
    }

    public function test_terms_have_a_home_popup_and_a_standalone_footer_section(): void
    {
        $popup = $this->get(route('terms', ['from'=>'home']))->assertOk()->assertViewIs('terms.home')
            ->assertSee('data-home-terms-dialog', false)->assertSee('<section class="hero">', false)
            ->assertSee('href="' . route('terms') . '"', false);
        $html = $popup->getContent();
        $this->assertSame(1, substr_count($html, '<header class="topbar">'));
        $this->assertSame(1, substr_count($html, '<footer class="footer">'));
        $this->assertSame(1, substr_count($html, 'data-terms-card>'));
        $this->get(route('terms'))->assertOk()->assertViewIs('terms')->assertSee('Survey usage rules')
            ->assertDontSee('data-home-terms-dialog', false)->assertDontSee('<section class="hero">', false);
    }

    public function test_popup_confirmation_returns_home_and_errors_keep_the_popup_context(): void
    {
        $popup = $this->get(route('terms', ['from'=>'home']))->assertOk();
        $this->post(route('feedback.terms-confirm'), ['agreement'=>'1','terms_token'=>$popup->viewData('termsToken')])
            ->assertRedirect(route('home'))->assertSessionHas('consent_confirmation')->assertSessionMissing('survey');
        $this->get(route('home'))->assertOk()->assertSee('data-terms-confirmed="1"', false)
            ->assertSee('value="agree" required checked', false);
        $this->get(route('terms', ['from'=>'home']))->assertOk()->assertSessionMissing('consent_confirmation');
        $this->post(route('feedback.terms-confirm'), ['agreement'=>'1','terms_token'=>'invalid'])
            ->assertRedirect(route('terms', ['from'=>'home']))->assertSessionMissing('consent_confirmation');
    }

    public function test_terms_confirmation_is_required_before_home_can_start_the_survey(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('data-terms-confirmed="0"', false);
        $this->post(route('feedback.start'), ['consent'=>'agree'])->assertRedirect(route('terms'))->assertSessionMissing('survey');
        $this->get(route('feedback.terms-agree'))->assertRedirect(route('home'))->assertSessionMissing('consent_confirmation');
        $terms = $this->get(route('terms'))->assertOk();
        $this->post(route('feedback.terms-confirm'), ['terms_token'=>$terms->viewData('termsToken')])->assertSessionHasErrors('agreement');
        $this->assertNull(session('consent_confirmation'));
        $this->confirmTerms();
        $confirmedAt = session('consent_confirmation.confirmed_at');
        $this->get(route('home'))->assertOk()->assertSee('data-terms-confirmed="1"', false);
        $this->post(route('feedback.start'), ['consent'=>'decline'])->assertSessionMissing('survey');
        $this->post(route('feedback.start'), ['consent'=>'agree'])->assertRedirect(route('feedback.show'))
            ->assertSessionHas('survey.consent_at', $confirmedAt)->assertSessionMissing('consent_confirmation');
    }

    public function test_terms_cancel_expiration_and_invalid_confirmation_do_not_unlock_home(): void
    {
        $this->confirmTerms();
        $this->get(route('terms'))->assertOk()->assertSessionMissing('consent_confirmation');
        $this->get(route('home'))->assertOk()->assertSee('data-terms-confirmed="0"', false)->assertSessionMissing('survey');
        $this->post(route('feedback.terms-confirm'), ['agreement'=>'1','terms_token'=>'invalid'])->assertRedirect(route('terms'))->assertSessionMissing('consent_confirmation');
        $this->confirmTerms();
        $this->travel(121)->minutes();
        $this->get(route('home'))->assertOk()->assertSee('data-terms-confirmed="0"', false);
        $this->post(route('feedback.start'), ['consent'=>'agree'])->assertRedirect(route('terms'))->assertSessionMissing('survey');
    }

    public function test_home_stays_generic_and_keeps_a_single_compact_terms_link(): void
    {
        $this->business->update(['business_name'=>"Eyra's Coffee Shop",'description'=>'A small coffee shop offering affordable drinks and snacks.']);
        $this->get(route('home'))->assertOk()->assertDontSee('Coffee Shop')->assertDontSee('A small coffee shop offering affordable drinks and snacks.')
            ->assertSee('I agree with the')->assertSee('Terms &amp; Conditions', false)->assertSee('css/home.css');
    }

    public function test_contact_is_a_generic_single_section_without_business_details(): void
    {
        config(['feedback.support_email'=>null,'feedback.support_phone'=>null]);
        $this->business->update(['business_name'=>"Eyra's Coffee Shop",'address'=>'Private shop address']);
        $this->get(route('contact'))->assertOk()->assertSee('Email Address')->assertSee('Contact Number')->assertSee('Not provided')
            ->assertSee('terms-card contact-card')->assertDontSee('<aside class="terms-nav"', false)
            ->assertDontSee('Coffee Shop')->assertDontSee('Private shop address')->assertDontSee('09123456789')->assertDontSee('contact@example.test');
    }

    public function test_contact_displays_only_configured_public_contact_details(): void
    {
        config(['feedback.support_email'=>'support@example.test','feedback.support_phone'=>'+63 912 345 6789']);
        $this->get(route('contact'))->assertOk()->assertSee('support@example.test')->assertSee('+63 912 345 6789')
            ->assertSee('href="mailto:support@example.test"', false)->assertSee('href="tel:+639123456789"', false)
            ->assertDontSee('admin@example.test')->assertDontSee('contact@example.test')->assertDontSee('Not provided');
    }

    public function test_anonymous_reviewed_submission_saves_answers_id_consent_and_notification(): void
    {
        $this->prepare();
        $this->post(route('feedback.store'))->assertRedirect(route('feedback.thank-you'))->assertSessionMissing('survey');
        $feedback = Feedback::with('answers.question')->sole();
        $this->assertSame('FB-000001',$feedback->feedback_code);
        $this->assertSame('Anonymous',$feedback->customerLabel());
        $this->assertNull($feedback->follow_up_email);
        $this->assertNull($feedback->follow_up_consent_at);
        $this->assertSame('Submitted',$feedback->status);
        $this->assertNotNull($feedback->submitted_at);
        $this->assertNotNull($feedback->consent_at);
        $this->assertSame(SurveyDefinition::POLICY_VERSION,$feedback->policy_version);
        $this->assertCount(16,$feedback->answers);
        $this->assertEquals(8.6,$feedback->ratingAverage());
        $this->assertSame('Coffee and sandwich',$feedback->answers->firstWhere('field_key','order_details')->answer);
        $this->assertSame('N/A',$feedback->answers->firstWhere('field_key','staff')->answer);
        $this->assertDatabaseHas('admin_notifications',['feedback_id'=>$feedback->id,'user_id'=>$this->business->user_id,'is_read'=>false]);
        Mail::assertSent(AdminFeedbackSubmitted::class,function ($mail) use ($feedback) {
            $body = view('emails.admin-feedback-submitted',['feedback'=>$mail->feedback])->render();
            return $mail->hasTo($feedback->business->user->email) && $mail->build()->subject === 'New Customer Feedback Received'
                && strpos($body,'FB-000001') !== false && strpos($body,'Anonymous') !== false
                && strpos($body,'8.6/10') !== false && strpos($body,route('feedback.detail',$feedback)) !== false;
        });
        $this->get(route('feedback.thank-you'))->assertOk()->assertSee('FB-000001');
    }

    public function test_complaint_email_has_a_clear_alert_and_includes_the_customer_comment(): void
    {
        Queue::fake();
        $this->prepare(['problem_today'=>'Yes', 'problem_comment'=>'My order was cold and arrived late.']);
        $this->post(route('feedback.store'))->assertRedirect(route('feedback.thank-you'));
        $feedback = Feedback::with('answers.question')->sole();

        $this->assertSame('Needs Action', $feedback->status);
        $this->assertSame('queued', $feedback->email_status);
        Queue::assertPushed(SendFeedbackNotification::class, fn ($job) => $job->feedbackId === $feedback->id);

        // Execute the queued handler explicitly so the test can inspect the
        // real complaint email without making survey submission synchronous.
        (new SendFeedbackNotification($feedback->id))->handle(app(FeedbackNotifications::class));
        Mail::assertSent(AdminFeedbackSubmitted::class, function ($mail) use ($feedback) {
            $html = view('emails.admin-feedback-submitted', ['feedback'=>$mail->feedback])->render();
            $text = view('emails.admin-feedback-submitted-text', ['feedback'=>$mail->feedback])->render();

            return $mail->hasTo($feedback->business->user->email)
                && $mail->build()->subject === 'URGENT: Customer Concern Requires Action'
                && str_contains($html, 'Customer complaint needs attention')
                && str_contains($html, '#fff1f0')
                && str_contains($html, 'My order was cold and arrived late.')
                && str_contains($html, route('feedback.detail', $feedback))
                && str_contains($text, 'ACTION REQUIRED: CUSTOMER COMPLAINT')
                && str_contains($text, 'My order was cold and arrived late.');
        });
    }

    public function test_feedback_email_job_is_queued_after_feedback_is_committed(): void
    {
        Queue::fake();
        $this->prepare();
        $this->post(route('feedback.store'))->assertRedirect(route('feedback.thank-you'));
        $feedback = Feedback::sole();

        $this->assertSame('queued', $feedback->email_status);
        Queue::assertPushed(SendFeedbackNotification::class, fn ($job) => $job->feedbackId === $feedback->id);
        $this->assertDatabaseHas('admin_notifications', ['feedback_id' => $feedback->id]);
    }

    public function test_final_validation_and_review_are_required_before_submission(): void
    {
        $this->withSession(['survey'=>$this->survey()])->post(route('feedback.store'))->assertRedirect(route('feedback.final'));
        $this->post(route('feedback.final.save'),$this->finalAnswers(['problem_today'=>'Yes']))->assertSessionHasErrors('problem_comment');
        $this->assertDatabaseCount('feedback',0);
        $this->post(route('feedback.final.save'),$this->finalAnswers())->assertRedirect(route('feedback.review'));
        $this->post(route('feedback.store'))->assertRedirect(route('feedback.review'));
        $this->assertDatabaseCount('feedback',0);
        $this->get(route('feedback.review'))->assertOk();
        $this->post(route('feedback.order.save'),['order_type'=>'Take Out','order_details'=>'Tea'])->assertRedirect(route('feedback.store-rating'));
        $this->post(route('feedback.store'))->assertRedirect(route('feedback.review'));
        $this->assertDatabaseCount('feedback',0);
    }

    public function test_opt_in_follow_up_requires_a_valid_email_and_separate_consent(): void
    {
        $this->prepare();
        $this->from(route('feedback.review'))->post(route('feedback.store'), ['follow_up_requested'=>'1'])
            ->assertRedirect(route('feedback.review'))->assertSessionHasErrors(['follow_up_email', 'follow_up_consent']);
        $this->get(route('feedback.review'))->assertOk()->assertSee('Enter your email address to request a follow-up.');
        $this->post(route('feedback.store'), ['follow_up_requested'=>'1', 'follow_up_email'=>'not-an-email', 'follow_up_consent'=>'1'])
            ->assertSessionHasErrors('follow_up_email');
        $this->post(route('feedback.store'), ['follow_up_requested'=>'1', 'follow_up_email'=>'customer@example.test', 'follow_up_consent'=>'0'])
            ->assertSessionHasErrors('follow_up_consent')->assertSessionHasInput('follow_up_email', 'customer@example.test');
        $this->get(route('feedback.review'))->assertOk()->assertSee('value="customer@example.test"', false);
        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_consented_follow_up_is_encrypted_and_visible_only_in_the_owners_feedback_details(): void
    {
        $this->prepare(['problem_today'=>'Yes', 'problem_comment'=>'Please check the delayed order']);
        $email = 'customer@example.test';
        $this->post(route('feedback.store'), ['follow_up_requested'=>'1', 'follow_up_email'=>$email, 'follow_up_consent'=>'1'])
            ->assertRedirect(route('feedback.thank-you'))->assertSessionMissing('survey');
        $feedback = Feedback::with('answers.question')->sole();
        $this->assertSame($email, $feedback->follow_up_email);
        $this->assertNotNull($feedback->follow_up_consent_at);
        $this->assertNotSame($email, $feedback->getRawOriginal('follow_up_email'));
        $this->assertStringNotContainsString($email, $feedback->getRawOriginal('follow_up_email'));
        $this->assertArrayNotHasKey('follow_up_email', $feedback->toArray());
        $this->assertArrayNotHasKey('follow_up_consent_at', $feedback->toArray());
        $this->assertSame('Follow-up requested', $feedback->customerLabel());
        $this->assertSame('Needs Action', $feedback->status);
        $this->assertCount(16, $feedback->answers);
        $this->get(route('feedback.thank-you'))->assertOk()->assertDontSee($email);
        $this->get(route('feedback.detail', $feedback))->assertRedirect(route('login'));
        $this->actingAs($this->business->user)->get(route('feedback.detail', $feedback))->assertOk()
            ->assertSee('Follow-up requested')->assertSee('href="mailto:' . $email . '"', false)
            ->assertSee('Email follow-up consent given')->assertDontSee('No follow-up requested.');
        $this->getJson(route('dashboard.notifications'))->assertOk()->assertDontSee($email);
        $csv = $this->get(route('dashboard.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Follow-up requested', $csv);
        $this->assertStringNotContainsString($email, $csv);
        $this->assertStringNotContainsString($email, $feedback->notification->message);
        Mail::assertSent(AdminFeedbackSubmitted::class, function ($mail) use ($email) {
            $body = view('emails.admin-feedback-submitted', ['feedback'=>$mail->feedback])->render();
            return strpos($body, 'Follow-up requested') !== false && strpos($body, $email) === false;
        });
        $this->actingAs(User::factory()->create())->get(route('feedback.detail', $feedback))->assertForbidden()->assertDontSee($email);
    }

    public function test_an_unchecked_follow_up_discards_even_a_posted_email_and_consent(): void
    {
        foreach ([[], ['follow_up_requested'=>'0']] as $choice) {
            $this->prepare();
            $this->post(route('feedback.store'), $choice + ['follow_up_email'=>['unwanted@example.test'], 'follow_up_consent'=>'1'])
                ->assertRedirect(route('feedback.thank-you'));
        }
        foreach (Feedback::all() as $feedback) {
            $this->assertNull($feedback->getRawOriginal('follow_up_email'));
            $this->assertNull($feedback->follow_up_consent_at);
            $this->assertSame('Anonymous', $feedback->customerLabel());
        }
    }

    public function test_follow_up_alone_does_not_mark_positive_feedback_as_a_concern_or_update_an_existing_submission(): void
    {
        $this->prepare();
        $survey = session('survey');
        $payload = ['follow_up_requested'=>'1', 'follow_up_email'=>'first@example.test', 'follow_up_consent'=>'1'];
        $this->post(route('feedback.store'), $payload)->assertRedirect(route('feedback.thank-you'));
        $this->withSession(['survey'=>$survey])->post(route('feedback.store'), array_merge($payload, ['follow_up_email'=>'changed@example.test']))
            ->assertRedirect(route('feedback.thank-you'));
        $this->assertDatabaseCount('feedback', 1);
        $this->assertSame('Submitted', Feedback::sole()->status);
        $this->assertSame('first@example.test', Feedback::sole()->follow_up_email);
        Mail::assertSent(AdminFeedbackSubmitted::class, 1);
    }

    public function test_follow_up_and_answers_survive_a_failed_transaction_and_can_be_opted_out_on_retry(): void
    {
        $this->prepare(['problem_comment'=>'Keep all answers']);
        $service = Mockery::mock(FeedbackNotifications::class);
        $service->shouldReceive('record')->once()->andThrow(new RuntimeException('Database write failed'));
        $this->app->instance(FeedbackNotifications::class, $service);
        $this->post(route('feedback.store'), ['follow_up_requested'=>'1', 'follow_up_email'=>'retry@example.test', 'follow_up_consent'=>'1'])
            ->assertRedirect(route('feedback.review'))->assertSessionHasErrors('submission')
            ->assertSessionHasInput('follow_up_email', 'retry@example.test')->assertSessionHas('survey.final.problem_comment', 'Keep all answers');
        $this->assertDatabaseCount('feedback', 0);
        $this->get(route('feedback.review'))->assertOk()->assertSee('value="retry@example.test"', false)->assertSee('Retry Submission');
        $this->app->instance(FeedbackNotifications::class, new FeedbackNotifications());
        $this->post(route('feedback.store'), ['follow_up_requested'=>'0', 'follow_up_email'=>'retry@example.test', 'follow_up_consent'=>'1'])
            ->assertRedirect(route('feedback.thank-you'));
        $this->assertNull(Feedback::sole()->follow_up_email);
        $this->assertNull(Feedback::sole()->follow_up_consent_at);
        $this->assertDatabaseCount('answers', 16);
    }

    public function test_admin_follow_up_action_notes_and_internal_resolution_update_history_and_counts(): void
    {
        $this->prepare(['problem_today'=>'Yes', 'problem_comment'=>'Delayed order']);
        $this->post(route('feedback.store'), ['follow_up_requested'=>'1', 'follow_up_email'=>'followup@example.test', 'follow_up_consent'=>'1']);
        $feedback = Feedback::sole();
        $this->actingAs($this->business->user)->post(route('feedback.action', $feedback), ['status'=>'Submitted', 'action_taken'=>'Not handled yet.'])
            ->assertSessionHasErrors('status');
        $this->assertSame('Needs Action', $feedback->fresh()->status);
        $this->assertSame(0, $feedback->actions()->count());
        $this->post(route('feedback.action', $feedback), ['status'=>'Needs Action', 'action_taken'=>'Contacted the customer by email; investigating.'])
            ->assertRedirect(route('feedback.detail', $feedback));
        $this->assertSame('Needs Action', $feedback->fresh()->status);
        $action = $feedback->actions()->sole();
        $this->assertSame($this->business->user_id, $action->user_id);
        $this->assertSame('Needs Action', $action->previous_status);
        $this->assertNotNull($action->created_at);
        $this->getJson(route('dashboard.stats'))->assertOk()->assertJsonPath('resolved', 0);
        $this->post(route('feedback.action', $feedback), [
            'status'=>'Resolved',
            'action_taken'=>'Concern corrected and customer informed.',
            'customer_message'=>'We corrected the issue and appreciate you bringing it to our attention.',
        ])->assertRedirect();
        $this->getJson(route('dashboard.stats'))->assertOk()->assertJsonPath('resolved', 1);
        $this->get(route('feedback.index', ['status'=>'Resolved']))->assertOk()->assertSee($feedback->displayCode());
        $this->assertSame(2, $feedback->actions()->count());
        $this->get(route('feedback.detail', $feedback))->assertOk()->assertSee('Contacted the customer by email; investigating.')
            ->assertSee($this->business->user->name)->assertSee('Concern resolved — Resolved');
        // Anonymous concerns can also be resolved through documented internal action.
        $this->prepare(['problem_today'=>'Yes', 'problem_comment'=>'Internal concern']);
        $this->post(route('feedback.store'))->assertRedirect(route('feedback.thank-you'));
        $anonymous = Feedback::latest('id')->first();
        $this->post(route('feedback.action', $anonymous), ['status'=>'Resolved', 'action_taken'=>'Corrected the service procedure internally.'])->assertRedirect();
        $this->assertNull($anonymous->fresh()->follow_up_email);
        $this->assertSame('Resolved', $anonymous->fresh()->status);
        $this->getJson(route('dashboard.stats'))->assertOk()->assertJsonPath('resolved', 2);
    }

    public function test_duplicate_retry_is_idempotent_and_new_surveys_get_unique_ids(): void
    {
        $this->prepare();
        $survey = session('survey');
        $this->post(route('feedback.store'))->assertRedirect(route('feedback.thank-you'));
        $this->post(route('feedback.store'))->assertRedirect(route('feedback.thank-you'));
        $this->withSession(['survey'=>$survey])->post(route('feedback.store'))->assertRedirect(route('feedback.thank-you'));
        $this->assertDatabaseCount('feedback',1);
        $this->prepare();
        $this->post(route('feedback.store'))->assertRedirect(route('feedback.thank-you'));
        $this->assertSame(['FB-000001','FB-000002'],Feedback::orderBy('id')->pluck('feedback_code')->all());
        Mail::assertSent(AdminFeedbackSubmitted::class,2);
    }

    public function test_database_commit_survives_smtp_failure(): void
    {
        $this->prepare();
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('SMTP unavailable'));
        $this->post(route('feedback.store'), ['follow_up_requested'=>'1', 'follow_up_email'=>'saved@example.test', 'follow_up_consent'=>'1'])
            ->assertRedirect(route('feedback.thank-you'));
        $this->assertDatabaseCount('answers',16);
        $this->assertDatabaseCount('admin_notifications',1);
        $this->assertSame('failed',Feedback::sole()->email_status);
        $this->assertSame('saved@example.test', Feedback::sole()->follow_up_email);
        $this->assertNotNull(Feedback::sole()->follow_up_consent_at);
    }

    public function test_failed_transaction_rolls_back_and_retains_review_for_retry(): void
    {
        $this->prepare(['problem_comment'=>'Keep this comment']);
        $service = Mockery::mock(FeedbackNotifications::class);
        $service->shouldReceive('record')->once()->andThrow(new RuntimeException('Database write failed'));
        $this->app->instance(FeedbackNotifications::class,$service);
        $this->post(route('feedback.store'))->assertRedirect(route('feedback.review'))->assertSessionHasErrors('submission')
            ->assertSessionHas('survey.final.problem_comment','Keep this comment');
        $this->assertDatabaseCount('feedback',0);
        $this->assertDatabaseCount('answers',0);
        $this->get(route('feedback.review'))->assertOk()->assertSee('Retry Submission');
        $this->app->instance(FeedbackNotifications::class,new FeedbackNotifications());
        $this->post(route('feedback.store'))->assertRedirect(route('feedback.thank-you'));
    }

    public function test_database_outage_preserves_retry_page(): void
    {
        $this->prepare(['problem_comment'=>'Retain during outage']);
        $original = config('database.default');
        config(['database.connections.unavailable'=>['driver'=>'sqlite','database'=>storage_path('does-not-exist.sqlite'),'prefix'=>''],'database.default'=>'unavailable']);
        try {
            $this->post(route('feedback.store'))->assertRedirect(route('feedback.review'))->assertSessionHasErrors('submission');
            $this->get(route('feedback.review'))->assertOk()->assertSee('Retain during outage');
        } finally { config(['database.default'=>$original]); }
        $this->assertDatabaseCount('feedback',0);
    }

    public function test_explicit_slider_selection_and_staff_na(): void
    {
        $this->withSession(['survey'=>$this->survey()])->post(route('feedback.store-rating.save'),['cleanliness'=>5,'speed_of_service'=>5,'staff'=>5])
            ->assertSessionHasErrors(['cleanliness_selected','speed_of_service_selected','staff_selected']);
        $this->post(route('feedback.store-rating.save'),['cleanliness'=>5,'speed_of_service'=>7,'staff_na'=>1,'cleanliness_selected'=>1,'speed_of_service_selected'=>1])
            ->assertRedirect(route('feedback.food-rating'))->assertSessionHas('survey.store_rating.staff','N/A');
        $food = $this->survey()['food_rating'];
        $this->post(route('feedback.food-rating.save'),['food_ratings'=>$food])->assertSessionHasErrors('food_rating_selected.taste');
        $this->post(route('feedback.food-rating.save'),['food_ratings'=>$food,'food_rating_selected'=>array_fill_keys(array_keys($food),'1')])
            ->assertRedirect(route('feedback.final'));
    }

    public function test_optional_custom_rating_does_not_record_an_untouched_default(): void
    {
        $question = $this->business->questions()->create(['question'=>'Optional rating','question_type'=>'rating','required'=>false,'step'=>'order','field_key'=>'custom_optional','sort_order'=>100]);
        $payload = ['order_type'=>'Dine In','order_details'=>'Coffee','extra'=>[$question->id=>5]];
        $this->withSession(['survey'=>$this->survey()])->post(route('feedback.order.save'), $payload)->assertRedirect(route('feedback.store-rating'));
        $this->assertArrayNotHasKey($question->id, session('survey.extras.order'));
        $payload['extra_selected'] = [$question->id=>'1'];
        $this->post(route('feedback.order.save'), $payload)->assertRedirect(route('feedback.store-rating'));
        $this->assertEquals(5, session('survey.extras.order.' . $question->id));
    }

    public function test_future_visit_and_skipped_steps_are_rejected(): void
    {
        $this->post(route('feedback.visit'),[])->assertRedirect(route('home'));
        $this->withSession(['survey'=>['agreed'=>true]])->post(route('feedback.food-rating.save'),[])->assertRedirect(route('feedback.show'));
        $this->withSession(['survey'=>$this->survey()])->post(route('feedback.visit'),['date_of_visit'=>now()->addDay()->toDateString(),'time_of_order'=>'10:00'])
            ->assertSessionHasErrors('date_of_visit');
        $this->travelTo(now()->startOfDay()->addHours(12));
        $this->withSession(['survey'=>$this->survey()])->post(route('feedback.visit'),['date_of_visit'=>now()->toDateString(),'time_of_order'=>'13:00'])->assertSessionHasErrors('time_of_order');
    }

    public function test_draft_continue_start_over_expiration_and_single_render(): void
    {
        $this->withSession(['survey'=>$this->survey()]);
        foreach (['feedback.order','feedback.store-rating','feedback.food-rating','feedback.final'] as $route) {
            for ($reload=0;$reload<2;$reload++) {
                $html = $this->get(route($route))->assertOk()->getContent();
                $this->assertSame(1,substr_count($html,'<header class="topbar">'));
                $this->assertSame(1,substr_count($html,'<footer class="footer">'));
                $this->assertSame(1,substr_count($html,'<section class="survey-card '));
            }
        }
        $this->get(route('home'))->assertSee('Continue Survey')->assertSessionHas('survey');
        $this->get(route('feedback.continue'))->assertRedirect(route('feedback.final'));
        $this->post(route('feedback.restart'))->assertRedirect(route('home'))->assertSessionMissing('survey');
        $draft = $this->survey(); $draft['expires_at'] = now()->subMinute()->toIso8601String();
        $this->withSession(['survey'=>$draft])->get(route('feedback.continue'))->assertRedirect(route('home'))->assertSessionMissing('survey');
    }

    public function test_question_snapshot_preserves_active_draft_and_historical_answers(): void
    {
        $question = $this->business->questions()->create(['question'=>'Preferred drink?','question_type'=>'multiple_choice','options'=>"Tea\nCoffee",'required'=>true,'step'=>'order','field_key'=>'custom_drink','sort_order'=>100]);
        $draft = $this->survey();
        $this->withSession(['survey'=>$draft])->post(route('feedback.order.save'),['order_type'=>'Dine In','order_details'=>'Coffee'])
            ->assertSessionHasErrors('extra.' . $question->id);
        $this->post(route('feedback.order.save'),['order_type'=>'Dine In','order_details'=>'Coffee','extra'=>[$question->id=>'Invalid']])->assertSessionHasErrors('extra.' . $question->id);
        $this->post(route('feedback.order.save'),['order_type'=>'Dine In','order_details'=>'Coffee','extra'=>[$question->id=>'Tea']])->assertRedirect(route('feedback.store-rating'));
        $question->update(['question'=>'Changed text','options'=>"Water\nJuice",'archived_at'=>now()]);
        $this->post(route('feedback.final.save'),$this->finalAnswers())->assertRedirect(route('feedback.review'));
        $this->get(route('feedback.review'))->assertOk()->assertSee('Preferred drink?');
        $this->post(route('feedback.store'))->assertRedirect(route('feedback.thank-you'));
        $answer = Feedback::sole()->answers()->where('question_id',$question->id)->firstOrFail();
        $this->assertSame('Preferred drink?',$answer->question_text);
        $this->assertSame("Tea\nCoffee",$answer->question_options);
        $this->assertSame('Tea',$answer->answer);
        $question->delete();
        $this->assertDatabaseHas('answers',['id'=>$answer->id]);
    }

    public function test_business_link_pins_correct_recipient_and_closed_survey_blocks_start(): void
    {
        $other = Business::create(['user_id'=>User::factory()->create()->id,'business_name'=>'Other Cafe','owner_name'=>'Other','business_type'=>'Cafe','address'=>'Other','contact_number'=>'123','notification_email'=>'other@example.test']);
        $this->get(route('home',['business'=>$other->survey_slug]))->assertOk()
            ->assertViewHas('business', fn ($business) => $business->id === $other->id)->assertDontSee('Other Cafe');
        $this->confirmTerms();
        $this->post(route('feedback.start'),['consent'=>'agree'])->assertRedirect(route('feedback.show'))->assertSessionHas('survey.business_id',$other->id);
        $this->assertSame($other->id,session('survey.business_id'));
        $this->post(route('feedback.restart'));
        $other->update(['survey_enabled'=>false]);
        $this->post(route('feedback.start'),['consent'=>'agree'])->assertSessionHasErrors('survey');
    }

    public function test_concern_workflow_requires_resolution_note_and_records_history(): void
    {
        $this->prepare(['problem_today'=>'Yes','problem_comment'=>'Order was delayed']);
        $this->post(route('feedback.store'));
        $feedback = Feedback::sole();
        $this->assertSame('Needs Action',$feedback->status);
        $this->actingAs($this->business->user)->post(route('feedback.action',$feedback),['status'=>'Resolved'])->assertSessionHasErrors('action_taken');
        $this->post(route('feedback.action',$feedback),['status'=>'In Progress','action_taken'=>'Reviewing order'])->assertRedirect();
        $this->post(route('feedback.action',$feedback),['status'=>'Resolved','action_taken'=>'Concern handled'])->assertRedirect();
        $feedback->refresh();
        $this->assertSame('Resolved',$feedback->status);
        $this->assertNotNull($feedback->resolved_at);
        $this->assertSame('Concern handled',$feedback->resolution_note);
        $this->assertCount(2,$feedback->actions);
        $this->assertSame('Needs Action',$feedback->actions->first()->previous_status);
        $this->actingAs(User::factory()->create())->get(route('feedback.detail',$feedback))->assertForbidden();
        $this->post(route('feedback.action',$feedback),['status'=>'Submitted'])->assertForbidden();
    }
}
