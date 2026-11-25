<?php

use App\Contracts\TryoutBankImportServiceInterface;
use App\Contracts\TryoutReadinessServiceInterface;
use App\Enums\AdminRole;
use App\Enums\ContentStatus;
use App\Enums\TryoutQuestionType;
use App\Enums\TryoutSkill;
use App\Filament\Resources\TryoutAttempts\Pages\ListTryoutAttempts;
use App\Filament\Resources\TryoutQuestions\Pages\CreateTryoutQuestion;
use App\Filament\Resources\TryoutQuestions\Pages\EditTryoutQuestion;
use App\Filament\Resources\TryoutQuestions\Pages\ListTryoutQuestions;
use App\Filament\Resources\Tryouts\Pages\EditTryout;
use App\Filament\Resources\Tryouts\Pages\ListTryouts;
use App\Filament\Resources\TryoutUnits\Pages\ListTryoutUnits;
use App\Models\Tryout;
use App\Models\TryoutAnswer;
use App\Models\TryoutQuestion;
use App\Models\TryoutUnit;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $role = Role::findOrCreate(AdminRole::CoAdmin->value, 'web');
    $permissions = [];

    foreach (['Tryout', 'TryoutUnit', 'TryoutQuestion', 'TryoutAttempt'] as $entity) {
        foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny', 'Replicate'] as $action) {
            $permissions[] = Permission::findOrCreate("{$action}:{$entity}", 'web')->name;
        }
    }

    $role->syncPermissions($permissions);
    $this->admin = User::factory()->create(['email_verified_at' => now()]);
    $this->admin->assignRole($role);
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('every try out admin page renders', function (string $page) {
    Tryout::factory()->withIeltsSections()->create();

    Livewire::test($page)->assertOk();
})->with([ListTryouts::class, ListTryoutUnits::class, ListTryoutQuestions::class, ListTryoutAttempts::class]);

test('a new single choice question starts with four options A to D', function () {
    $component = Livewire::test(CreateTryoutQuestion::class);

    expect($component->get('data.options'))->toHaveCount(4);
});

test('a single choice question stores lettered options and a key from those options', function () {
    $unit = TryoutUnit::factory()->create(['skill' => TryoutSkill::Reading]);

    $component = Livewire::test(CreateTryoutQuestion::class)
        ->fillForm([
            'code' => 'RB-U9-01',
            'skill' => TryoutSkill::Reading->value,
            'tryout_unit_id' => $unit->id,
            'question_type' => TryoutQuestionType::SingleChoice->value,
            'level' => 2,
            'prompt' => 'Shrimp farming is the only cause.',
            'status' => ContentStatus::Published->value,
            'options' => [['text' => 'TRUE'], ['text' => 'FALSE'], ['text' => 'NOT GIVEN']],
            'answer_key' => 'B',
        ]);

    $component
        ->call('create')
        ->assertHasNoFormErrors();

    $question = TryoutQuestion::query()->where('code', 'RB-U9-01')->firstOrFail();

    expect($question->options)->toBe(['A' => 'TRUE', 'B' => 'FALSE', 'C' => 'NOT GIVEN'])
        ->and($question->answer_key)->toBe('B');
});

test('the key must be one of the filled options and at least two options are required', function () {
    $unit = TryoutUnit::factory()->create(['skill' => TryoutSkill::Reading]);

    Livewire::test(CreateTryoutQuestion::class)
        ->fillForm([
            'code' => 'RB-U9-02',
            'skill' => TryoutSkill::Reading->value,
            'tryout_unit_id' => $unit->id,
            'question_type' => TryoutQuestionType::SingleChoice->value,
            'level' => 2,
            'prompt' => 'Question?',
            'status' => ContentStatus::Draft->value,
            'options' => [['text' => 'Only one']],
            'answer_key' => 'D',
        ])
        ->call('create')
        ->assertHasFormErrors(['options', 'answer_key']);
});

