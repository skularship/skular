<?php

namespace App\Filament\Resources\TwibbonTemplates\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

/**
 * Create/edit form schema of the Template Twibbon resource.
 */
class TwibbonTemplateForm
{
    /**
     * Add the form components to the schema.
     *
     * @param  Schema  $schema  The schema to configure.
     * @return Schema The configured schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                FileUpload::make('image_path')
                    ->image()
                    ->required(),
                TextInput::make('font_path'),
                Textarea::make('placements')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('ordering')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
