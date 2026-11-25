<?php

namespace App\Exceptions;

use App\Enums\AiFailureReason;
use RuntimeException;
use Throwable;

/**
 * Thrown when a Try Out AI step (Writing assessment, transcription, Speaking
 * assessment) fails. Carries the classified reason; the message is friendly
 * Indonesian and always says the answer is safe.
 */
class TryoutAiException extends RuntimeException
{
    /**
     * @param  string  $message  Friendly Indonesian message.
     * @param  AiFailureReason|null  $reason  Classified provider failure.
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly ?AiFailureReason $reason = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Friendly message for a classified failure.
     */
    public static function messageFor(AiFailureReason $reason): string
    {
        return match ($reason) {
            AiFailureReason::Configuration => 'Layanan AI belum dikonfigurasi dengan lengkap. Jawabanmu tetap tersimpan, silakan hubungi admin ya.',
            AiFailureReason::RateLimited => 'Penilaian AI sedang ramai. Jawabanmu tetap tersimpan, coba nilai ulang sekitar satu menit lagi ya.',
            AiFailureReason::Timeout => 'Penilaian AI butuh waktu lebih lama dari biasanya. Jawabanmu tetap tersimpan, coba nilai ulang ya.',
            AiFailureReason::Unavailable => 'Layanan AI tidak dapat dihubungi. Jawabanmu tetap tersimpan, coba nilai ulang beberapa saat lagi.',
            AiFailureReason::InvalidResponse => 'AI belum memberi penilaian yang lengkap. Jawabanmu tetap tersimpan, coba nilai ulang ya.',
        };
    }
}
