<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Enums\TryoutSkill;
use App\Filament\Resources\TryoutQuestions\TryoutQuestionResource;
use App\Models\TryoutQuestion;
use Filament\Widgets\Widget;

/**
 * Pool health of the Try Out bank (US13): published questions per skill and
 * level; a cell turns red below tryout.adaptive.min_pool_per_level.
 */
class TryoutPoolHealth extends Widget
{
    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.tryout-pool-health';

    /**
     * Show the widget only to admins who may list the question bank.
     */
    public static function canView(): bool
    {
        return TryoutQuestionResource::canViewAny();
    }

    /**
     * Published question counts: skill => [level => count], and the minimum.
     *
     * @return array{matrix: array<string, array<int, int>>, min: int, labels: array<string, string>}
     */
    protected function getViewData(): array
    {
        $rows = TryoutQuestion::query()
            ->where('status', ContentStatus::Published->value)
            ->selectRaw('skill, level, count(*) as aggregate')
            ->groupBy('skill', 'level')
            ->get();

        $matrix = [];
        $labels = [];

        foreach ([TryoutSkill::Listening, TryoutSkill::Reading] as $skill) {
            $labels[$skill->value] = $skill->getLabel();
            $matrix[$skill->value] = array_fill(1, 5, 0);
        }

        foreach ($rows as $row) {
            $skill = $row->getAttribute('skill');
            $skill = $skill instanceof TryoutSkill ? $skill->value : (string) $skill;

            if (isset($matrix[$skill])) {
                $matrix[$skill][(int) $row->getAttribute('level')] = (int) $row->getAttribute('aggregate');
            }
        }

        return [
            'matrix' => $matrix,
            'min' => (int) config('tryout.adaptive.min_pool_per_level', 8),
            'labels' => $labels,
        ];
    }
}
