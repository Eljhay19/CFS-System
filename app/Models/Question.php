<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    const TYPE_RATING = 'rating';
    const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    const TYPE_COMMENT = 'comment';

    protected $fillable = [
        'question',
        'question_type',
        'options',
        'required',
        'sort_order',
        'business_id', 'field_key', 'step', 'help_text', 'is_core', 'archived_at',
    ];

    protected $casts = [
        'required' => 'boolean',
        'is_core' => 'boolean', 'archived_at' => 'datetime',
    ];

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function optionList()
    {
        if (! $this->options) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode("\n", $this->options))));
    }
}
