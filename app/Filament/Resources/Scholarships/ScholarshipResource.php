<?php

namespace App\Filament\Resources\Scholarships;

use App\Filament\Resources\Scholarships\Pages\CreateScholarship;
use App\Filament\Resources\Scholarships\Pages\EditScholarship;
use App\Filament\Resources\Scholarships\Pages\ListScholarships;
use App\Filament\Resources\Scholarships\Schemas\ScholarshipForm;
use App\Filament\Resources\Scholarships\Tables\ScholarshipsTable;
use App\Models\Scholarship;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for Scholarship ("Beasiswa") at /admin/scholarships.
 *
 * Scholarship catalogue used for matching. Names must stay descriptive with a link to the official source.
 * Access is governed by the matching Shield policy in app/Policies.
 */
class ScholarshipResource extends Resource
{
    protected static ?string $model = Scholarship::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Beasiswa';

    protected static ?string $navigationLabel = 'Beasiswa';

    protected static ?string $modelLabel = 'beasiswa';

    protected static ?string $pluralModelLabel = 'Beasiswa';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'scholarship_name';

    /**
     * Build the create/edit form.
     *
     * @param  Schema  $schema  The schema provided by Filament.
     * @return Schema The configured form schema.
     */
    public static function form(Schema $schema): Schema
    {
        return ScholarshipForm::configure($schema);
    }

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return ScholarshipsTable::configure($table);
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
            'index' => ListScholarships::route('/'),
            'create' => CreateScholarship::route('/create'),
            'edit' => EditScholarship::route('/{record}/edit'),
        ];
    }
}
