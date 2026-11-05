<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyStep extends Model
{
    protected $fillable = ['business_id', 'key', 'title', 'description', 'is_published', 'archived_at'];
    protected $casts = ['is_published' => 'boolean', 'archived_at' => 'datetime'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'step', 'key')->where('business_id', $this->business_id);
    }
}
