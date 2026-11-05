@extends('layouts.app')

@section('title', 'CFS - Privacy Policy')
@section('body_class', 'feedback-screen terms-screen')

@section('content')
    @php
        $privacySections = [
            [
                'id' => 'handling',
                'label' => 'How customer data is handled',
                'icon' => '<path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6z"></path><path d="m9 12 2 2 4-5"></path>',
                'title' => 'How customer data is handled',
                'paragraphs' => [
                    'CFS handles customer data with care and uses it only for feedback monitoring, review, and service improvement.',
                    'Access to customer information is limited to authorized users who need it for system records or customer concern handling.',
                ],
            ],
            [
                'id' => 'collection',
                'label' => 'Data collection',
                'icon' => '<path d="M4 7h16M4 12h16M4 17h10"></path><path d="M7 4v16"></path>',
                'title' => 'Data collection',
                'paragraphs' => [
                    'The survey collects responses about the date and time of visit, order experience, ratings, comments, and concern details when provided.',
                    'No name is required. You may submit without an email address. If you request a follow-up, your email and explicit consent are saved with your feedback and are accessible only to the authorized admin for that follow-up. Such a response is not anonymous. Unchecking follow-up removes the email from the submission.',
                    'Avoid entering sensitive personal information in comments. Contact Us separately stores your email address, message, and optional name for support.',
                ],
            ],
            [
                'id' => 'security',
                'label' => 'Data storage and security',
                'icon' => '<rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path><path d="M12 14v2"></path>',
                'title' => 'Data storage and security',
                'paragraphs' => [
                    'Submitted feedback is securely stored and protected through the admin system\'s access controls.',
                    'Reasonable safeguards are used to help prevent unauthorized access, alteration, or misuse of feedback records.',
                ],
            ],
            [
                'id' => 'sharing',
                'label' => 'Data access and sharing',
                'icon' => '<circle cx="8" cy="8" r="3"></circle><path d="M3 20a5 5 0 0 1 10 0"></path><circle cx="17" cy="9" r="2.5"></circle><path d="M14.5 15.5A4.5 4.5 0 0 1 21 20"></path>',
                'title' => 'Data access and sharing',
                'paragraphs' => [
                    'Feedback data may be accessed only by the authorized business owner or admin users for review, reporting, and action tracking.',
                    'Personal data will not be sold or publicly posted without permission, unless required by a valid legal or administrative process.',
                ],
            ],
            [
                'id' => 'retention',
                'label' => 'Data retention and deletion',
                'icon' => '<path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v5M14 11v5"></path>',
                'title' => 'Data retention and deletion',
                'paragraphs' => [
                    'Feedback records are retained while they are needed for business monitoring, reports, service improvement, and concern resolution.',
                    'Retention and deletion requests are handled by the business owner. This system does not automatically delete submitted records on a schedule.',
                ],
            ],
            [
                'id' => 'rights',
                'label' => 'User privacy rights',
                'icon' => '<path d="M12 3 5 6v5c0 4.8 2.9 8 7 10 4.1-2 7-5.2 7-10V6z"></path><path d="M9 12h6"></path><path d="M12 9v6"></path>',
                'title' => 'User privacy rights',
                'paragraphs' => [
                    'Users may ask how their feedback data is used and may request correction or deletion when applicable.',
                    'For privacy concerns, users may contact CFS support or the business owner responsible for the feedback system.',
                ],
            ],
        ];
    @endphp

    <x-legal-card
        title="Privacy Policy"
        intro="Learn how CFS collects, protects, and uses customer feedback data."
        :sections="$privacySections"
        nav-label="Privacy sections"
    />
@endsection
