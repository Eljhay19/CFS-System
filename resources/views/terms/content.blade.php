    @php
        $termsSections = [
            [
                'id' => 'rules',
                'label' => 'Survey usage rules',
                'icon' => '<path d="M6 3h8l4 4v14H6z"></path><path d="M14 3v5h5"></path><path d="M9 13h6M9 17h5"></path>',
                'title' => 'Survey usage rules',
                'paragraphs' => [
                    'Use the CFS survey to provide honest, respectful, and relevant feedback about your customer experience.',
                    'Spam, threats, false information, and comments unrelated to the service are not allowed.',
                ],
            ],
            [
                'id' => 'voluntary',
                'label' => 'Voluntary participation',
                'icon' => '<path d="M12 21s-7-4.4-7-11a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 6.6-7 11-7 11Z"></path>',
                'title' => 'Voluntary participation',
                'paragraphs' => [
                    'Participation in the survey is voluntary. You may stop at any time before submitting your feedback.',
                    'By submitting a response, you allow CFS to use your feedback for monitoring, review, and service improvement.',
                ],
            ],
            [
                'id' => 'guidelines',
                'label' => 'Survey guidelines',
                'icon' => '<path d="M9 11h6M9 15h6"></path><path d="M7 4h10a2 2 0 0 1 2 2v14l-4-2-3 2-3-2-4 2V6a2 2 0 0 1 2-2Z"></path>',
                'title' => 'Survey guidelines',
                'paragraphs' => [
                    'Answer each question based on your actual experience with the order, food, staff, and service quality.',
                    'Avoid entering sensitive personal information in comment fields unless it is necessary for your concern.',
                ],
            ],
            [
                'id' => 'submission',
                'label' => 'Feedback submission rules',
                'icon' => '<path d="M4 12h12"></path><path d="m10 6 6 6-6 6"></path><path d="M20 4v16"></path>',
                'title' => 'Feedback submission rules',
                'paragraphs' => [
                    'Make sure your answers are accurate and complete before submitting. Submitted feedback may be reviewed by an administrator for proper action.',
                    'Duplicate, offensive, or misleading submissions may be excluded from review or reporting.',
                ],
            ],
            [
                'id' => 'responsibilities',
                'label' => 'User responsibilities',
                'icon' => '<circle cx="8" cy="8" r="3"></circle><path d="M3 20a5 5 0 0 1 10 0"></path><circle cx="17" cy="9" r="2.5"></circle><path d="M14.5 15.5A4.5 4.5 0 0 1 21 20"></path>',
                'title' => 'User responsibilities',
                'paragraphs' => [
                    'Users are responsible for providing truthful, respectful, and relevant feedback.',
                    'Do not include another person\'s private data, abusive language, or content that may harm customers or staff.',
                ],
            ],
        ];
    @endphp

    <x-legal-card
        title="Terms & Conditions"
        intro="Please read these rules before using the CFS feedback survey."
        :sections="$termsSections"
        nav-label="Terms sections"
        :requires-agreement="true"
        agreement-label="I agree with the Terms & Conditions"
        :complete-url="route('feedback.terms-confirm')"
        :confirmation-token="$termsToken"
    />
