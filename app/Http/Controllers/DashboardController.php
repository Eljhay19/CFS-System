<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Services\DashboardAnalytics;
use App\Services\FeedbackFilters;
use App\Services\FeedbackSnapshot;
use App\Services\NotificationEmailVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return $this->renderDashboard($request);
    }

    public function section(Request $request, string $section)
    {
        abort_unless(in_array($section, ['total', 'average', 'today', 'unread', 'inbox'], true), 404);

        return $this->renderDashboard($request, $section);
    }

    private function renderDashboard(Request $request, ?string $section = null)
    {
        $business = $request->user()->business;

        if (! $business) {
            return redirect()
                ->route('business.edit')
                ->with('status', 'Please complete your business information first.');
        }

        $liveRevision = app(FeedbackSnapshot::class)->revision($business);
        $filterService = app(FeedbackFilters::class);
        $filters = $filterService->values($request);
        $feedback = $filterService->query($business, $request, false)->get();
        $notificationQuery = $request->user()->notificationCenterItems()->where(function ($query) use ($feedback, $business, $filters) {
            $query->whereIn('feedback_id', $feedback->pluck('id'));
            if (empty($filters['status']) && ($filters['filter'] ?? 'all') === 'all' && empty($filters['search'])) {
                $query->orWhere('type', 'system');
                if ($business->contact_message_alerts) {
                    $query->orWhereHas('contactMessage', function ($contact) use ($business, $filters) {
                        $contact->where('business_id', $business->id);
                        if (! empty($filters['from'])) $contact->whereDate('created_at', '>=', $filters['from']);
                        if (! empty($filters['to'])) $contact->whereDate('created_at', '<=', $filters['to']);
                    });
                }
            }
        });
        $totalFeedback = $feedback->count();
        $ratingAnswers = $feedback->flatMap->answers->filter(function ($answer) {
            return $answer->type() === Question::TYPE_RATING && is_numeric($answer->answer);
        });
        $averageRating = $ratingAnswers->count() ? round($ratingAnswers->avg('answer'), 1) : 0;
        $resolvedFeedback = $feedback->where('status', 'Resolved')->count();
        $needsActionFeedback = $feedback->filter(function ($item) {
            return $item->status === 'Needs Action';
        })->count();
        $responseRate = $totalFeedback ? round(($resolvedFeedback / $totalFeedback) * 100) : 0;
        $ratingBuckets = collect([5, 4, 3, 2, 1])->mapWithKeys(function ($stars) use ($feedback) {
            $count = $feedback->filter(function ($item) use ($stars) {
                $average = $item->ratingAverage();

                if ($average === null) {
                    return false;
                }

                return max(1, min(5, (int) round($average / 2))) === $stars;
            })->count();

            return [$stars => $count];
        });
        $ratingTrend = $filterService->trend($feedback, $filters['period'] ?? 'daily', $filters['to'] ?? null);
        $feedbackSparks = collect([
            'all' => $feedback,
            'resolved' => $feedback->where('status', 'Resolved'),
            'needs' => $feedback->where('status', 'Needs Action'),
            'today' => $feedback->filter(fn ($item) => optional($item->submitted_at)->isToday()),
        ])->map(fn ($items) => $filterService->trend($items, $filters['period'] ?? 'daily', $filters['to'] ?? null)->take(-4));
        $activeFeedbackFilter = $section === 'total' ? $request->query('filter', 'all') : 'all';

        if (! in_array($activeFeedbackFilter, ['all', 'resolved', 'needs', 'today'], true)) {
            $activeFeedbackFilter = 'all';
        }

        $feedbackDetailItems = $feedback;

        if ($activeFeedbackFilter === 'resolved') {
            $feedbackDetailItems = $feedback->where('status', 'Resolved')->values();
        } elseif ($activeFeedbackFilter === 'needs') {
            $feedbackDetailItems = $feedback->filter(function ($item) {
                return $item->status === 'Needs Action';
            })->values();
        } elseif ($activeFeedbackFilter === 'today') {
            $feedbackDetailItems = $feedback->filter(function ($item) {
                return optional($item->submitted_at)->isToday();
            })->values();
        }

        $feedbackDetailTotal = $feedbackDetailItems->count();
        $feedbackDetailRatingAnswers = $feedbackDetailItems->flatMap->answers->filter(function ($answer) {
            return $answer->type() === Question::TYPE_RATING && is_numeric($answer->answer);
        });
        $feedbackDetailAverageRating = $feedbackDetailRatingAnswers->count() ? round($feedbackDetailRatingAnswers->avg('answer'), 1) : 0;
        $feedbackDetailRatingBuckets = collect([5, 4, 3, 2, 1])->mapWithKeys(function ($stars) use ($feedbackDetailItems) {
            $count = $feedbackDetailItems->filter(function ($item) use ($stars) {
                $average = $item->ratingAverage();

                if ($average === null) {
                    return false;
                }

                return max(1, min(5, (int) round($average / 2))) === $stars;
            })->count();

            return [$stars => $count];
        });

        return view('dashboard', [
            'dashboardDistribution' => app(DashboardAnalytics::class)->distribution($feedback),
            'dashboardSeries' => app(DashboardAnalytics::class)->series($feedback, $filters['period'] ?? 'daily'),
            'liveRevision' => $liveRevision,
            'feedbackSparks' => $feedbackSparks,
            'filters' => $filters,
            'activeDashboardSection' => $section,
            'activeFeedbackFilter' => $activeFeedbackFilter,
            'business' => $business,
            'totalFeedback' => $totalFeedback,
            'averageRating' => $averageRating,
            'ratingBuckets' => $ratingBuckets,
            'ratingTrend' => $ratingTrend,
            'allFeedback' => $feedback,
            'feedbackDetailItems' => $feedbackDetailItems,
            'feedbackDetailTotal' => $feedbackDetailTotal,
            'feedbackDetailAverageRating' => $feedbackDetailAverageRating,
            'feedbackDetailRatingBuckets' => $feedbackDetailRatingBuckets,
            'recentFeedback' => $feedback->take(10),
            'resolvedFeedback' => $resolvedFeedback,
            'needsActionFeedback' => $needsActionFeedback,
            'responseRate' => $responseRate,
            'notifications' => (clone $notificationQuery)->with('feedback.answers.question')->latest()->orderByDesc('id')->take(5)->get(),
            'notificationItems' => $section === 'inbox' ? (clone $notificationQuery)->with('feedback.answers.question')->latest()->orderByDesc('id')->paginate(20)->withQueryString() : null,
            'unreadNotificationItems' => (clone $notificationQuery)->with('feedback.answers.question')->where('is_read', false)->latest()->orderByDesc('id')->get(),
            'unreadNotifications' => (clone $notificationQuery)->where('is_read', false)->count(),
            'todayFeedbackItems' => $feedback->filter(function ($item) {
                return optional($item->submitted_at)->isToday();
            })->values(),
            'todayFeedback' => $feedback->filter(function ($item) {
                return optional($item->submitted_at)->isToday();
            })->count(),
        ]);
    }

    public function stats(Request $request)
    {
        $business = $request->user()->business;

        abort_if(! $business, 404);

        $revision = app(FeedbackSnapshot::class)->revision($business);
        $feedback = app(FeedbackFilters::class)->query($business, $request, false)->get();
        $ratingAnswers = $feedback->flatMap->answers->filter(function ($answer) {
            return $answer->type() === Question::TYPE_RATING && is_numeric($answer->answer);
        });
        $latest = $feedback->first();
        $totalFeedback = $feedback->count();
        $resolvedFeedback = $feedback->where('status', 'Resolved')->count();

        return response()->json([
            'revision' => $revision,
            'total' => $totalFeedback,
            'average' => $ratingAnswers->count() ? round($ratingAnswers->avg('answer'), 1) : 0,
            'today' => $feedback->filter(function ($item) {
                return optional($item->submitted_at)->isToday();
            })->count(),
            'unread_notifications' => $request->user()->notificationCenterItems()->where('is_read', false)->count(),
            'high_concerns' => $feedback->where('concern_level', 'High')->count(),
            'resolved' => $resolvedFeedback,
            'response_rate' => $totalFeedback ? round(($resolvedFeedback / $totalFeedback) * 100) : 0,
            'latest' => $latest ? [
                'id' => $latest->id,
                'feedback_id' => $latest->displayCode(),
                'comment' => $latest->firstComment() ?: 'No comment provided.',
                'rating' => $latest->ratingAverage() ?: 'N/A',
                'concern_level' => $latest->concern_level,
                'status' => $latest->status,
                'submitted_at' => optional($latest->submitted_at)->format('M d, Y h:i A'),
            ] : null,
        ]);
    }

    public function notifications(Request $request)
    {
        return response()->json([
            'unread_count' => $request->user()->notificationCenterItems()->where('is_read', false)->count(),
            'notifications' => $this->notificationFeed($request),
        ]);
    }

    public function markNotificationsRead(Request $request)
    {
        $request->user()
            ->notificationCenterItems()
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return $this->notifications($request);
    }

    public function openNotification(Request $request, \App\Models\AdminNotification $notification)
    {
        abort_unless($request->user()->notificationCenterItems()->whereKey($notification->id)->exists(), 404);
        $notification->update(['is_read' => true]);

        if ($notification->feedback_id) {
            return redirect()->route('feedback.detail', [
                'feedback' => $notification->feedback_id,
                'notification' => $notification->id,
            ]);
        }
        if ($notification->contact_message_id) {
            return redirect()->route('messages.show', $notification->contact_message_id);
        }

        $ids = $request->user()->notifications()->orderByDesc('id')->pluck('id');
        $position = $ids->search($notification->id);
        $page = $position === false ? 1 : intdiv($position, 20) + 1;

        return redirect()->to(route('dashboard.section', ['section' => 'inbox', 'page' => $page]) . '#notification-' . $notification->id);
    }

    private function notificationFeed(Request $request)
    {
        return $request->user()
            ->notificationCenterItems()
            ->with('feedback.answers.question')
            ->latest()
            ->orderByDesc('id')
            ->take(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => Str::limit($notification->title, 52),
                    'message' => $notification->summary(),
                    'detail' => $notification->compactDetail(),
                    'icon' => $notification->icon(),
                    'feedback_code' => optional($notification->feedback)->displayCode(),
                    'rating' => optional($notification->feedback)->ratingAverage(),
                    'status' => optional($notification->feedback)->status,
                    'type' => $notification->type,
                    'is_read' => $notification->is_read,
                    'time' => optional($notification->created_at)->diffForHumans(),
                    'created_at' => optional($notification->created_at)->toIso8601String(),
                    'submitted_at' => optional(optional($notification->feedback)->submitted_at)->format('M d, Y h:i A'),
                    'url' => $notification->url(),
                ];
            });
    }

    public function exportReport(Request $request)
    {
        $business = $request->user()->business;

        abort_if(! $business, 404);

        $format = strtolower((string) $request->query('format', 'csv'));
        abort_unless(in_array($format, ['csv', 'pdf', 'svg', 'jpg'], true), 400, 'Unsupported export format.');

        $filterRequest = $request->boolean('all') ? new Request() : $request;
        $feedback = app(FeedbackFilters::class)->query($business, $filterRequest)->get();
        $filename = 'cfs-feedback-report-' . now()->format('Y-m-d');

        if ($format === 'pdf') {
            $lines = ['CFS Feedback Report', 'Generated ' . now()->format('M d, Y h:i A'), 'Records: ' . $feedback->count(), ''];
            foreach ($feedback as $item) {
                $lines[] = implode(' | ', [
                    $item->displayCode(),
                    optional($item->submitted_at)->format('Y-m-d H:i'),
                    'Rating ' . ($item->ratingAverage() ?: 'N/A') . '/10',
                    $item->status,
                    $item->customerLabel(),
                ]);
                $comment = trim((string) ($item->firstComment() ?: 'No comment provided.'));
                foreach (explode("\n", wordwrap($comment, 105, "\n", true)) as $commentLine) {
                    $lines[] = '  ' . $commentLine;
                }
                $lines[] = '';
            }

            return response($this->buildReportPdf($lines), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.pdf"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        if ($format === 'jpg') {
            abort_unless(function_exists('imagecreatetruecolor') && function_exists('imagejpeg'), 503, 'JPG export is unavailable because the server image extension is not enabled.');

            $width = 1400;
            $rowHeight = 54;
            $height = 128 + max(1, $feedback->count()) * $rowHeight;
            $image = imagecreatetruecolor($width, $height);
            $white = imagecolorallocate($image, 255, 255, 255);
            $navy = imagecolorallocate($image, 11, 35, 64);
            $ink = imagecolorallocate($image, 16, 33, 63);
            $muted = imagecolorallocate($image, 83, 104, 135);
            $line = imagecolorallocate($image, 216, 225, 238);
            $header = imagecolorallocate($image, 237, 242, 248);
            imagefill($image, 0, 0, $white);
            imagefilledrectangle($image, 0, 0, $width, 82, $navy);
            imagestring($image, 5, 28, 18, 'CFS Feedback Report', $white);
            imagestring($image, 3, 28, 52, 'Generated ' . now()->format('M d, Y h:i A') . ' | ' . $feedback->count() . ' records', imagecolorallocate($image, 213, 225, 240));
            imagefilledrectangle($image, 0, 83, $width, 116, $header);

            $columns = [
                ['Feedback ID', 24], ['Date & Time', 170], ['Rating', 355],
                ['Status', 450], ['Customer', 590], ['Comment', 760],
            ];
            foreach ($columns as [$label, $x]) {
                imagestring($image, 3, $x, 96, $label, $ink);
            }

            $plain = static function ($value): string {
                $value = trim(preg_replace('/\\s+/u', ' ', (string) $value) ?? '');
                $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
                return $ascii === false ? $value : $ascii;
            };

            if ($feedback->isEmpty()) {
                imagestring($image, 3, 24, 137, 'No feedback records found.', $muted);
            }

            foreach ($feedback as $index => $item) {
                $y = 117 + ($index * $rowHeight);
                if ($index % 2 === 0) {
                    imagefilledrectangle($image, 0, $y, $width, $y + $rowHeight - 1, imagecolorallocate($image, 248, 250, 252));
                }
                $values = [
                    [$item->displayCode(), 24],
                    [optional($item->submitted_at)->format('M d, Y h:i A'), 170],
                    [($item->ratingAverage() ?: 'N/A') . '/10', 355],
                    [$item->status, 450],
                    [$item->customerLabel(), 590],
                    [$item->firstComment() ?: 'No comment provided.', 760],
                ];
                foreach ($values as [$value, $x]) {
                    $text = $plain($value);
                    $maxChars = $x === 760 ? 88 : 24;
                    if (strlen($text) > $maxChars) {
                        $text = substr($text, 0, $maxChars - 3) . '...';
                    }
                    imagestring($image, 2, $x, $y + 20, $text, $ink);
                }
                imageline($image, 20, $y + $rowHeight - 1, $width - 20, $y + $rowHeight - 1, $line);
            }

            ob_start();
            imagejpeg($image, null, 88);
            $jpg = ob_get_clean();
            imagedestroy($image);

            return response($jpg, 200, [
                'Content-Type' => 'image/jpeg',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.jpg"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        if ($format === 'svg') {
            $rowHeight = 64;
            $height = 100 + max(1, $feedback->count()) * $rowHeight;
            $escape = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="' . $height . '" viewBox="0 0 1200 ' . $height . '">';
            $svg .= '<rect width="100%" height="100%" fill="#fff"/><rect width="1200" height="82" fill="#0b2340"/>';
            $svg .= '<text x="28" y="37" fill="#fff" font-family="Arial,sans-serif" font-size="25" font-weight="700">CFS Feedback Report</text>';
            $svg .= '<text x="28" y="62" fill="#d5e1f0" font-family="Arial,sans-serif" font-size="14">Generated ' . $escape(now()->format('M d, Y h:i A')) . ' · ' . $feedback->count() . ' records</text>';
            $svg .= '<rect x="0" y="82" width="1200" height="34" fill="#edf2f8"/>';
            foreach ([['Feedback ID', 24], ['Date & Time', 170], ['Rating', 335], ['Status', 430], ['Customer', 570], ['Comment', 750]] as [$label, $x]) {
                $svg .= '<text x="' . $x . '" y="104" fill="#10213f" font-family="Arial,sans-serif" font-size="13" font-weight="700">' . $label . '</text>';
            }
            if ($feedback->isEmpty()) {
                $svg .= '<text x="24" y="148" fill="#536887" font-family="Arial,sans-serif" font-size="14">No feedback records found.</text>';
            }
            foreach ($feedback as $index => $item) {
                $y = 116 + $index * $rowHeight;
                $svg .= '<rect x="0" y="' . $y . '" width="1200" height="' . $rowHeight . '" fill="' . ($index % 2 ? '#fff' : '#f8fafc') . '"/>';
                $values = [
                    [$item->displayCode(), 24],
                    [optional($item->submitted_at)->format('M d, Y h:i A'), 170],
                    [($item->ratingAverage() ?: 'N/A') . '/10', 335],
                    [$item->status, 430],
                    [$item->customerLabel(), 570],
                    [Str::limit(trim((string) ($item->firstComment() ?: 'No comment provided.')), 66), 750],
                ];
                foreach ($values as [$value, $x]) {
                    $svg .= '<text x="' . $x . '" y="' . ($y + 27) . '" fill="#10213f" font-family="Arial,sans-serif" font-size="13">' . $escape($value) . '</text>';
                }
                $svg .= '<line x1="20" y1="' . ($y + $rowHeight) . '" x2="1180" y2="' . ($y + $rowHeight) . '" stroke="#d8e1ee"/>';
            }
            $svg .= '</svg>';

            return response($svg, 200, [
                'Content-Type' => 'image/svg+xml; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.svg"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        $filename .= '.csv';
        $columns = $feedback->flatMap->answers->unique(fn ($answer) => $answer->question_id . ':' . $answer->question_text)->values();
        return response()->streamDownload(function () use ($feedback, $columns) {
            $handle = fopen('php://output', 'w');
            $safe = static function ($value) {
                $value = (string) $value;
                return preg_match('/^[\x00-\x20]*[=+@\-]|^[\t\r\n]/u', $value) ? "'" . $value : $value;
            };
            fputcsv($handle, array_merge([
                'Feedback ID',
                'Submitted At',
                'Average Rating',
                'Status',
                'Concern Level',
                'Comment',
                'Customer', 'Admin Action', 'Resolution Note',
            ], $columns->map(fn ($answer) => 'Question #' . $answer->question_id . ': ' . $answer->question_text)->all()));

            foreach ($feedback as $item) {
                $answers = $item->answers->keyBy(fn ($answer) => $answer->question_id . ':' . $answer->question_text);
                $row = [
                    $item->displayCode(),
                    optional($item->submitted_at)->format('Y-m-d H:i:s'),
                    $item->ratingAverage() ?: 'N/A',
                    $item->status,
                    $item->concern_level,
                    $item->firstComment() ?: 'No comment provided.',
                    $item->customerLabel(), $item->action_taken, $item->resolution_note,
                ];
                foreach ($columns as $column) {
                    $answer = $answers->get($column->question_id . ':' . $column->question_text);
                    $value = $answer ? $answer->answer : '';
                    $row[] = $value === 'Like Up' ? 'Yes' : ($value === 'Like Down' ? 'No' : $value);
                }
                fputcsv($handle, array_map($safe, $row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function buildReportPdf(array $lines): string
    {
        $pages = array_chunk($lines, 52);
        if (! $pages) $pages = [['CFS Feedback Report', 'No feedback records found.']];

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
        ];
        $pageRefs = [];
        foreach ($pages as $index => $pageLines) {
            $pageId = 4 + ($index * 2);
            $contentId = $pageId + 1;
            $pageRefs[] = $pageId . ' 0 R';
            $stream = "BT\n/F1 9 Tf\n42 760 Td\n12 TL\n";
            foreach ($pageLines as $line) {
                $line = substr(preg_replace('/[^\\x20-\\x7E]/', '?', (string) $line), 0, 115);
                $line = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
                $stream .= '(' . $line . ") Tj\nT*\n";
            }
            $stream .= "ET";
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
            $objects[$contentId] = '<< /Length ' . strlen($stream) . ">>\nstream\n" . $stream . "\nendstream";
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageRefs) . '] /Count ' . count($pageRefs) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $size = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 {$size}\n0000000000 65535 f \n";
        for ($id = 1; $id < $size; $id++) $pdf .= sprintf("%010d 00000 n \n", $offsets[$id] ?? 0);
        $pdf .= "trailer\n<< /Size {$size} /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";
        return $pdf;
    }

    public function updateNotificationEmail(Request $request, NotificationEmailVerification $verification)
    {
        $business = $request->user()->business;

        abort_if(! $business, 404);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = Str::lower(trim($data['email']));
        if ($email === Str::lower($request->user()->email)) {
            $business->update(['use_account_email_for_feedback' => true, 'pending_notification_email' => null]);
            return redirect()->back()->with('status', 'Feedback notifications will use the account email.');
        }

        if ($business->notification_email_verified_at && Str::lower((string) $business->notification_email) === $email) {
            $business->update(['use_account_email_for_feedback' => false, 'pending_notification_email' => null]);
            return redirect()->back()->with('status', 'Verified notification email activated.');
        }

        $business->update(['pending_notification_email' => $email]);
        try {
            $verification->issue($request->user(), $email);
        } catch (\Throwable $error) {
            report($error);
            return redirect()->back()->withErrors(['email' => 'The verification email could not be sent. Check the mail service and retry.']);
        }

        return redirect()
            ->back()
            ->with('status', 'A verification code was sent. Enter it in Settings to activate this notification address.');
    }
}
