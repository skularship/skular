<?php

/*
|--------------------------------------------------------------------------
| Plan rules (Gratis vs Pro)
|--------------------------------------------------------------------------
|
| One place for every limit that depends on the plan, so a rule changes here
| and nowhere else. Read them only through EntitlementServiceInterface.
| The scholarship cap (max 5) is not a plan rule and stays in
| ScholarshipApplicationService::MAX_SELECTIONS.
|
*/

return [
    'free' => [
        'vault' => false,
        'checklist' => false,
        'reminder_applications' => 1,
        // Try Out: score and level are free; key, explanation, theory and mentor are Pro.
        'tryout_review' => false,
        'tryout_mentor' => false,
        'tryout_theory' => false,
        'tryout_free_attempts' => 1,
        // Tes IQ: questions are free; score, key, explanation, material and mentor need one paid access.
        'iq_premium' => false,
    ],

    'pro' => [
        'vault' => true,
        'checklist' => true,
        'reminder_applications' => null,
        'tryout_review' => true,
        'tryout_mentor' => true,
        'tryout_theory' => true,
        'tryout_free_attempts' => null,
        'iq_premium' => true,
    ],

    'vault' => [
        'max_files' => 50,
        'max_kb' => 5120,
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
    ],

    // Days before the closing date that a reminder email is sent.
    'reminder_offsets' => [7, 3, 1],
];
