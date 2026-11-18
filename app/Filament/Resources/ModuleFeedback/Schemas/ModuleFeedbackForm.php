<?php

namespace App\Filament\Resources\ModuleFeedback\Schemas;

use App\Enums\FeedbackDifficulty;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/**
 * Create/edit form schema of the Feedback modul resource.
 */
class ModuleFeedbackForm
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
                TextInput::make('helpful_score')
                    ->label('Seberapa membantu (1-5)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5),
                Select::make('difficulty')
                    ->label('Bagian paling menyulitkan')
                    ->options(FeedbackDifficulty::options()),
                TextInput::make('open_question')
                    ->label('Pertanyaan terbuka yang ditampilkan')
                    ->columnSpanFull(),
                Textarea::make('open_answer')
                    ->label('Jawaban terbuka')
                    ->columnSpanFull(),
                Textarea::make('low_score_reason')
                    ->label('Alasan skor rendah')
                    ->columnSpanFull(),
            ]);
    }
}
