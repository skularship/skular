<?php

namespace App\Filament\Actions;

use App\Contracts\ModuleReadinessServiceInterface;
use App\Data\ModuleReadinessCheckData;
use App\Enums\ContentStatus;
use App\Models\Module;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

/**
 * Publishes a module only when its content is actually complete.
 *
 * The completeness rules live in ModuleReadinessService, so the same rules
 * drive the checklist the curriculum team sees and the gate the backend
 * enforces here — the button is never the only protection.
 */
class PublishModuleAction extends Action
{
    public static function make(?string $name = 'publish'): static
    {
        return parent::make($name)
            ->label('Terbitkan')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Terbitkan modul ini?')
            ->modalDescription('Setelah diterbitkan, modul ini langsung tampil pada jalur belajar mentee.')
            ->modalSubmitActionLabel('Terbitkan sekarang')
            ->authorize('update')
            ->visible(fn (?Module $record): bool => $record?->isDraft() ?? false)
            ->action(function (Module $record): void {
                $blockers = app(ModuleReadinessServiceInterface::class)->blockers($record);

                if ($blockers !== []) {
                    Notification::make()
                        ->title('Modul belum bisa diterbitkan')
                        ->body(self::blockersMessage($blockers))
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                $record->update(['content_status' => ContentStatus::Published]);

                Notification::make()
                    ->title('Modul diterbitkan')
                    ->body('“'.$record->title.'” sekarang terlihat oleh mentee.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Explain what still has to be fixed before the module can go live.
     *
     * @param  array<int, ModuleReadinessCheckData>  $blockers
     */
    private static function blockersMessage(array $blockers): HtmlString
    {
        $items = '';

        foreach ($blockers as $blocker) {
            $items .= '<li><strong>'.e($blocker->label).'</strong><br>'.e($blocker->hint).'</li>';
        }

        return new HtmlString('<ul style="list-style: disc; margin-inline-start: 1rem;">'.$items.'</ul>');
    }
}
