<?php

use App\Contracts\ScholarshipReportServiceInterface;
use App\Enums\AdminRole;
use App\Enums\ReportedField;
use App\Enums\ReportStatus;
use App\Filament\Resources\ScholarshipInfoReports\Pages\ListScholarshipInfoReports;
use App\Filament\Resources\ScholarshipInfoReports\ScholarshipInfoReportResource;
use App\Livewire\Scholarships\Show;
use App\Models\Scholarship;
use App\Models\ScholarshipInfoReport;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->mentee = User::factory()->profiled()->create();
    $this->scholarship = Scholarship::factory()->create(['scholarship_name' => 'Beasiswa Laporan Uji']);
});

/**
 * Create an admin with the seeded permissions of the given role.
 */
function reportAdmin(AdminRole $role): User
{
    (new RoleSeeder)->run();

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole(Role::findByName($role->value, 'web'));

    return $admin;
}

test('a mentee can send a report from the scholarship detail page', function () {
    Livewire::actingAs($this->mentee)
        ->test(Show::class, ['scholarship' => $this->scholarship])
        ->assertSee('Informasi Tidak Akurat?')
        ->set('show_report_modal', true)
        ->set('report_field', ReportedField::ClosingDate->value)
        ->set('report_suggested_value', '<b>Deadline</b> diperpanjang   sampai 30 November 2026')
        ->set('report_source_url', 'https://contoh.org/pengumuman')
        ->call('submitReport')
        ->assertHasNoErrors()
        ->assertSet('show_report_modal', false)
        ->assertSet('report_suggested_value', '')
        ->assertDispatched('toast', variant: 'success', text: 'Terima kasih, laporanmu kami cek.');

    $report = ScholarshipInfoReport::query()->sole();

    expect($report->user_id)->toBe($this->mentee->id)
        ->and($report->scholarship_id)->toBe($this->scholarship->id)
        ->and($report->field)->toBe(ReportedField::ClosingDate)
        ->and($report->status)->toBe(ReportStatus::New)
        // Stored as plain text: tags stripped, spaces collapsed.
        ->and($report->suggested_value)->toBe('Deadline diperpanjang sampai 30 November 2026');
});

test('an invalid report is rejected with field errors', function (array $input, string $error) {
    Livewire::actingAs($this->mentee)
        ->test(Show::class, ['scholarship' => $this->scholarship])
        ->set('report_field', $input['field'] ?? ReportedField::Quota->value)
        ->set('report_suggested_value', $input['suggested_value'] ?? 'Kuota 100 orang.')
        ->set('report_source_url', $input['source_url'] ?? '')
        ->call('submitReport')
        ->assertHasErrors($error);

    expect(ScholarshipInfoReport::query()->count())->toBe(0);
})->with([
    'missing suggestion' => [['suggested_value' => ''], 'report_suggested_value'],
    'only markup' => [['suggested_value' => '<p> </p><br>'], 'report_suggested_value'],
    'unknown field' => [['field' => 'logo'], 'report_field'],
    'javascript link' => [['source_url' => 'javascript:alert(1)'], 'report_source_url'],
    'too long' => [['suggested_value' => str_repeat('a', 1001)], 'report_suggested_value'],
]);

test('a mentee cannot keep two open reports for the same part of a scholarship', function () {
    $service = app(ScholarshipReportServiceInterface::class);
    $input = ['field' => ReportedField::Link->value, 'suggested_value' => 'Tautan sudah berubah.'];

    $service->submit($this->mentee, $this->scholarship, $input);

    expect(fn () => $service->submit($this->mentee, $this->scholarship, $input))
        ->toThrow(ValidationException::class);

    // Once the first report is decided, a new one for the same part is allowed.
    ScholarshipInfoReport::query()->update(['status' => ReportStatus::Rejected->value]);

    $service->submit($this->mentee, $this->scholarship, $input);

    expect(ScholarshipInfoReport::query()->count())->toBe(2);
});

test('a mentee can send at most five reports per day', function () {
    $service = app(ScholarshipReportServiceInterface::class);
    $scholarships = Scholarship::factory()->count(6)->create();

    foreach ($scholarships->take(5) as $scholarship) {
        $service->submit($this->mentee, $scholarship, ['field' => 'quota', 'suggested_value' => 'Kuota berubah.']);
    }

    Livewire::actingAs($this->mentee)
        ->test(Show::class, ['scholarship' => $scholarships->last()])
        ->set('report_field', 'quota')
        ->set('report_suggested_value', 'Kuota berubah.')
        ->call('submitReport')
        ->assertHasErrors('report')
        ->assertSee('Kamu sudah mengirim 5 laporan dalam 24 jam terakhir');

    $this->travel(25)->hours();

    $service->submit($this->mentee, $scholarships->last(), ['field' => 'quota', 'suggested_value' => 'Kuota berubah.']);

    expect($this->mentee->scholarshipInfoReports()->count())->toBe(6);
});

test('a draft scholarship cannot be reported', function () {
    $draft = Scholarship::factory()->draft()->create();

    expect(fn () => app(ScholarshipReportServiceInterface::class)
        ->submit($this->mentee, $draft, ['field' => 'quota', 'suggested_value' => 'Kuota berubah.']))
        ->toThrow(ValidationException::class);
});

test('mentees never see reports or evidence links of other mentees', function () {
    ScholarshipInfoReport::factory()->create([
        'scholarship_id' => $this->scholarship->id,
        'suggested_value' => 'Usulan rahasia mentee lain',
        'source_url' => 'https://bukti-rahasia.example/doc',
    ]);

    $this->actingAs($this->mentee)
        ->get(route('scholarships.show', $this->scholarship))
        ->assertOk()
        ->assertDontSee('Usulan rahasia mentee lain')
        ->assertDontSee('bukti-rahasia.example');

    $this->actingAs($this->mentee)
        ->get(ScholarshipInfoReportResource::getUrl('index'))
        ->assertForbidden();
});

test('a co-admin can accept a report with a note', function () {
    $admin = reportAdmin(AdminRole::CoAdmin);
    $report = ScholarshipInfoReport::factory()->create(['scholarship_id' => $this->scholarship->id]);

    $this->actingAs($admin)->get(ScholarshipInfoReportResource::getUrl('index'))->assertOk();

    Livewire::actingAs($admin)
        ->test(ListScholarshipInfoReports::class)
        ->assertCanSeeTableRecords([$report])
        ->callAction(TestAction::make('accept')->table($report), ['admin_note' => 'Sudah dicek ke sumber.'])
        ->assertHasNoActionErrors();

    $report->refresh();

    expect($report->status)->toBe(ReportStatus::Accepted)
        ->and($report->reviewed_by)->toBe($admin->id)
        ->and($report->reviewed_at)->not->toBeNull()
        ->and($report->admin_note)->toBe('Sudah dicek ke sumber.');
});

test('staff may review reports but never delete them', function () {
    $staff = reportAdmin(AdminRole::StaffDivisi);
    $report = ScholarshipInfoReport::factory()->create();

    expect(Gate::forUser($staff)->allows('update', $report))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('delete', $report))->toBeFalse();
});

test('reports survive account deletion and block deleting the scholarship', function () {
    $report = ScholarshipInfoReport::factory()->create([
        'user_id' => $this->mentee->id,
        'scholarship_id' => $this->scholarship->id,
    ]);

    $this->mentee->delete();

    expect($report->fresh()->user_id)->toBeNull()
        ->and($this->scholarship->delete())->toBeFalse()
        ->and(Scholarship::query()->whereKey($this->scholarship->id)->exists())->toBeTrue();
});
