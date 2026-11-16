<?php

use App\Contracts\VaultServiceInterface;
use App\Exceptions\VaultException;
use App\Livewire\Checklist\Show as ChecklistShow;
use App\Livewire\Vault\Index as VaultIndex;
use App\Models\ChecklistItem;
use App\Models\ScholarshipApplication;
use App\Models\Subscription;
use App\Models\User;
use App\Models\VaultDocument;
use Database\Factories\ScholarshipFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    config(['pricing.enabled' => true]);
    Storage::fake('local');
});

/**
 * Create a mentee with an active Pro plan.
 */
function proUser(): User
{
    $user = User::factory()->create();
    Subscription::query()->create(['user_id' => $user->id, 'plan' => 'pro_12bln', 'active_until' => now()->addMonth()]);

    return $user->fresh();
}

test('while Premium is off the vault, checklist and download do not exist', function () {
    config(['pricing.enabled' => false]);
    $user = User::factory()->create();
    $application = ScholarshipApplication::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('vault.index'))->assertNotFound();
    $this->actingAs($user)->get(route('checklist.show', $application))->assertNotFound();
});

test('a Gratis mentee sees the vault locked and cannot upload', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('vault.index'))->assertOk()
        ->assertSee('vault-locked', false)
        ->assertSee('Lihat paket Pro');

    Livewire::actingAs($user)->test(VaultIndex::class)
        ->set('title', 'Transkrip')
        ->set('file', UploadedFile::fake()->create('transkrip.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertHasErrors('file');

    expect(VaultDocument::query()->count())->toBe(0);
});

