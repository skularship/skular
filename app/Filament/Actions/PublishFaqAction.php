<?php

namespace App\Filament\Actions;

use App\Models\Faq;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Publishes a finished FAQ while leaving the rest of the list untouched.
 *
 * The model stamps `published_at` the first time this happens, so the public
 * scope and the admin list agree on when the answer went live.
 */
class PublishFaqAction extends Action
{
    public static function make(?string $name = 'publish'): static
    {
        return parent::make($name)
            ->label('Terbitkan')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Terbitkan FAQ ini?')
            ->modalDescription('Setelah diterbitkan, pertanyaan dan jawaban ini langsung dapat dibaca pengunjung website.')
            ->modalSubmitActionLabel('Terbitkan sekarang')
            ->authorize('update')
            ->visible(fn (?Faq $record): bool => $record instanceof Faq && ! $record->is_published)
            ->action(function (Faq $record): void {
                $record->update(['is_published' => true]);

                Notification::make()
                    ->title('FAQ diterbitkan')
                    ->body('“'.$record->question.'” sekarang tampil di halaman FAQ publik.')
                    ->success()
                    ->send();
            });
    }
}