test('the code of an answered question is locked', function () {
    $question = TryoutQuestion::factory()->create();
    TryoutAnswer::factory()->create(['tryout_question_id' => $question->id]);

    Livewire::test(EditTryoutQuestion::class, ['record' => $question->getRouteKey()])
        ->assertFormFieldDisabled('code');
});

test('publishing checks the pool first', function () {
    $tryout = Tryout::factory()->draft()->withIeltsSections(question_count: 4, units: 2, per_level: 2)->create();

    // 2 per level is below the adaptive minimum of 8.
    expect(app(TryoutReadinessServiceInterface::class)->problems($tryout))->not->toBeEmpty();

    Livewire::test(EditTryout::class, ['record' => $tryout->getRouteKey()])
        ->callAction('publishTryout');

    expect($tryout->fresh()->status)->toBe(ContentStatus::Draft);

    config(['tryout.adaptive.min_pool_per_level' => 2]);

    expect(app(TryoutReadinessServiceInterface::class)->problems($tryout))->toBe([]);

    Livewire::test(EditTryout::class, ['record' => $tryout->getRouteKey()])
        ->callAction('publishTryout');

    expect($tryout->fresh()->status)->toBe(ContentStatus::Published);
});

test('an IELTS try out without the four subtests in order cannot be published', function () {
    $tryout = Tryout::factory()->draft()->create();

    expect(app(TryoutReadinessServiceInterface::class)->problems($tryout))
        ->toContain('Try Out IELTS wajib punya tepat 4 subtes berurutan: Listening, Reading, Writing, Speaking.');
});

test('the json import validates rows, upserts by code and never changes answered questions', function () {
    $payload = [
        'units' => [['code' => 'RB-U1', 'skill' => 'reading', 'title' => 'Mangrove forests', 'passage' => 'Text', 'status' => 'published']],
        'questions' => [
            ['id' => 'RB-U1-01', 'subtes' => 'reading', 'unit' => 'RB-U1', 'tipe_ui' => 'pilihan_tunggal', 'level' => 3, 'pertanyaan' => 'Q1', 'pilihan' => ['TRUE', 'FALSE', 'NOT GIVEN'], 'kunci' => 'b', 'pembahasan' => 'Karena...'],
            ['id' => 'RB-U1-02', 'subtes' => 'reading', 'unit' => 'RB-U1', 'tipe_ui' => 'teks_isian', 'level' => 2, 'pertanyaan' => 'Q2', 'kunci' => '2600', 'jawaban_alternatif' => ['two thousand six hundred']],
            ['id' => 'RB-U1-03', 'subtes' => 'reading', 'unit' => 'TIDAK-ADA', 'tipe_ui' => 'pilihan_tunggal', 'level' => 9, 'pertanyaan' => 'Q3'],
        ],
    ];

    $dry = app(TryoutBankImportServiceInterface::class)->import($payload, true);

    expect($dry->questions_created)->toBe(2)
        ->and($dry->errors)->toHaveCount(1)
        ->and(TryoutQuestion::query()->count())->toBe(0);

    app(TryoutBankImportServiceInterface::class)->import($payload);

    $choice = TryoutQuestion::query()->where('code', 'RB-U1-01')->firstOrFail();

    expect($choice->options)->toBe(['A' => 'TRUE', 'B' => 'FALSE', 'C' => 'NOT GIVEN'])
        ->and($choice->answer_key)->toBe('B')
        ->and($choice->status)->toBe(ContentStatus::Draft);

    TryoutAnswer::factory()->create(['tryout_question_id' => $choice->id]);
    $payload['questions'][0]['pertanyaan'] = 'Changed';

    $report = app(TryoutBankImportServiceInterface::class)->import($payload);

    expect($choice->fresh()->prompt)->toBe('Q1')
        ->and(collect($report->errors)->implode(' '))->toContain('sudah dijawab mentee');
});
