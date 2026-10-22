<?php

namespace App\Filament\Resources\ModuleFeedback;

use App\Filament\Resources\ModuleFeedback\Pages\CreateModuleFeedback;
use App\Filament\Resources\ModuleFeedback\Pages\EditModuleFeedback;
use App\Filament\Resources\ModuleFeedback\Pages\ListModuleFeedback;
use App\Filament\Resources\ModuleFeedback\Schemas\ModuleFeedbackForm;
use App\Filament\Resources\ModuleFeedback\Tables\ModuleFeedbackTable;
use App\Models\ModuleFeedback;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ModuleFeedbackResource extends Resource
{
    protected static ?string $model = ModuleFeedback::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Pemantauan Mentee';

    protected static ?string $navigationLabel = 'Feedback modul';

    protected static ?string $modelLabel = 'feedback modul';

    protected static ?string $pluralModelLabel = 'Feedback modul';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return ModuleFeedbackForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ModuleFeedbackTable::configure($table);
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
            'index' => ListModuleFeedback::route('/'),
            'create' => CreateModuleFeedback::route('/create'),
            'edit' => EditModuleFeedback::route('/{record}/edit'),
        ];
    }
}
