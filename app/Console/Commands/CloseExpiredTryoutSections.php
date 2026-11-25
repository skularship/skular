<?php

namespace App\Console\Commands;

use App\Contracts\TryoutAttemptServiceInterface;
use App\Enums\TryoutSectionStatus;
use App\Models\TryoutAttemptSection;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Closes Try Out subtests whose server deadline (plus grace) passed while the
 * mentee was away, so scores and attempt status never wait for the next request.
 */
#[Signature('tryout:close-expired')]
#[Description('Tutup subtes Try Out yang waktunya sudah habis')]
class CloseExpiredTryoutSections extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TryoutAttemptServiceInterface $attempts): int
    {
        $grace = (int) config('tryout.answer_grace_seconds', 5);
        $closed = 0;

        TryoutAttemptSection::query()
            ->where('status', TryoutSectionStatus::InProgress->value)
            ->where('deadline_at', '<', now()->subSeconds($grace))
            ->orderBy('id')
            ->chunkById(100, function ($sections) use ($attempts, &$closed): void {
                foreach ($sections as $section) {
                    if ($attempts->closeExpired($section)) {
                        $closed++;
                    }
                }
            });

        $this->info($closed.' subtes ditutup.');

        return self::SUCCESS;
    }
}
