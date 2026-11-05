<?php

return [
    'business_id' => env('CFS_BUSINESS_ID'),
    'draft_minutes' => 120,
    'support_email' => env('CFS_SUPPORT_EMAIL'),
    'support_phone' => env('CFS_SUPPORT_PHONE'),
    'send_email_to' => env('FEEDBACK_ADMIN_EMAIL', 'user@admin.com'),
    'from_address' => env('MAIL_FROM_ADDRESS', 'user@admin.com'),
    'enabled' => env('FEEDBACK_ADMIN_EMAIL_ENABLED', true),
];
