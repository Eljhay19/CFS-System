<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = [
        'feedback_id',
        'question_id',
        'answer',
        'question_text', 'question_type', 'question_options', 'field_key',
    ];

    public function text(): string
    {
        return $this->question_text ?? optional($this->question)->question ?? 'Question removed';
    }

    public function type(): ?string
    {
        return $this->question_type ?? optional($this->question)->question_type;
    }

    public function displayValue(): string
    {
        $value = (string) $this->answer;
        if ($value === '') return 'No answer provided.';
        if ($this->type() === Question::TYPE_RATING && is_numeric($value)) return $value . '/10';
        $key = $this->field_key ?: optional($this->question)->field_key;
        if (in_array($key, ['order_accurate', 'food_satisfied'], true)) {
            if ($value === 'Like Up') return 'Yes';
            if ($value === 'Like Down') return 'No';
        }
        $formats = ['date_of_visit' => ['Y-m-d', 'M d, Y'], 'time_of_order' => ['H:i', 'g:i A']];
        if (isset($formats[$key]) && strpos($value, "\0") === false) {
            [$input, $output] = $formats[$key];
            $date = \DateTimeImmutable::createFromFormat('!' . $input, $value);
            if ($date && $date->format($input) === $value) return $date->format($output);
        }
        return $value;
    }

    public function feedback()
    {
        return $this->belongsTo(Feedback::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
