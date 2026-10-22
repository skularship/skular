<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * A single answer returned by the AI Mentor chatbot.
 */
class MentorReplyData extends Data
{
    public function __construct(
        public string $answer,
    ) {}
}
