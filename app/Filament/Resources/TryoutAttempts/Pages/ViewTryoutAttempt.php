<?php

namespace App\Filament\Resources\TryoutAttempts\Pages;

use App\Contracts\SpeakingAssessmentServiceInterface;
use App\Contracts\TryoutAttemptServiceInterface;
use App\Contracts\WritingAssessmentServiceInterface;
use App\Enums\AiFeedbackStatus;
use App\Enums\TryoutAttemptStatus;
use App\Enums\TryoutSkill;
use App\Filament\Resources\TryoutAttempts\TryoutAttemptResource;
use App\Models\TryoutAttempt;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

/**
 * Detail page of a Try Out attempt with "Nilai ulang Writing/Speaking" (one
 * AI step per click, within the request budget) and "Tutup attempt macet".
 */
class ViewTryoutAttempt extends ViewRecord
{
    protected static string $resource = TryoutAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rerunWriting')
                ->label('Nilai ulang Writing')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->authorize('update')
                ->visible(fn (TryoutAttempt $record): bool => $record->answers()
                    ->whereHas('attemptSection', fn ($query) => $query->where('skill', TryoutSkill::Writing->value))
                    ->whereIn('tryout_answers.ai_status', [AiFeedbackStatus::Failed->value, AiFeedbackStatus::Pending->value, AiFeedbackStatus::Processing->value])
                    ->exists())
                ->action(function (TryoutAttempt $record): void {
                    $answer = $record->answers()
                        ->whereHas('attemptSection', fn ($query) => $query->where('skill', TryoutSkill::Writing->value))
                        ->where('tryout_answers.ai_status', '!=', AiFeedbackStatus::Completed->value)
                        ->first();

                    if ($answer !== null) {
                        app(WritingAssessmentServiceInterface::class)->assess($answer);
                    }

                    Notification::make()->title('Satu task Writing sudah diproses. Klik lagi bila masih ada task lain.')->send();
                }),
            Action::make('rerunSpeaking')
                ->label('Nilai ulang Speaking')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->authorize('update')
                ->visible(fn (TryoutAttempt $record): bool => in_array($record->section(TryoutSkill::Speaking)?->ai_status, [AiFeedbackStatus::Failed, AiFeedbackStatus::Pending, AiFeedbackStatus::Processing], true))
                ->action(function (TryoutAttempt $record): void {
                    $section = $record->section(TryoutSkill::Speaking);
                    $service = app(SpeakingAssessmentServiceInterface::class);

                    if ($section !== null && ! $service->nextStep($section)) {
                        $service->retry($section);
                    }

                    Notification::make()->title('Satu langkah Speaking sudah diproses.')->send();
                }),
            Action::make('closeStuck')
                ->label('Tutup attempt macet')
                ->icon('heroicon-o-stop-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Subtes yang masih berjalan ditutup dan dinilai dari jawaban yang sudah tersimpan. Jawaban mentee tidak dihapus.')
                ->authorize('update')
                ->visible(fn (TryoutAttempt $record): bool => $record->status === TryoutAttemptStatus::InProgress)
                ->action(function (TryoutAttempt $record): void {
                    $attempts = app(TryoutAttemptServiceInterface::class);

                    foreach ($record->sections()->get() as $section) {
                        $attempts->submitSection($section);
                    }

                    $attempts->complete($record->fresh() ?? $record);

                    Notification::make()->title('Attempt ditutup')->success()->send();
                }),
        ];
    }
}
