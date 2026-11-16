<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\Models\Product;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Create/edit form schema of the Langganan Pro resource.
 */
class SubscriptionForm
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
                Section::make('Langganan Pro')
                    ->description('Dipakai untuk memberi Pro manual atau memperbaiki tanggal berakhir. Pembelian lewat Midtrans tetap menyambung dari tanggal ini.')
                    ->schema([
                        Select::make('user_id')
                            ->label('Mentee')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabledOn('edit'),
                        Select::make('plan')
                            ->label('Paket')
                            ->options(fn (): array => Product::query()->pluck('name', 'code')->all())
                            ->required(),
                        DateTimePicker::make('active_until')
                            ->label('Aktif sampai')
                            ->required()
                            ->seconds(false),
                    ])
                    ->columns(2),
            ]);
    }
}
