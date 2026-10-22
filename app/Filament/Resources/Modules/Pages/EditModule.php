<?php

namespace App\Filament\Resources\Modules\Pages;

use App\Filament\Actions\PublishModuleAction;
use App\Filament\Actions\UnpublishModuleAction;
use App\Filament\Resources\Modules\ModuleResource;
use App\Services\BrainstormingConfigService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditModule extends EditRecord
{
    protected static string $resource = ModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Pratinjau')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn (): string => ModuleResource::getUrl('preview', ['record' => $this->getRecord()]))
                ->openUrlInNewTab(),
            PublishModuleAction::make(),
            UnpublishModuleAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * Normalise the brainstorming configuration before it is stored.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('brainstorming_questions', $data)) {
            $data['brainstorming_questions'] = app(BrainstormingConfigService::class)
                ->normaliseForStorage($data['brainstorming_questions']);
        }

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Perubahan modul tersimpan';
    }
}
