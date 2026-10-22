<?php

use App\Ai\Agents\ModuleBrainstormingAgent;
use App\Contracts\ModuleBrainstormingServiceInterface;
use App\Data\BrainstormQuestionData;
use App\Enums\BrainstormQuestionType;
use App\Enums\ModuleCategory;
use App\Exceptions\AiBrainstormingException;
use App\Livewire\Learning\Brainstorming;
use App\Models\Fase;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserBrainstormingSession;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->fase = Fase::factory()->create(['fase_order' => 1]);
    $this->module = Module::factory()->create([
        'fase_id' => $this->fase->id,
        'modul_order' => 1,
        'goal' => 'Menulis pengalaman organisasi yang berkesan.',
        'brainstorming_questions' => brainstormingTestConfig(),
    ]);
    $this->form = PracticeForm::factory()->create([
        'module_id' => $this->module->id,
        'fields' => [
            ['name' => 'jawaban', 'label' => 'Ceritakan pengalaman organisasimu', 'type' => 'textarea', 'required' => true],
        ],
    ]);
    $this->mentee = User::factory()->profiled()->create();
    $this->service = app(ModuleBrainstormingServiceInterface::class);
});

/**
 * Build the admin-managed brainstorming configuration used by the tests.
 *
 * @return array<int, array<string, mixed>>
 */
function brainstormingTestConfig(): array
{
    return [
        [
            'id' => 'q_role',
            'question' => 'Apa peran utamamu?',
            'type' => 'single_choice',
            'required' => true,
            'active' => true,
            'hint' => 'Pilih peran yang paling sering kamu jalankan.',
            'options' => [
                ['id' => 'o_chair', 'label' => 'Ketua', 'value' => 'chair', 'active' => true],
                ['id' => 'o_member', 'label' => 'Anggota', 'value' => 'member', 'active' => true],
            ],
        ],
        [
            'id' => 'q_challenges',
            'question' => 'Apa tantangan terbesarmu?',
            'type' => 'multiple_choice',
            'required' => false,
            'active' => true,
            'hint' => null,
            'options' => [
                ['id' => 'o_time', 'label' => 'Waktu', 'value' => 'time', 'active' => true],
                ['id' => 'o_conflict', 'label' => 'Konflik', 'value' => 'conflict', 'active' => true],
            ],
        ],
        [
            'id' => 'q_moment',
            'question' => 'Momen apa yang paling kamu ingat?',
            'type' => 'short_text',
            'required' => false,
            'active' => true,
            'hint' => null,
            'options' => [],
        ],
    ];
}

/**
 * Build a valid structured outline payload.
 *
 * @return array<string, mixed>
 */
function brainstormingOutlinePayload(): array
{
    return [
        'title' => 'Kerangka pengalaman organisasi',
        'core_idea' => 'Kamu memimpin tim dan menghadapi tantangan waktu.',
        'points' => [
            'Sebutkan peranmu sebagai ketua.',
            'Ceritakan cara mengatur waktu.',
        ],
        'writing_flow' => [
            'Buka dengan peran dan periode.',
            'Lanjutkan dengan tantangan dan solusinya.',
        ],
        'missing_information' => [
            'Detail jumlah anggota tim.',
        ],
        'reflection_questions' => [
            'Apa hasil nyata dari kepemimpinanmu?',
        ],
        'sections' => [
            [
                'field' => 'Ceritakan pengalaman organisasimu',
                'guidance' => 'Mulai dari peranmu, lalu jelaskan tantangannya.',
                'points' => ['Sebutkan periode dan jumlah anggota.'],
            ],
        ],
    ];
}

test('questions come from the module configuration without calling the ai', function () {
    ModuleBrainstormingAgent::fake([brainstormingOutlinePayload()]);

    $result = $this->service->questions($this->module, $this->form);

    expect($result->questions)->toHaveCount(3)
        ->and($result->questions[0])->toBeInstanceOf(BrainstormQuestionData::class)
        ->and($result->questions[0]->id)->toBe('q_role')
        ->and($result->questions[0]->type)->toBe(BrainstormQuestionType::SingleChoice)
        ->and($result->questions[0]->options[0]->id)->toBe('o_chair')
        ->and($result->questions[0]->options[0]->label)->toBe('Ketua')
        ->and($result->questions[2]->type)->toBe(BrainstormQuestionType::ShortText);

    ModuleBrainstormingAgent::assertNeverPrompted();
});

