<?php

use App\Contracts\SupportTicketServiceInterface;
use App\Enums\AdminRole;
use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketStatus;
use App\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Livewire\Support\Help;
use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->mentee = User::factory()->profiled()->create(['name' => 'Rina Uji']);
});

/**
 * Create an admin with the seeded permissions of the given role.
 */
function supportAdmin(AdminRole $role): User
{
    (new RoleSeeder)->run();

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole(Role::findByName($role->value, 'web'));

    return $admin;
}

/**
 * Valid form input for a bug report.
 *
 * @return array<string, string>
 */
function supportInput(): array
{
    return [
        'category' => SupportTicketCategory::Error->value,
        'subject' => 'Halaman modul error',
        'description' => 'Setelah klik Submit Jawaban muncul pesan error 500.',
    ];
}

test('guests are redirected from the help page to the login page', function () {
    $this->get(route('support.index'))->assertRedirect(route('login'));
});

test('the help page shows the whatsapp help line with a prefilled message', function () {
    $this->actingAs($this->mentee)
        ->get(route('support.index'))
        ->assertOk()
        ->assertSee('Bantuan')
        ->assertSee('+62 896-0488-3024')
        ->assertSee('https://wa.me/6289604883024?text=', false)
        ->assertSee(rawurlencode('saya Rina Uji'), false);
});

test('a mentee can report a bug with a private screenshot', function () {
    Storage::fake('local');

    Livewire::actingAs($this->mentee)
        ->test(Help::class)
        ->set('category', SupportTicketCategory::Bug->value)
        ->set('subject', '<b>Tombol</b> simpan   tidak jalan')
        ->set('description', 'Saya klik Simpan di halaman Profil tapi tidak ada yang terjadi.')
        ->set('page_url', 'https://skularship.com/settings/profile')
        ->set('screenshot', UploadedFile::fake()->image('error.png'))
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('subject', '')
        ->assertDispatched('toast', variant: 'success')
        ->assertSee('Tombol simpan tidak jalan');

    $ticket = SupportTicket::query()->sole();

    expect($ticket->user_id)->toBe($this->mentee->id)
        ->and($ticket->category)->toBe(SupportTicketCategory::Bug)
        ->and($ticket->status)->toBe(SupportTicketStatus::New)
        ->and($ticket->subject)->toBe('Tombol simpan tidak jalan')
        ->and($ticket->page_url)->toBe('https://skularship.com/settings/profile')
        ->and($ticket->screenshot_path)->toStartWith('support-tickets/'.$this->mentee->id.'/');

    Storage::disk('local')->assertExists($ticket->screenshot_path);
});

test('an invalid report is rejected with field errors', function (array $override, string $error) {
    Livewire::actingAs($this->mentee)
        ->test(Help::class)
        ->set('category', $override['category'] ?? SupportTicketCategory::Bug->value)
        ->set('subject', $override['subject'] ?? 'Judul yang valid')
        ->set('description', $override['description'] ?? 'Penjelasan yang cukup panjang.')
        ->set('page_url', $override['page_url'] ?? '')
        ->call('submit')
        ->assertHasErrors($error);

    expect(SupportTicket::query()->count())->toBe(0);
})->with([
    'missing category' => [['category' => ''], 'category'],
    'unknown category' => [['category' => 'hack'], 'category'],
    'short subject' => [['subject' => 'Eh'], 'subject'],
    'markup only description' => [['description' => '<p></p><br>'], 'description'],
    'too long description' => [['description' => str_repeat('a', 2001)], 'description'],
    'javascript link' => [['page_url' => 'javascript:alert(1)'], 'page_url'],
]);

