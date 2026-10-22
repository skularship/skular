<?php

namespace App\Filament\Actions;

use App\Models\Faq;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Returns a published FAQ to draft so it disappears from every public read
 * path while the team reworks the answer.
 */
class UnpublishFaqAction extends Action
{
    public static function make(?string $name = 'unpublish'): static
    {
        return parent::make($name)
            ->label('Jadikan draf')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Sembunyikan FAQ ini dari website?')
            ->modalDescription('FAQ kembali menjadi draf dan tidak muncul pada halaman FAQ maupun landing page sampai diterbitkan lagi.')
            ->modalSubmitActionLabel('Ya, jadikan draf')
            ->authorize('update')
            ->visible(fn (?Faq $record): bool => $record instanceof Faq && $record->is_published)
            ->action(function (Faq $record): void {
                $record->update(['is_published' => false]);

                Notification::make()
                    ->title('FAQ kembali menjadi draf')
                    ->body('“'.$record->question.'” disembunyikan dari halaman publik.')
                    ->success()
                    ->send();
            });
    }
}