test('inactive questions and options are hidden from mentees', function () {
    $this->module->update([
        'brainstorming_questions' => [
            ...brainstormingTestConfig(),
            [
                'id' => 'q_hidden',
                'question' => 'Pertanyaan nonaktif',
                'type' => 'single_choice',
                'required' => true,
                'active' => false,
                'options' => [
                    ['id' => 'o_hidden', 'label' => 'Tidak tampil', 'value' => 'hidden', 'active' => true],
                ],
            ],
        ],
    ]);

    $result = $this->service->questions($this->module, $this->form);

    expect($result->questions)->toHaveCount(3)
        ->and(collect($result->questions)->pluck('id'))->not->toContain('q_hidden');

    ModuleBrainstormingAgent::assertNeverPrompted();
});

test('the ui renders admin questions and choosing an answer never calls the ai', function () {
    ModuleBrainstormingAgent::fake([brainstormingOutlinePayload()]);

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->assertSet('state', 'active')
        ->assertSee('Apa peran utamamu?')
        ->assertSee('Ketua')
        ->assertSee('Momen apa yang paling kamu ingat?')
        ->assertSet('answers.q_challenges', [])
        ->set('answers.q_role', 'o_chair')
        ->set('answers.q_moment', 'Saat memimpin rapat pertama.')
        ->assertSet('state', 'active');

    ModuleBrainstormingAgent::assertNeverPrompted();
});

test('finishing sends the configured context and answers to the ai', function () {
    ModuleBrainstormingAgent::fake([brainstormingOutlinePayload()]);

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->set('answers', [
            'q_role' => 'o_chair',
            'q_challenges' => ['o_time', 'o_conflict'],
            'q_moment' => 'Saat memimpin rapat pertama.',
        ])
        ->call('finish')
        ->assertSet('state', 'summary')
        ->assertSee('Kerangka pengalaman organisasi')
        ->assertSee('Kamu memimpin tim dan menghadapi tantangan waktu.')
        ->assertSee('Detail jumlah anggota tim.')
        ->assertSee('Panduan per bagian latihan')
        ->assertSee('Ceritakan pengalaman organisasimu');

    ModuleBrainstormingAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, 'Menulis pengalaman organisasi yang berkesan.')
        && str_contains($prompt->prompt, 'Latihan: '.$this->form->title)
        && str_contains($prompt->prompt, 'Ceritakan pengalaman organisasimu')
        && str_contains($prompt->prompt, 'Apa peran utamamu?')
        && str_contains($prompt->prompt, 'Ketua')
        && str_contains($prompt->prompt, 'Waktu, Konflik')
        && str_contains($prompt->prompt, 'Saat memimpin rapat pertama.'));
});

test('a template module carries its scholarship context into the outline prompt', function () {
    $scholarship = Scholarship::factory()->create([
        'scholarship_name' => 'Beasiswa Nusantara',
        'institution' => 'Yayasan Nusantara',
        'values_sought' => ['Kepemimpinan', 'Dampak sosial'],
    ]);

    $module = Module::factory()->template($scholarship)->create([
        'fase_id' => $this->fase->id,
        'modul_order' => 2,
        'title' => 'Esai Beasiswa Nusantara',
        'goal' => 'Menulis esai yang sesuai nilai beasiswa.',
        'brainstorming_questions' => brainstormingTestConfig(),
    ]);

    ModuleBrainstormingAgent::fake([brainstormingOutlinePayload()]);

    $this->service->summary($module, $this->form, [
        'q_role' => 'o_chair',
    ]);

    ModuleBrainstormingAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, 'Jenis modul: Template Beasiswa')
        && str_contains($prompt->prompt, 'Beasiswa terkait: Beasiswa Nusantara')
        && str_contains($prompt->prompt, 'Yayasan Nusantara')
        && str_contains($prompt->prompt, 'Kepemimpinan')
        && str_contains($prompt->prompt, 'Dampak sosial'));
});

test('non-template modules never receive an unrelated scholarship context', function (ModuleCategory $category) {
    Scholarship::factory()->create(['scholarship_name' => 'Beasiswa Rahasia']);

    $module = Module::factory()->create([
        'fase_id' => $this->fase->id,
        'modul_order' => 2,
        'title' => 'Modul Umum',
        'category' => $category,
        'brainstorming_questions' => brainstormingTestConfig(),
    ]);

    ModuleBrainstormingAgent::fake([brainstormingOutlinePayload()]);

    $this->service->summary($module, $this->form, ['q_role' => 'o_chair']);

    ModuleBrainstormingAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, 'Jenis modul: '.$category->getLabel())
        && ! str_contains($prompt->prompt, 'Beasiswa Rahasia')
        && ! str_contains($prompt->prompt, 'Beasiswa terkait:'));
})->with([
    'inti' => [ModuleCategory::Inti],
    'perpustakaan' => [ModuleCategory::Perpustakaan],
]);

test('required questions are validated before the ai is called', function () {
    ModuleBrainstormingAgent::fake([brainstormingOutlinePayload()]);

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->set('answers.q_moment', 'Hanya menjawab pertanyaan opsional.')
        ->call('finish')
        ->assertHasErrors('answers.q_role')
        ->assertSet('state', 'active');

    ModuleBrainstormingAgent::assertNeverPrompted();
});

