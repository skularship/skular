<?php

namespace App\Filament\Resources\Events\RelationManagers;

use App\Contracts\EventServiceInterface;
use App\Enums\EventRegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Participants of an event: answers to the registration questions, status,
 * feedback, the bulk "Tandai hadir" action, and a CSV export. Registrations
 * are created only by mentees (EventService::register()).
 */
class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    protected static ?string $title = 'Peserta';

    protected static ?string $modelLabel = 'peserta';

    protected static ?string $pluralModelLabel = 'Peserta';

    /**
     * Build the participants table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->defaultSort('created_at')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama')
                    ->description(fn (EventRegistration $record): string => $record->user->email)
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (EventRegistrationStatus $state): string => $state->getLabel())
                    ->badge()
                    ->color(fn (EventRegistrationStatus $state): string => match ($state) {
                        EventRegistrationStatus::Attended => 'success',
                        EventRegistrationStatus::Registered => 'info',
                        EventRegistrationStatus::Cancelled => 'gray',
                        EventRegistrationStatus::NoShow => 'warning',
                    }),
                TextColumn::make('answers')
                    ->label('Jawaban pendaftaran')
                    ->state(fn (EventRegistration $record): string => self::answersText($record))
                    ->wrap(),
                TextColumn::make('feedback_score')
                    ->label('Skor acara')
                    ->placeholder('-'),
                TextColumn::make('feedback_text')
                    ->label('Masukan')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Daftar')
                    ->dateTime('j M Y H:i', Event::DISPLAY_TIMEZONE)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(EventRegistrationStatus::options()),
            ])
            ->headerActions([
                Action::make('exportCsv')
                    ->label('Ekspor CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (): StreamedResponse => $this->exportCsv()),
            ])
            ->toolbarActions([
                BulkAction::make('markAttended')
                    ->label('Tandai hadir')
                    ->icon('heroicon-o-check-badge')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => Auth::user()?->can('Update:EventRegistration') ?? false)
                    ->action(function (Collection $records): void {
                        $registrations = $records
                            ->filter(fn (mixed $record): bool => $record instanceof EventRegistration)
                            ->load('user');

                        $changed = app(EventServiceInterface::class)->markAttended($registrations);

                        Notification::make()
                            ->title($changed.' peserta ditandai hadir.')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->emptyStateHeading('Belum ada peserta')
            ->emptyStateDescription('Mentee mendaftar sendiri dari halaman event.');
    }

    /**
     * Stream every registration of this event as a CSV file.
     */
    private function exportCsv(): StreamedResponse
    {
        /** @var Event $event */
        $event = $this->getOwnerRecord();

        return response()->streamDownload(function () use ($event): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fputcsv($out, ['nama', 'email', 'status', 'jawaban', 'skor_acara', 'masukan', 'waktu_daftar_wib']);

            $event->registrations()->with('user')->orderBy('id')->chunk(200, function ($registrations) use ($out): void {
                foreach ($registrations as $registration) {
                    /** @var EventRegistration $registration */
                    fputcsv($out, [
                        $registration->user->name,
                        $registration->user->email,
                        $registration->status->getLabel(),
                        self::answersText($registration),
                        $registration->feedback_score,
                        $registration->feedback_text,
                        $registration->created_at?->setTimezone(Event::DISPLAY_TIMEZONE)->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($out);
        }, $event->slug.'-peserta.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    /**
     * The registration answers as "question: answer" lines.
     */
    private static function answersText(EventRegistration $record): string
    {
        return collect($record->answers ?? [])
            ->map(fn (array $row): string => $row['question'].': '.$row['answer'])
            ->implode(' | ');
    }
}
