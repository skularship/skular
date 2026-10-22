<?php

namespace App\Filament\Resources\ScholarshipApplications\Schemas;

use App\Enums\ApplicationResult;
use App\Enums\ApplicationStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ScholarshipApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('scholarship_id')
                    ->relationship('scholarship', 'scholarship_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('status')
                    ->options(ApplicationStatus::class)
                    ->default('selected')
                    ->required(),
                Toggle::make('is_primary')
                    ->required(),
                DateTimePicker::make('selected_at'),
                DateTimePicker::make('submitted_at'),
                TextInput::make('submission_proof')
                    ->label('Bukti pendaftaran')
                    ->helperText('Diunggah mentee melalui aplikasi. File disimpan privat.')
                    ->disabled()
                    ->dehydrated(false),
                Textarea::make('submission_note')
                    ->columnSpanFull(),
                DateTimePicker::make('submission_confirmed_at'),
                Select::make('result')
                    ->options(ApplicationResult::class),
                DateTimePicker::make('result_reported_at'),
                KeyValue::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
