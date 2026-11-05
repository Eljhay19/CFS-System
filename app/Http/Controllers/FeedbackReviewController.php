<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Question;
use App\Models\FeedbackAction;
use App\Services\FeedbackCustomerUpdates;
use App\Services\FeedbackFilters;
use App\Services\FeedbackSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FeedbackReviewController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->user()->business;

        if (! $business) {
            return redirect()->route('business.edit');
        }

        $liveRevision = app(FeedbackSnapshot::class)->revision($business);
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $unread = $request->boolean('unread');
        $filterService = app(FeedbackFilters::class);
        $filters = $filterService->values($request);
        $feedbackQuery = $filterService->query($business, $request);
        $allFeedback = (clone $feedbackQuery)->get();

        $totalFeedback = $allFeedback->count();
        $ratingAnswers = $allFeedback->flatMap->answers->filter(function ($answer) {
            return $answer->type() === Question::TYPE_RATING && is_numeric($answer->answer);
        });
        $averageRating = $ratingAnswers->count() ? round($ratingAnswers->avg('answer'), 1) : 0;
        $resolvedFeedback = $allFeedback->where('status', 'Resolved')->count();
        $needsActionFeedback = $allFeedback->filter(function ($item) {
            return $item->status === 'Needs Action';
        })->count();
        $todayFeedback = $allFeedback->filter(function ($item) {
            return optional($item->submitted_at)->isToday();
        })->count();
        $ratingBuckets = collect([5, 4, 3, 2, 1])->mapWithKeys(function ($stars) use ($allFeedback) {
            $count = $allFeedback->filter(function ($item) use ($stars) {
                $average = $item->ratingAverage();

                if ($average === null) {
                    return false;
                }

                return max(1, min(5, (int) round($average / 2))) === $stars;
            })->count();

            return [$stars => $count];
        });
        $statusCounts = collect([
            'Submitted' => $allFeedback->whereIn('status', ['Submitted', 'New'])->count(),
            'Needs Action' => $allFeedback->where('status', 'Needs Action')->count(),
            'In Progress' => $allFeedback->where('status', 'In Progress')->count(),
            'Resolved' => $resolvedFeedback,
        ]);
        $statusColors = [
            'Submitted' => '#045eb7',
            'Needs Action' => '#f59e0b',
            'In Progress' => '#536f9a',
            'Resolved' => '#0f9f6e',
        ];
        $statusCursor = 0;
        $statusSegments = $statusCounts->map(function ($count, $label) use (&$statusCursor, $totalFeedback, $statusColors) {
            $from = $totalFeedback ? round(($statusCursor / $totalFeedback) * 360, 1) : 0;
            $statusCursor += $count;
            $to = $totalFeedback ? round(($statusCursor / $totalFeedback) * 360, 1) : 0;

            return [
                'label' => $label,
                'count' => $count,
                'color' => $statusColors[$label],
                'from' => $from,
                'to' => $to,
            ];
        })->values();
        $dailyTrend = $filterService->trend($allFeedback, $filters['period'] ?? 'daily', $filters['to'] ?? null);

        return view('feedback.index', [
            'liveRevision' => $liveRevision,
            'filters' => $filters,
            'feedbackItems' => $feedbackQuery->paginate(10)->appends($filters),
            'search' => $search,
            'status' => $status,
            'unread' => $unread,
            'feedbackOverview' => [
                'total' => $totalFeedback,
                'averageRating' => $averageRating,
                'resolved' => $resolvedFeedback,
                'needsAction' => $needsActionFeedback,
                'today' => $todayFeedback,
                'ratingBuckets' => $ratingBuckets,
                'statusCounts' => $statusCounts,
                'statusSegments' => $statusSegments,
                'dailyTrend' => $dailyTrend,
                'metricLists' => [
                    'total' => $allFeedback,
                    'average' => $allFeedback->filter(fn ($item) => $item->ratingAverage() !== null)->values(),
                    'resolved' => $allFeedback->where('status', 'Resolved')->values(),
                    'needs_action' => $allFeedback->where('status', 'Needs Action')->values(),
                ],
            ],
        ]);
    }

    public function show(Request $request, Feedback $feedback)
    {
        $feedback->load('answers.question', 'business', 'actions.user');

        if ($feedback->business->user_id !== auth()->id()) {
            abort(403);
        }

        $notificationId = (int) $request->query('notification', 0);
        if ($notificationId) {
            $feedback->notifications()->whereKey($notificationId)
                ->where('user_id', auth()->id())->update(['is_read' => true]);
        } else {
            $feedback->notification()->where('user_id', auth()->id())
                ->where('is_read', false)->update(['is_read' => true]);
        }

        $coreOrder = array_flip(['date_of_visit', 'time_of_order', 'order_type', 'order_details',
            'cleanliness', 'speed_of_service', 'staff', 'taste', 'freshness', 'temperature', 'presentation', 'value_for_money',
            'order_accurate', 'food_satisfied', 'problem_today', 'problem_comment']);
        $feedback->setRelation('answers', $feedback->answers->sortBy(function ($answer) use ($coreOrder) {
            $key = $answer->field_key ?: optional($answer->question)->field_key;
            return $coreOrder[$key] ?? count($coreOrder) + $answer->id;
        })->values());

        return view('feedback.show', [
            'feedback' => $feedback,
            'visitAnswers' => $feedback->answers->filter(fn ($answer) => in_array($answer->field_key ?: optional($answer->question)->field_key, ['date_of_visit', 'time_of_order', 'order_type', 'order_details'], true)),
            'ratingQuestions' => $feedback->answers->filter(function ($answer) {
                return $answer->type() === Question::TYPE_RATING;
            }),
        ]);
    }

    public function updateAction(Request $request, Feedback $feedback)
    {
        $feedback->load('business');

        if ($feedback->business->user_id !== auth()->id()) {
            abort(403);
        }

        $allowedStatuses = $feedback->hasCustomerConcern()
            ? ['Needs Action', 'In Progress', 'Resolved']
            : ['Submitted', 'Needs Action', 'In Progress', 'Resolved'];
        $data = $request->validate([
            'status' => ['required', Rule::in($allowedStatuses)],
            'action_taken' => [Rule::requiredIf($request->input('status') === 'Resolved'), 'nullable', 'string', 'max:2000'],
            'customer_message' => ['nullable', 'string', 'max:2000'],
        ], ['status.in' => 'Keep a reported concern as Needs Action or In Progress until it is resolved.']);
        $data['action_taken'] = $data['action_taken'] ?? null;
        $customerMessage = trim((string) ($data['customer_message'] ?? '')) ?: null;
        unset($data['customer_message']);
        if ($customerMessage && ! $feedback->hasFollowUp()) {
            return back()->withErrors(['customer_message' => 'A customer email can only be sent when the customer requested follow-up and gave consent.'])->withInput();
        }
        if ($data['status'] === 'Resolved' && $feedback->hasFollowUp() && ! $customerMessage) {
            return back()->withErrors(['customer_message' => 'Add a customer-facing summary of the outcome before marking this follow-up resolved.'])->withInput();
        }

        $action = DB::transaction(function () use ($feedback, $data, $request, $customerMessage) {
            $feedback = Feedback::whereKey($feedback->id)->lockForUpdate()->firstOrFail();
            $latestAction = $feedback->actions()->latest('id')->first();
            $sameAction = $latestAction
                && $latestAction->status === $data['status']
                && $latestAction->note === ($data['action_taken'] ?? null)
                && $latestAction->customer_message === $customerMessage;
            if ($feedback->status === $data['status']
                && $feedback->action_taken === ($data['action_taken'] ?? null)
                && ($customerMessage === null || $sameAction)) {
                return null;
            }
            $action = $feedback->actions()->create([
                'user_id' => $request->user()->id,
                'previous_status' => $feedback->status,
                'status' => $data['status'],
                'note' => $data['action_taken'] ?? null,
                'customer_message' => $customerMessage,
            ]);
            $feedback->update(array_merge($data, ['resolved_at'=>$data['status'] === 'Resolved' ? ($feedback->resolved_at ?: now()) : null, 'resolution_note'=>$data['status'] === 'Resolved' ? $data['action_taken'] : null]));
            $feedback->notifications()->where('user_id', $request->user()->id)->update(['is_read' => true]);
            return $action;
        });

        if ($action) {
            $feedback = Feedback::with('business.user', 'answers.question')->findOrFail($feedback->id);
            $customerEmailStatus = app(FeedbackCustomerUpdates::class)->queue($feedback, $action);
            $adminEmailStatus = app(\App\Services\FeedbackActionNotifications::class)->notify($feedback, $action, $request->user());
        } else {
            $adminEmailStatus = null;
            $customerEmailStatus = null;
        }

        $statusMessage = 'No changes to save.';
        if ($action) {
            $statusMessage = 'Admin action updated.';
            if ($feedback->hasFollowUp() && ($action->previous_status !== $action->status || $customerMessage)) {
                $statusMessage .= match ($customerEmailStatus) {
                    'sent' => ' The customer update was emailed.',
                    'queued', 'sending' => ' The customer update is queued for delivery.',
                    default => ' The customer update email failed; check SMTP settings and retry it from Action History.',
                };
            }
            $statusMessage .= match ($adminEmailStatus) {
                'sent' => ' Admin notification sent.',
                'queued', 'sending' => ' Admin notification is queued for delivery.',
                'disabled' => ' Admin email alerts are turned off in Settings.',
                default => ' Admin notification email failed; check SMTP settings.',
            };
        }

        return redirect()
            ->route('feedback.detail', $feedback)
            ->with('status', $statusMessage);
    }

    public function retryCustomerUpdate(Request $request, Feedback $feedback, FeedbackAction $action, FeedbackCustomerUpdates $updates)
    {
        $feedback->load('business');
        abort_unless($feedback->business->user_id === $request->user()->id, 403);
        abort_unless((int) $action->feedback_id === (int) $feedback->id, 404);
        abort_unless($feedback->hasFollowUp(), 403);

        $status = $updates->queue($feedback, $action);
        $message = match ($status) {
            'sent' => 'Customer update email sent.',
            'queued', 'sending' => 'Customer update email queued for delivery.',
            default => 'Customer update email failed. Check the SMTP sender credentials and try again.',
        };

        return redirect()->route('feedback.detail', $feedback)->with('status', $message);
    }
}
