<?php

namespace App\Filament\Widgets;

use App\Contracts\ActivityTrackerInterface;
use App\Filament\Resources\Users\UserResource;
use Filament\Widgets\Widget;

/**
 * "Funnel Mentee": of the mentees who registered in the last 7 or 30 days,
 * how many reached each step (catalog, match button, profiling, selection,
 * first module, feedback, event) and the share that continued.
 */
class MenteeFunnel extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    /**
     * @var view-string
     */
    protected string $view = 'filament.widgets.mentee-funnel';

    /**
     * Registration window in days (7 or 30), chosen in the widget header.
     */
    public int $days = 7;

    /**
     * Only admins who may list mentees see behaviour numbers.
     */
    public static function canView(): bool
    {
        return UserResource::canViewAny();
    }

    /**
     * Keep the window to the two offered ranges.
     */
    public function updatedDays(): void
    {
        $this->days = in_array($this->days, [7, 30], true) ? $this->days : 7;
    }

    /**
     * Pass the funnel steps to the view.
     *
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'steps' => app(ActivityTrackerInterface::class)->funnel($this->days),
        ];
    }
}
