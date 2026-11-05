@extends('layouts.app')

@section('title', 'CFS - FAQs')
@section('body_class', 'feedback-screen terms-screen')

@section('content')
    @php
        $faqSections = [
            [
                'id' => 'purpose',
                'label' => 'Purpose of the survey',
                'icon' => '<circle cx="12" cy="12" r="8"></circle><path d="M12 8v4l3 2"></path>',
                'title' => 'Purpose of the survey',
                'paragraphs' => [
                    'The CFS survey helps collect customer feedback about orders, food quality, service, staff, and overall satisfaction.',
                    'Responses help the business understand what is working well and what needs improvement.',
                ],
            ],
            [
                'id' => 'account',
                'label' => 'Account and registration requirements',
                'icon' => '<circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path>',
                'title' => 'Account and registration requirements',
                'paragraphs' => [
                    'Customers do not need to create an account to answer the feedback survey.',
                    'Admin access is only for the business owner or authorized users who manage questions, review feedback, and monitor reports.',
                ],
            ],
            [
                'id' => 'participation',
                'label' => 'Survey participation',
                'icon' => '<path d="M9 12l2 2 4-5"></path><path d="M21 12a9 9 0 1 1-9-9"></path>',
                'title' => 'Survey participation',
                'paragraphs' => [
                    'Participation is voluntary. You may answer the survey based on your recent customer experience.',
                    'Feedback is most helpful when it is honest, specific, and related to your actual visit or order.',
                ],
            ],
            [
                'id' => 'submit',
                'label' => 'How to submit feedback',
                'icon' => '<path d="M22 2 11 13"></path><path d="m22 2-7 20-4-9-9-4z"></path>',
                'title' => 'How to submit feedback',
                'paragraphs' => [
                    'Complete the feedback steps, provide ratings or comments where needed, then click Submit Feedback on the final step.',
                    'After submitting, you will see a Thank You page confirming that your response was successfully recorded.',
                ],
            ],
            [
                'id' => 'comments',
                'label' => 'Comments and suggestions',
                'icon' => '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path>',
                'title' => 'Comments and suggestions',
                'paragraphs' => [
                    'You may leave comments, concerns, or suggestions in the available text fields.',
                    'Please keep comments respectful and avoid sharing sensitive personal information that is not needed for the concern.',
                ],
            ],
            [
                'id' => 'privacy',
                'label' => 'Feedback anonymity and privacy',
                'icon' => '<path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6z"></path><path d="M9 12h6"></path>',
                'title' => 'Feedback anonymity and privacy',
                'paragraphs' => [
                    'Feedback may be reviewed by authorized admin users for reporting and action tracking.',
                    'You can submit without a name or email. Optional email follow-up requires your consent; only the authorized admin can view that email. A response with a follow-up email is not anonymous.',
                ],
            ],
            [
                'id' => 'usage',
                'label' => 'How feedback is used',
                'icon' => '<path d="M4 19V5"></path><path d="M8 17v-6"></path><path d="M12 17V7"></path><path d="M16 17v-3"></path><path d="M20 17V9"></path>',
                'title' => 'How feedback is used',
                'paragraphs' => [
                    'Feedback is used to monitor ratings, identify customer concerns, improve services, and support business reports.',
                    'Low ratings or concerns may be marked for admin review so the business can decide the proper action.',
                ],
            ],
            [
                'id' => 'editing',
                'label' => 'Editing submitted responses',
                'icon' => '<path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path>',
                'title' => 'Editing submitted responses',
                'paragraphs' => [
                    'Submitted customer responses are usually final once sent through the survey.',
                    'If a response needs correction, contact the business owner or support so they can review whether an update is possible.',
                ],
            ],
            [
                'id' => 'review',
                'label' => 'Feedback review process',
                'icon' => '<path d="M9 11h6M9 15h6"></path><path d="M5 3h14v18H5z"></path>',
                'title' => 'Feedback review process',
                'paragraphs' => [
                    'Admin users can view submitted responses, check ratings, and tag feedback that needs action.',
                    'The dashboard summarizes feedback counts, ratings, notifications, and recent responses for faster review.',
                ],
            ],
            [
                'id' => 'support',
                'label' => 'Contact and support inquiries',
                'icon' => '<path d="M4 13a8 8 0 0 1 16 0"></path><path d="M4 13v4a2 2 0 0 0 2 2h2v-8H6a2 2 0 0 0-2 2Z"></path><path d="M20 13v4a2 2 0 0 1-2 2h-2v-8h2a2 2 0 0 1 2 2Z"></path>',
                'title' => 'Contact and support inquiries',
                'paragraphs' => [
                    'For questions about the survey, submitted feedback, privacy, or support concerns, contact the business owner or CFS support channel.',
                    'Please include enough details about the concern so the team can review it properly.',
                ],
            ],
        ];
    @endphp

    <x-legal-card
        title="FAQs"
        intro="Quick answers about the CFS feedback survey, submission process, privacy, and support."
        :sections="$faqSections"
        nav-label="FAQ sections"
    />
@endsection
