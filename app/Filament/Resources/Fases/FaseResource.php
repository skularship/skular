<?php

namespace App\Filament\Resources\Fases;

use App\Enums\ContentStatus;
use App\Filament\Resources\Fases\Pages\CreateFase;
use App\Filament\Resources\Fases\Pages\EditFase;
use App\Filament\Resources\Fases\Pages\ListFases;
use App\Filament\Resources\Fases\Schemas\FaseForm;
use App\Filament\Resources\Fases\Tables\FasesTable;
use App\Models\Fase;
use App\Models\Module;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FaseResource extends Resource
{
    protected static ?string $model = Fase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Kurikulum';

    protected static ?string $navigationLabel = 'Fase pembelajaran';

    protected static ?string $modelLabel = 'fase';

    protected static ?string $pluralModelLabel = 'Fase pembelajaran';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationBadge(): ?string
    {
        $drafts = Module::whereHas('fase', fn ($query) => $query->where('content_status', ContentStatus::Draft->value))
            ->count();

        return $drafts === 0 ? null : (string) $drafts;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Modul berstatus draf di dalam fase ini, belum terlihat oleh mentee';
    }

    public static function form(Schema $schema): Schema
    {
        return FaseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FasesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFases::route('/'),
            'create' => CreateFase::route('/create'),
            'edit' => EditFase::route('/{record}/edit'),
        ];
    }
}
