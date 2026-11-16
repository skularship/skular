<?php

namespace App\Console\Commands;

use App\Enums\ScholarshipStatus;
use App\Jobs\SendDeadlineReminder;
use App\Models\ScholarshipApplication;
use Illuminate\Console\Command;

/**
 * Runs daily: queues one reminder job per application whose scholarship closes in 7, 3 or 1 day.
 */
class DispatchDeadlineReminders extends Command
{
    /**
     * @var string
     */
    protected $signature = 'reminders:dispatch';

    /**
     * @var string
     */
    protected $description = 'Queue deadline reminder emails for scholarships closing soon';

    /**
     * Find applications at each offset and dispatch their jobs.
     */
    public function handle(): int
    {
        if (! config('pricing.enabled')) {
            return self::SUCCESS;
        }

        $count = 0;

        foreach ((array) config('plans.reminder_offsets') as $days) {
            $date = now()->addDays((int) $days)->toDateString();

            ScholarshipApplication::query()
                ->whereIn('status', ['selected', 'in_progress'])
                ->whereHas('scholarship', fn ($query) => $query->where('status', ScholarshipStatus::Active)->whereDate('closing_date', $date))
                ->each(function (ScholarshipApplication $application) use ($days, &$count): void {
                    SendDeadlineReminder::dispatch($application->id, (int) $days);
                    $count++;
                });
        }

        $this->info("{$count} pengingat diantrekan.");

        return self::SUCCESS;
    }
}
