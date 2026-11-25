<?php

namespace App\Filament\Resources\IqTests;

use App\Filament\Resources\IqTests\Pages\CreateIqTest;
use App\Filament\Resources\IqTests\Pages\EditIqTest;
use App\Filament\Resources\IqTests\Pages\ListIqTests;
use App\Filament\Resources\IqTests\Schemas\IqTestForm;
use App\Filament\Resources\IqTests\Tables\IqTestsTable;
use App\Models\IqTest;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for IqTest at /admin/iq-tests ("Tes IQ"): title,
 * duration, status, material slides (PDF) and the mentor notes. The newest
 * published test is the one mentees take.
 */
class IqTestResource extends Resource
{
    protected static ?string $model = IqTest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static string|UnitEnum|null $navigationGroup = 'Tes IQ';

    protected static ?string $navigationLabel = 'Tes IQ';

    protected static ?string $modelLabel = 'tes IQ';

    protected static ?string $pluralModelLabel = 'Tes IQ';

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
        return IqTestForm::configure($schema);
    }

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return IqTestsTable::configure($table);
    }

    /**
     * Get the pages (routes) of this resource.
     *
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListIqTests::route('/'),
            'create' => CreateIqTest::route('/create'),
            'edit' => EditIqTest::route('/{record}/edit'),
        ];
    }
}
