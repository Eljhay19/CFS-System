<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Jobs\SendFeedbackNotification;
use App\Models\Feedback;
use App\Services\FeedbackNotifications;
use App\Services\SurveyDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CustomerFeedbackController extends Controller
{
    private $definition;

    public function __construct(SurveyDefinition $definition)
    {
        $this->definition = $definition;
    }

    private function business(Request $request): ?Business
    {
        if ($request->filled('business')) {
            $business = Business::where('survey_slug', $request->input('business'))->firstOrFail();
            $request->session()->put('survey_business_id', $business->id);
            return $business;
        }
        $id = session('survey.business_id', session('survey_business_id', config('feedback.business_id')));
        return $id ? Business::find($id) : Business::where('is_default', true)->first();
    }

    public function publicSurvey(Business $business, Request $request)
    {
        return redirect()->route('home', ['business' => $business->survey_slug]);
    }

    public function home(Request $request)
    {
        if (session('feedback.completed')) {
            $request->session()->forget(['survey', 'feedback', 'consent_confirmation', 'terms_review']);
        }
        $this->expireDraft();
        $business = $this->business($request);
        if (session('survey.agreed') && session('survey.business_id') && $business && $business->id !== session('survey.business_id')) {
            $business = Business::find(session('survey.business_id'));
            $request->session()->now('status', 'An unfinished response for ' . optional($business)->business_name . ' is available.');
        }
        return view('home', ['business' => $business, 'hasDraft' => (bool) session('survey.agreed'), 'termsConfirmed' => $this->termsConfirmed($business)]);
    }

    public function terms(Request $request)
    {
        $business = $this->business($request);
        $onHome = $request->query('from') === 'home';
        $request->session()->forget('consent_confirmation');
        $review = [
            'token' => (string) Str::uuid(), 'business_id' => optional($business)->id,
            'policy_version' => SurveyDefinition::POLICY_VERSION,
            'context' => $onHome ? 'home' : null,
            'expires_at' => now()->addMinutes(config('feedback.draft_minutes', 120))->toIso8601String(),
        ];
        $request->session()->put('terms_review', $review);
        return view($onHome ? 'terms.home' : 'terms', [
            'termsToken' => $review['token'], 'business' => $business,
            'hasDraft' => (bool) session('survey.agreed'), 'termsConfirmed' => false,
        ]);
    }

    public function confirmTerms(Request $request)
    {
        $data = $request->validate(['agreement' => ['accepted'], 'terms_token' => ['required', 'string']]);
        $review = $request->session()->get('terms_review', []);
        $business = $this->business($request);
        if (! $business || ($review['business_id'] ?? null) !== $business->id
            || ($review['policy_version'] ?? null) !== SurveyDefinition::POLICY_VERSION
            || empty($review['expires_at']) || Carbon::parse($review['expires_at'])->isPast()
            || ! hash_equals((string) ($review['token'] ?? ''), $data['terms_token'])) {
            return redirect()->route('terms', array_filter(['from' => $review['context'] ?? null]))
                ->withErrors(['agreement' => 'Please review the current Terms & Conditions and confirm your agreement.']);
        }
        $request->session()->put('consent_confirmation', [
            'business_id' => $business->id, 'policy_version' => SurveyDefinition::POLICY_VERSION,
            'confirmed_at' => now()->toIso8601String(), 'expires_at' => $review['expires_at'],
        ]);
        $request->session()->forget('terms_review');
        return redirect()->route('home');
    }

    private function termsConfirmed(?Business $business): bool
    {
        $confirmation = session('consent_confirmation', []);
        return $business && ($confirmation['business_id'] ?? null) === $business->id
            && ($confirmation['policy_version'] ?? null) === SurveyDefinition::POLICY_VERSION
            && ! empty($confirmation['expires_at']) && Carbon::parse($confirmation['expires_at'])->isFuture();
    }

    public function start(Request $request)
    {
        $data = $request->validate(['consent' => ['required', 'in:agree,decline']]);
        if ($data['consent'] !== 'agree') {
            return redirect()->route('home')->with('status', 'You can start the survey whenever you are ready.');
        }
        $business = $this->business($request);
        if (! $business || ! $business->survey_enabled) {
            return redirect()->route('home')->withErrors(['survey' => 'This survey is currently closed.']);
        }
        if (session('survey.agreed')) return redirect()->route('feedback.continue');
        if (! $this->termsConfirmed($business)) {
            return redirect()->route('terms')->withErrors(['consent' => 'Please confirm your agreement inside Terms & Conditions first.']);
        }
        $confirmation = $request->session()->get('consent_confirmation');
        try { $snapshot = $this->definition->draft($business); }
        catch (Throwable $error) {
            report($error);
            return redirect()->route('home')->withErrors(['survey' => 'The survey is temporarily unavailable. Please try again.']);
        }
        $request->session()->forget('feedback');
        $request->session()->put('survey', [
            'agreed' => true, 'token' => (string) Str::uuid(), 'business_id' => $business->id,
            'consent_at' => $confirmation['confirmed_at'], 'policy_version' => $confirmation['policy_version'],
            'expires_at' => now()->addMinutes(config('feedback.draft_minutes', 120))->toIso8601String(),
            'questions' => $snapshot['questions'],
            'steps' => $snapshot['steps'],
        ]);
        $request->session()->forget(['consent_confirmation', 'terms_review']);
        return redirect()->route('feedback.show');
    }

    public function agreeFromTerms()
    {
        return redirect()->route('home');
    }

    public function restart(Request $request)
    {
        $request->session()->forget(['survey', 'feedback', 'consent_confirmation', 'terms_review']);
        return redirect()->route('home')->with('status', 'Your previous draft has been cleared.');
    }

    public function continueDraft()
    {
        $this->ensureStarted();
        $route = session('survey.current_route', 'feedback.show');
        if ($route === 'feedback.additional' && isset($this->definition->steps()[session('survey.current_step')])) {
            return redirect()->to($this->definition->url(session('survey.current_step')));
        }
        return redirect()->route(in_array($route, ['feedback.show', 'feedback.order', 'feedback.store-rating', 'feedback.food-rating', 'feedback.final', 'feedback.review'], true) ? $route : 'feedback.show');
    }

    private function renderStep(string $step, string $route)
    {
        session()->put('survey.current_route', $route);
        session()->put('survey.current_step', $step);
        $views = ['visit'=>'visit','order'=>'order','service'=>'store-rating','food'=>'food-rating','final'=>'final'];
        return view('feedback.' . $views[$step], [
            'step' => $step, 'questions' => $this->definition->questions($step),
            'steps' => $this->definition->steps(),
        ]);
    }

    public function show()
    {
        $this->ensureStarted();
        return $this->renderStep('visit', 'feedback.show');
    }

    public function saveVisit(Request $request)
    {
        $this->ensureStarted();
        $data = $request->validate(array_merge([
            'date_of_visit' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'time_of_order' => ['required', 'date_format:H:i'],
        ], $this->definition->extraRules('visit')));
        if (Carbon::parse($data['date_of_visit'] . ' ' . $data['time_of_order'])->gt(now())) {
            throw ValidationException::withMessages(['time_of_order' => 'Future date and time entries are not allowed.']);
        }
        return $this->saveStep('visit', 'visit', $data, 'feedback.order');
    }

    public function order()
    {
        $this->ensureStep('visit');
        return $this->renderStep('order', 'feedback.order');
    }

    public function saveOrder(Request $request)
    {
        $this->ensureStep('visit');
        $data = $request->validate(array_merge([
            'order_type' => ['required', 'in:Dine In,Take Out'], 'order_details' => ['required', 'string', 'max:1000'],
        ], $this->definition->extraRules('order')));
        return $this->saveStep('order', 'order', $data, 'feedback.store-rating');
    }

    public function storeRating()
    {
        $this->ensureStep('order');
        return $this->renderStep('service', 'feedback.store-rating');
    }

    public function saveStoreRating(Request $request)
    {
        $this->ensureStep('order');
        $data = $request->validate(array_merge([
            'cleanliness_selected' => ['accepted'], 'speed_of_service_selected' => ['accepted'],
            'staff_selected' => $request->boolean('staff_na') ? ['nullable'] : ['accepted'],
            'staff_na' => ['nullable', 'boolean'], 'cleanliness' => ['required', 'integer', 'between:1,10'],
            'speed_of_service' => ['required', 'integer', 'between:1,10'],
            'staff' => [Rule::requiredIf(! $request->boolean('staff_na')), 'nullable', 'integer', 'between:1,10'],
        ], $this->definition->extraRules('service')));
        $values = ['cleanliness' => $data['cleanliness'], 'speed_of_service' => $data['speed_of_service'], 'staff' => $request->boolean('staff_na') ? 'N/A' : $data['staff']];
        return $this->saveStep('service', 'store_rating', array_merge($values, ['extra' => $data['extra'] ?? []]), 'feedback.food-rating');
    }

    public function foodRating()
    {
        $this->ensureStep('store_rating');
        return $this->renderStep('food', 'feedback.food-rating');
    }

    public function saveFoodRating(Request $request)
    {
        $this->ensureStep('store_rating');
        $rules = $this->definition->extraRules('food');
        foreach (['taste', 'freshness', 'temperature', 'presentation', 'value_for_money'] as $key) {
            $rules['food_ratings.' . $key] = ['required', 'integer', 'between:1,10'];
            $rules['food_rating_selected.' . $key] = ['accepted'];
        }
        $data = $request->validate($rules);
        return $this->saveStep('food', 'food_rating', array_merge($data['food_ratings'], ['extra' => $data['extra'] ?? []]), 'feedback.final');
    }

    public function finalStep()
    {
        $this->ensureStep('food_rating');
        return $this->renderStep('final', 'feedback.final');
    }

    public function saveFinal(Request $request)
    {
        $this->ensureStep('food_rating');
        $data = $request->validate(array_merge([
            'order_accurate' => ['required', 'in:Like Up,Like Down'], 'food_satisfied' => ['required', 'in:Like Up,Like Down'],
            'problem_today' => ['required', 'in:Yes,No'],
            'problem_comment' => [Rule::requiredIf($request->input('problem_today') === 'Yes'), 'nullable', 'string', 'max:1000'],
        ], $this->definition->extraRules('final')));
        $response = $this->saveStep('final', 'final', $data, 'feedback.review');
        $next = array_keys(array_diff_key($this->definition->steps(), SurveyDefinition::STEPS))[0] ?? null;
        return $next ? redirect()->to($this->definition->url($next)) : $response;
    }

    public function additional(string $stepKey)
    {
        $this->ensureAdditional($stepKey);
        session()->put('survey.current_route', 'feedback.additional');
        session()->put('survey.current_step', $stepKey);
        return view('feedback.additional', ['step' => $stepKey, 'steps' => $this->definition->steps(), 'questions' => $this->definition->questions($stepKey)]);
    }

    public function saveAdditional(Request $request, string $stepKey)
    {
        $this->ensureAdditional($stepKey);
        $data = $request->validate($this->definition->extraRules($stepKey));
        session()->put('survey.extras.' . $stepKey, $data['extra'] ?? []);
        session()->put('survey.completed_custom.' . $stepKey, true);
        session()->put('survey.expires_at', now()->addMinutes(config('feedback.draft_minutes', 120))->toIso8601String());
        session()->forget('survey.reviewed');
        $keys = array_keys($this->definition->steps());
        $next = $keys[array_search($stepKey, $keys, true) + 1] ?? null;
        return $next ? redirect()->to($this->definition->url($next)) : redirect()->route('feedback.review');
    }

    private function ensureAdditional(?string $target = null): void
    {
        $this->ensureStep('final');
        $custom = array_diff_key($this->definition->steps(), SurveyDefinition::STEPS);
        if ($target !== null) abort_unless(isset($custom[$target]), 404);
        foreach ($custom as $key => $step) {
            if ($key === $target) break;
            if (! session('survey.completed_custom.' . $key)) abort(redirect()->to($this->definition->url($key))->with('status', 'Please complete the previous step first.'));
        }
    }

    private function saveStep(string $step, string $section, array $data, string $next)
    {
        session()->put('survey.extras.' . $step, $data['extra'] ?? []);
        unset($data['extra'], $data['extra_selected']);
        session()->put('survey.' . $section, $data);
        session()->put('survey.expires_at', now()->addMinutes(config('feedback.draft_minutes', 120))->toIso8601String());
        session()->forget('survey.reviewed');
        return redirect()->route($next);
    }

    public function review()
    {
        $this->ensureAdditional();
        session()->put('survey.current_route', 'feedback.review');
        session()->put('survey.reviewed', $this->fingerprint());
        return view('feedback.review', ['questions' => session('survey.questions'), 'definition' => $this->definition, 'steps' => $this->definition->steps()]);
    }

    private function fingerprint(): string
    {
        $survey = session('survey');
        unset($survey['reviewed'], $survey['current_route'], $survey['current_step'], $survey['expires_at']);
        return hash('sha256', json_encode($survey));
    }

    public function store(Request $request, FeedbackNotifications $notifications)
    {
        if (session('feedback.completed') && ! session('survey.agreed')) return redirect()->route('feedback.thank-you');
        $this->ensureAdditional();
        if (! hash_equals($this->fingerprint(), (string) session('survey.reviewed'))) {
            return redirect()->route('feedback.review')->withInput()->withErrors(['review' => 'Please review your answers before submitting.']);
        }
        $request->validate(['follow_up_requested' => ['sometimes', 'boolean']]);
        $followUp = $request->boolean('follow_up_requested');
        $followUpData = $followUp ? $request->validate([
            'follow_up_email' => ['required', 'string', 'email:rfc', 'max:254'],
            'follow_up_consent' => ['required', 'accepted'],
        ], [
            'follow_up_email.required' => 'Enter your email address to request a follow-up.',
            'follow_up_email.email' => 'Enter a valid email address.',
            'follow_up_consent.required' => 'Please consent to email follow-up or uncheck the follow-up request.',
            'follow_up_consent.accepted' => 'Please consent to email follow-up or uncheck the follow-up request.',
        ]) : [];
        // Never retain a posted email or consent when the customer opts out.
        $retryInput = ['follow_up_requested' => $followUp ? '1' : '0'] + $followUpData;
        $survey = session('survey');
        $token = $survey['token'];
        try {
            if (Feedback::where('submission_token', $token)->exists()) return $this->completeSubmission($request, Feedback::where('submission_token', $token)->first());
            $business = Business::findOrFail($survey['business_id']);
            if (! $business->survey_enabled) {
                return redirect()->route('feedback.review')->withInput($retryInput)->withErrors(['submission' => 'This survey is closed. Your answers are retained. Please try again when it reopens.']);
            }
            $feedback = DB::transaction(function () use ($business, $survey, $token, $notifications, $followUpData, $followUp) {
                $feedback = $business->feedback()->create([
                    'submission_token' => $token, 'submitted_at' => now(), 'consent_at' => $survey['consent_at'],
                    'policy_version' => $survey['policy_version'], 'status' => 'Submitted', 'concern_level' => 'Normal',
                    'follow_up_email' => $followUp ? $followUpData['follow_up_email'] : null,
                    'follow_up_consent_at' => $followUp ? now() : null,
                ]);
                foreach ($survey['questions'] as $question) {
                    $value = $this->definition->value($question, $survey);
                    $feedback->answers()->create([
                        'question_id' => $question['id'], 'question_text' => $question['text'], 'question_type' => $question['type'],
                        'question_options' => implode("\n", $question['options']), 'field_key' => $question['key'], 'answer' => $value,
                    ]);
                }
                $feedback->load('answers.question', 'business.user');
                $concern = $feedback->hasCustomerConcern();
                $feedback->update(['concern_level' => $feedback->severityLevel(), 'status' => $concern ? 'Needs Action' : 'Submitted']);
                $notifications->record($feedback);
                return $feedback;
            });
        } catch (Throwable $error) {
            try {
                $existing = Feedback::where('submission_token', $token)->first();
                if ($existing) return $this->completeSubmission($request, $existing);
            } catch (Throwable $lookupError) { /* Keep the retry page available during an outage. */ }
            Log::error('Feedback submission failed', ['error' => $error->getMessage()]);
            return redirect()->route('feedback.review')->withInput($retryInput)->withErrors(['submission' => 'We could not save your feedback. Your answers are retained. Please retry.']);
        }
        try {
            $feedback->update(['email_status' => 'queued', 'email_error' => null]);
            SendFeedbackNotification::dispatch($feedback->id)->afterCommit();
        } catch (Throwable $error) {
            // Queue or mail outages must not undo a successfully saved customer response.
            Log::error('Feedback notification could not be delivered', ['feedback_id' => $feedback->id, 'error' => $error->getMessage()]);
            try { $feedback->update(['email_status' => 'failed', 'email_error' => 'Email delivery failed. Check the active notification address and SMTP mail settings.']); }
            catch (Throwable $statusError) { /* Keep the committed response even if status tracking is unavailable. */ }
        }
        return $this->completeSubmission($request, $feedback);
    }

    private function completeSubmission(Request $request, Feedback $feedback)
    {
        $request->session()->forget('survey');
        $request->session()->put('feedback', ['completed' => true, 'code' => $feedback->displayCode()]);
        return redirect()->route('feedback.thank-you');
    }

    public function thankYou()
    {
        return session('feedback.completed') ? view('feedback.thank-you') : redirect()->route('home');
    }

    private function expireDraft(): void
    {
        if (session('survey.expires_at') && Carbon::parse(session('survey.expires_at'))->isPast()) {
            session()->forget('survey');
            session()->flash('status', 'Your survey draft expired. Please start a new response.');
        }
    }

    private function ensureStarted(): void
    {
        $this->expireDraft();
        if (! session('survey.agreed')) abort(redirect()->route('home')->with('status', 'Please agree to the Terms & Privacy before starting.'));
        // Upgrade drafts created before question snapshots were introduced.
        if (! session('survey.questions')) {
            $business = $this->business(request());
            abort_unless($business, 404);
            session()->put('survey.business_id', $business->id);
            $snapshot = $this->definition->draft($business);
            session()->put('survey.questions', $snapshot['questions']);
            session()->put('survey.steps', $snapshot['steps']);
            session()->put('survey.consent_at', now()->toIso8601String());
            session()->put('survey.policy_version', SurveyDefinition::POLICY_VERSION);
        }
        if (! session('survey.token')) session()->put('survey.token', (string) Str::uuid());
    }

    private function ensureStep(string $required): void
    {
        $this->ensureStarted();
        foreach (['visit' => 'feedback.show', 'order' => 'feedback.order', 'store_rating' => 'feedback.store-rating', 'food_rating' => 'feedback.food-rating', 'final' => 'feedback.final'] as $key => $route) {
            if (! session()->has('survey.' . $key)) abort(redirect()->route($route)->with('status', 'Please complete the previous step first.'));
            if ($key === $required) break;
        }
    }
}
