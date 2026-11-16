<?php

namespace App\Filament\Resources\PaymentEvents\Tables;

use App\Jobs\ProcessMidtransNotification;
use App\Models\PaymentEvent;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * List table of the Notifikasi Midtrans resource.
 */
class PaymentEventsTable
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
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('order_id')->label('Order ID')->searchable()->placeholder('-'),
                TextColumn::make('payload.transaction_status')->label('Status Midtrans')->badge()->color('gray'),
                TextColumn::make('payload.payment_type')->label('Metode')->placeholder('-')->toggleable(),
                TextColumn::make('processed_at')
                    ->label('Diproses')
                    ->dateTime()
                    ->placeholder('Belum')
                    ->color(fn (PaymentEvent $record): string => $record->processed_at === null ? 'warning' : 'success'),
                TextColumn::make('created_at')->label('Diterima')->dateTime()->sortable(),
            ])
            ->filters([
                Filter::make('belum_diproses')
                    ->label('Belum diproses')
                    ->query(fn (Builder $query): Builder => $query->whereNull('processed_at')),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('requeue')
                    ->label('Antrekan ulang')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->visible(fn (PaymentEvent $record): bool => $record->processed_at === null && auth()->user()?->can('Update:PaymentEvent') === true)
                    ->action(function (PaymentEvent $record): void {
                        ProcessMidtransNotification::dispatch($record->id);

                        Notification::make()->title('Notifikasi diantrekan ulang')->success()->send();
                    }),
            ]);
    }
}
