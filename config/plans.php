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
    ],

    'pro' => [
        'vault' => true,
        'checklist' => true,
        'reminder_applications' => null,
    ],

    'vault' => [
        'max_files' => 50,
        'max_kb' => 5120,
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
    ],

    // Days before the closing date that a reminder email is sent.
    'reminder_offsets' => [7, 3, 1],
];
