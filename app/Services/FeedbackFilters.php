<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class FeedbackFilters
{
    public function dateRanges(): array
    {
        $today = now()->toDateString();
        return [
            'all' => ['from'=>'', 'to'=>''],
            'today' => ['from'=>$today, 'to'=>$today],
            'last7' => ['from'=>now()->subDays(6)->toDateString(), 'to'=>$today],
            'last30' => ['from'=>now()->subDays(29)->toDateString(), 'to'=>$today],
            'month' => ['from'=>now()->startOfMonth()->toDateString(), 'to'=>$today],
        ];
    }

    public function values(Request $request): array
    {
        $input = $request->all();
        $range = $input['range'] ?? 'custom';
        if (is_string($range) && isset($this->dateRanges()[$range]) && $range !== 'all') {
            $input = array_merge($input, $this->dateRanges()[$range]);
        }
        $toRules = ['nullable', 'date_format:Y-m-d'];
        if (! empty($input['from'])) $toRules[] = 'after_or_equal:from';
        $values = Validator::make($input, [
            'from' => ['nullable', 'date_format:Y-m-d'], 'to' => $toRules,
            'status' => ['nullable', Rule::in(['Submitted','Needs Action','In Progress','Resolved'])],
            'period' => ['nullable', Rule::in(['daily','weekly','monthly'])], 'search' => ['nullable','string','max:200'],
            'filter' => ['nullable', Rule::in(['all','resolved','needs','today'])], 'unread' => ['nullable','boolean'],
            'range' => ['nullable', Rule::in(['all','today','last7','last30','month','custom'])],
        ])->validate();
        if (isset($values['search'])) $values['search'] = trim($values['search']);
        $range = $values['range'] ?? 'custom';
        if ($range !== 'all' && isset($this->dateRanges()[$range])) $values = array_merge($values, $this->dateRanges()[$range]);
        if ($range === 'all' && (! empty($values['from']) || ! empty($values['to']))) $values['range'] = 'custom';
        return array_filter($values, fn ($value) => $value !== null && $value !== '');
    }

    public function query(Business $business, Request $request, bool $detailFilter = true)
    {
        $filters = $this->values($request);
        $query = $business->feedback()->with('answers.question','notification')->latest('submitted_at')->orderByDesc('id');
        if (!empty($filters['from'])) $query->whereDate('submitted_at', '>=', $filters['from']);
        if (!empty($filters['to'])) $query->whereDate('submitted_at', '<=', $filters['to']);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['unread'])) $query->whereHas('notification', fn ($query) => $query->where('is_read', false));
        if (isset($filters['search']) && $filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($query) use ($search) {
                $query->where('feedback_code','like',$search)->orWhere('status','like',$search)->orWhere('action_taken','like',$search)
                    ->orWhereHas('answers', fn ($query) => $query->where('answer','like',$search));
            });
        }
        if ($detailFilter) {
            if (($filters['filter'] ?? '') === 'resolved') $query->where('status','Resolved');
            if (($filters['filter'] ?? '') === 'needs') $query->where('status','Needs Action');
            if (($filters['filter'] ?? '') === 'today') $query->whereDate('submitted_at',now()->toDateString());
        }
        return $query;
    }

    public function trend(Collection $feedback, string $period = 'daily', ?string $endDate = null): Collection
    {
        $anchor = $endDate ? \Illuminate\Support\Carbon::parse($endDate) : now();
        return collect(range(5,0))->map(function ($index) use ($feedback,$period,$anchor) {
            $date = $anchor->copy();
            if ($period === 'weekly') { $date->subWeeks($index)->startOfWeek(); $end = $date->copy()->endOfWeek(); $label = $date->format('M d'); }
            elseif ($period === 'monthly') { $date->startOfMonth()->subMonths($index); $end = $date->copy()->endOfMonth(); $label = $date->format('M Y'); }
            else { $date->subDays($index)->startOfDay(); $end = $date->copy()->endOfDay(); $label = $date->format('M d'); }
            $items = $feedback->filter(fn ($item) => $item->submitted_at && $item->submitted_at->between($date,$end));
            $ratings = $items->flatMap->answers->filter(fn ($answer) => $answer->type() === 'rating' && is_numeric($answer->answer));
            return ['label'=>$label, 'count'=>$items->count(), 'average'=>$ratings->count() ? round($ratings->avg('answer'),1) : 0];
        });
    }
}
