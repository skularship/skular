<?php

namespace App\Filament\Resources\Checkpoints;

use App\Filament\Resources\Checkpoints\Pages\CreateCheckpoint;
use App\Filament\Resources\Checkpoints\Pages\EditCheckpoint;
use App\Filament\Resources\Checkpoints\Pages\ListCheckpoints;
use App\Filament\Resources\Checkpoints\Schemas\CheckpointForm;
use App\Filament\Resources\Checkpoints\Tables\CheckpointsTable;
use App\Models\Checkpoint;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for Checkpoint ("Checkpoint mentee") at /admin/checkpoints.
 *
 * Monitoring and manual adjustment of module progress rows (locked/available/in progress/completed) per mentee.
 * Access is governed by the matching Shield policy in app/Policies.
 */
class CheckpointResource extends Resource
{
    protected static ?string $model = Checkpoint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static string|UnitEnum|null $navigationGroup = 'Pemantauan Mentee';

    protected static ?string $navigationLabel = 'Checkpoint mentee';

    protected static ?string $modelLabel = 'checkpoint';

    protected static ?string $pluralModelLabel = 'Checkpoint mentee';

    protected static ?int $navigationSort = 1;

    /**
     * Build the create/edit form.
     *
     * @param  Schema  $schema  The schema provided by Filament.
     * @return Schema The configured form schema.
     */
    public static function form(Schema $schema): Schema
    {
        return CheckpointForm::configure($schema);
    }

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return CheckpointsTable::configure($table);
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
            'index' => ListCheckpoints::route('/'),
            'create' => CreateCheckpoint::route('/create'),
            'edit' => EditCheckpoint::route('/{record}/edit'),
        ];
    }
}
