<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Support\Rupiah;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * List table of the Pesanan resource (read-only).
 */
class OrdersTable
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
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user', 'product']))
            ->columns([
                TextColumn::make('order_id')->label('Order ID')->searchable()->copyable(),
                TextColumn::make('user.email')->label('Mentee')->searchable()->wrap(),
                TextColumn::make('product.name')->label('Paket'),
                TextColumn::make('amount')->label('Jumlah')->formatStateUsing(fn (int $state): string => Rupiah::format($state))->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->getLabel())
                    ->color(fn (OrderStatus $state): string => match ($state) {
                        OrderStatus::Paid => 'success',
                        OrderStatus::Pending => 'warning',
                        OrderStatus::Failed => 'danger',
                        OrderStatus::Expired => 'gray',
                    }),
                TextColumn::make('promo_code')->label('Referral')->placeholder('-')->toggleable(),
                TextColumn::make('payment_type')->label('Metode')->placeholder('-')->toggleable(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $status): array => [$status->value => $status->getLabel()])->all()),
                Filter::make('pakai_referral')
                    ->label('Pakai kode referral')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('referrer_user_id')),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
