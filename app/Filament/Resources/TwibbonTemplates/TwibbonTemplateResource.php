<?php

namespace App\Filament\Resources\TwibbonTemplates;

use App\Filament\Resources\TwibbonTemplates\Pages\CreateTwibbonTemplate;
use App\Filament\Resources\TwibbonTemplates\Pages\EditTwibbonTemplate;
use App\Filament\Resources\TwibbonTemplates\Pages\ListTwibbonTemplates;
use App\Filament\Resources\TwibbonTemplates\Schemas\TwibbonTemplateForm;
use App\Filament\Resources\TwibbonTemplates\Tables\TwibbonTemplatesTable;
use App\Models\TwibbonTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

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

    public static function form(Schema $schema): Schema
    {
        return TwibbonTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TwibbonTemplatesTable::configure($table);
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
            'index' => ListTwibbonTemplates::route('/'),
            'create' => CreateTwibbonTemplate::route('/create'),
            'edit' => EditTwibbonTemplate::route('/{record}/edit'),
        ];
    }
}
