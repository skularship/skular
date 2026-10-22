<?php

namespace App\Filament\Resources\UserPracticeResponses\Tables;

use App\Enums\AiFeedbackStatus;
use App\Models\UserPracticeResponse;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UserPracticeResponsesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Mentee')
                    ->searchable(),
                TextColumn::make('module.title')
                    ->label('Modul')
                    ->searchable(),
                TextColumn::make('practiceForm.title')
                    ->label('Latihan')
                    ->searchable(),
                TextColumn::make('ai_score')
                    ->label('Skor AI')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ai_status')
                    ->label('Status AI')
                    ->badge()
                    ->sortable(),
                TextColumn::make('attempts')
                    ->label('Percobaan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->label('Dikirim')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->filters([
                SelectFilter::make('ai_status')
                    ->label('Status AI')
                    ->options(AiFeedbackStatus::options()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat')
                    ->modalHeading('Detail jawaban praktik')
                    ->schema(fn (UserPracticeResponse $record): array => [
                        Section::make('Konteks')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('user.name')->label('Mentee'),
                                TextEntry::make('module.title')->label('Modul'),
                                TextEntry::make('practiceForm.title')->label('Latihan'),
                                TextEntry::make('submitted_at')->label('Dikirim')->dateTime(),
                            ]),
                        Section::make('Jawaban mentee')
                            ->schema([
                                TextEntry::make('answers')
                                    ->hiddenLabel()
                                    ->formatStateUsing(fn (mixed $state): string => self::formatAnswers($state)),
                            ]),
                        Section::make('Feedback AI')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('ai_status')->label('Status')->badge(),
                                TextEntry::make('ai_score')->label('Skor'),
                                TextEntry::make('ai_error')
                                    ->label('Kesalahan')
                                    ->columnSpanFull()
                                    ->visible(fn (UserPracticeResponse $record): bool => filled($record->ai_error)),
                                TextEntry::make('ai_feedback')
                                    ->hiddenLabel()
                                    ->columnSpanFull()
                                    ->formatStateUsing(fn (mixed $state): string => self::formatFeedback($state)),
                            ]),
                    ]),
            ]);
    }

    /**
     * Convert the stored dynamic answers into a readable list.
     */
    public static function formatAnswers(mixed $answers): string
    {
        if (! is_array($answers) || $answers === []) {
            return '—';
        }

        $lines = [];

        foreach ($answers as $name => $value) {
            $lines[] = $name.': '.self::stringify($value);
        }

        return implode("\n", $lines);
    }

    /**
     * Convert the structured AI feedback payload into readable text.
     */
    public static function formatFeedback(mixed $feedback): string
    {
        if (! is_array($feedback) || $feedback === []) {
            return '—';
        }

        $labels = [
            'feedback' => 'Ringkasan',
            'strengths' => 'Kelebihan',
            'improvements' => 'Perbaikan',
            'next_steps' => 'Langkah berikutnya',
            'score' => 'Skor',
        ];

        $lines = [];

        foreach ($feedback as $key => $value) {
            $lines[] = ($labels[$key] ?? $key).': '.self::stringify($value);
        }

        return implode("\n", $lines);
    }

    /**
     * Turn a scalar or list answer value into a single display string.
     */
    private static function stringify(mixed $value): string
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn (mixed $item): string => self::stringify($item))
                ->filter(fn (string $item): bool => $item !== '')
                ->implode(', ');
        }

        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
