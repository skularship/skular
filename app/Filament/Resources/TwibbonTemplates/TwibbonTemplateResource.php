<?php

namespace App\Filament\Resources\TwibbonTemplates;

use App\Filament\Resources\TwibbonTemplates\Pages\CreateTwibbonTemplate;
use App\Filament\Resources\TwibbonTemplates\Pages\EditTwibbonTemplate;
use App\Filament\Resources\TwibbonTemplates\Pages\ListTwibbonTemplates;
use App\Filament\Resources\TwibbonTemplates\Schemas\TwibbonTemplateForm;
use App\Filament\Resources\TwibbonTemplates\Tables\TwibbonTemplatesTable;
use App\Models\TwibbonTemplate;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for TwibbonTemplate ("Template Twibbon") at /admin/twibbon-templates.
 *
 * Twibbon image templates and text placements used by the twibbon generator.
 * Access is governed by the matching Shield policy in app/Policies.
 */
class TwibbonTemplateResource extends Resource
{
    protected static ?string $model = TwibbonTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Administrasi Sistem';

    protected static ?string $navigationLabel = 'Twibbon';

    protected static ?string $modelLabel = 'template twibbon';

    protected static ?string $pluralModelLabel = 'Template Twibbon';

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
        return TwibbonTemplateForm::configure($schema);
    }

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return TwibbonTemplatesTable::configure($table);
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
            'index' => ListTwibbonTemplates::route('/'),
            'create' => CreateTwibbonTemplate::route('/create'),
            'edit' => EditTwibbonTemplate::route('/{record}/edit'),
        ];
    }
}
