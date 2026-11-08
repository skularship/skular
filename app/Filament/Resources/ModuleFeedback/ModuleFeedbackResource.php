<?php

namespace App\Filament\Resources\ModuleFeedback;

use App\Filament\Resources\ModuleFeedback\Pages\CreateModuleFeedback;
use App\Filament\Resources\ModuleFeedback\Pages\EditModuleFeedback;
use App\Filament\Resources\ModuleFeedback\Pages\ListModuleFeedback;
use App\Filament\Resources\ModuleFeedback\Schemas\ModuleFeedbackForm;
use App\Filament\Resources\ModuleFeedback\Tables\ModuleFeedbackTable;
use App\Models\ModuleFeedback;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for ModuleFeedback ("Feedback modul") at /admin/module-feedback.
 *
 * Feedback (checklist + suggestion) mentees leave in the checkpoint step.
 * Access is governed by the matching Shield policy in app/Policies.
 */
class ModuleFeedbackResource extends Resource
{
    protected static ?string $model = ModuleFeedback::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Pemantauan Mentee';

    protected static ?string $navigationLabel = 'Feedback modul';

    protected static ?string $modelLabel = 'feedback modul';

    protected static ?string $pluralModelLabel = 'Feedback modul';

    protected static ?int $navigationSort = 3;

    /**
     * Build the create/edit form.
     *
     * @param  Schema  $schema  The schema provided by Filament.
     * @return Schema The configured form schema.
     */
    public static function form(Schema $schema): Schema
    {
        return ModuleFeedbackForm::configure($schema);
    }

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return ModuleFeedbackTable::configure($table);
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
            'index' => ListModuleFeedback::route('/'),
            'create' => CreateModuleFeedback::route('/create'),
            'edit' => EditModuleFeedback::route('/{record}/edit'),
        ];
    }
}
