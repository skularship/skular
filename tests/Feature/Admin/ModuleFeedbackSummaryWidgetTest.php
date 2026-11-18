<?php

use App\Contracts\ModuleFeedbackInsightsServiceInterface;
use App\Enums\AdminRole;
use App\Enums\FeedbackDifficulty;
use App\Filament\Widgets\ModuleFeedbackSummary;
use App\Models\Module;
use App\Models\ModuleFeedback;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Create an admin that may list module feedback.
 */
function feedbackSummaryAdmin(): User
{
    $role = Role::findOrCreate(AdminRole::SuperAdmin->value, 'web');
    Permission::findOrCreate('ViewAny:ModuleFeedback', 'web');
    $role->syncPermissions(['ViewAny:ModuleFeedback']);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

test('the summary counts mentees per hardest part, scores, and ranks weak modules first', function () {
    $strong = Module::factory()->create(['title' => 'Modul Esai Kuat']);
    $weak = Module::factory()->create(['title' => 'Modul Wawancara Lemah']);

    ModuleFeedback::factory()->count(2)->create(['module_id' => $strong->id, 'helpful_score' => 5, 'difficulty' => FeedbackDifficulty::TidakAda]);
    ModuleFeedback::factory()->create(['module_id' => $weak->id, 'helpful_score' => 2, 'difficulty' => FeedbackDifficulty::Praktik, 'low_score_reason' => 'Instruksi praktiknya membingungkan.']);
    ModuleFeedback::factory()->create(['module_id' => $weak->id, 'helpful_score' => 3, 'difficulty' => FeedbackDifficulty::Praktik, 'low_score_reason' => 'Contoh jawabannya kurang banyak.']);
    ModuleFeedback::factory()->legacy()->create(['module_id' => $weak->id]);
    ModuleFeedback::factory()->create(['module_id' => $strong->id, 'helpful_score' => 1, 'updated_at' => now()->subDays(40)]);

    $summary = app(ModuleFeedbackInsightsServiceInterface::class)->summary(30);

    $praktik = collect($summary->difficulties)->firstWhere('difficulty', FeedbackDifficulty::Praktik);

    expect($summary->total)->toBe(4)
        ->and($summary->average_score)->toBe(3.8)
        ->and($summary->low_score_count)->toBe(2)
        ->and($summary->lowScorePercentage())->toBe(50)
        ->and($summary->score_distribution)->toBe([1 => 0, 2 => 1, 3 => 1, 4 => 0, 5 => 2])
        ->and($praktik)->toMatchArray(['count' => 2, 'percentage' => 50])
        ->and($summary->difficulties[0]['difficulty'])->toBe(FeedbackDifficulty::Praktik)
        ->and(end($summary->difficulties)['difficulty'])->toBe(FeedbackDifficulty::TidakAda)
        ->and($summary->modules[0])->toMatchArray([
            'title' => 'Modul Wawancara Lemah',
            'total' => 2,
            'average_score' => 2.5,
            'low_score_count' => 2,
            'top_difficulty' => FeedbackDifficulty::Praktik,
        ])
        ->and($summary->recent_low_scores)->toHaveCount(2);

    expect(app(ModuleFeedbackInsightsServiceInterface::class)->summary(0, $strong->id)->total)->toBe(3);
});

test('the dashboard widget shows the summary and filters by module', function () {
    $module = Module::factory()->create(['title' => 'Modul Wawancara Lemah']);
    $other = Module::factory()->create(['title' => 'Modul Esai Kuat']);

    ModuleFeedback::factory()->create(['module_id' => $module->id, 'helpful_score' => 2, 'difficulty' => FeedbackDifficulty::AiMentor, 'low_score_reason' => 'Jawaban AI Mentor terlalu umum buatku.']);
    ModuleFeedback::factory()->create(['module_id' => $other->id, 'helpful_score' => 5]);

    Livewire::actingAs(feedbackSummaryAdmin())
        ->test(ModuleFeedbackSummary::class)
        ->assertSee('Ringkasan Feedback Modul')
        ->assertSee('Bagian yang paling menyulitkan')
        ->assertSee('Modul yang paling perlu perhatian')
        ->assertSee('Jawaban AI Mentor terlalu umum buatku.')
        ->set('module', (string) $other->id)
        ->assertDontSee('Jawaban AI Mentor terlalu umum buatku.')
        ->assertSee('Tidak ada feedback dengan skor rendah pada rentang ini.');
});

test('the widget shows an empty state and is hidden from admins without access', function () {
    Livewire::actingAs(feedbackSummaryAdmin())
        ->test(ModuleFeedbackSummary::class)
        ->assertSee('Belum ada feedback modul pada rentang ini.');

    $this->actingAs(User::factory()->create());

    expect(ModuleFeedbackSummary::canView())->toBeFalse();
});
