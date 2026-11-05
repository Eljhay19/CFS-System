<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Services\SurveyDefinition;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Keep the database seeder free of sample users and feedback. Existing
        // business profiles still receive their core survey questions.
        $surveyDefinition = app(SurveyDefinition::class);

        Business::query()->each(function (Business $business) use ($surveyDefinition): void {
            $surveyDefinition->initialize($business);
        });
    }
}
