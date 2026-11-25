<?php

use App\Contracts\IqAttemptServiceInterface;
use App\Contracts\IqBankImportServiceInterface;
use App\Enums\AdminRole;
use App\Enums\IqSubtest;
use App\Filament\Resources\IqQuestions\Pages\CreateIqQuestion;
use App\Filament\Resources\IqQuestions\Pages\EditIqQuestion;
use App\Filament\Resources\IqQuestions\Pages\ListIqQuestions;
use App\Filament\Resources\IqTests\Pages\EditIqTest;
use App\Filament\Resources\IqTests\Pages\ListIqTests;
use App\Models\IqQuestion;
use App\Models\IqTest;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $role = Role::findOrCreate(AdminRole::CoAdmin->value, 'web');
    $permissions = [];

    foreach (['IqTest', 'IqQuestion'] as $entity) {
        foreach (['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny'] as $action) {
            $permissions[] = Permission::findOrCreate("{$action}:{$entity}", 'web')->name;
        }
    }

    $role->syncPermissions($permissions);
    $this->admin = User::factory()->create(['email_verified_at' => now()]);
    $this->admin->assignRole($role);
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

/**
 * The starter bank shipped with the repository (from Bank-Soal-Tes-IQ-v0.1-Mudah.docx).
 *
 * @return array<mixed>
 */
function iqStarterBank(): array
{
    return json_decode((string) file_get_contents(database_path('data/iq/bank-soal-v0.1.json')), true);
}

test('every tes iq admin page renders', function (string $page) {
    $test = IqTest::factory()->withQuestions()->create();

    $parameters = match ($page) {
        EditIqTest::class => ['record' => $test->getRouteKey()],
        EditIqQuestion::class => ['record' => $test->questions()->first()->getRouteKey()],
        default => [],
    };

    Livewire::test($page, $parameters)->assertOk();
})->with([
    ListIqTests::class,
    EditIqTest::class,
    ListIqQuestions::class,
    CreateIqQuestion::class,
    EditIqQuestion::class,
]);

