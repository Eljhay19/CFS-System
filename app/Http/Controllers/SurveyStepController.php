<?php

namespace App\Http\Controllers;

use App\Models\SurveyStep;
use App\Models\Question;
use App\Services\AdminSystemNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SurveyStepController extends Controller
{
    public function store(Request $request)
    {
        $business = $request->user()->business;
        abort_unless($business, 404);
        $data = $this->validated($request);
        $business->surveySteps()->create($data + ['key' => 'step_' . Str::lower(Str::random(16))]);
        app(AdminSystemNotifications::class)->record($request->user(), 'Survey step added', 'A survey step was added as a draft.');
        return redirect()->route('questions.index')->with('status', 'Step created as a draft.');
    }

    public function update(Request $request, SurveyStep $surveyStep)
    {
        $this->authorizeStep($request, $surveyStep);
        $data = $this->validated($request);
        $surveyStep->fill($data);
        $changed = $surveyStep->isDirty();
        $surveyStep->update($data);
        if ($changed) app(AdminSystemNotifications::class)->record($request->user(), 'Survey step updated', 'A survey step was updated.');
        return redirect()->route('questions.index')->with('status', 'Step updated. Existing customer drafts are unchanged.');
    }

    public function publish(Request $request, SurveyStep $surveyStep)
    {
        $this->authorizeStep($request, $surveyStep);
        if (! $surveyStep->is_published && ($surveyStep->archived_at || ! $surveyStep->questions()->whereNull('archived_at')->exists())) {
            return back()->withErrors(['step' => 'Add at least one active question before publishing this step.']);
        }
        $surveyStep->update(['is_published' => ! $surveyStep->is_published]);
        app(AdminSystemNotifications::class)->record($request->user(), $surveyStep->is_published ? 'Survey step published' : 'Survey step returned to draft', $surveyStep->is_published ? 'A survey step is now available to new respondents.' : 'A survey step was returned to draft.');
        return redirect()->route('questions.index')->with('status', $surveyStep->is_published ? 'Step published for new survey responses.' : 'Step returned to draft.');
    }

    public function archive(Request $request, SurveyStep $surveyStep)
    {
        $this->authorizeStep($request, $surveyStep);
        $surveyStep->update(['archived_at' => $surveyStep->archived_at ? null : now(), 'is_published' => false]);
        app(AdminSystemNotifications::class)->record($request->user(), $surveyStep->archived_at ? 'Survey step archived' : 'Survey step restored', $surveyStep->archived_at ? 'A survey step was archived.' : 'A survey step was restored as a draft.');
        return redirect()->route('questions.index')->with('status', 'Step archive status updated. Saved answers and existing drafts are preserved.');
    }

    public function destroy(Request $request, SurveyStep $surveyStep)
    {
        $this->authorizeStep($request, $surveyStep);
        DB::transaction(function () use ($surveyStep) {
            $step = SurveyStep::whereKey($surveyStep->id)->lockForUpdate()->firstOrFail();
            Question::where('business_id', $step->business_id)->where('step', $step->key)->delete();
            $step->delete();
        });
        app(AdminSystemNotifications::class)->record($request->user(), 'Survey step deleted', 'A survey step and its questions were permanently deleted. Existing feedback answers are preserved.');
        return redirect()->route('questions.index')->with('status', 'Step and its questions permanently deleted. Existing feedback answers are preserved.');
    }

    private function authorizeStep(Request $request, SurveyStep $step): void
    {
        abort_unless(optional($request->user()->business)->id === $step->business_id, 403);
    }

    private function validated(Request $request): array
    {
        return $request->validate(['title' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:300']]);
    }
}
