<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Question;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SurveyDefinition
{
    public const POLICY_VERSION = '2026-10-18';
    public const STEPS = ['visit' => 'Date & Time', 'order' => 'Order Details', 'service' => 'Service & Staff', 'food' => 'Food & Quality', 'final' => 'Final Questions'];

    public function defaultSteps(): array
    {
        $descriptions = ['visit' => 'Date of visit and time of order', 'order' => 'Share your order details', 'service' => 'Tell us about our service', 'food' => 'Rate your experience', 'final' => 'Any other suggestions'];
        return collect(self::STEPS)->map(fn ($title, $key) => ['title' => $title, 'description' => $descriptions[$key]])->all();
    }

    public function stepSnapshot(Business $business): array
    {
        $steps = $this->defaultSteps();
        $activeQuestionSteps = $business->questions()->whereNull('archived_at')->select('step');
        foreach ($business->surveySteps()->whereNull('archived_at')->where('is_published', true)->whereIn('key', $activeQuestionSteps)->orderBy('id')->get() as $step) {
            $steps[$step->key] = ['title' => $step->title, 'description' => $step->description ?: 'Additional feedback'];
        }
        return $steps;
    }

    public function steps(): array
    {
        return session('survey.steps', $this->defaultSteps());
    }

    public function stepLabels(Business $business): array
    {
        return self::STEPS + $business->surveySteps()->orderBy('id')->get()->mapWithKeys(fn ($step) => [$step->key => $step->title . ($step->archived_at ? ' (Archived)' : '')])->all();
    }

    public function url(string $step): string
    {
        $routes = ['visit' => 'feedback.show', 'order' => 'feedback.order', 'service' => 'feedback.store-rating', 'food' => 'feedback.food-rating', 'final' => 'feedback.final'];
        return isset($routes[$step]) ? route($routes[$step]) : route('feedback.additional', ['stepKey' => $step]);
    }

    public function core(): array
    {
        return [
            'date_of_visit' => ['visit', 'Date of Visit', 'comment'],
            'time_of_order' => ['visit', 'Time of Order', 'comment'],
            'order_type' => ['order', 'Your order was', 'multiple_choice', "Dine In\nTake Out"],
            'order_details' => ['order', 'What did you order?', 'comment'],
            'cleanliness' => ['service', 'How clean was the store during your visit?', 'rating'],
            'speed_of_service' => ['service', 'How fast was the service when you placed and received your order?', 'rating'],
            'staff' => ['service', 'How would you rate the staff who assisted you?', 'rating'],
            'taste' => ['food', 'How would you rate the taste of the food you ordered?', 'rating'],
            'freshness' => ['food', 'How fresh did the food taste when it was served?', 'rating'],
            'temperature' => ['food', 'How would you rate the food temperature when you received it?', 'rating'],
            'presentation' => ['food', 'How satisfied were you with the food presentation?', 'rating'],
            'value_for_money' => ['food', 'How would you rate the food value for money?', 'rating'],
            'order_accurate' => ['final', 'Was your order accurate?', 'multiple_choice', "Like Up\nLike Down"],
            'food_satisfied' => ['final', 'Were you satisfied with the food?', 'multiple_choice', "Like Up\nLike Down"],
            'problem_today' => ['final', 'Did you experience any problem today?', 'multiple_choice', "Yes\nNo"],
            'problem_comment' => ['final', 'Comment', 'comment'],
        ];
    }

    public function initialize(Business $business): void
    {
        $aliases = [
            'date_of_visit' => ['Date of visit'], 'time_of_order' => ['Time of order'],
            'order_details' => ['Order details'], 'problem_comment' => ['Problem or comment details'],
            'cleanliness' => ['Store cleanliness rating'], 'speed_of_service' => ['Speed of service rating'], 'staff' => ['Staff rating'],
        ];
        $sort = 0;
        foreach ($this->core() as $key => $definition) {
            $sort++;
            $query = $business->questions()->where('field_key', $key);
            if ($query->exists()) continue;
            $matches = $business->questions()->whereNull('field_key')->whereIn('question', array_merge([$definition[1]], $aliases[$key] ?? []))->orderBy('id')->get();
            $question = $matches->shift() ?: new Question(['business_id' => $business->id, 'question' => $definition[1]]);
            $question->fill([
                'field_key' => $key, 'step' => $definition[0], 'is_core' => true,
                'question_type' => $definition[2], 'options' => $definition[3] ?? null,
                'required' => $key !== 'problem_comment', 'sort_order' => $sort,
            ])->save();
            foreach ($matches as $duplicate) {
                $duplicate->update(['archived_at' => now(), 'field_key' => 'custom_' . Str::uuid()]);
            }
        }
        foreach ($business->questions()->whereNull('field_key')->get() as $question) {
            $question->update(['field_key' => 'custom_' . Str::uuid()]);
        }
    }

    public function draft(Business $business): array
    {
        return DB::transaction(function () use ($business) {
            $this->initialize($business);
            $steps = $this->stepSnapshot($business);
            return ['steps' => $steps, 'questions' => $this->snapshot($business, $steps)];
        });
    }

    public function snapshot(Business $business, ?array $steps = null): array
    {
        $this->initialize($business);
        return $business->questions()->whereNull('archived_at')->whereIn('step', array_keys($steps ?? $this->stepSnapshot($business)))->orderBy('sort_order')->orderBy('id')->get()->map(function ($question) {
            return [
                'id' => $question->id, 'key' => $question->field_key, 'step' => $question->step,
                'text' => $question->question, 'type' => $question->question_type,
                'options' => $question->optionList(), 'required' => $question->required,
                'help' => $question->help_text, 'core' => $question->is_core,
            ];
        })->all();
    }

    public function questions(string $step): array
    {
        return array_values(array_filter(session('survey.questions', []), fn ($question) => $question['step'] === $step));
    }

    public function extraRules(string $step): array
    {
        $rules = [];
        foreach ($this->questions($step) as $question) {
            if ($question['core']) continue;
            $field = 'extra.' . $question['id'];
            $rules[$field] = [$question['required'] ? 'required' : 'nullable'];
            if ($question['type'] === 'rating') {
                if (! $question['required'] && request()->input('extra_selected.' . $question['id']) !== '1') {
                    $rules[$field] = ['exclude'];
                    continue;
                }
                $rules[$field] = array_merge($rules[$field], ['integer', 'between:1,10']);
                $rules['extra_selected.' . $question['id']] = $question['required'] ? ['accepted'] : ['nullable'];
            } elseif ($question['type'] === 'multiple_choice') {
                $rules[$field][] = Rule::in($question['options']);
            } else {
                $rules[$field] = array_merge($rules[$field], ['string', 'max:1000']);
            }
        }
        return $rules;
    }

    public function value(array $question, array $survey)
    {
        $sections = ['visit' => 'visit', 'order' => 'order', 'service' => 'store_rating', 'food' => 'food_rating', 'final' => 'final'];
        return $question['core'] ? ($survey[$sections[$question['step']]][$question['key']] ?? '')
            : ($survey['extras'][$question['step']][$question['id']] ?? '');
    }
}
