<?php

use App\Ai\Agents\ModuleBrainstormingAgent;
use App\Ai\Agents\ModuleMentorAgent;
use App\Ai\Agents\PracticeFeedbackAgent;
use App\Contracts\AiFeedbackServiceInterface;
use App\Contracts\ModuleBrainstormingServiceInterface;
use App\Contracts\ModuleMentorServiceInterface;
use App\Exceptions\AiBrainstormingException;
use App\Exceptions\AiFeedbackException;
use App\Exceptions\AiMentorException;
use App\Livewire\Learning\Brainstorming;
use App\Livewire\Learning\MentorChat;
use App\Models\Fase;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->fase = Fase::factory()->create(['fase_order' => 1]);
    $this->module = Module::factory()->create([
        'fase_id' => $this->fase->id,
        'modul_order' => 1,
        'title' => 'Modul Konfigurasi AI',
    ]);
    $this->module->update([
        'brainstorming_questions' => aiConfigurationBrainstormingQuestions(),
    ]);
    $this->form = PracticeForm::factory()->create([
        'module_id' => $this->module->id,
        'fields' => [
            ['name' => 'jawaban', 'label' => 'Jawaban praktik', 'type' => 'textarea', 'required' => true],
        ],
    ]);
    $this->mentee = User::factory()->profiled()->create();
});

/**
 * Get the provider configured for AI requests.
 */
function aiConfigurationProvider(): string
{
    return (string) config('ai.default');
}

/**
 * Get the text model configured for the default provider.
 */
function aiConfigurationModel(): string
{
    return (string) config('ai.providers.'.aiConfigurationProvider().'.models.text.default');
}

/**
 * Build the admin-managed brainstorming configuration used by these tests.
 *
 * @return array<int, array<string, mixed>>
 */
function aiConfigurationBrainstormingQuestions(): array
{
    return [
        [
            'id' => 'q_role',
            'question' => 'Apa peranmu?',
            'type' => 'single_choice',
            'required' => true,
            'active' => true,
            'hint' => null,
            'options' => [
                ['id' => 'o_chair', 'label' => 'Ketua', 'value' => 'chair', 'active' => true],
                ['id' => 'o_member', 'label' => 'Anggota', 'value' => 'member', 'active' => true],
            ],
        ],
    ];
}

/**
 * Build a structured outline payload for configuration tests.
 *
 * @return array<string, mixed>
 */
function aiConfigurationOutlinePayload(): array
{
    return [
        'title' => 'Kerangka jawaban',
        'core_idea' => 'Kamu berperan sebagai ketua.',
        'points' => ['Sebutkan peranmu.'],
        'writing_flow' => ['Buka dengan peran.'],
        'missing_information' => ['Periode jabatan.'],
        'reflection_questions' => ['Apa hasilnya?'],
        'sections' => [],
    ];
}

/**
 * Build a structured feedback payload for configuration tests.
 *
 * @return array<string, mixed>
 */
function aiConfigurationFeedbackPayload(): array
{
    return [
        'score' => 80,
        'strengths' => ['Jelas.'],
        'improvements' => ['Tambahkan detail.'],
        'feedback' => 'Jawaban sudah baik.',
        'next_steps' => ['Perkuat contoh.'],
    ];
}

test('the default AI configuration has a provider and text model', function () {
    expect(aiConfigurationProvider())->not->toBe('')
        ->and(aiConfigurationModel())->not->toBe('');
});

test('ai features follow a changed configured model', function () {
    config(['ai.providers.'.aiConfigurationProvider().'.models.text.default' => 'configured/sentinel-model']);

    $seen = [];

    ModuleBrainstormingAgent::fake(function ($prompt, $attachments, $provider, $model) use (&$seen): array {
        $seen[] = ['provider' => $provider->name(), 'model' => $model];

        return aiConfigurationOutlinePayload();
    });

    app(ModuleBrainstormingServiceInterface::class)->summary($this->module, $this->form, ['q_role' => 'o_chair']);

    expect($seen)->toHaveCount(1)
        ->and($seen[0]['model'])->toBe('configured/sentinel-model');
});

test('brainstorming uses the configured provider and model', function () {
    $seen = [];

    ModuleBrainstormingAgent::fake(function ($prompt, $attachments, $provider, $model) use (&$seen): array {
        $seen[] = ['provider' => $provider->name(), 'model' => $model];

        return aiConfigurationOutlinePayload();
    });

    app(ModuleBrainstormingServiceInterface::class)->summary($this->module, $this->form, ['q_role' => 'o_chair']);

    expect($seen)->toHaveCount(1)
        ->and($seen[0]['provider'])->toBe(aiConfigurationProvider())
        ->and($seen[0]['model'])->toBe(aiConfigurationModel());
});

