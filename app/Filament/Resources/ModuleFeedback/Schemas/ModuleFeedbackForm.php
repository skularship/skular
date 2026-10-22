<?php

namespace App\Filament\Resources\ModuleFeedback\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ModuleFeedbackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('module_id')
                    ->relationship('module', 'title')
                    ->required(),
                Textarea::make('ratings')
                    ->columnSpanFull(),
                Textarea::make('suggestion')
                    ->columnSpanFull(),
            ]);
    }
}
