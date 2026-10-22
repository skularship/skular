<?php

namespace App\Filament\Resources\Modules\Pages;

use App\Filament\Resources\Modules\ModuleResource;
use App\Services\BrainstormingConfigService;
use Filament\Resources\Pages\CreateRecord;

class CreateModule extends CreateRecord
{
    protected static string $resource = ModuleResource::class;

    /**
     * Normalise the brainstorming configuration before it is stored.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (array_key_exists('brainstorming_questions', $data)) {
            $data['brainstorming_questions'] = app(BrainstormingConfigService::class)
                ->normaliseForStorage($data['brainstorming_questions']);
        }

        return $data;
    }

    /**
     * Create an empty draft and keep the admin on the editor, where the
     * quick questions and practice exercises are added.
     */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Modul dibuat sebagai draf';
    }

    protected function getCreatedNotificationMessage(): ?string
    {
        return 'Lanjutkan dengan kata pengantar, materi, dan latihan. Modul tampil ke mentee setelah kamu menerbitkannya.';
    }
}
