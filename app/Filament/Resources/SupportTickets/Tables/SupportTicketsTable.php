<?php

namespace App\Filament\Resources\SupportTickets\Tables;

use App\Contracts\SupportTicketServiceInterface;
use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * List table of the Tiket bantuan resource: columns, filters, the ticket
 * detail view, the private screenshot download and the status actions.
 */
class SupportTicketsTable
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
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user', 'handler']))
            ->emptyStateHeading('Belum ada tiket bantuan')
            ->emptyStateDescription('Laporan bug dan permintaan bantuan dari halaman Bantuan mentee akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-lifebuoy')
            ->columns([
                TextColumn::make('subject')
                    ->label('Judul')
                    ->searchable()
                    ->limit(60)
                    ->wrap(),
                TextColumn::make('category')
                    ->label('Jenis')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Mentee')
                    ->placeholder('Akun dihapus')
                    ->searchable(),
                IconColumn::make('screenshot_path')
                    ->label('Gambar')
                    ->boolean()
                    ->getStateUsing(fn (SupportTicket $record): bool => filled($record->screenshot_path))
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
                    ->options(SupportTicketStatus::options()),
                SelectFilter::make('category')
                    ->label('Jenis')
                    ->options(SupportTicketCategory::options()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat')
                    ->modalHeading('Detail tiket bantuan')
                    ->schema([
                        Section::make('Laporan mentee')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('subject')->label('Judul')->columnSpanFull(),
                                TextEntry::make('category')->label('Jenis')->badge(),
                                TextEntry::make('status')->label('Status')->badge(),
                                TextEntry::make('description')->label('Penjelasan')->columnSpanFull(),
                                TextEntry::make('page_url')
                                    ->label('Halaman')
                                    ->url(fn (SupportTicket $record): ?string => $record->page_url, shouldOpenInNewTab: true)
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                                TextEntry::make('user_agent')->label('Browser')->placeholder('—')->columnSpanFull(),
                            ]),
                        Section::make('Penanganan')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('user.name')->label('Mentee')->placeholder('Akun dihapus'),
                                TextEntry::make('user.email')->label('Email')->placeholder('—')->copyable(),
                                TextEntry::make('created_at')->label('Dikirim')->dateTime(),
                                TextEntry::make('handler.name')->label('Ditangani oleh')->placeholder('—'),
                                TextEntry::make('resolved_at')->label('Selesai pada')->dateTime()->placeholder('—'),
                                TextEntry::make('admin_note')->label('Catatan admin')->placeholder('—')->columnSpanFull(),
                            ]),
                    ]),
                Action::make('downloadScreenshot')
                    ->label('Unduh gambar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->authorize('view')
                    ->visible(fn (SupportTicket $record): bool => filled($record->screenshot_path))
                    ->action(fn (SupportTicket $record): ?StreamedResponse => Storage::disk('local')->exists((string) $record->screenshot_path)
                        ? Storage::disk('local')->download((string) $record->screenshot_path)
                        : null),
                ActionGroup::make([
                    self::statusAction('startHandling', SupportTicketStatus::InProgress, 'Tangani', 'heroicon-o-wrench-screwdriver', 'info'),
                    self::statusAction('resolve', SupportTicketStatus::Resolved, 'Tandai selesai', 'heroicon-o-check-circle', 'success'),
                    self::statusAction('close', SupportTicketStatus::Closed, 'Tutup', 'heroicon-o-x-circle', 'gray'),
                ])->label('Ubah status')->button(),
            ]);
    }

    /**
     * Build one action that moves an open ticket to the given status.
     *
     * @param  string  $name  Action name.
     * @param  SupportTicketStatus  $status  Target status.
     * @param  string  $label  Button label.
     * @param  string  $icon  Heroicon name.
     * @param  string  $color  Filament color.
     * @return Action The configured action.
     */
    private static function statusAction(string $name, SupportTicketStatus $status, string $label, string $icon, string $color): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->authorize('update')
            ->visible(fn (SupportTicket $record): bool => $record->status->isOpen() && $record->status !== $status)
            ->modalHeading($label.' tiket')
            ->schema([
                Textarea::make('admin_note')
                    ->label('Catatan admin (internal)')
                    ->rows(3)
                    ->maxLength(SupportTicketServiceInterface::MAX_DESCRIPTION_LENGTH),
            ])
            ->action(function (SupportTicket $record, array $data) use ($status): void {
                $admin = Auth::user();

                if (! $admin instanceof User) {
                    return;
                }

                $note = $data['admin_note'] ?? null;

                app(SupportTicketServiceInterface::class)->updateStatus($record, $admin, $status, is_string($note) ? $note : null);

                Notification::make()->success()->title('Status tiket diperbarui menjadi '.$status->label())->send();
            });
    }
}
