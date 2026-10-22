<?php

namespace App\Filament\Resources\Checkpoints;

use App\Filament\Resources\Checkpoints\Pages\CreateCheckpoint;
use App\Filament\Resources\Checkpoints\Pages\EditCheckpoint;
use App\Filament\Resources\Checkpoints\Pages\ListCheckpoints;
use App\Filament\Resources\Checkpoints\Schemas\CheckpointForm;
use App\Filament\Resources\Checkpoints\Tables\CheckpointsTable;
use App\Models\Checkpoint;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CheckpointResource extends Resource
{
    protected static ?string $model = Checkpoint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static string|UnitEnum|null $navigationGroup = 'Pemantauan Mentee';

    protected static ?string $navigationLabel = 'Checkpoint mentee';

    protected static ?string $modelLabel = 'checkpoint';

    protected static ?string $pluralModelLabel = 'Checkpoint mentee';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return CheckpointForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CheckpointsTable::configure($table);
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
            'index' => ListCheckpoints::route('/'),
            'create' => CreateCheckpoint::route('/create'),
            'edit' => EditCheckpoint::route('/{record}/edit'),
        ];
    }
}
