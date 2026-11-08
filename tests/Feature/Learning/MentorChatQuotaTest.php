<?php

use App\Ai\Agents\ModuleMentorAgent;
use App\Enums\AdminRole;
use App\Filament\Resources\Modules\Pages\EditModule;
use App\Livewire\Learning\MentorChat;
use App\Models\Fase;
use App\Models\Module;
use App\Models\ModuleMentorUsage;
use App\Models\PracticeForm;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $fase = Fase::factory()->create(['fase_order' => 1]);
    $this->module = Module::factory()->create(['fase_id' => $fase->id, 'modul_order' => 1, 'title' => 'Esai Motivasi']);
    $this->form = PracticeForm::factory()->create(['module_id' => $this->module->id, 'title' => 'Paragraf Pembuka']);
    $this->mentee = User::factory()->profiled()->create();
});

function mentorChat(Module $module, PracticeForm $form, User $mentee): Testable
{
    return Livewire::actingAs($mentee)
        ->test(MentorChat::class, ['module' => $module, 'form_id' => $form->id])
        ->set('open', true);
}

function usedQuota(User $mentee, Module $module): int
{
    return (int) ModuleMentorUsage::query()
        ->where('user_id', $mentee->id)
        ->where('module_id', $module->id)
        ->value('used');
}

test('the default limit is five questions per module', function () {
    expect(config('ai.module_chat.limit'))->toBe(5);

    mentorChat($this->module, $this->form, $this->mentee)
        ->assertSee('Sisa 5 dari 5 pertanyaan');
});

test('a successful question uses one quota that is stored in the database', function () {
    ModuleMentorAgent::fake(['Mulai dari satu momen yang paling berkesan.']);

    mentorChat($this->module, $this->form, $this->mentee)
        ->set('question', 'Bagaimana memulai esai?')
        ->call('send')
        ->assertSee('Mulai dari satu momen yang paling berkesan.')
        ->assertSee('Sisa 4 dari 5 pertanyaan');

    expect(usedQuota($this->mentee, $this->module))->toBe(1);

    // A fresh component (a page refresh) still sees the stored usage.
    mentorChat($this->module, $this->form, $this->mentee)
        ->assertSee('Sisa 4 dari 5 pertanyaan');
});

test('a failed ai call does not use the quota', function () {
    ModuleMentorAgent::fake([fn () => throw new RuntimeException('provider down')]);

    mentorChat($this->module, $this->form, $this->mentee)
        ->set('question', 'Bagaimana memulai esai?')
        ->call('send')
        ->assertSet('failed_question', 'Bagaimana memulai esai?')
        ->assertSee('Sisa 5 dari 5 pertanyaan');

    expect(usedQuota($this->mentee, $this->module))->toBe(0);
});

test('the chat is disabled once the quota is used up and nothing is sent to the model', function () {
    ModuleMentorAgent::fake()->preventStrayPrompts();

    ModuleMentorUsage::query()->create(['user_id' => $this->mentee->id, 'module_id' => $this->module->id, 'used' => 5]);

    mentorChat($this->module, $this->form, $this->mentee)
        ->assertSee('Sisa 0 dari 5 pertanyaan')
        ->assertSee('Kuota tanya AI Mentor untuk modul ini sudah habis')
        ->set('question', 'Satu lagi ya?')
        ->call('send')
        ->assertHasErrors('question')
        ->assertSet('messages', []);

    ModuleMentorAgent::assertNeverPrompted();
    expect(usedQuota($this->mentee, $this->module))->toBe(5);
});

test('the limit is configurable', function () {
    config(['ai.module_chat.limit' => 1]);
    ModuleMentorAgent::fake(['Jawaban pertama.']);

    mentorChat($this->module, $this->form, $this->mentee)
        ->assertSee('Sisa 1 dari 1 pertanyaan')
        ->set('question', 'Pertanyaan pertama')
        ->call('send')
        ->assertSee('Sisa 0 dari 1 pertanyaan')
        ->set('question', 'Pertanyaan kedua')
        ->call('send')
        ->assertHasErrors('question');

    ModuleMentorAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, 'Pertanyaan pertama'));
    ModuleMentorAgent::assertNotPrompted(fn ($prompt): bool => str_contains($prompt->prompt, 'Pertanyaan kedua'));
});

test('the quota is counted per module', function () {
    ModuleMentorUsage::query()->create(['user_id' => $this->mentee->id, 'module_id' => $this->module->id, 'used' => 5]);

    $other_form = PracticeForm::factory()->create(['module_id' => Module::factory()->create([
        'fase_id' => $this->module->fase_id,
        'modul_order' => 0,
    ])->id]);

    mentorChat($other_form->module, $other_form, $this->mentee)
        ->assertSee('Sisa 5 dari 5 pertanyaan');
});

test('a suggestion with an admin answer is shown without the model and keeps the quota', function () {
    ModuleMentorAgent::fake()->preventStrayPrompts();

    $this->module->update(['mentor_suggestions' => [
        ['question' => 'Berapa panjang ideal paragraf pembuka?', 'answer' => 'Cukup 3-4 kalimat, fokus pada satu momen.'],
    ]]);

    mentorChat($this->module, $this->form, $this->mentee)
        ->assertSee('Berapa panjang ideal paragraf pembuka?')
        ->assertSee('Gratis')
        ->call('useSuggestion', 0)
        ->assertSee('Cukup 3-4 kalimat, fokus pada satu momen.')
        ->assertSee('Jawaban dari tim Skular')
        ->assertSee('Sisa 5 dari 5 pertanyaan');

    ModuleMentorAgent::assertNeverPrompted();
    expect(usedQuota($this->mentee, $this->module))->toBe(0);
});

test('a suggestion without an answer is sent to the ai and uses one quota', function () {
    ModuleMentorAgent::fake(['Fokus pada dampak.']);

    $this->module->update(['mentor_suggestions' => [
        ['question' => 'Apa yang dinilai pewawancara?', 'answer' => null],
    ]]);

    mentorChat($this->module, $this->form, $this->mentee)
        ->call('useSuggestion', 0)
        ->assertSee('Fokus pada dampak.')
        ->assertSee('Sisa 4 dari 5 pertanyaan');

    ModuleMentorAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, 'Apa yang dinilai pewawancara?'));
});

test('a module without admin suggestions offers questions built from its content', function () {
    mentorChat($this->module, $this->form, $this->mentee)
        ->assertSee('Apa poin terpenting dari materi &quot;Esai Motivasi&quot;?', false)
        ->assertSee('Bagaimana cara memulai latihan &quot;Paragraf Pembuka&quot;?', false)
        ->assertDontSee('Gratis');
});

test('an admin can store mentor suggestions on a module', function () {
    $role = Role::findOrCreate(AdminRole::SuperAdmin->value, 'web');
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole($role);

    Livewire::actingAs($admin)
        ->test(EditModule::class, ['record' => $this->module->getRouteKey()])
        ->fillForm([
            'mentor_suggestions' => [
                ['question' => 'Apa contoh pembuka yang baik?', 'answer' => 'Mulai dari momen spesifik.'],
                ['question' => 'Bagaimana menutup esai?', 'answer' => ''],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->module->refresh()->mentorSuggestionList())->toBe([
        ['question' => 'Apa contoh pembuka yang baik?', 'answer' => 'Mulai dari momen spesifik.'],
        ['question' => 'Bagaimana menutup esai?', 'answer' => null],
    ]);
});
