<?php

/*
|--------------------------------------------------------------------------
| Tes IQ Latihan
|--------------------------------------------------------------------------
|
| Every rule of the practice IQ test that is not plan dependent. What a
| paying mentee unlocks (score, key, explanation, material, mentor) lives in
| config/plans.php under "iq_premium" and is read through
| EntitlementServiceInterface. No IQ number is ever computed or shown:
| there is no norm table, so only correct counts and percentages exist.
|
*/

return [
    // Default length of a test; each test row stores its own duration.
    'duration_minutes' => 20,

    // Answers arriving after deadline_at + this many seconds are refused.
    'answer_grace_seconds' => 5,

    // Answer picks per minute a mentee may send (choices can be changed).
    'answers_per_minute' => 120,

    // Seconds counted at most for one stay on a question (tab left open, etc.).
    'max_seconds_per_visit' => 1200,

    // Support message after this share (percent) of the questions is answered.
    'support_milestones' => [25, 50, 75],

    /*
    | Total score (percent, rounded) => category. Read from the highest
    | threshold down; the first row with percent >= threshold wins.
    | SEMENTARA: rentang 80/60/40 belum final (keputusan Aqilah).
    */
    'categories' => [
        80 => 'Sangat baik',
        60 => 'Baik',
        40 => 'Cukup, perlu latihan',
        0 => 'Perlu latihan dasar',
    ],

    'mentor' => [
        // Questions to the AI mentor per mentee per day (Asia/Jakarta). Angka sementara.
        'daily_limit' => (int) env('IQ_MENTOR_DAILY_LIMIT', 10),
        'timezone' => 'Asia/Jakarta',
    ],

    // Question images (visuospatial items) are public so they load fast on phones.
    'images' => [
        'disk' => 'public',
        'directory' => 'iq-questions',
        'max_kb' => 2048,
    ],

    // Material slides (PPT exported to PDF) are private and only read in the in-page viewer.
    'material_pdf' => [
        'disk' => 'local',
        'directory' => 'iq-material',
        'max_kb' => 20480,
    ],

    'disclaimer' => 'Ini skor latihan, bukan skor IQ resmi. Hasil ini tidak dapat dipakai sebagai syarat beasiswa.',
];
