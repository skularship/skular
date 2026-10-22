<?php

namespace App\Filament\Resources\Checkpoints\Schemas;

use App\Enums\CheckpointStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CheckpointForm
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
                Select::make('fase_id')
                    ->relationship('fase', 'title')
                    ->required(),
                Select::make('status')
                    ->options(CheckpointStatus::class)
                    ->default('available')
                    ->required(),
                TextInput::make('score')
                    ->numeric(),
                TextInput::make('attempts')
                    ->required()
                    ->numeric()
                    ->default(0),
                KeyValue::make('metadata')
                    ->columnSpanFull(),
                DateTimePicker::make('available_at'),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('completed_at'),
            ]);
    }
}
