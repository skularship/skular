<?php

namespace App\Filament\Resources\QuickQuestions;

use App\Filament\Resources\QuickQuestions\Pages\CreateQuickQuestion;
use App\Filament\Resources\QuickQuestions\Pages\EditQuickQuestion;
use App\Filament\Resources\QuickQuestions\Pages\ListQuickQuestions;
use App\Filament\Resources\QuickQuestions\Schemas\QuickQuestionForm;
use App\Filament\Resources\QuickQuestions\Tables\QuickQuestionsTable;
use App\Models\QuickQuestion;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for QuickQuestion ("Quick Question") at /admin/quick-questions.
 *
 * True/false comprehension questions shown in the Quick Question step (not the AI Mentor suggestions).
 * Access is governed by the matching Shield policy in app/Policies.
 */
class QuickQuestionResource extends Resource
{
    protected static ?string $model = QuickQuestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Materi & Latihan';

    protected static ?string $navigationLabel = 'Quick Question';

    protected static ?string $modelLabel = 'quick question';

    protected static ?string $pluralModelLabel = 'Quick Question';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'question';

    /**
     * Build the create/edit form.
     *
     * @param  Schema  $schema  The schema provided by Filament.
     * @return Schema The configured form schema.
     */
    public static function form(Schema $schema): Schema
    {
        return QuickQuestionForm::configure($schema);
    }

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return QuickQuestionsTable::configure($table);
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
            'index' => ListQuickQuestions::route('/'),
            'create' => CreateQuickQuestion::route('/create'),
            'edit' => EditQuickQuestion::route('/{record}/edit'),
        ];
    }
}
