<?php

namespace App\Filament\Resources\Tryouts;

use App\Filament\Resources\Tryouts\Pages\CreateTryout;
use App\Filament\Resources\Tryouts\Pages\EditTryout;
use App\Filament\Resources\Tryouts\Pages\ListTryouts;
use App\Filament\Resources\Tryouts\RelationManagers\SectionsRelationManager;
use App\Filament\Resources\Tryouts\Schemas\TryoutForm;
use App\Filament\Resources\Tryouts\Tables\TryoutsTable;
use App\Models\Tryout;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for Tryout at /admin/tryouts: the Try Out package, its
 * theory layers (theory module, private theory PDF, paid material link) and
 * its subtests (SectionsRelationManager). Publishing goes through the
 * "Terbitkan" action, which checks TryoutReadinessService.
 */
class TryoutResource extends Resource
{
    protected static ?string $model = Tryout::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Try Out';

    protected static ?string $navigationLabel = 'Paket Try Out';

    protected static ?string $modelLabel = 'Try Out';

    protected static ?string $pluralModelLabel = 'Paket Try Out';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 1;

    /**
     * Build the create/edit form.
     *
     * @param  Schema  $schema  The schema provided by Filament.
     * @return Schema The configured form schema.
     */
    public static function form(Schema $schema): Schema
    {
        return TryoutForm::configure($schema);
    }

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return TryoutsTable::configure($table);
    }

    /**
     * The subtests table on the edit page.
     *
     * @return array<int, class-string>
     */
    public static function getRelations(): array
    {
        return [
            SectionsRelationManager::class,
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
            'index' => ListTryouts::route('/'),
            'create' => CreateTryout::route('/create'),
            'edit' => EditTryout::route('/{record}/edit'),
        ];
    }
}
