@extends('layouts.admin')

@section('title', 'Manage Questions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/questions.css') }}?v={{ filemtime(public_path('css/questions.css')) }}">
@endpush

@section('admin_content')
    <section class="questions-panel">
        <div class="questions-head">
            <div>
                <p class="muted">Survey Setup</p>
                <h1>Manage Questions</h1>
                <p>Create and organize survey questions for your feedback form.</p>
            </div>
            <div class="question-head-actions">
            <button class="button compact-button step-add-button" type="button" data-step-add><x-icon name="plus" />Add Step</button>
            <button class="button questions-add-button compact-button" type="button" data-question-modal-open>
                <x-icon name="plus" />
                Add Question
            </button>
            </div>
        </div>

        <nav class="question-view-tabs" aria-label="Question status">
            <a href="{{ route('questions.index') }}" @if (! $archived) aria-current="page" @endif>Active Questions</a>
            <a href="{{ route('questions.index', ['archived' => 1]) }}" @if ($archived) aria-current="page" @endif>Archived Questions</a>
        </nav>

        @include('questions.steps')

        @if (! empty($search))
            <p class="muted questions-filter-note">Search results for "{{ $search }}". <a class="panel-link" href="{{ route('questions.index') }}">Clear search</a></p>
        @endif

        <div class="questions-list-toolbar">
            <span class="muted">{{ $questions->count() }} {{ $questions->count() === 1 ? 'question' : 'questions' }}</span>
            <span class="questions-list-note">Core questions move to Archived Questions; custom questions can be permanently deleted.</span>
            @if ($questions->count() > 4)
                <button class="questions-text-action" type="button" data-question-list-open>View all questions</button>
            @endif
        </div>
        <div class="questions-table-wrap" tabindex="0" role="region" aria-label="Survey questions">
            <table class="questions-table">
                <thead>
                    <tr>
                        <th class="questions-col-number">#</th>
                        <th class="questions-col-question">Question</th>
                        <th class="questions-col-type">Type</th>
                        <th class="questions-col-required">Required</th>
                        <th class="questions-col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($questions as $question)
                        <tr data-question-row data-is-core="{{ $question->is_core ? '1' : '0' }}">
                            <td class="questions-col-number" data-label="#">{{ $loop->iteration }}</td>
                            <td class="questions-col-question" data-label="Question">{{ $question->question }}<small class="workflow-table-note">{{ $stepLabels[$question->step] ?? 'Archived Step' }}{{ $question->is_core ? ' / Core' : '' }}</small></td>
                            <td class="questions-col-type" data-label="Type">{{ ucwords(str_replace('_', ' ', $question->question_type)) }}</td>
                            <td class="questions-col-required" data-label="Required">{{ $question->required ? 'Yes' : 'No' }}</td>
                            <td class="questions-col-actions" data-label="Actions">
                                <div class="questions-actions">
                                    <button class="questions-action questions-edit" type="button" data-question-edit data-question-id="{{ $question->id }}">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 20 8-8-4-4-8 8-2 6 6-2Z"></path><path d="m14 6 4 4"></path></svg>
                                        Edit
                                    </button>
                                    @if ($question->is_core && ! $question->archived_at)
                                        <form action="{{ route('questions.destroy', $question) }}" method="POST" data-question-delete-form data-question-delete-mode="archive" data-question-id="{{ $question->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="questions-action questions-delete-link" type="submit" aria-label="Remove {{ $question->question }} from the active survey">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v5M14 11v5"></path></svg>
                                                Delete
                                            </button>
                                        </form>
                                    @elseif (! $question->is_core)
                                        <form action="{{ route('questions.permanently-destroy', $question) }}" method="POST" data-question-delete-form data-question-delete-mode="permanent" data-question-id="{{ $question->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="questions-action questions-delete-link" type="submit" aria-label="Permanently delete {{ $question->question }}">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v5M14 11v5"></path></svg>
                                                Delete
                                            </button>
                                        </form>
                                    @endunless
                                    @if ($question->archived_at)
                                    <form action="{{ route('questions.restore', $question) }}" method="POST">@csrf<button class="questions-action questions-edit" type="submit">Restore</button></form>
                                    @elseif (! $question->is_core)
                                    <form action="{{ route('questions.destroy', $question) }}" method="POST" onsubmit="return confirm('Archive this question? Existing answers will be preserved.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="questions-action questions-delete" type="submit">
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v5M14 11v5"></path></svg>
                                            Archive
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="muted">No questions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($questions->count() > 4)
            <dialog class="questions-list-dialog" aria-labelledby="questions-list-title" data-question-list-dialog>
                <header class="questions-list-dialog-head">
                    <div><h2 id="questions-list-title">All {{ $archived ? 'Archived ' : '' }}Questions</h2><p>Custom questions are shown first. Deleting a core question moves it to Archived Questions and keeps saved feedback; custom questions can be permanently deleted when they have no saved responses.</p></div>
                    <button class="terms-close" type="button" aria-label="Close all questions" data-question-list-close>&times;</button>
                </header>
                <div class="questions-list-dialog-table" data-question-list-mount></div>
            </dialog>
        @endif
        <dialog class="question-delete-confirm" aria-labelledby="question-delete-title" aria-describedby="question-delete-message" data-question-delete-dialog>
            <h2 id="question-delete-title">Delete this question?</h2>
            <p id="question-delete-message" data-delete-question-message>Are you sure you want to permanently delete <strong data-delete-question-label>this question</strong>? This cannot be undone.</p>
            <div class="question-delete-confirm-actions">
                <button class="question-delete-no" type="button" data-delete-question-no>No</button>
                <button class="question-delete-yes" type="button" data-delete-question-yes>Yes, delete</button>
            </div>
        </dialog>
    </section>

    @php
        $questionPayload = $questions->mapWithKeys(fn ($question) => [
            $question->id => [
                'id' => $question->id,
                'action' => route('questions.update', $question),
                'question' => $question->question,
                'question_type' => $question->question_type,
                'required' => (bool) $question->required,
                'sort_order' => $question->sort_order,
                'options' => $question->optionList(),
                'step' => $question->step, 'help_text' => $question->help_text, 'is_core' => $question->is_core,
            ],
        ]);
    @endphp

    <script type="application/json" id="questions-data">@json($questionPayload)</script>

    <div class="question-modal-backdrop" hidden data-question-modal>
        <div class="question-modal" role="dialog" aria-modal="true" aria-labelledby="question-modal-title">
            <button class="question-modal-close" type="button" aria-label="Close question modal" data-question-modal-close>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
            </button>

            <div class="question-modal-head">
                <h2 id="question-modal-title" data-question-modal-title>Add New Question</h2>
                <p data-question-modal-subtitle>Create a survey item for your feedback form.</p>
                <p class="question-core-edit-note" data-core-edit-note hidden>Core questions stay part of the survey, but their wording, type, section, hint, and display order can be changed.</p>
            </div>

            <form class="question-modal-form" action="{{ route('questions.store') }}" method="POST" data-question-form data-store-action="{{ route('questions.store') }}">
                @csrf
                <input type="hidden" name="_method" value="" disabled data-method-field>
                <input type="hidden" name="_question_mode" value="add" data-modal-mode>
                <input type="hidden" name="_question_id" value="" data-modal-question-id>
                <textarea name="options" hidden data-choices-output>{{ old('options') }}</textarea>

                <div class="question-field">
                    <label for="modal_question">Question Text</label>
                    <textarea id="modal_question" name="question" maxlength="200" required placeholder="Enter your question here..." data-question-text>{{ old('question') }}</textarea>
                    <span class="question-counter"><span data-question-count>0</span>/200</span>
                    @error('question')
                        <small class="question-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="question-field">
                    <label for="modal_question_type">Question Type</label>
                    <div class="question-select-shell">
                        <select id="modal_question_type" name="question_type" required data-question-type>
                            <option value="multiple_choice" {{ old('question_type', 'multiple_choice') === 'multiple_choice' ? 'selected' : '' }}>Multiple Choice</option>
                            <option value="rating" {{ old('question_type') === 'rating' ? 'selected' : '' }}>Rating</option>
                            <option value="comment" {{ old('question_type') === 'comment' ? 'selected' : '' }}>Comment</option>
                        </select>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
                    </div>
                    @error('question_type')
                        <small class="question-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="question-field">
                    <label for="modal_help_text" data-help-label>Help Text <span>(optional)</span></label>
                    <input id="modal_help_text" name="help_text" value="{{ old('help_text') }}" placeholder="Add a short description (optional)...">
                </div>

                <div class="question-field"><label for="modal_step">Survey Section</label><div class="question-select-shell"><select id="modal_step" name="step" required>@foreach ($stepLabels as $key => $label)<option value="{{ $key }}" {{ old('step', 'final') === $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg></div></div>
                <div class="question-field"><label for="modal_sort">Display Order</label><input id="modal_sort" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $nextSortOrder) }}" data-sort-order></div>
                <label class="question-toggle-row">
                    <span>Required</span>
                    <input type="checkbox" name="required" value="1" {{ old('required', true) ? 'checked' : '' }}>
                    <i aria-hidden="true"></i>
                    <em data-required-label>Yes</em>
                </label>

                <div class="question-choice-section" data-choice-section>
                    <h3>Answer Choices</h3>
                    <div class="question-choice-list" data-choice-list></div>
                    <button class="question-choice-add" type="button" data-choice-add>
                        <span aria-hidden="true">+</span>
                        Add Choice
                    </button>
                    @error('options')
                        <small class="question-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="question-modal-actions">
                    <button class="button question-cancel" type="button" data-question-modal-close>Cancel</button>
                    <button class="button question-save" type="submit">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.querySelector('[data-question-modal]');
            const openButton = document.querySelector('[data-question-modal-open]');
            const questionListDialog = document.querySelector('[data-question-list-dialog]');
            const questionListOpen = document.querySelector('[data-question-list-open]');
            const questionListClose = document.querySelector('[data-question-list-close]');
            const questionListMount = document.querySelector('[data-question-list-mount]');
            const sourceQuestionTable = document.querySelector('.questions-table-wrap .questions-table');
            const deleteDialog = document.querySelector('[data-question-delete-dialog]');
            const deleteQuestionLabel = document.querySelector('[data-delete-question-label]');
            const deleteQuestionMessage = document.querySelector('[data-delete-question-message]');
            const deleteNoButton = document.querySelector('[data-delete-question-no]');
            const deleteYesButton = document.querySelector('[data-delete-question-yes]');
            const closeButtons = document.querySelectorAll('[data-question-modal-close]');
            const form = document.querySelector('[data-question-form]');
            const modalTitle = document.querySelector('[data-question-modal-title]');
            const modalSubtitle = document.querySelector('[data-question-modal-subtitle]');
            const coreEditNote = document.querySelector('[data-core-edit-note]');
            const methodField = document.querySelector('[data-method-field]');
            const modalModeField = document.querySelector('[data-modal-mode]');
            const modalQuestionIdField = document.querySelector('[data-modal-question-id]');
            const sortOrderInput = document.querySelector('[data-sort-order]');
            const stepSelect = document.querySelector('#modal_step');
            let editingCore = false;
            let pendingDeleteForm = null;
            const questionText = document.querySelector('[data-question-text]');
            const questionCount = document.querySelector('[data-question-count]');
            const typeSelect = document.querySelector('[data-question-type]');
            const helpLabel = document.querySelector('[data-help-label]');
            const helpInput = document.getElementById('modal_help_text');
            const requiredInput = document.querySelector('.question-toggle-row input[name="required"]');
            const choiceSection = document.querySelector('[data-choice-section]');
            const choiceList = document.querySelector('[data-choice-list]');
            const choiceAdd = document.querySelector('[data-choice-add]');
            const choicesOutput = document.querySelector('[data-choices-output]');
            const questionsDataScript = document.getElementById('questions-data');

            if (!modal || !openButton || !form) {
                return;
            }

            let questionsData = {};
            try {
                questionsData = JSON.parse(questionsDataScript?.textContent || '{}');
            } catch (error) {
                questionsData = {};
            }

            const storeAction = form.dataset.storeAction || form.action;
            const defaultSortOrder = @json(old('sort_order', $nextSortOrder));
            const oldMode = @json(old('_question_mode'));
            const oldQuestionId = @json(old('_question_id'));
            const oldQuestion = @json(old('question'));
            const oldQuestionType = @json(old('question_type'));
            const oldHelpText = @json(old('help_text'));
            const oldSortOrder = @json(old('sort_order'));
            const existingChoices = (choicesOutput.value || '')
                .split(/\r?\n/)
                .map((choice) => choice.trim())
                .filter(Boolean);
            const ratingDefaults = ['1 - Poor', '5 - Average', '10 - Excellent'];
            const choiceDefaults = ['Choice 1', 'Choice 2'];
            const hintDefaults = {
                rating: 'Select a rating from 1 to 10.',
                multiple_choice: 'Choose the best answer from the choices.',
                comment: 'Write your answer or suggestion here.',
            };

            function escapeAttribute(value) {
                return value.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }

            function openModal(focusTarget = questionText) {
                modal.hidden = false;
                document.body.classList.add('question-modal-open');
                setTimeout(() => focusTarget?.focus(), 30);
            }

            function closeModal() {
                modal.hidden = true;
                document.body.classList.remove('question-modal-open');
            }

            function updateCount() {
                questionCount.textContent = String((questionText.value || '').length);
                document.querySelector('[data-required-label]').textContent = requiredInput.checked ? 'Yes' : 'No';
            }

            function syncChoices() {
                const values = Array.from(choiceList.querySelectorAll('[data-choice-input]'))
                    .map((input) => input.value.trim())
                    .filter(Boolean);

                choicesOutput.value = values.join('\n');
            }

            function clearChoices() {
                choiceList.innerHTML = '';
                choicesOutput.value = '';
            }

            function addChoice(value = '') {
                const row = document.createElement('div');
                row.className = 'question-choice-row';
                row.innerHTML = `
                    <span class="question-choice-grip" draggable="true" tabindex="0" role="button" aria-label="Move choice">⋮⋮</span>
                    <input type="text" value="${escapeAttribute(value)}" placeholder="Choice ${choiceList.children.length + 1}" data-choice-input>
                    <button type="button" aria-label="Remove choice" data-choice-remove>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
                    </button>
                `;
                choiceList.appendChild(row);
                syncChoices();
            }

            function setChoices(values = choiceDefaults) {
                clearChoices();
                values
                    .map((choice) => String(choice || '').trim())
                    .filter(Boolean)
                    .forEach(addChoice);
                ensureChoices();
                syncChoices();
            }

            function ensureChoices(defaults = choiceDefaults) {
                while (choiceList.children.length < 2) {
                    addChoice(defaults[choiceList.children.length] || 'Choice ' + (choiceList.children.length + 1));
                }
            }

            function toggleChoices() {
                const acceptsChoices = typeSelect.value === 'multiple_choice';
                choiceSection.hidden = !acceptsChoices;
                if (acceptsChoices) {
                    const defaults = typeSelect.value === 'rating' ? ratingDefaults : choiceDefaults;
                    ensureChoices(defaults);
                    syncChoices();
                } else {
                    choicesOutput.value = '';
                }
            }

            function setMethod(method = '') {
                if (!method) {
                    methodField.value = '';
                    methodField.disabled = true;
                    return;
                }

                methodField.value = method;
                methodField.disabled = false;
            }

            function setHelpLabel(text) {
                helpLabel.innerHTML = `${text} <span>(optional)</span>`;
            }

            function prepareAddModal(useCurrentValues = false) {
                editingCore = false;
                coreEditNote.hidden = true;
                typeSelect.disabled = false; stepSelect.disabled = false; requiredInput.disabled = false;
                stepSelect.value = @json(old('step', 'final'));
                modal.classList.add('is-editing');
                form.action = storeAction;
                setMethod('');
                modalModeField.value = 'add';
                modalQuestionIdField.value = '';
                modalTitle.textContent = 'Add New Question';
                modalSubtitle.textContent = 'Create a survey item for your feedback form.';
                setHelpLabel('Help Text');

                if (!useCurrentValues) {
                    questionText.value = '';
                    typeSelect.value = 'multiple_choice';
                    helpInput.value = '';
                    sortOrderInput.value = defaultSortOrder;
                    requiredInput.checked = true;
                    setChoices(choiceDefaults);
                } else {
                    setChoices(existingChoices.length ? existingChoices : choiceDefaults);
                }

                updateCount();
                toggleChoices();
            }

            function prepareEditModal(question) {
                editingCore = Boolean(question.is_core);
                coreEditNote.hidden = !editingCore;
                typeSelect.disabled = false; stepSelect.disabled = false; requiredInput.disabled = editingCore;
                stepSelect.value = question.step || 'final';
                modal.classList.add('is-editing');
                form.action = question.action;
                setMethod('PUT');
                modalModeField.value = 'edit';
                modalQuestionIdField.value = question.id || '';
                modalTitle.textContent = 'Edit Question';
                modalSubtitle.textContent = 'Modify this survey question and update its details.';
                setHelpLabel('Placeholder or Hint');

                questionText.value = question.question || '';
                typeSelect.value = question.question_type || 'comment';
                helpInput.value = question.help_text || '';
                sortOrderInput.value = question.sort_order || 0;
                requiredInput.checked = Boolean(question.required);

                let choices = Array.isArray(question.options) ? question.options : [];
                if (typeSelect.value === 'rating' && choices.length === 0) {
                    choices = ratingDefaults;
                }
                if (typeSelect.value === 'multiple_choice' && choices.length === 0) {
                    choices = choiceDefaults;
                }
                setChoices(choices);
                updateCount();
                toggleChoices();
            }

            function applyOldValues() {
                if (oldQuestion !== null) {
                    questionText.value = oldQuestion || '';
                }
                if (oldQuestionType) {
                    typeSelect.value = oldQuestionType;
                }
                helpInput.value = oldHelpText || '';
                if (oldSortOrder !== null) {
                    sortOrderInput.value = oldSortOrder;
                }

                const fallbackChoices = typeSelect.value === 'rating' ? ratingDefaults : choiceDefaults;
                setChoices(existingChoices.length ? existingChoices : fallbackChoices);
                updateCount();
                toggleChoices();
            }

            openButton.addEventListener('click', () => {
                prepareAddModal();
                openModal();
            });
            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-question-edit]');
                if (!button) return;
                const question = questionsData[button.dataset.questionId];
                if (!question) return;
                questionListDialog?.close();
                prepareEditModal(question);
                openModal();
            });
            const questionRows = Array.from(document.querySelectorAll('[data-question-row]'));
            const fitQuestionRows = () => {
                const visibleLimit = window.matchMedia('(max-width: 760px)').matches ? 4 : 7;
                questionRows.forEach((row, index) => { row.hidden = index >= visibleLimit; });
            };
            fitQuestionRows();
            window.addEventListener('resize', fitQuestionRows, { passive: true });
            questionListOpen?.addEventListener('click', () => {
                if (!sourceQuestionTable || !questionListDialog || !questionListMount) return;
                const fullTable = sourceQuestionTable.cloneNode(true);
                fullTable.querySelectorAll('tr[hidden]').forEach((row) => row.removeAttribute('hidden'));
                const tableBody = fullTable.querySelector('tbody');
                Array.from(tableBody?.querySelectorAll('[data-question-row]') || [])
                    .sort((first, second) => Number(first.dataset.isCore) - Number(second.dataset.isCore))
                    .forEach((row) => tableBody.appendChild(row));
                questionListMount.replaceChildren(fullTable);
                questionListDialog.showModal();
            });
            questionListClose?.addEventListener('click', () => questionListDialog?.close());
            document.addEventListener('submit', (event) => {
                const deleteForm = event.target.closest('[data-question-delete-form]');
                if (!deleteForm) return;
                event.preventDefault();
                pendingDeleteForm = deleteForm;
                const question = questionsData[deleteForm.dataset.questionId];
                deleteQuestionLabel.textContent = question?.question || 'this question';
                const coreRemoval = deleteForm.dataset.questionDeleteMode === 'archive';
                deleteQuestionMessage.firstChild.textContent = coreRemoval
                    ? 'Are you sure you want to remove '
                    : 'Are you sure you want to permanently delete ';
                deleteQuestionMessage.lastChild.textContent = coreRemoval
                    ? '? It will move to Archived Questions and existing feedback will be kept.'
                    : '? This cannot be undone and is allowed only if there are no saved responses.';
                deleteYesButton.textContent = coreRemoval ? 'Yes, remove' : 'Yes, delete';
                deleteDialog.showModal();
                deleteNoButton.focus();
            });
            deleteNoButton.addEventListener('click', () => deleteDialog.close());
            deleteDialog.addEventListener('close', () => { pendingDeleteForm = null; });
            deleteYesButton.addEventListener('click', () => {
                if (pendingDeleteForm) pendingDeleteForm.submit();
            });
            closeButtons.forEach((button) => button.addEventListener('click', closeModal));
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.hidden) {
                    closeModal();
                }
            });

            questionText.addEventListener('input', updateCount);
            requiredInput.addEventListener('change', updateCount);
            typeSelect.addEventListener('change', toggleChoices);
            choiceAdd.addEventListener('click', () => {
                addChoice('');
                choiceList.lastElementChild?.querySelector('input')?.focus();
            });
            choiceList.addEventListener('input', syncChoices);
            let draggedChoice = null;
            choiceList.addEventListener('dragstart', (event) => {
                if (!event.target.closest('.question-choice-grip')) return;
                draggedChoice = event.target.closest('.question-choice-row');
                event.dataTransfer.setData('text/plain', 'choice'); event.dataTransfer.effectAllowed = 'move';
            });
            choiceList.addEventListener('dragover', (event) => {
                const target = event.target.closest('.question-choice-row');
                if (!draggedChoice || !target || target === draggedChoice) return;
                event.preventDefault();
                const bounds = target.getBoundingClientRect();
                choiceList.insertBefore(draggedChoice, event.clientY < bounds.y + bounds.height / 2 ? target : target.nextSibling);
            });
            choiceList.addEventListener('dragend', () => { draggedChoice = null; syncChoices(); });
            choiceList.addEventListener('keydown', (event) => {
                if (!event.target.matches('.question-choice-grip') || !['ArrowUp', 'ArrowDown'].includes(event.key)) return;
                event.preventDefault();
                const row = event.target.closest('.question-choice-row');
                const sibling = event.key === 'ArrowUp' ? row.previousElementSibling : row.nextElementSibling;
                if (sibling) choiceList.insertBefore(row, event.key === 'ArrowUp' ? sibling : sibling.nextSibling);
                event.target.focus(); syncChoices();
            });
            choiceList.addEventListener('click', (event) => {
                const removeButton = event.target.closest('[data-choice-remove]');
                if (!removeButton) {
                    return;
                }

                removeButton.closest('.question-choice-row')?.remove();
                ensureChoices();
                syncChoices();
            });
            form.addEventListener('submit', syncChoices);

            setChoices(existingChoices.length ? existingChoices : choiceDefaults);
            updateCount();
            toggleChoices();

            @if ($errors->any() && old('_question_mode'))
                if (oldMode === 'edit' && questionsData[oldQuestionId]) {
                    prepareEditModal(questionsData[oldQuestionId]);
                    applyOldValues();
                } else {
                    prepareAddModal(true);
                    applyOldValues();
                }
                openModal();
            @else
                const requestedQuestion = questionsData[new URLSearchParams(location.search).get('edit')];
                if (requestedQuestion) { prepareEditModal(requestedQuestion); openModal(); }
            @endif
        })();
    </script>
@endsection
