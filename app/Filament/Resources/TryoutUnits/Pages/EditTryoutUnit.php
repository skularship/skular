<?php

namespace App\Filament\Resources\TryoutUnits\Pages;

use App\Filament\Resources\TryoutUnits\TryoutUnitResource;
use App\Models\TryoutUnit;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit page of a Try Out unit with its questions tab and an audio preview.
 */
class EditTryoutUnit extends EditRecord
{
    protected static string $resource = TryoutUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('previewAudio')
                ->label('Pratinjau audio')
                ->icon('heroicon-o-play')
                ->color('gray')
                ->visible(fn (TryoutUnit $record): bool => filled($record->audio_path))
                ->url(fn (TryoutUnit $record): ?string => $record->audioUrl(), shouldOpenInNewTab: true),
            DeleteAction::make()->hidden(fn (TryoutUnit $record): bool => $record->questions()->exists() || $record->sections()->exists()),
        ];
    }
}
