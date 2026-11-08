<?php

namespace App\Filament\Resources\PracticeForms;

use App\Filament\Resources\PracticeForms\Pages\CreatePracticeForm;
use App\Filament\Resources\PracticeForms\Pages\EditPracticeForm;
use App\Filament\Resources\PracticeForms\Pages\ListPracticeForms;
use App\Filament\Resources\PracticeForms\Schemas\PracticeFormForm;
use App\Filament\Resources\PracticeForms\Tables\PracticeFormsTable;
use App\Models\PracticeForm;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for PracticeForm ("Latihan praktik") at /admin/practice-forms.
 *
 * Practice forms (dynamic question builder) evaluated by AI; may feed the mentee CV.
 * Access is governed by the matching Shield policy in app/Policies.
 */
class PracticeFormResource extends Resource
{
    protected static ?string $model = PracticeForm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Materi & Latihan';

    protected static ?string $navigationLabel = 'Latihan praktik';

    protected static ?string $modelLabel = 'latihan praktik';

    protected static ?string $pluralModelLabel = 'Latihan praktik';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    /**
     * Build the create/edit form.
     *
     * @param  Schema  $schema  The schema provided by Filament.
     * @return Schema The configured form schema.
     */
    public static function form(Schema $schema): Schema
    {
        return PracticeFormForm::configure($schema);
    }

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return PracticeFormsTable::configure($table);
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
            'index' => ListPracticeForms::route('/'),
            'create' => CreatePracticeForm::route('/create'),
            'edit' => EditPracticeForm::route('/{record}/edit'),
        ];
    }
}
