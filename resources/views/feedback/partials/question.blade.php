@php
    $key = $question['key'];
    $isFood = $question['step'] === 'food';
    $name = $question['core'] ? 'core[' . $question['id'] . ']' : 'extra[' . $question['id'] . ']';
    $oldKey = $question['core'] ? 'core.' . $question['id'] : 'extra.' . $question['id'];
    $saved = app(\App\Services\SurveyDefinition::class)->value($question, session('survey', []));
    $value = old($oldKey, $saved);
    $id = ($question['core'] ? 'core_' : 'extra_') . $question['id'];
    $required = $question['required'];
    $icons = [
        'cleanliness' => '<path d="M15.5 3.5 20.5 8.5"></path><path d="M14 5 6.5 12.5"></path><path d="M5.5 13.5 10.5 18.5"></path><path d="M8 12 12 16"></path><path d="M4 20.5c2.4-.3 4.5-1.4 6.2-3.2"></path><path d="M5.8 18.7 7.3 20.2"></path>',
        'speed_of_service' => '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path>',
        'staff' => '<circle cx="8" cy="8" r="3"></circle><circle cx="17" cy="9" r="3"></circle><path d="M3 21v-2a5 5 0 0 1 10 0v2m2-6a4 4 0 0 1 6 4v2"></path>',
        'taste' => '<path d="M5 3v7m3-7v7M5 7h3M6.5 10v11M15 3v18m0-18c6 2 6 10 0 12"></path>',
        'freshness' => '<path d="M5 19C13 19 18 14 19 5 10 6 5 11 5 19Zm0 0L19 5"></path>',
        'temperature' => '<path d="M9 14V5a3 3 0 0 1 6 0v9a5 5 0 1 1-6 0Zm3-7v10"></path>',
        'presentation' => '<path d="M3 20h18M4 17h16M6 17a6 6 0 0 1 12 0m-6-9V6m-2 0h4"></path>',
        'value_for_money' => '<ellipse cx="9" cy="7" rx="5" ry="3"></ellipse><path d="M4 7v7c0 4 10 4 10 0V7M4 11c0 4 10 4 10 0m1 0c8 0 8 6 0 6m-4 0c1 4 10 4 10 0v-3"></path>',
    ];
@endphp
@if ($question['type'] === 'rating')
    @php
        $selectedName = $question['core'] ? 'core_selected[' . $question['id'] . ']' : 'extra_selected[' . $question['id'] . ']';
        $selectedOld = $question['core'] ? 'core_selected.' . $question['id'] : 'extra_selected.' . $question['id'];
        $selected = old($selectedOld, $saved !== '' && $saved !== null ? '1' : '') === '1';
        $na = $key === 'staff' && (bool) old('staff_na', $saved === 'N/A');
    @endphp
    <div class="service-rating-row {{ $isFood ? 'food-rating-row' : '' }}">
        <svg class="service-rating-icon" viewBox="0 0 24 24" aria-hidden="true">{!! $icons[$key] ?? '<path d="m12 3 3 6 6 1-4 5 1 6-6-3-6 3 1-6-4-5 6-1Z"></path>' !!}</svg>
        <div class="service-rating-main">
            <div class="slider-heading"><div>
                @if (in_array($key, ['cleanliness', 'speed_of_service', 'staff']))<strong>{{ ['cleanliness' => 'Store Cleanliness', 'speed_of_service' => 'Service Speed', 'staff' => 'Staff Assistance'][$key] }}</strong>@endif
                <label for="{{ $id }}">{{ $question['text'] }} @if ($required && ! $question['core'])<span class="required-dot">*</span>@endif</label>
            </div><span class="slider-value" data-output-for="{{ $id }}">{{ $na ? 'N/A' : ($selected ? $value : ((bool) ($custom ?? false) ? '' : 'Select')) }}</span></div>
            <div class="smooth-range {{ $selected ? 'is-selected' : '' }} {{ $na ? 'is-disabled' : '' }}" data-range-shell>
                <span class="smooth-range-track" aria-hidden="true"></span><span class="smooth-range-fill" aria-hidden="true"></span><span class="smooth-range-thumb" aria-hidden="true"></span>
                <input class="range-control" id="{{ $id }}" name="{{ $name }}" type="range" min="1" max="10" value="{{ is_numeric($value) ? $value : 5 }}" data-range data-required="{{ $required ? '1' : '0' }}" {{ $na ? 'disabled' : '' }}>
                <input type="hidden" name="{{ $selectedName }}" value="{{ $selected ? '1' : '' }}" data-range-selected>
            </div>
            <div class="range-scale"><span>1 Poor</span><span>10 Excellent</span></div>
            @if ($key === 'staff')<label class="rating-na-choice"><input type="checkbox" name="staff_na" value="1" data-staff-na {{ $na ? 'checked' : '' }}>N/A - No staff assistance experienced</label>@endif
            @if ($question['help'])<small class="muted question-help">{{ $question['help'] }}</small>@endif
        </div>
    </div>