test('a Pro mentee uploads to the private disk and downloads their own file', function () {
    $user = proUser();

    Livewire::actingAs($user)->test(VaultIndex::class)
        ->set('title', 'Transkrip semester 5')
        ->set('file', UploadedFile::fake()->create('transkrip.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertHasNoErrors();

    $document = VaultDocument::query()->first();
    Storage::disk('local')->assertExists($document->path);
    expect($document->user_id)->toBe($user->id)->and($document->size_kb)->toBe(100);

    $this->actingAs($user)->get(route('vault.download', $document))->assertOk()->assertDownload('transkrip.pdf');
});

test('nobody else can download or delete someone elses document', function () {
    $owner = proUser();
    $document = VaultDocument::query()->create([
        'user_id' => $owner->id, 'title' => 'KTP', 'path' => 'vault/1/ktp.pdf', 'original_name' => 'ktp.pdf', 'size_kb' => 10,
    ]);
    Storage::disk('local')->put($document->path, 'x');
    $other = proUser();

    $this->actingAs($other)->get(route('vault.download', $document))->assertNotFound();

    Livewire::actingAs($other)->test(VaultIndex::class)->call('delete', $document->id)->assertNotFound();
    expect(VaultDocument::query()->count())->toBe(1);
});

test('wrong file types and files over the size limit are refused', function () {
    $user = proUser();

    Livewire::actingAs($user)->test(VaultIndex::class)
        ->set('title', 'Skrip')
        ->set('file', UploadedFile::fake()->create('virus.exe', 10))
        ->call('save')
        ->assertHasErrors('file');

    Livewire::actingAs($user)->test(VaultIndex::class)
        ->set('title', 'Besar')
        ->set('file', UploadedFile::fake()->create('besar.pdf', 6000, 'application/pdf'))
        ->call('save')
        ->assertHasErrors('file');
});

test('the vault stops at the configured number of files', function () {
    config(['plans.vault.max_files' => 1]);
    $user = proUser();

    Livewire::actingAs($user)->test(VaultIndex::class)
        ->set('title', 'Satu')->set('file', UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'))->call('save')
        ->set('title', 'Dua')->set('file', UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'))->call('save')
        ->assertHasErrors('file');

    expect(VaultDocument::query()->count())->toBe(1);
});

test('after Pro ends the mentee keeps the files and can still delete them but not upload', function () {
    $user = proUser();
    $document = VaultDocument::query()->create([
        'user_id' => $user->id, 'title' => 'CV', 'path' => 'vault/1/cv.pdf', 'original_name' => 'cv.pdf', 'size_kb' => 10,
    ]);
    Storage::disk('local')->put($document->path, 'x');
    Subscription::query()->where('user_id', $user->id)->update(['active_until' => now()->subDay()]);

    $this->actingAs($user->fresh())->get(route('vault.download', $document))->assertOk();

    expect(fn () => app(VaultServiceInterface::class)->store($user->fresh(), UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'), 'B'))
        ->toThrow(VaultException::class);

    Livewire::actingAs($user->fresh())->test(VaultIndex::class)->call('delete', $document->id);
    expect(VaultDocument::query()->count())->toBe(0);
    Storage::disk('local')->assertMissing($document->path);
});

test('the checklist is created once from defaults and adds SKTM only when the scholarship requires it', function () {
    $user = proUser();
    $application = ScholarshipApplication::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)->test(ChecklistShow::class, ['application' => $application]);
    Livewire::actingAs($user)->test(ChecklistShow::class, ['application' => $application]);

    $without = $application->checklistItems()->count();
    expect($without)->toBe(6);

    $sktm = ScholarshipApplication::factory()->create([
        'user_id' => $user->id,
        'scholarship_id' => ScholarshipFactory::new()->requiringSktm()->create()->id,
    ]);
    Livewire::actingAs($user)->test(ChecklistShow::class, ['application' => $sktm]);

    expect($sktm->checklistItems()->count())->toBe(7);
});

test('a Pro mentee ticks, adds, attaches a vault file to, and removes checklist items', function () {
    $user = proUser();
    $application = ScholarshipApplication::factory()->create(['user_id' => $user->id]);
    $document = VaultDocument::query()->create([
        'user_id' => $user->id, 'title' => 'CV', 'path' => 'vault/1/cv.pdf', 'original_name' => 'cv.pdf', 'size_kb' => 10,
    ]);

    $component = Livewire::actingAs($user)->test(ChecklistShow::class, ['application' => $application]);
    $item = $application->checklistItems()->first();

    $component->call('toggle', $item->id);
    expect($item->fresh()->is_done)->toBeTrue();

    $component->call('attach', $item->id, $document->id);
    expect($item->fresh()->vault_document_id)->toBe($document->id);

    $component->set('new_label', 'Portofolio')->call('add')->assertHasNoErrors();
    expect($application->checklistItems()->where('label', 'Portofolio')->exists())->toBeTrue();

    $component->call('remove', $item->id);
    expect(ChecklistItem::query()->whereKey($item->id)->exists())->toBeFalse();
});

test('a checklist cannot use or be opened with someone elses data', function () {
    $user = proUser();
    $mine = ScholarshipApplication::factory()->create(['user_id' => $user->id]);
    $theirs = ScholarshipApplication::factory()->create();
    $strangers_document = VaultDocument::query()->create([
        'user_id' => $theirs->user_id, 'title' => 'X', 'path' => 'vault/9/x.pdf', 'original_name' => 'x.pdf', 'size_kb' => 1,
    ]);

    $this->actingAs($user)->get(route('checklist.show', $theirs))->assertNotFound();

    $component = Livewire::actingAs($user)->test(ChecklistShow::class, ['application' => $mine]);
    $item = $mine->checklistItems()->first();
    $component->call('attach', $item->id, $strangers_document->id)->assertNotFound();
});

test('a Gratis mentee sees the checklist locked and cannot change it', function () {
    $user = User::factory()->create();
    $application = ScholarshipApplication::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('checklist.show', $application))->assertOk()
        ->assertSee('checklist-locked', false);

    Livewire::actingAs($user)->test(ChecklistShow::class, ['application' => $application])
        ->set('new_label', 'Apa saja')->call('add')->assertForbidden();

    expect($application->checklistItems()->count())->toBe(0);
});
