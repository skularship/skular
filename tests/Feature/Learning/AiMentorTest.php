<?php

use App\Ai\Agents\ModuleMentorAgent;
use App\Contracts\ModuleMentorServiceInterface;
use App\Exceptions\AiMentorException;
use App\Livewire\Learning\MentorChat;
use App\Models\Fase;
use App\Models\Module;
use App\Models\PracticeForm;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->fase = Fase::factory()->create(['fase_order' => 1]);
    $this->module = Module::factory()->create([
        'fase_id' => $this->fase->id,
        'modul_order' => 1,
        'title' => 'Modul Esai Beasiswa',
        'ai_knowledge' => 'Rubrik: gunakan struktur STAR dan tunjukkan dampak nyata.',
    ]);
    $this->form = PracticeForm::factory()->create(['module_id' => $this->module->id]);
    $this->mentee = User::factory()->profiled()->create();
    $this->service = app(ModuleMentorServiceInterface::class);
});

test('the mentor answers using the module context', function () {
    ModuleMentorAgent::fake(['Ini penjelasan singkat materi modul.']);

    $reply = $this->service->reply($this->module, $this->form, 'Jelaskan materi ini.');

    expect($reply->answer)->toBe('Ini penjelasan singkat materi modul.');

    ModuleMentorAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, 'Modul Esai Beasiswa')
        && str_contains($prompt->prompt, 'Jelaskan materi ini.'));
});

test('the mentor forwards only the bounded recent chat history', function () {
    ModuleMentorAgent::fake(['Baik, kita lanjut.']);

    $history = [];

    for ($turn = 1; $turn <= 8; $turn++) {
        $history[] = [
            'role' => $turn % 2 === 0 ? 'assistant' : 'user',
            'content' => 'pesan '.$turn,
        ];
    }

    $this->service->reply($this->module, $this->form, 'Lanjut ya.', $history);

    ModuleMentorAgent::assertPrompted(function ($prompt): bool {
        $messages = $prompt->messages ?? [];

        return count($messages) === 6
            && $messages[0]->content === 'pesan 3'
            && $messages[5]->content === 'pesan 8';
    });
});

test('the mentor prompt includes the active practice field when provided', function () {
    ModuleMentorAgent::fake(['Oke.']);

    $this->service->reply($this->module, $this->form, 'Bagaimana ini?', [], 'Pengalaman Organisasi');

    ModuleMentorAgent::assertPrompted(fn ($prompt): bool => str_contains(
        $prompt->prompt,
        'Bagian yang sedang dikerjakan mentee: Pengalaman Organisasi',
    ));
});

test('an empty question is rejected', function () {
    ModuleMentorAgent::fake();

    expect(fn () => $this->service->reply($this->module, $this->form, '   '))
        ->toThrow(ValidationException::class);
});

test('an over-long question is rejected', function () {
    ModuleMentorAgent::fake();

    expect(fn () => $this->service->reply($this->module, $this->form, str_repeat('a', 1200)))
        ->toThrow(ValidationException::class);
});

test('an ai failure is surfaced as a mentor exception', function () {
    ModuleMentorAgent::fake([fn () => throw new RuntimeException('provider down')]);

    expect(fn () => $this->service->reply($this->module, $this->form, 'Bantu saya.'))
        ->toThrow(AiMentorException::class);
});

test('the mentor chat rejects an empty question in the ui', function () {
    ModuleMentorAgent::fake();

    Livewire::actingAs($this->mentee)
        ->test(MentorChat::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->set('question', '')
        ->call('send')
        ->assertHasErrors('question');
});

test('the mentor chat never writes to a practice form', function () {
    ModuleMentorAgent::fake(['Coba mulai dari pengalaman paling berkesan.']);

    Livewire::actingAs($this->mentee)
        ->test(MentorChat::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->set('question', 'Bagaimana cara memulai jawaban?')
        ->call('send')
        ->assertHasNoErrors();

    expect($this->mentee->practiceResponses()->count())->toBe(0);
});

test('a mentee cannot chat about a locked module', function () {
    ModuleMentorAgent::fake();

    $second = Module::factory()->create(['fase_id' => $this->fase->id, 'modul_order' => 2]);

    // The component refuses to mount for a locked module and answers 403.
    Livewire::actingAs($this->mentee)
        ->test(MentorChat::class, ['module' => $second])
        ->assertStatus(403);
});

test('retry does not duplicate the previous answer', function () {
    $attempts = 0;

    ModuleMentorAgent::fake(function () use (&$attempts): string {
        $attempts++;

        if ($attempts === 1) {
            throw new RuntimeException('first fails');
        }

        return 'Jawaban benar setelah retry.';
    });

    $component = Livewire::actingAs($this->mentee)
        ->test(MentorChat::class, ['module' => $this->module, 'form_id' => $this->form->id])
        ->set('question', 'Tolong bantu saya.')
        ->call('send')
        ->assertSet('failed_question', 'Tolong bantu saya.')
        ->call('retry')
        ->assertHasNoErrors()
        ->assertSet('failed_question', null);

    $messages = $component->get('messages');

    // user question + error bubble replaced by one assistant answer.
    expect(collect($messages)->where('role', 'user'))->toHaveCount(1)
        ->and(collect($messages)->where('role', 'assistant')->where('error', true))->toHaveCount(0);
});
