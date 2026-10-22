<?php

namespace App\Filament\Resources\ScholarshipApplications;

use App\Filament\Resources\ScholarshipApplications\Pages\CreateScholarshipApplication;
use App\Filament\Resources\ScholarshipApplications\Pages\EditScholarshipApplication;
use App\Filament\Resources\ScholarshipApplications\Pages\ListScholarshipApplications;
use App\Filament\Resources\ScholarshipApplications\Schemas\ScholarshipApplicationForm;
use App\Filament\Resources\ScholarshipApplications\Tables\ScholarshipApplicationsTable;
use App\Models\ScholarshipApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ScholarshipApplicationResource extends Resource
{
    protected static ?string $model = ScholarshipApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Pendaftaran Beasiswa';

    protected static ?string $navigationLabel = 'Pendaftaran mentee';

    protected static ?string $modelLabel = 'pendaftaran';

    protected static ?string $pluralModelLabel = 'Pendaftaran mentee';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return ScholarshipApplicationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScholarshipApplicationsTable::configure($table);
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
            'index' => ListScholarshipApplications::route('/'),
            'create' => CreateScholarshipApplication::route('/create'),
            'edit' => EditScholarshipApplication::route('/{record}/edit'),
        ];
    }
}