test('a mentee cannot submit an option that is not configured', function () {
    ModuleBrainstormingAgent::fake([brainstormingOutlinePayload()]);

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->set('answers.q_role', 'o_forged')
        ->call('finish')
        ->assertHasErrors('answers.q_role')
        ->assertSet('state', 'active');

    ModuleBrainstormingAgent::assertNeverPrompted();
});

test('a mentee cannot submit a free-text answer that is too long', function () {
    ModuleBrainstormingAgent::fake([brainstormingOutlinePayload()]);

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->set('answers.q_role', 'o_chair')
        ->set('answers.q_moment', str_repeat('a', 501))
        ->call('finish')
        ->assertHasErrors('answers.q_moment');

    ModuleBrainstormingAgent::assertNeverPrompted();
});

test('invalid ai output is rejected without breaking the session', function () {
    ModuleBrainstormingAgent::fake(['bukan json sama sekali']);

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->set('answers.q_role', 'o_chair')
        ->call('finish')
        ->assertSet('state', 'active')
        ->assertSet('answers.q_role', 'o_chair')
        ->assertSet('error', fn ($value): bool => is_string($value) && $value !== '');
});

test('a timed out provider gets friendly retry guidance and keeps the answers', function () {
    ModuleBrainstormingAgent::fake(fn () => throw new RuntimeException('cURL error 28: Operation timed out'));

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->set('answers.q_role', 'o_chair')
        ->call('finish')
        ->assertSet('state', 'active')
        ->assertSet('answers.q_role', 'o_chair')
        ->assertSet('error', fn ($value): bool => is_string($value) && str_contains($value, 'lebih lama'));

    $this->assertDatabaseHas('user_brainstorming_sessions', [
        'user_id' => $this->mentee->id,
        'module_id' => $this->module->id,
        'practice_form_id' => $this->form->id,
    ]);

    expect(UserBrainstormingSession::query()->firstOrFail()->answers)->toMatchArray(['q_role' => 'o_chair']);
});

test('answers are persisted and restored when the mentee returns', function () {
    ModuleBrainstormingAgent::fake([brainstormingOutlinePayload()]);

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->set('answers.q_role', 'o_chair');

    $this->assertDatabaseHas('user_brainstorming_sessions', [
        'user_id' => $this->mentee->id,
        'module_id' => $this->module->id,
        'practice_form_id' => $this->form->id,
    ]);

    $stored = UserBrainstormingSession::query()->firstOrFail();

    expect($stored->answers)->toMatchArray(['q_role' => 'o_chair']);

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->assertSet('answers.q_role', 'o_chair')
        ->assertSet('answers.q_challenges', [])
        ->assertSet('state', 'active');

    ModuleBrainstormingAgent::assertNeverPrompted();
});

test('a generated outline is restored without calling the ai again', function () {
    $calls = 0;

    ModuleBrainstormingAgent::fake(function () use (&$calls): array {
        $calls++;

        return brainstormingOutlinePayload();
    });

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->set('answers.q_role', 'o_chair')
        ->call('finish')
        ->assertSet('state', 'summary');

    $stored = UserBrainstormingSession::query()->firstOrFail();

    expect($stored->outline)->toBeArray()
        ->and($stored->summary)->toContain('Kerangka pengalaman organisasi')
        ->and($calls)->toBe(1);

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->assertSet('state', 'summary')
        ->assertSet('answers.q_role', 'o_chair')
        ->assertSee('Kerangka pengalaman organisasi')
        ->assertSee('Informasi yang masih perlu kamu lengkapi');

    expect($calls)->toBe(1);
});

test('starting over removes the stored session', function () {
    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->set('answers.q_role', 'o_chair')
        ->call('restart')
        ->assertSet('answers', fn ($value): bool => is_array($value) && ! isset($value['q_role']));

    expect(UserBrainstormingSession::query()->count())->toBe(0);
});

test('another mentee never sees a stored brainstorming session', function () {
    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->set('answers.q_role', 'o_chair');

    $other = User::factory()->profiled()->create();

    Livewire::actingAs($other)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->assertSet('answers', fn ($value): bool => is_array($value) && ! isset($value['q_role']));

    expect(UserBrainstormingSession::query()->count())->toBe(1);
});

test('an obsolete outline is not restored when no stored answer is still valid', function () {
    UserBrainstormingSession::query()->create([
        'user_id' => $this->mentee->id,
        'module_id' => $this->module->id,
        'practice_form_id' => $this->form->id,
        'answers' => ['q_role' => 'o_removed'],
        'outline' => brainstormingOutlinePayload(),
        'summary' => 'Kerangka lama',
    ]);

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->assertSet('state', 'active')
        ->assertSet('outline', null)
        ->assertSet('answers', fn ($value): bool => is_array($value) && ! isset($value['q_role']));
});

