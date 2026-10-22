<?php

namespace App\Filament\Actions;

use App\Enums\ContentStatus;
use App\Models\Module;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Returns a published module to draft so it is hidden from mentees again
 * while the curriculum team reworks it.
 */
class UnpublishModuleAction extends Action
{
    public static function make(?string $name = 'unpublish'): static
    {
        return parent::make($name)
            ->label('Jadikan draf')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Sembunyikan modul ini dari mentee?')
            ->modalDescription('Modul kembali menjadi draf. Progres dan jawaban mentee yang sudah ada tetap tersimpan.')
            ->modalSubmitActionLabel('Ya, jadikan draf')
            ->authorize('update')
            ->visible(fn (?Module $record): bool => $record?->isPublished() ?? false)
            ->action(function (Module $record): void {
                $record->update(['content_status' => ContentStatus::Draft]);

                Notification::make()
                    ->title('Modul kembali menjadi draf')
                    ->body('“'.$record->title.'” disembunyikan dari mentee sampai diterbitkan lagi.')
                    ->success()
                    ->send();
            });
    }
}