test('a screenshot larger than 5 MB or not an image is rejected', function (UploadedFile $file) {
    Storage::fake('local');

    Livewire::actingAs($this->mentee)
        ->test(Help::class)
        ->set('category', SupportTicketCategory::Bug->value)
        ->set('subject', 'Judul yang valid')
        ->set('description', 'Penjelasan yang cukup panjang.')
        ->set('screenshot', $file)
        ->call('submit')
        ->assertHasErrors('screenshot');

    expect(SupportTicket::query()->count())->toBe(0);
})->with([
    'too large' => fn () => UploadedFile::fake()->image('besar.png')->size(6000),
    'not an image' => fn () => UploadedFile::fake()->create('skrip.pdf', 10, 'application/pdf'),
]);

test('a mentee can send at most five tickets per day', function () {
    $service = app(SupportTicketServiceInterface::class);

    foreach (range(1, 5) as $ignored) {
        $service->submit($this->mentee, supportInput());
    }

    Livewire::actingAs($this->mentee)
        ->test(Help::class)
        ->set('category', SupportTicketCategory::Bug->value)
        ->set('subject', 'Laporan keenam')
        ->set('description', 'Penjelasan yang cukup panjang.')
        ->call('submit')
        ->assertHasErrors('ticket')
        ->assertSee('Kamu sudah mengirim 5 laporan dalam 24 jam terakhir');

    $this->travel(25)->hours();

    $service->submit($this->mentee, supportInput());

    expect($this->mentee->supportTickets()->count())->toBe(6);
});

test('a refused ticket does not leave its screenshot behind', function () {
    Storage::fake('local');
    $service = app(SupportTicketServiceInterface::class);

    foreach (range(1, 5) as $ignored) {
        $service->submit($this->mentee, supportInput());
    }

    expect(fn () => $service->submit($this->mentee, supportInput(), UploadedFile::fake()->image('a.png')))
        ->toThrow(ValidationException::class);

    expect(Storage::disk('local')->allFiles('support-tickets'))->toBe([]);
});

test('mentees only see their own tickets and never the admin note', function () {
    SupportTicket::factory()->create(['subject' => 'Tiket milik mentee lain']);
    SupportTicket::factory()->create([
        'user_id' => $this->mentee->id,
        'subject' => 'Tiket milik saya sendiri',
        'status' => SupportTicketStatus::InProgress,
        'admin_note' => 'Catatan internal rahasia',
    ]);

    Livewire::actingAs($this->mentee)
        ->test(Help::class)
        ->assertSee('Tiket milik saya sendiri')
        ->assertSee('Sedang ditangani')
        ->assertDontSee('Tiket milik mentee lain')
        ->assertDontSee('Catatan internal rahasia');

    $this->actingAs($this->mentee)
        ->get(SupportTicketResource::getUrl('index'))
        ->assertForbidden();
});

test('an admin can resolve a ticket with an internal note', function () {
    $admin = supportAdmin(AdminRole::CoAdmin);
    $ticket = SupportTicket::factory()->create();

    $this->actingAs($admin)->get(SupportTicketResource::getUrl('index'))->assertOk();

    Livewire::actingAs($admin)
        ->test(ListSupportTickets::class)
        ->assertCanSeeTableRecords([$ticket])
        ->callAction(TestAction::make('resolve')->table($ticket), ['admin_note' => 'Sudah diperbaiki di rilis terbaru.'])
        ->assertHasNoActionErrors();

    $ticket->refresh();

    expect($ticket->status)->toBe(SupportTicketStatus::Resolved)
        ->and($ticket->handled_by)->toBe($admin->id)
        ->and($ticket->resolved_at)->not->toBeNull()
        ->and($ticket->admin_note)->toBe('Sudah diperbaiki di rilis terbaru.');
});

test('staff may handle tickets but never delete them', function () {
    $staff = supportAdmin(AdminRole::StaffDivisi);
    $ticket = SupportTicket::factory()->create();

    expect(Gate::forUser($staff)->allows('update', $ticket))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('delete', $ticket))->toBeFalse();
});

test('tickets survive account deletion', function () {
    $ticket = SupportTicket::factory()->create(['user_id' => $this->mentee->id]);

    $this->mentee->delete();

    expect($ticket->fresh()->user_id)->toBeNull();
});