test('stale stored answers that no longer match the configuration are dropped', function () {
    UserBrainstormingSession::query()->create([
        'user_id' => $this->mentee->id,
        'module_id' => $this->module->id,
        'practice_form_id' => $this->form->id,
        'answers' => [
            'q_role' => 'o_removed',
            'q_moment' => 'Momen yang masih valid.',
        ],
    ]);

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->assertSet('answers', function ($value): bool {
            return is_array($value)
                && ! isset($value['q_role'])
                && ($value['q_moment'] ?? null) === 'Momen yang masih valid.';
        });
});

test('an ai failure can be retried without retyping the answers', function () {
    $attempt = 0;

    ModuleBrainstormingAgent::fake(function () use (&$attempt): array {
        $attempt++;

        if ($attempt === 1) {
            throw new RuntimeException('provider down');
        }

        return brainstormingOutlinePayload();
    });

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->set('answers.q_role', 'o_chair')
        ->call('finish')
        ->assertSet('state', 'active')
        ->assertSet('answers.q_role', 'o_chair')
        ->call('finish')
        ->assertSet('state', 'summary')
        ->assertSee('Kerangka pengalaman organisasi');

    expect($attempt)->toBe(2);
});

test('a second request is ignored while the outline is loading', function () {
    ModuleBrainstormingAgent::fake([brainstormingOutlinePayload()]);

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->set('state', 'loading')
        ->call('finish')
        ->assertSet('state', 'loading');

    ModuleBrainstormingAgent::assertNeverPrompted();
});

test('outline requests are rate limited per mentee', function () {
    ModuleBrainstormingAgent::fake([brainstormingOutlinePayload()]);

    $key = 'ai-brainstorm:'.$this->mentee->id;
    RateLimiter::clear($key);

    for ($attempt = 0; $attempt < 8; $attempt++) {
        RateLimiter::hit($key, 60);
    }

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->set('answers.q_role', 'o_chair')
        ->call('finish')
        ->assertSet('state', 'active')
        ->assertSet('error', fn ($value): bool => is_string($value) && str_contains($value, 'Terlalu banyak'));

    ModuleBrainstormingAgent::assertNeverPrompted();
});

test('a mentee cannot brainstorm a locked module', function () {
    ModuleBrainstormingAgent::fake([brainstormingOutlinePayload()]);

    $locked = Module::factory()->create([
        'fase_id' => $this->fase->id,
        'modul_order' => 2,
        'brainstorming_questions' => brainstormingTestConfig(),
    ]);

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $locked, 'form_id' => $this->form->id])
        ->assertStatus(403);

    ModuleBrainstormingAgent::assertNeverPrompted();
});

test('a form from another module cannot be brainstormed', function () {
    ModuleBrainstormingAgent::fake([brainstormingOutlinePayload()]);

    $other_module = Module::factory()->create([
        'fase_id' => $this->fase->id,
        'modul_order' => 2,
    ]);
    $other_form = PracticeForm::factory()->create(['module_id' => $other_module->id]);

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $other_form->id])
        ->assertStatus(403);

    ModuleBrainstormingAgent::assertNeverPrompted();
});

test('the brainstorming flow never overwrites the practice answer', function () {
    ModuleBrainstormingAgent::fake([brainstormingOutlinePayload()]);

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->call('toggle')
        ->set('answers.q_role', 'o_chair')
        ->call('finish')
        ->assertSet('state', 'summary')
        ->assertSet('answers.q_role', 'o_chair');

    expect($this->mentee->practiceResponses()->count())->toBe(0);
});

test('a module without configured questions shows an empty state without calling the ai', function () {
    $module = Module::factory()->library()->create([
        'fase_id' => $this->fase->id,
        'modul_order' => 2,
        'brainstorming_questions' => null,
    ]);

    $form = PracticeForm::factory()->create(['module_id' => $module->id]);

    Livewire::actingAs($this->mentee)
        ->test(Brainstorming::class, ['module' => $module, 'form_id' => $form->id])
        ->call('toggle')
        ->assertSet('state', 'empty')
        ->assertSee('Pertanyaan pemancing belum tersedia');

    ModuleBrainstormingAgent::assertNeverPrompted();
});

test('a summary request without any usable answer is rejected', function () {
    ModuleBrainstormingAgent::fake([brainstormingOutlinePayload()]);

    expect(fn () => $this->service->summary($this->module, $this->form, []))
        ->toThrow(AiBrainstormingException::class);

    ModuleBrainstormingAgent::assertNeverPrompted();
});
