<?php

/*
|--------------------------------------------------------------------------
| Try Out (simulasi IELTS, nanti SAT)
|--------------------------------------------------------------------------
|
| Every rule of the Try Out feature that is not plan dependent. Plan rules
| (review, mentor, theory, free attempts) live in config/plans.php and are
| read through EntitlementServiceInterface.
|
*/

return [
    // D1: SAT stays hidden from admins until its phase starts.
    'enabled_exam_types' => ['ielts'],

    // D2: admins choose the number of options freely; A to D is ideal.
    'options' => ['recommended' => 4, 'min' => 2],

    'adaptive' => [
        'start_level' => 3,
        'min_level' => 1,
        'max_level' => 5,
        'min_pool_per_level' => 8,
    ],

    // Answers arriving after deadline_at + this many seconds are refused.
    'answer_grace_seconds' => 5,

    // D12: false = an answer over the word limit is stored and still compared with the key.
    'strict_word_limit' => false,

    // D13: start = Writing/Speaking prompts use the start level; final_lr = later.
    'productive_level_source' => 'start',

    /*
    | Raw score (out of 40) => band. Read from the highest raw_min down; the
    | first row with raw >= raw_min wins. A section with another question
    | count is scaled to 40 first.
    |
    | SEMENTARA (D3): tabel umum IELTS Academic sampai dokumen
    | TO_IELTS_Academic_Set1_v0.2 dikirim. Ganti angka di sini saja.
    */
    'band_tables' => [
        'listening' => [39 => 9.0, 37 => 8.5, 35 => 8.0, 32 => 7.5, 30 => 7.0, 26 => 6.5, 23 => 6.0, 18 => 5.5, 16 => 5.0, 13 => 4.5, 10 => 4.0, 8 => 3.5, 6 => 3.0, 4 => 2.5, 2 => 2.0, 1 => 1.0, 0 => 0.0],
        'reading' => [39 => 9.0, 37 => 8.5, 35 => 8.0, 33 => 7.5, 30 => 7.0, 27 => 6.5, 23 => 6.0, 19 => 5.5, 15 => 5.0, 13 => 4.5, 10 => 4.0, 8 => 3.5, 6 => 3.0, 4 => 2.5, 2 => 2.0, 1 => 1.0, 0 => 0.0],
    ],

    'writing' => [
        // Autosave interval of the essay editor, in seconds (US5).
        'autosave_seconds' => 10,
        'max_characters' => 8000,
    ],

    'speaking' => [
        // Transcribed in the browser (Web Speech API); no audio is uploaded or stored.
        'recognition_language' => 'en-US',
        'max_transcript_characters' => 6000,
        // Upper bound of a spoken part accepted from the browser (seconds).
        'max_part_seconds' => 600,
    ],

    'ai' => [
        // "Nilai ulang" costs a provider call, so it is capped per answer.
        'max_retries_per_answer' => 2,
    ],

    'mentor' => [
        'limit' => (int) env('TRYOUT_MENTOR_LIMIT', 10),
    ],

    'theory_pdf' => [
        'disk' => 'local',
        'directory' => 'tryout-theory',
        'max_kb' => 20480,
    ],

    'disclaimer' => 'Latihan tidak resmi, tidak berafiliasi dengan IELTS, IDP, British Council, atau Cambridge. Skor adalah estimasi latihan.',
];
