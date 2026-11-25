<?php

namespace App\Filament\Resources\TryoutUnits\RelationManagers;

use App\Filament\Resources\TryoutQuestions\Schemas\TryoutQuestionForm;
use App\Filament\Resources\TryoutQuestions\Tables\TryoutQuestionsTable;
use App\Models\TryoutUnit;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * Questions of a unit, edited with the same form as the Bank Soal. The skill
 * and exam type come from the unit.
 */
class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Soal';

    protected static ?string $modelLabel = 'soal';

    protected static ?string $pluralModelLabel = 'Soal';

    /**
     * Build the question form; the skill is preset from the unit by the create action.
     *
     * @param  Schema  $schema  The schema provided by Filament.
     * @return Schema The configured schema.
     */
    public function form(Schema $schema): Schema
    {
        return TryoutQuestionForm::configure($schema, in_unit: true);
    }

    /**
     * Build the question table with a create action that copies the unit's skill.
     *
     * @param  Table  $table  The table provided by Filament.
     * @return Table The configured table.
     */
    public function table(Table $table): Table
    {
        /** @var TryoutUnit $unit */
        $unit = $this->getOwnerRecord();

        return TryoutQuestionsTable::configure($table)
            ->filters([])
            ->headerActions([
                CreateAction::make()
                    ->fillForm(['skill' => $unit->skill->value])
                    ->mutateDataUsing(fn (array $data): array => [
                        ...$data,
                        'skill' => $unit->skill->value,
                        'exam_type' => $unit->exam_type->value,
                    ]),
            ]);
    }
}
