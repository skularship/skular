<?php

/**
 * Central loading copy for the whole application.
 *
 * Every reusable loading component resolves its message from here, so the
 * wording stays consistent and can be revised (or translated) in one place
 * instead of being scattered across Blade views.
 *
 * Guidelines for the copy:
 * - Short, warm Indonesian that a student reads in one glance.
 * - Describe what the system is doing, never promise a duration or a
 *   percentage the backend cannot actually measure.
 * - Keep the main action label untouched; this copy only appears while the
 *   request is genuinely running.
 */
return [

    'copy' => [
        // Generic
        'save' => 'Bentar ya, lagi disimpan…',
        'send' => 'Oke, lagi dikirim…',
        'upload' => 'Lagi upload, jangan tutup dulu ya…',
        'page' => 'Lagi nyiapin halaman…',

        // Profiling & rekomendasi
        'profiling.save' => 'Bentar ya, jawabanmu lagi disimpan…',
        'profiling.recommendation' => 'Lagi nyusun rekomendasi buat kamu…',
        'profiling.readiness' => 'Lagi ngitung kesiapanmu…',

        // Dashboard & pembelajaran
        'dashboard.advance' => 'Lagi pindahin jalur belajarmu…',
        'module.open' => 'Bentar, kita buka materinya dulu…',
        'module.section' => 'Lagi buka bagian ini…',
        'module.practice' => 'Lagi nyiapin halaman praktik…',
        'module.quick' => 'Lagi cek jawabanmu…',
        'module.checkpoint' => 'Lagi nyimpen progres belajarmu…',
        'module.feedback' => 'Lagi kirim masukanmu…',

        // Beasiswa & dokumen
        'scholarship.search' => 'Lagi nyari peluang buat kamu…',
        'scholarship.save' => 'Lagi nyimpen pilihan beasiswamu…',
        'scholarship.confirm' => 'Lagi nyimpen konfirmasi pendaftaranmu…',
        'scholarship.result' => 'Lagi nyimpen hasilnya…',
        'document.twibbon' => 'Lagi merapikan twibbon kamu…',
        'cv.generate' => 'Lagi merapikan CV kamu…',
        'faq.search' => 'Lagi nyari jawaban yang pas…',

        // AI
        'ai.brainstorm' => 'Lagi bantu kamu nyari ide…',
        'ai.brainstorm_summary' => 'Lagi ngerangkum idemu…',
        'ai.mentor' => 'Minskul lagi mikir nih…',
        'ai.feedback' => 'Lagi nge-review jawabanmu…',
        'ai.feedback_submit' => 'Lagi nilai jawabanmu…',
        'ai.feedback_retry' => 'Lagi nyoba menghubungi AI lagi…',

        // Auth
        'auth.submit' => 'Bentar ya, lagi diproses…',
    ],

    /*
    |--------------------------------------------------------------------------
    | Supporting detail lines
    |--------------------------------------------------------------------------
    |
    | Optional second lines that reassure the user without over-promising.
    | Only rendered by the loading components when the caller asks for it.
    |
    */
    'detail' => [
        'ai.brainstorm' => 'Biasanya cuma butuh beberapa detik. Jawaban praktikmu tetap aman.',
        'ai.mentor' => 'Jawabanmu tetap aman selama Minskul menyiapkan balasan.',
        'ai.feedback' => 'Jawabanmu sudah tersimpan. Feedback muncul begitu AI selesai menilai.',
        'ai.feedback_retry' => 'Proses ini tidak mengubah jawaban yang sudah kamu kirim.',
    ],
];
