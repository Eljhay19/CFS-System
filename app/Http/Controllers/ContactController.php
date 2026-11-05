<?php

namespace App\Http\Controllers;

use App\Jobs\SendContactMessageNotification;
use App\Models\Business;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ContactController extends Controller
{
    public function show(Request $request)
    {
        if (! $request->session()->has('contact.token')) {
            $id = session('survey.business_id', session('survey_business_id', config('feedback.business_id')));
            $business = $id ? Business::find($id) : Business::where('is_default', true)->first();
            $request->session()->put('contact', ['token'=>(string) Str::uuid(), 'business_id'=>optional($business)->id]);
        }
        return view('contact', [
            'supportEmail' => trim((string) config('feedback.support_email')),
            'supportPhone' => trim((string) config('feedback.support_phone')),
            'contactToken' => session('contact.token'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:100', 'not_regex:/[\r\n]/'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'submission_token' => ['required', 'uuid'],
        ]);
        $token = $data['submission_token'];
        $allowed = session('contact.token');
        $last = session('contact_last_token');
        if ((! $allowed || ! hash_equals($allowed, $token)) && (! $last || ! hash_equals($last, $token))) {
            return back()->withErrors(['contact'=>'Please reload Contact Us and try again.'])->withInput($request->only('name', 'email', 'message'));
        }
        try {
            $existing = ContactMessage::where('submission_token', $token)->first();
            if ($existing) {
                if ($existing->email !== $data['email'] || $existing->message !== $data['message'] || $existing->name !== ($data['name'] ?? null)) {
                    $request->session()->put('contact', ['token'=>(string) Str::uuid(), 'business_id'=>$existing->business_id]);
                    return back()->withErrors(['contact'=>'This form was already used. Review your message and send it again.'])->withInput($request->only('name', 'email', 'message'));
                }
                return $this->received($request, $token);
            }
            $business = Business::find(session('contact.business_id'));
            // Contact Us uses the public support address when configured;
            // otherwise it falls back to the selected business recipient.
            $recipient = trim((string) config('feedback.support_email')) ?: optional($business)->notificationRecipient();
            if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                return back()->withErrors(['contact'=>'The message service is temporarily unavailable. Please try again later.'])->withInput($request->only('name', 'email', 'message'));
            }
            $message = DB::transaction(function () use ($data, $business, $recipient) {
                $message = ContactMessage::create(array_merge($data, ['business_id'=>optional($business)->id, 'recipient_email'=>$recipient]));
                if ($business && $business->dashboard_notifications && $business->contact_message_alerts) {
                    $business->user->notifications()->create([
                        'contact_message_id' => $message->id, 'title' => 'New contact message received',
                        'message' => Str::limit($message->message, 160), 'type' => 'info',
                    ]);
                }
                return $message;
            });
        } catch (Throwable $error) {
            report($error);
            return back()->withErrors(['contact'=>'Your message could not be saved. Please try again.'])->withInput($request->only('name', 'email', 'message'));
        }
        try {
            $message->update(['email_status' => 'queued', 'email_error' => null]);
            SendContactMessageNotification::dispatch($message->id)->afterCommit();
        } catch (Throwable $error) {
            Log::error('Contact message email could not be queued.', [
                'contact_message_id' => $message->id,
                'error' => $error->getMessage(),
            ]);
            try { $message->update(['email_status' => 'failed', 'email_error' => 'Email could not be queued. Check the database queue settings.']); }
            catch (Throwable $statusError) { report($statusError); }
        }
        return $this->received($request, $token);
    }

    private function received(Request $request, string $token)
    {
        if ($request->session()->get('contact.token') === $token) $request->session()->forget('contact');
        $request->session()->put('contact_last_token', $token);
        return redirect()->route('contact')->with('contact_status', 'Your message has been received.');
    }
}
