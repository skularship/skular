<?php

namespace App\Filament\Actions;

use App\Contracts\TryoutReadinessServiceInterface;
use App\Enums\ContentStatus;
use App\Models\Tryout;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

/**
 * Publishes a Try Out only when TryoutReadinessService finds no problem
 * (subtest order, pool size, 8 questions per level for adaptive pools).
 */
class PublishTryoutAction extends Action
{
    /**
     * Build the "Terbitkan" header action.
     *
     * @param  string|null  $name  Action name.
     * @return static The configured action.
     */
    public static function make(?string $name = 'publishTryout'): static
    {
        return parent::make($name)
            ->label('Terbitkan')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Terbitkan Try Out ini?')
            ->modalDescription('Pool soal dicek dulu. Setelah terbit, mentee bisa langsung mengerjakan.')
            ->authorize('update')
            ->visible(fn (?Tryout $record): bool => $record !== null && ! $record->status->isPublished())
            ->action(function (Tryout $record): void {
                $problems = app(TryoutReadinessServiceInterface::class)->problems($record);

                if ($problems !== []) {
                    Notification::make()
                        ->title('Try Out belum bisa diterbitkan')
                        ->body(new HtmlString('<ul style="list-style: disc; margin-inline-start: 1rem;">'.collect($problems)->map(fn (string $problem): string => '<li>'.e($problem).'</li>')->implode('').'</ul>'))
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                $record->update(['status' => ContentStatus::Published]);

                Notification::make()->title('Try Out diterbitkan')->success()->send();
            });
    }
}
