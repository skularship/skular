<?php

namespace App\Filament\Resources\IqQuestions;

use App\Filament\Resources\IqQuestions\Pages\CreateIqQuestion;
use App\Filament\Resources\IqQuestions\Pages\EditIqQuestion;
use App\Filament\Resources\IqQuestions\Pages\ListIqQuestions;
use App\Filament\Resources\IqQuestions\Schemas\IqQuestionForm;
use App\Filament\Resources\IqQuestions\Tables\IqQuestionsTable;
use App\Models\IqQuestion;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament resource for IqQuestion at /admin/iq-questions ("Bank Soal IQ"):
 * add, edit, (de)activate questions, upload images for Visuospasial items,
 * and import/export the bank as JSON. A question only goes live when it has
 * a prompt, four options, exactly one key A-D and (Visuospasial) an image
 * with alt text. Answered questions are deactivated instead of deleted.
 */
class IqQuestionResource extends Resource
{
    protected static ?string $model = IqQuestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Tes IQ';

    protected static ?string $navigationLabel = 'Bank Soal IQ';

    protected static ?string $modelLabel = 'soal IQ';

    protected static ?string $pluralModelLabel = 'Bank Soal IQ';

    protected static ?int $navigationSort = 2;

    /**
     * Build the create/edit form.
     *
     * @param  Schema  $schema  The schema provided by Filament.
     * @return Schema The configured form schema.
     */
    public static function form(Schema $schema): Schema
    {
        return IqQuestionForm::configure($schema);
    }

    /**
     * Build the list table.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public static function table(Table $table): Table
    {
        return IqQuestionsTable::configure($table);
    }

    /**
     * Get the pages (routes) of this resource.
     *
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListIqQuestions::route('/'),
            'create' => CreateIqQuestion::route('/create'),
            'edit' => EditIqQuestion::route('/{record}/edit'),
        ];
    }
}
