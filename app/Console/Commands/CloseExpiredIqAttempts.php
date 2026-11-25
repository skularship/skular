<?php

namespace App\Console\Commands;

use App\Contracts\IqAttemptServiceInterface;
use App\Enums\IqAttemptStatus;
use App\Models\IqAttempt;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Closes and scores Tes IQ attempts whose server deadline passed while the
 * mentee was away (tab closed), so they count as "Waktu habis" with the
 * answers saved in time. Pages close them on access too; this keeps the
 * data right for mentees who never come back.
 */
#[Signature('iq:close-expired')]
#[Description('Tutup percobaan Tes IQ yang waktunya sudah habis')]
class CloseExpiredIqAttempts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(IqAttemptServiceInterface $attempts): int
    {
        $closed = 0;
        $grace = (int) config('iq.answer_grace_seconds', 5);

        IqAttempt::query()
            ->where('status', IqAttemptStatus::InProgress->value)
            ->where('deadline_at', '<', now()->subSeconds($grace))
            ->orderBy('id')
            ->each(function (IqAttempt $attempt) use ($attempts, &$closed): void {
                if ($attempts->closeIfExpired($attempt)) {
                    $closed++;
                }
            });

        $this->info("Ditutup: {$closed} percobaan.");

        return self::SUCCESS;
    }
}
