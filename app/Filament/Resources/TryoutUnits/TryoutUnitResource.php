<?php

namespace App\Filament\Resources\TryoutUnits;

use App\Filament\Resources\TryoutUnits\Pages\CreateTryoutUnit;
use App\Filament\Resources\TryoutUnits\Pages\EditTryoutUnit;
use App\Filament\Resources\TryoutUnits\Pages\ListTryoutUnits;
use App\Filament\Resources\TryoutUnits\RelationManagers\QuestionsRelationManager;
use App\Filament\Resources\TryoutUnits\Schemas\TryoutUnitForm;
use App\Filament\Resources\TryoutUnits\Tables\TryoutUnitsTable;
use App\Models\TryoutUnit;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for TryoutUnit at /admin/tryout-units ("Unit Teks & Audio"):
 * Reading passages, Listening audio (mp3 on the public disk) with transcript,
 * map images and tables, Writing/Speaking prompt groups, and their questions.
 */
class TryoutUnitResource extends Resource
{
    protected static ?string $model = TryoutUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSpeakerWave;

    protected static string|UnitEnum|null $navigationGroup = 'Try Out';

    protected static ?string $navigationLabel = 'Unit Teks & Audio';

    protected static ?string $modelLabel = 'unit';

    protected static ?string $pluralModelLabel = 'Unit Teks & Audio';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 2;

    /**
     * Build the create/edit form.
     *
     * @param  Schema  $schema  The schema provided by Filament.
     * @return Schema The configured form schema.
     */
    public static function form(Schema $schema): Schema
    {
        return TryoutUnitForm::configure($schema);
    }

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return TryoutUnitsTable::configure($table);
    }

    /**
     * The questions tab on the edit page.
     *
     * @return array<int, class-string>
     */
    public static function getRelations(): array
    {
        return [
            QuestionsRelationManager::class,
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
            'index' => ListTryoutUnits::route('/'),
            'create' => CreateTryoutUnit::route('/create'),
            'edit' => EditTryoutUnit::route('/{record}/edit'),
        ];
    }
}
