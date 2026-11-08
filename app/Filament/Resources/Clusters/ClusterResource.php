<?php

namespace App\Filament\Resources\Clusters;

use App\Filament\Resources\Clusters\Pages\CreateCluster;
use App\Filament\Resources\Clusters\Pages\EditCluster;
use App\Filament\Resources\Clusters\Pages\ListClusters;
use App\Filament\Resources\Clusters\Schemas\ClusterForm;
use App\Filament\Resources\Clusters\Tables\ClustersTable;
use App\Models\Cluster;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for Cluster ("Cluster bidang") at /admin/clusters.
 *
 * Fields of study offered in profiling; a mentee picks one and scholarships are matched against it.
 * Access is governed by the matching Shield policy in app/Policies.
 */
class ClusterResource extends Resource
{
    protected static ?string $model = Cluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Beasiswa';

    protected static ?string $navigationLabel = 'Cluster bidang';

    protected static ?string $modelLabel = 'cluster';

    protected static ?string $pluralModelLabel = 'Cluster bidang';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Build the create/edit form.
     *
     * @param  Schema  $schema  The schema provided by Filament.
     * @return Schema The configured form schema.
     */
    public static function form(Schema $schema): Schema
    {
        return ClusterForm::configure($schema);
    }

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return ClustersTable::configure($table);
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
            'index' => ListClusters::route('/'),
            'create' => CreateCluster::route('/create'),
            'edit' => EditCluster::route('/{record}/edit'),
        ];
    }
}
