<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\SurveyStep;
use App\Services\SurveyDefinition;
use App\Services\AdminSystemNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->user()->business;
        if (! $business) return redirect()->route('business.edit');
        app(SurveyDefinition::class)->initialize($business);
        $data = $request->validate(['search' => ['nullable', 'string', 'max:200'], 'archived' => ['nullable', 'boolean']]);
        $search = trim($data['search'] ?? '');
        $archived = $request->boolean('archived');
        $questions = $business->questions()->when($archived, fn ($query) => $query->whereNotNull('archived_at'), fn ($query) => $query->whereNull('archived_at'))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('question', 'like', '%' . $search . '%')->orWhere('question_type', 'like', '%' . $search . '%')->orWhere('help_text', 'like', '%' . $search . '%');
                });
            })->orderByRaw("CASE step WHEN 'visit' THEN 1 WHEN 'order' THEN 2 WHEN 'service' THEN 3 WHEN 'food' THEN 4 ELSE 5 END")->orderBy('sort_order')->orderBy('id')->get();
        $stepLabels = app(SurveyDefinition::class)->stepLabels($business);
        $questions = $questions->sortBy(fn ($question) => array_search($question->step, array_keys($stepLabels), true))->values();
        $surveySteps = $business->surveySteps()
            ->when($archived, fn ($query) => $query->whereNotNull('archived_at'), fn ($query) => $query->whereNull('archived_at'))
            ->orderBy('id')
            ->get();

        return view('questions.index', ['questions' => $questions, 'search' => $search, 'archived' => $archived, 'stepLabels' => $stepLabels, 'surveySteps' => $surveySteps, 'nextSortOrder' => (int) $business->questions()->max('sort_order') + 1]);
    }

    public function create()
    {
        return redirect()->route('questions.index');
    }

    public function edit(Question $question)
    {
        $this->authorizeQuestion($question);
        return redirect()->route('questions.index', ['edit' => $question->id]);
    }

    public function store(Request $request)
    {
        $business = $request->user()->business;
        abort_unless($business, 404);
        $business->questions()->create(array_merge($this->validated($request), ['field_key' => 'custom_' . Str::uuid(), 'is_core' => false]));
        app(AdminSystemNotifications::class)->record($request->user(), 'Survey question added', 'A survey question was added.');
        return redirect()->route('questions.index')->with('status', 'Question added successfully.');
    }

    public function update(Request $request, Question $question)
    {
        $this->authorizeQuestion($question);
        $data = $this->validated($request, $question);
        $oldStep = $question->step;
        $question->fill($data);
        $changed = $question->isDirty();
        DB::transaction(function () use ($question, $data, $oldStep) {
            $question->update($data);
            $this->unpublishEmptyStep($question->business_id, $oldStep);
        });
        if ($changed) app(AdminSystemNotifications::class)->record($request->user(), 'Survey question updated', 'A survey question was updated.');
        return redirect()->route('questions.index')->with('status', 'Question updated successfully.');
    }

    public function destroy(Question $question)
    {
        $this->authorizeQuestion($question);
        DB::transaction(function () use ($question) {
            $question->update(['archived_at' => now()]);
            $this->unpublishEmptyStep($question->business_id, $question->step);
        });
        app(AdminSystemNotifications::class)->record(auth()->user(), 'Survey question archived', 'A survey question was archived. Existing answers are preserved.');
        return redirect()->route('questions.index')->with('status', 'Question archived. Existing answers are preserved.');
    }

    public function restore(Question $question)
    {
        $this->authorizeQuestion($question);
        $question->update(['archived_at' => null]);
        app(AdminSystemNotifications::class)->record(auth()->user(), 'Survey question restored', 'A survey question was restored.');
        return redirect()->route('questions.index')->with('status', 'Question restored.');
    }

    public function destroyPermanently(Question $question)
    {
        $this->authorizeQuestion($question);
        if ($question->is_core) {
            return back()->withErrors(['question' => 'Core survey questions cannot be deleted.']);
        }

        $deletedStep = $question->step;
        $wasDeleted = DB::transaction(function () use ($question, $deletedStep) {
            $lockedQuestion = Question::whereKey($question->id)->lockForUpdate()->firstOrFail();
            if ($lockedQuestion->answers()->exists()) return false;

            // Use the query builder to bypass Question's archive-only deleting hook.
            DB::table('questions')->where('id', $lockedQuestion->id)->delete();
            $this->unpublishEmptyStep($lockedQuestion->business_id, $deletedStep);
            return true;
        });

        if (! $wasDeleted) {
            return back()->withErrors(['question' => 'This question already has responses. Archive it to keep its feedback history.']);
        }

        app(AdminSystemNotifications::class)->record(auth()->user(), 'Survey question deleted', 'A survey question with no responses was permanently deleted.');
        return redirect()->route('questions.index')->with('status', 'Question permanently deleted.');
    }

    private function authorizeQuestion(Question $question): void
    {
        abort_unless(optional(auth()->user()->business)->id === $question->business_id, 403);
    }

    private function unpublishEmptyStep(int $businessId, string $key): void
    {
        $hasActiveQuestions = Question::where('business_id', $businessId)
            ->where('step', $key)
            ->whereNull('archived_at')
            ->exists();

        if ($hasActiveQuestions) return;

        // Keep the step row for historical answer references, but remove an empty custom step
        // from the active survey setup. Archived steps remain restorable in the Archived tab.
        SurveyStep::where('business_id', $businessId)
            ->where('key', $key)
            ->whereNull('archived_at')
            ->update(['is_published' => false, 'archived_at' => now()]);
    }

    private function validated(Request $request, ?Question $question = null): array
    {
        $allowedSteps = array_merge(array_keys(SurveyDefinition::STEPS), $request->user()->business->surveySteps()->whereNull('archived_at')->pluck('key')->all(), $question ? [$question->step] : []);
        $data = $request->validate([
            'question' => ['required', 'string', 'max:200'], 'question_type' => ['required', 'in:rating,multiple_choice,comment'],
            'step' => ['required', Rule::in($allowedSteps)],
            'options' => ['nullable', 'string', 'max:2000'], 'help_text' => ['nullable', 'string', 'max:300'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
        $data['required'] = $question && $question->is_core ? $question->required : $request->boolean('required');
        $data['sort_order'] = $data['sort_order'] ?? 100;
        if ($data['question_type'] === 'multiple_choice') {
            $choices = array_values(array_unique(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', str_replace('\\n', "\n", $data['options'] ?? ''))), fn ($value) => $value !== '')));
            if (count($choices) < 2 || count($choices) > 20 || collect($choices)->contains(fn ($choice) => mb_strlen($choice) > 100)) {
                throw ValidationException::withMessages(['options' => 'Provide 2 to 20 different choices, with up to 100 characters each.']);
            }
            $data['options'] = implode("\n", $choices);
        } else {
            $data['options'] = null;
        }
        return $data;
    }
}