test('an admin creates a question and the key must be exactly one letter A-D', function () {
    $test = IqTest::factory()->create();

    Livewire::test(CreateIqQuestion::class)
        ->fillForm([
            'iq_test_id' => $test->id,
            'number' => 1,
            'subtest' => IqSubtest::Numerik->value,
            'prompt' => '1, 2, 3, ?',
            'option_a' => '4',
            'option_b' => '5',
            'option_c' => '6',
            'option_d' => '7',
            'answer_key' => 'E',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['answer_key']);

    Livewire::test(CreateIqQuestion::class)
        ->fillForm([
            'iq_test_id' => $test->id,
            'number' => 1,
            'subtest' => IqSubtest::Numerik->value,
            'prompt' => '1, 2, 3, ?',
            'option_a' => '4',
            'option_b' => '5',
            'option_c' => '6',
            'option_d' => '7',
            'answer_key' => 'A',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(IqQuestion::query()->where('number', 1)->first())
        ->is_active->toBeTrue()
        ->answer_key->toBe('A');
});

test('a visuospatial question without an image cannot be activated', function () {
    $test = IqTest::factory()->create();

    Livewire::test(CreateIqQuestion::class)
        ->fillForm([
            'iq_test_id' => $test->id,
            'number' => 8,
            'subtest' => IqSubtest::Visuospasial->value,
            'prompt' => 'Pilih bentuk yang tepat.',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'answer_key' => 'C',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['image_path']);
});

test('every question kind of the v0.1 bank can be entered in the admin form', function () {
    Storage::fake('public');
    $test = IqTest::factory()->create();

    // Visuospasial no. 8: symbol options, image upload with alt text.
    Livewire::test(CreateIqQuestion::class)
        ->fillForm([
            'iq_test_id' => $test->id,
            'number' => 8,
            'subtest' => IqSubtest::Visuospasial->value,
            'prompt' => 'Pilih bentuk yang tepat untuk menggantikan tanda tanya pada gambar.',
            'image_path' => UploadedFile::fake()->image('soal-8.png', 800, 800),
            'image_alt' => 'Tiga baris bentuk. Baris 3: lingkaran, segitiga, tanda tanya.',
            'option_a' => '●',
            'option_b' => '▲',
            'option_c' => '■',
            'option_d' => '◆',
            'answer_key' => 'C',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Teks no. 28: reading text above the question.
    Livewire::test(CreateIqQuestion::class)
        ->fillForm([
            'iq_test_id' => $test->id,
            'number' => 28,
            'subtest' => IqSubtest::Teks->value,
            'passage' => 'Rina pergi ke pasar pagi hari. Ia membeli 2 kg tomat dan 1 kg bawang. Tomat Rp 10.000 per kg. Bawang Rp 15.000 per kg.',
            'prompt' => 'Berapa kg total belanjaan Rina?',
            'option_a' => '2',
            'option_b' => '3',
            'option_c' => '4',
            'option_d' => '5',
            'answer_key' => 'B',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Logika no. 20: long sentence options.
    Livewire::test(CreateIqQuestion::class)
        ->fillForm([
            'iq_test_id' => $test->id,
            'number' => 20,
            'subtest' => IqSubtest::Logika->value,
            'prompt' => 'Semua kucing adalah mamalia. Semua mamalia bernapas. Kesimpulan yang benar adalah?',
            'option_a' => 'Semua kucing bernapas',
            'option_b' => 'Semua mamalia adalah kucing',
            'option_c' => 'Tidak ada kucing yang bernapas',
            'option_d' => 'Sebagian kucing tidak bernapas',
            'answer_key' => 'A',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $visual = IqQuestion::query()->where('number', 8)->firstOrFail();

    expect($visual->is_active)->toBeTrue()
        ->and($visual->optionList())->toBe(['A' => '●', 'B' => '▲', 'C' => '■', 'D' => '◆'])
        ->and($visual->image_path)->toStartWith('iq-questions/')
        ->and(Storage::disk('public')->exists($visual->image_path))->toBeTrue()
        ->and(IqQuestion::query()->where('number', 28)->value('passage'))->toContain('Rina pergi ke pasar')
        ->and(IqQuestion::query()->where('number', 20)->value('option_d'))->toBe('Sebagian kucing tidak bernapas')
        ->and(IqQuestion::query()->where('is_active', true)->count())->toBe(3);
});

test('the model never stores an incomplete question as active', function () {
    $question = IqQuestion::factory()->create(['subtest' => IqSubtest::Verbal, 'option_d' => '', 'is_active' => true]);

    expect($question->fresh()->is_active)->toBeFalse()
        ->and($question->activationErrors())->toContain('Pilihan D masih kosong.');
});

test('the starter bank imports 35 questions; visuospatial ones wait for their image', function () {
    $importer = app(IqBankImportServiceInterface::class);

    $dry = $importer->import(iqStarterBank(), dry_run: true);

    expect($dry->errors)->toBe([])
        ->and($dry->created)->toBe(35)
        ->and(IqQuestion::query()->count())->toBe(0);

    $report = $importer->import(iqStarterBank());
    $test = IqTest::query()->where('code', 'IQ-V01')->firstOrFail();

    expect($report->created)->toBe(35)
        ->and($report->inactive)->toBe(7)
        ->and($test->duration_minutes)->toBe(20)
        ->and($test->questions()->where('is_active', true)->count())->toBe(28)
        ->and($test->questions()->where('subtest', IqSubtest::Visuospasial->value)->where('is_active', true)->count())->toBe(0)
        ->and($test->questions()->where('subtest', IqSubtest::Teks->value)->whereNotNull('passage')->count())->toBe(8);
});

test('re-importing updates questions without wiping an uploaded image', function () {
    $importer = app(IqBankImportServiceInterface::class);
    $importer->import(iqStarterBank());

    $question = IqQuestion::query()->where('number', 8)->firstOrFail();
    $question->update(['image_path' => 'iq-questions/q8.png', 'image_alt' => 'Pola tiga baris.', 'is_active' => true]);

    $bank = iqStarterBank();
    $bank['soal'][0]['kunci'] = 'C';
    $report = $importer->import($bank);

    expect($report->updated)->toBe(35)
        ->and($report->created)->toBe(0)
        ->and($question->fresh()->image_path)->toBe('iq-questions/q8.png')
        ->and($question->fresh()->is_active)->toBeTrue()
        ->and(IqQuestion::query()->where('number', 1)->value('answer_key'))->toBe('C');
});

test('invalid import rows are reported and skipped', function () {
    $report = app(IqBankImportServiceInterface::class)->import([
        'tes' => ['kode' => 'IQ-X'],
        'soal' => [
            ['nomor' => 1, 'subtes' => 'numerik', 'soal' => '1+1?', 'pilihan' => ['A' => '1', 'B' => '2', 'C' => '3', 'D' => ''], 'kunci' => 'B'],
            ['nomor' => 2, 'subtes' => 'musik', 'soal' => 'x', 'pilihan' => ['A' => '1', 'B' => '2', 'C' => '3', 'D' => '4'], 'kunci' => 'B'],
            ['nomor' => 3, 'subtes' => 'verbal', 'soal' => 'x', 'opsi_a' => '1', 'opsi_b' => '2', 'opsi_c' => '3', 'opsi_d' => '4', 'kunci' => 'AB'],
            ['nomor' => 4, 'subtes' => 'verbal', 'teks_soal' => 'Sinonim BESAR?', 'opsi_a' => 'kecil', 'opsi_b' => 'agung', 'opsi_c' => 'tipis', 'opsi_d' => 'pendek', 'kunci' => 'b'],
        ],
    ]);

    expect($report->errors)->toHaveCount(3)
        ->and($report->created)->toBe(1)
        ->and(IqQuestion::query()->where('number', 4)->first())
        ->answer_key->toBe('B')
        ->is_active->toBeTrue();
});

test('the export round-trips through the import format', function () {
    $importer = app(IqBankImportServiceInterface::class);
    $importer->import(iqStarterBank());
    $test = IqTest::query()->where('code', 'IQ-V01')->firstOrFail();

    $export = $importer->export($test);

    expect($export['tes']['kode'])->toBe('IQ-V01')
        ->and($export['soal'])->toHaveCount(35)
        ->and($export['soal'][0])->toMatchArray(['nomor' => 1, 'subtes' => 'numerik', 'kunci' => 'B'])
        ->and($importer->import($export, dry_run: true)->errors)->toBe([]);
});

test('the iq:import command validates with --dry-run', function () {
    $this->artisan('iq:import', ['file' => database_path('data/iq/bank-soal-v0.1.json'), '--dry-run' => true])
        ->expectsOutputToContain('[Mode coba] Soal: 35 baru')
        ->assertSuccessful();

    expect(IqQuestion::query()->count())->toBe(0);
});

test('a test mentees took cannot be deleted', function () {
    $test = IqTest::factory()->withQuestions(['numerik' => 1])->create();
    app(IqAttemptServiceInterface::class)->start(User::factory()->create(), $test);

    expect(fn () => $test->delete())->toThrow(LogicException::class);
});
