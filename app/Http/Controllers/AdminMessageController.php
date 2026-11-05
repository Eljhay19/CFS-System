<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Jobs\SendContactMessageReply as SendContactMessageReplyJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminMessageController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->user()->business;
        abort_unless($business, 404);
        $data = $request->validate(['search' => ['nullable', 'string', 'max:200']]);
        $search = trim($data['search'] ?? '');
        $messages = ContactMessage::where('business_id', $business->id)->when($search !== '', function ($query) use ($search) {
            $query->where(fn ($fields) => $fields->where('name', 'like', '%' . $search . '%')->orWhere('email', 'like', '%' . $search . '%')->orWhere('message', 'like', '%' . $search . '%'));
        })->latest()->paginate(20)->appends($request->only('search'));
        return view('messages.index', compact('messages'));
    }

    public function show(Request $request, ContactMessage $contactMessage)
    {
        abort_unless(optional($request->user()->business)->id === $contactMessage->business_id, 403);
        $request->user()->notifications()->where('contact_message_id', $contactMessage->id)->where('is_read', false)->update(['is_read' => true]);
        return view('messages.show', ['message' => $contactMessage->load('replies')]);
    }

    public function reply(Request $request, ContactMessage $contactMessage)
    {
        $this->authorizeMessage($request, $contactMessage);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $reply = $contactMessage->replies()->create([
            'user_id' => $request->user()->id,
            'body' => trim($data['body']),
            'email_status' => 'pending',
        ]);

        return $this->deliverReply($contactMessage, $reply);
    }

    public function retryReply(Request $request, ContactMessage $contactMessage, ContactMessageReply $reply)
    {
        $this->authorizeMessage($request, $contactMessage);
        abort_unless((int) $reply->contact_message_id === (int) $contactMessage->id, 404);
        abort_unless(in_array($reply->email_status, ['failed', 'pending'], true), 409);

        return $this->deliverReply($contactMessage, $reply);
    }

    private function deliverReply(ContactMessage $contactMessage, ContactMessageReply $reply)
    {
        try {
            $reply->update(['email_status' => 'queued', 'email_error' => null]);
            SendContactMessageReplyJob::dispatch($reply->id)->afterCommit();

            return redirect()->route('messages.show', $contactMessage)->with('status', 'Reply saved and queued for email delivery.');
        } catch (Throwable $error) {
            Log::error('Contact reply email could not be queued.', [
                'contact_message_id' => $contactMessage->id,
                'reply_id' => $reply->id,
                'error' => $error->getMessage(),
            ]);
            $reply->update([
                'email_status' => 'failed',
                'email_error' => 'Email could not be queued. Check the database queue settings and retry this reply.',
            ]);

            return redirect()->route('messages.show', $contactMessage)->withErrors([
                'reply' => 'The reply was saved, but could not be queued. Check the queue settings and retry it below.',
            ]);
        }
    }

    private function authorizeMessage(Request $request, ContactMessage $contactMessage): void
    {
        abort_unless(optional($request->user()->business)->id === $contactMessage->business_id, 403);
    }
}
