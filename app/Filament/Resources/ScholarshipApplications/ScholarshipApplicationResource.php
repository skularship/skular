<?php

namespace App\Filament\Resources\ScholarshipApplications;

use App\Filament\Resources\ScholarshipApplications\Pages\CreateScholarshipApplication;
use App\Filament\Resources\ScholarshipApplications\Pages\EditScholarshipApplication;
use App\Filament\Resources\ScholarshipApplications\Pages\ListScholarshipApplications;
use App\Filament\Resources\ScholarshipApplications\Schemas\ScholarshipApplicationForm;
use App\Filament\Resources\ScholarshipApplications\Tables\ScholarshipApplicationsTable;
use App\Models\ScholarshipApplication;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for ScholarshipApplication ("Pendaftaran mentee") at /admin/scholarship-applications.
 *
 * Scholarships selected by mentees (max 5 each) and their tracker status.
 * Access is governed by the matching Shield policy in app/Policies.
 */
class ScholarshipApplicationResource extends Resource
{
    protected static ?string $model = ScholarshipApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Pendaftaran Beasiswa';

    protected static ?string $navigationLabel = 'Pendaftaran mentee';

    protected static ?string $modelLabel = 'pendaftaran';

    protected static ?string $pluralModelLabel = 'Pendaftaran mentee';

    protected static ?int $navigationSort = 1;

    /**
     * Build the create/edit form.
     *
     * @param  Schema  $schema  The schema provided by Filament.
     * @return Schema The configured form schema.
     */
    public static function form(Schema $schema): Schema
    {
        return ScholarshipApplicationForm::configure($schema);
    }

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return ScholarshipApplicationsTable::configure($table);
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
            'index' => ListScholarshipApplications::route('/'),
            'create' => CreateScholarshipApplication::route('/create'),
            'edit' => EditScholarshipApplication::route('/{record}/edit'),
        ];
    }
}
