<?php

namespace Tests\Unit;

use App\Models\Answer;
use PHPUnit\Framework\TestCase;

class AnswerDisplayTest extends TestCase
{
    public function test_admin_values_preserve_raw_answers_and_format_only_known_fields(): void
    {
        foreach ([
            ['comment', 'date_of_visit', '2026-10-12', 'Oct 12, 2026'],
            ['comment', 'time_of_order', '19:27', '7:27 PM'],
            ['comment', 'date_of_visit', '2026-02-31', '2026-02-31'],
            ['rating', 'staff', 'N/A', 'N/A'],
            ['rating', 'cleanliness', '5', '5/10'],
            ['like', 'order_accurate', 'Like Down', 'No'],
            ['like', 'order_accurate', 'Like Up', 'Yes'],
            ['comment', 'custom_text', '0', '0'],
            ['comment', 'custom_text', 'Like Up', 'Like Up'],
            ['comment', 'date_of_visit', "2026-10-12\0", "2026-10-12\0"],
            ['comment', 'custom_text', '', 'No answer provided.'],
        ] as [$type, $key, $raw, $expected]) {
            $answer = new Answer(['question_type'=>$type, 'field_key'=>$key, 'answer'=>$raw]);
            $this->assertSame($expected, $answer->displayValue());
            $this->assertSame($raw, $answer->answer);
        }
    }
}
