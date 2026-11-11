<?php

namespace App\Filament\Resources\ScholarshipInfoReports\Tables;

use App\Contracts\ScholarshipReportServiceInterface;
use App\Enums\ReportedField;
use App\Enums\ReportStatus;
use App\Filament\Resources\Scholarships\ScholarshipResource;
use App\Models\ScholarshipInfoReport;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * List table of the Laporan info beasiswa resource: columns, filters, the
 * current-vs-suggested detail view and the review actions.
 */
class ScholarshipInfoReportsTable
{
    /**
     * Add columns, filters and actions to the table.
     *
     * @param  Table  $table  The table to configure.
     * @return Table The configured table.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['scholarship', 'user', 'reviewer']))
            ->emptyStateHeading('Belum ada laporan')
            ->emptyStateDescription('Laporan "Informasi Tidak Akurat?" dari mentee akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-flag')
            ->columns([
                TextColumn::make('scholarship.scholarship_name')
                    ->label('Beasiswa')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('field')
                    ->label('Bagian')
                    ->badge(),
                TextColumn::make('suggested_value')
                    ->label('Usulan mentee')
                    ->limit(60)
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Pelapor')
                    ->placeholder('Akun dihapus')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Dikirim')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ReportStatus::options())
                    ->default(ReportStatus::New->value),
                SelectFilter::make('scholarship_id')
                    ->label('Beasiswa')
                    ->relationship('scholarship', 'scholarship_name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('field')
                    ->label('Bagian')
                    ->options(ReportedField::options()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat')
                    ->modalHeading('Detail laporan')
                    ->schema([
                        Section::make('Perbandingan')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('scholarship.scholarship_name')->label('Beasiswa')->columnSpanFull(),
                                TextEntry::make('field')->label('Bagian')->badge(),
                                TextEntry::make('status')->label('Status')->badge(),
                                TextEntry::make('current_value')
                                    ->label('Nilai sekarang')
                                    ->state(fn (ScholarshipInfoReport $record): string => $record->field->currentValue($record->scholarship)),
                                TextEntry::make('suggested_value')->label('Usulan mentee'),
                                TextEntry::make('source_url')
                                    ->label('Tautan bukti')
                                    ->url(fn (ScholarshipInfoReport $record): ?string => $record->source_url, shouldOpenInNewTab: true)
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                                TextEntry::make('note')->label('Catatan mentee')->placeholder('—')->columnSpanFull(),
                            ]),
                        Section::make('Peninjauan')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('user.name')->label('Pelapor')->placeholder('Akun dihapus'),
                                TextEntry::make('created_at')->label('Dikirim')->dateTime(),
                                TextEntry::make('reviewer.name')->label('Ditinjau oleh')->placeholder('—'),
                                TextEntry::make('reviewed_at')->label('Ditinjau pada')->dateTime()->placeholder('—'),
                                TextEntry::make('admin_note')->label('Catatan admin')->placeholder('—')->columnSpanFull(),
                            ]),
                    ]),
                Action::make('editScholarship')
                    ->label('Edit beasiswa')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->url(fn (ScholarshipInfoReport $record): string => ScholarshipResource::getUrl('edit', ['record' => $record->scholarship_id]))
                    ->visible(fn (): bool => ScholarshipResource::canViewAny()),
                ActionGroup::make([
                    self::reviewAction('markInReview', ReportStatus::InReview, 'Tandai ditinjau', 'heroicon-o-eye', 'info'),
                    self::reviewAction('accept', ReportStatus::Accepted, 'Terima', 'heroicon-o-check-circle', 'success'),
                    self::reviewAction('reject', ReportStatus::Rejected, 'Tolak', 'heroicon-o-x-circle', 'danger'),
                ])->label('Tinjau')->button(),
            ]);
    }

    /**
     * Build one review action that moves an open report to the given status.
     *
     * @param  string  $name  Action name.
     * @param  ReportStatus  $status  Target status.
     * @param  string  $label  Button label.
     * @param  string  $icon  Heroicon name.
     * @param  string  $color  Filament color.
     * @return Action The configured action.
     */
    private static function reviewAction(string $name, ReportStatus $status, string $label, string $icon, string $color): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->authorize('update')
            ->visible(fn (ScholarshipInfoReport $record): bool => $record->status->isOpen() && $record->status !== $status)
            ->modalHeading($label.' laporan')
            ->modalDescription($status === ReportStatus::Accepted
                ? 'Status laporan berubah menjadi Diterima. Data beasiswa tidak berubah otomatis: perbarui lewat tombol "Edit beasiswa".'
                : null)
            ->schema([
                Textarea::make('admin_note')
                    ->label('Catatan admin (internal)')
                    ->rows(3)
                    ->maxLength(ScholarshipReportServiceInterface::MAX_TEXT_LENGTH),
            ])
            ->action(function (ScholarshipInfoReport $record, array $data) use ($status): void {
                $admin = Auth::user();

                if (! $admin instanceof User) {
                    return;
                }

                $note = $data['admin_note'] ?? null;

                app(ScholarshipReportServiceInterface::class)->review($record, $admin, $status, is_string($note) ? $note : null);

                Notification::make()->success()->title('Status laporan diperbarui menjadi '.$status->label())->send();
            });
    }
}