@else
    <div class="survey-field final-question">
        <label class="final-question-label" for="{{ $id }}">{{ $question['text'] }} @if ($required)<span class="required-dot">*</span>@endif @if ($key === 'problem_comment')<span class="required-dot" data-comment-required hidden>*</span>@endif</label>
        @if ($question['type'] === 'comment' && in_array($key, ['date_of_visit', 'time_of_order']))
            <div class="survey-input-shell"><input id="{{ $id }}" name="{{ $name }}" type="{{ $key === 'date_of_visit' ? 'date' : 'time' }}" value="{{ $value ?: now()->format($key === 'date_of_visit' ? 'Y-m-d' : 'H:i') }}" @if ($key === 'date_of_visit') max="{{ now()->format('Y-m-d') }}" @endif required></div>
        @elseif ($question['type'] === 'multiple_choice' && in_array($key, ['order_accurate', 'food_satisfied']) && $question['options'] === ['Like Up', 'Like Down'])
            <div class="final-thumb-row">
                @foreach (['Like Up' => 'Yes', 'Like Down' => 'No'] as $choice => $label)
                    <label class="final-thumb-choice" aria-label="{{ $question['text'] }} {{ $label }}"><input type="radio" name="{{ $name }}" value="{{ $choice }}" {{ $value === $choice ? 'checked' : '' }} required><span class="final-thumb-icon" aria-hidden="true"><svg viewBox="0 0 24 24">
                        @if ($choice === 'Like Up')<path class="thumb-line" d="M7 10v11"></path><path class="thumb-fill" d="M2 10h5v11H4a2 2 0 0 1-2-2v-9Z"></path><path class="thumb-fill" d="M7 10l4.4-7.2A2 2 0 0 1 15 4.1V9h4.1a2 2 0 0 1 1.9 2.6l-2 7A2 2 0 0 1 17.1 20H7"></path>
                        @else<path class="thumb-line" d="M17 14V3"></path><path class="thumb-fill" d="M22 14h-5V3h3a2 2 0 0 1 2 2v9Z"></path><path class="thumb-fill" d="M17 14l-4.4 7.2A2 2 0 0 1 9 19.9V15H4.9A2 2 0 0 1 3 12.4l2-7A2 2 0 0 1 6.9 4H17"></path>@endif
                    </svg></span></label>
                @endforeach
            </div>
        @elseif ($question['type'] === 'multiple_choice')
            <div class="{{ $key === 'order_type' ? 'survey-choice-row' : 'final-radio-row' }}">
                @foreach ($question['options'] as $choice)
                    <label class="{{ $key === 'order_type' ? 'survey-choice order-type-choice' : 'final-radio-choice' }}"><input type="radio" name="{{ $name }}" value="{{ $choice }}" {{ $value === $choice ? 'checked' : '' }} {{ $required ? 'required' : '' }}>
                        @if ($key === 'order_type')<svg viewBox="0 0 24 24" aria-hidden="true">@if ($choice === 'Dine In')<path d="M4 3v7m3-7v7M4 7h3M5.5 10v11M15 3v18m0-18c6 2 6 10 0 12"></path>@else<path d="M6 8h12l-1 13H7L6 8Zm3 0V6a3 3 0 0 1 6 0v2"></path>@endif</svg>@endif
                        {{ $choice }}
                    </label>
                @endforeach
            </div>
        @else
            <textarea class="survey-textarea" id="{{ $id }}" name="{{ $name }}" maxlength="1000" {{ $required ? 'required' : '' }} placeholder="{{ $key === 'order_details' ? 'Example: Iced coffee, fries, burger...' : 'Enter your answer...' }}">{{ $value }}</textarea>
        @endif
        @if ($question['help'])<small class="muted question-help">{{ $question['help'] }}</small>@endif
    </div>
@endif