test('ai mentor uses the configured provider and model', function () {
    $seen = [];

    ModuleMentorAgent::fake(function ($prompt, $attachments, $provider, $model) use (&$seen): string {
        $seen[] = ['provider' => $provider->name(), 'model' => $model];

        return 'Jawaban mentor.';
    });

    app(ModuleMentorServiceInterface::class)->reply($this->module, $this->form, 'Bantu saya.');

    expect($seen)->toHaveCount(1)
        ->and($seen[0]['provider'])->toBe(aiConfigurationProvider())
        ->and($seen[0]['model'])->toBe(aiConfigurationModel());
});

test('ai feedback uses the configured provider and model', function () {
    $seen = [];

    PracticeFeedbackAgent::fake(function ($prompt, $attachments, $provider, $model) use (&$seen): array {
        $seen[] = ['provider' => $provider->name(), 'model' => $model];

        return aiConfigurationFeedbackPayload();
    });

    app(AiFeedbackServiceInterface::class)->generate($this->module, $this->form, ['jawaban' => 'Jawaban saya.']);

    expect($seen)->toHaveCount(1)
        ->and($seen[0]['provider'])->toBe(aiConfigurationProvider())
        ->and($seen[0]['model'])->toBe(aiConfigurationModel());
});

test('the mentor retry path keeps the configured provider and model', function () {
    $seen = [];

    ModuleMentorAgent::fake(function ($prompt, $attachments, $provider, $model) use (&$seen): string {
        $seen[] = ['provider' => $provider->name(), 'model' => $model];

        if (count($seen) === 1) {
            throw new RuntimeException('sementara gagal');
        }

        return 'Jawaban setelah retry.';
    });

    Livewire::actingAs($this->mentee)
        ->test(MentorChat::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->set('question', 'Bantu saya.')
        ->call('send')
        ->call('retry')
        ->assertSet('failed_question', null);

    expect($seen)->toHaveCount(2);

    foreach ($seen as $call) {
        expect($call['provider'])->toBe(aiConfigurationProvider())
            ->and($call['model'])->toBe(aiConfigurationModel());
    }
});

test('an empty brainstorming model configuration fails clearly without prompting', function () {
    config(['ai.providers.'.aiConfigurationProvider().'.models.text.default' => '']);

    ModuleBrainstormingAgent::fake([aiConfigurationOutlinePayload()]);

    expect(fn () => app(ModuleBrainstormingServiceInterface::class)->summary($this->module, $this->form, ['q_role' => 'o_chair']))
        ->toThrow(AiBrainstormingException::class, 'belum dikonfigurasi');

    ModuleBrainstormingAgent::assertNeverPrompted();
});

test('an empty mentor model configuration fails clearly without prompting', function () {
    config(['ai.providers.'.aiConfigurationProvider().'.models.text.default' => '']);

    ModuleMentorAgent::fake(['Jawaban.']);

    expect(fn () => app(ModuleMentorServiceInterface::class)->reply($this->module, $this->form, 'Bantu saya.'))
        ->toThrow(AiMentorException::class, 'belum dikonfigurasi');

    ModuleMentorAgent::assertNeverPrompted();
});

test('an empty feedback model configuration fails clearly without prompting', function () {
    config(['ai.providers.'.aiConfigurationProvider().'.models.text.default' => '']);

    PracticeFeedbackAgent::fake([aiConfigurationFeedbackPayload()]);

    expect(fn () => app(AiFeedbackServiceInterface::class)->generate($this->module, $this->form, ['jawaban' => 'Jawaban.']))
        ->toThrow(AiFeedbackException::class, 'belum dikonfigurasi');

    PracticeFeedbackAgent::assertNeverPrompted();
});

test('a missing ai configuration ends the brainstorming loading state', function () {
    config(['ai.providers.'.aiConfigurationProvider().'.models.text.default' => '']);

    ModuleBrainstormingAgent::fake([aiConfigurationOutlinePayload()]);

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->set('answers.q_role', 'o_chair')
        ->call('finish')
        ->assertSet('state', 'active')
        ->assertSet('error', fn ($value): bool => is_string($value) && str_contains($value, 'belum dikonfigurasi'));

    ModuleBrainstormingAgent::assertNeverPrompted();
});

test('a missing provider configuration fails clearly without prompting', function () {
    config(['ai.default' => 'provider-yang-tidak-ada']);

    ModuleBrainstormingAgent::fake([aiConfigurationOutlinePayload()]);

    expect(fn () => app(ModuleBrainstormingServiceInterface::class)->summary($this->module, $this->form, ['q_role' => 'o_chair']))
        ->toThrow(AiBrainstormingException::class, 'belum dikonfigurasi');

    ModuleBrainstormingAgent::assertNeverPrompted();
});
