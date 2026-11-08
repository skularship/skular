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
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for Fase ("Fase pembelajaran") at /admin/fases.
 *
 * Learning phases that group modules; their order drives the roadmap. Bump ContentCache on save/delete (handled by the model).
 * Access is governed by the matching Shield policy in app/Policies.
 */
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

    /**
     * Get the navigation badge (record count needing attention).
     *
     * @return string|null Badge text, or null to hide it.
     */
    public static function getNavigationBadge(): ?string
    {
        // content_status lives on modules, not fases. Qualifying it keeps the
        // count correct even if fases ever gains a column of the same name
        // (the old unqualified filter only worked as an outer-column reference).
        $drafts = Module::query()
            ->where('modules.content_status', ContentStatus::Draft->value)
            ->whereHas('fase')
            ->count();

        return $drafts === 0 ? null : (string) $drafts;
    }

    /**
     * Get the navigation badge color.
     *
     * @return string|null Filament color name.
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    /**
     * Get the navigation badge tooltip.
     *
     * @return string|null Tooltip text.
     */
    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Modul berstatus draf di dalam fase ini, belum terlihat oleh mentee';
    }

    /**
     * Build the create/edit form.
     *
     * @param  Schema  $schema  The schema provided by Filament.
     * @return Schema The configured form schema.
     */
    public static function form(Schema $schema): Schema
    {
        return FaseForm::configure($schema);
    }

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return FasesTable::configure($table);
    }

    /**
     * Get the relation managers shown on the edit page.
     *
     * @return array<int, class-string>
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Get the pages (routes) of this resource.
     *
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListFases::route('/'),
            'create' => CreateFase::route('/create'),
            'edit' => EditFase::route('/{record}/edit'),
        ];
    }
}
