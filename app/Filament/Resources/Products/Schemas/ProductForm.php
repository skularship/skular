<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Edit form schema of the Paket Pro resource.
 */
class ProductForm
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
                Section::make('Paket')
                    ->description('Perubahan harga berlaku untuk pembayaran berikutnya. Pesanan yang sudah dibuat tidak berubah. Cek biaya Midtrans sebelum menurunkan harga.')
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode paket')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Dipakai kode aplikasi, tidak bisa diubah.'),
                        TextInput::make('name')
                            ->label('Nama paket')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('price_normal')
                            ->label('Harga normal (Rp)')
                            ->required()
                            ->integer()
                            ->minValue(1000)
                            ->maxValue(10000000),
                        TextInput::make('price_launching')
                            ->label('Harga launching (Rp)')
                            ->integer()
                            ->minValue(1000)
                            ->lt('price_normal')
                            ->helperText('Kosongkan bila paket tidak punya harga launching. Dipakai hanya selama PRICING_LAUNCHING_UNTIL belum lewat.'),
                        TextInput::make('duration_days')
                            ->label('Lama aktif (hari)')
                            ->required()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(1100),
                        Toggle::make('is_active')
                            ->label('Dijual')
                            ->helperText('Matikan untuk menyembunyikan paket dari halaman harga dan menolak pembelian baru.'),
                    ])
                    ->columns(2),
            ]);
    }
}
