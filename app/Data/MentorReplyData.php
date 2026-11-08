<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * A single answer returned by the AI Mentor chatbot.
 */
class MentorReplyData extends Data
{
    /**
     * @param  string  $answer  Trimmed, non-empty answer text from the AI Mentor.
     */
    public function __construct(
        public string $answer,
    ) {}
}
