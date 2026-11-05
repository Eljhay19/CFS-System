<?php

namespace Tests\Feature;

use App\Mail\ContactMessageReceived;
use App\Models\Business;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    use RefreshDatabase;

    private $business;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['feedback.support_email'=>null, 'feedback.support_phone'=>null]);
        $this->business = Business::create(['user_id'=>User::factory()->create()->id, 'business_name'=>'CFS Business',
            'owner_name'=>'Admin', 'business_type'=>'Services', 'address'=>'Private address', 'contact_number'=>'123', 'email'=>'admin@example.test']);
    }

    private function payload(): array
    {
        $page = $this->get(route('contact'))->assertOk();
        return ['name'=>'', 'email'=>'visitor@example.test', 'message'=>'Please help with the survey.', 'submission_token'=>$page->viewData('contactToken')];
    }

    public function test_contact_message_is_stored_separately_and_emailed_to_configured_admin(): void
    {
        $payload = $this->payload();
        $this->post(route('contact.store'), $payload)->assertRedirect(route('contact'))->assertSessionHas('contact_status');
        $message = ContactMessage::sole();
        $this->assertNull($message->name);
        $this->assertSame('visitor@example.test', $message->email);
        $this->assertSame($this->business->id, $message->business_id);
        $this->assertSame($this->business->user->email, $message->recipient_email);
        $this->assertDatabaseCount('feedback', 0);
        $this->assertSame(1, $this->business->user->notifications()->where('contact_message_id', $message->id)->count());
        Mail::assertSent(ContactMessageReceived::class, function ($mail) {
            $html = view('emails.contact-message', ['contactMessage'=>$mail->contactMessage])->render();
            return $mail->hasTo($this->business->user->email) && strpos($html, 'Please help with the survey.') !== false;
        });
        $this->post(route('contact.store'), $payload)->assertRedirect(route('contact'));
        $this->assertDatabaseCount('contact_messages', 1);
        Mail::assertSent(ContactMessageReceived::class, 1);
    }

    public function test_public_support_recipient_overrides_business_email_without_exposing_private_details(): void
    {
        config(['feedback.support_email'=>'support@example.test']);
        $payload = $this->payload();
        $this->post(route('contact.store'), $payload)->assertRedirect(route('contact'));
        Mail::assertSent(ContactMessageReceived::class, fn ($mail) => $mail->hasTo('support@example.test'));
        $this->get(route('contact'))->assertOk()->assertSee('support@example.test')->assertDontSee('admin@example.test');
    }

    public function test_reusing_a_form_with_different_content_preserves_the_new_message(): void
    {
        $payload = $this->payload();
        $this->post(route('contact.store'), $payload)->assertRedirect(route('contact'));
        $this->post(route('contact.store'), array_merge($payload, ['message'=>'A different support question.']))
            ->assertSessionHasErrors('contact')->assertSessionHas('_old_input.message', 'A different support question.');
        $this->assertDatabaseCount('contact_messages', 1);
        $this->assertNotSame($payload['submission_token'], session('contact.token'));
    }

    public function test_contact_validation_and_invalid_token_do_not_create_records(): void
    {
        $payload = $this->payload();
        $this->post(route('contact.store'), array_merge($payload, ['email'=>'invalid', 'message'=>'']))->assertSessionHasErrors(['email','message']);
        $this->post(route('contact.store'), array_merge($payload, ['submission_token'=>(string) \Illuminate\Support\Str::uuid()]))->assertSessionHasErrors('contact');
        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingSent();
    }

    public function test_mail_failure_keeps_the_saved_message(): void
    {
        $payload = $this->payload();
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('Mail provider unavailable'));
        $this->post(route('contact.store'), $payload)->assertRedirect(route('contact'))->assertSessionHas('contact_status');
        $message = ContactMessage::sole();
        $this->assertSame('failed', $message->email_status);
        $this->assertSame($payload['message'], $message->message);
    }

    public function test_failed_database_save_preserves_message_for_retry(): void
    {
        $payload = $this->payload();
        ContactMessage::creating(function () { throw new RuntimeException('Temporary storage failure'); });
        try {
            $this->post(route('contact.store'), $payload)->assertSessionHasErrors('contact')
                ->assertSessionHas('_old_input.message', $payload['message'])->assertSessionHas('contact.token', $payload['submission_token']);
            Mail::assertNothingSent();
        } finally { ContactMessage::flushEventListeners(); }
        $this->post(route('contact.store'), $payload)->assertRedirect(route('contact'));
        $this->assertDatabaseCount('contact_messages', 1);
    }
}
