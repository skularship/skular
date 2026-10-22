<?php

namespace App\Livewire\Scholarships;

use App\Contracts\ScholarshipApplicationServiceInterface;
use App\Contracts\ScholarshipMatchingServiceInterface;
use App\Data\ScholarshipMatchData;
use App\Data\UserProfileData;
use App\Models\User;
use App\Services\ScholarshipApplicationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * @property-read Collection<int, ScholarshipMatchData> $matches
 */
#[Title('Rekomendasi Beasiswa')]
class Recommendation extends Component
{
    /**
     * Scholarship ids selected by the mentee.
     *
     * @var array<int, int>
     */
    public array $selected = [];

    /**
     * The scholarship chosen as primary.
     */
    public ?int $primary = null;

    public function mount(): void
    {
        $user = $this->user();

        $this->selected = array_values(array_map(
            fn (mixed $id): int => (int) $id,
            $user->scholarshipApplications()->pluck('scholarship_id')->all(),
        ));

        $primary = $user->scholarshipApplications()->where('is_primary', true)->value('scholarship_id');

        $this->primary = is_numeric($primary) ? (int) $primary : null;
    }

    /**
     * Store the mentee's selection and open the dashboard.
     */
    public function confirmSelection(ScholarshipApplicationServiceInterface $application_service): void
    {
        $user = $this->user();

        if ($this->selected === []) {
            $this->addError('selected', 'Pilih minimal satu beasiswa untuk memulai roadmap belajarmu.');

            return;
        }

        // The backend cap is re-checked here so the mentee gets a clear message
        // before the request is stored; the service still enforces it.
        $limit = ScholarshipApplicationService::MAX_SELECTIONS;

        if (count(array_unique($this->selected)) > $limit) {
            $this->addError('selected', __('Kamu hanya boleh memilih maksimal :count beasiswa.', ['count' => $limit]));

            return;
        }

        $existing = $user->scholarshipApplications()->pluck('scholarship_id')->all();
        $new = array_diff(array_unique($this->selected), $existing);

        if (count($existing) + count($new) > $limit) {
            $this->addError('selected', __('Kamu sudah punya :count beasiswa. Maksimal :limit beasiswa per mentee.', [
                'count' => count($existing),
                'limit' => $limit,
            ]));

            return;
        }

        if ($application_service->unavailableIds($this->selected) !== []) {
            $this->addError('selected', __('Beasiswa yang sudah tidak dibuka untuk pendaftaran tidak bisa dipilih. Hapus pilihan tersebut lalu simpan lagi.'));

            return;
        }

        $added = $application_service->select($user, $this->selected, $this->primary);

        if ($added === 0) {
            $this->dispatch('toast', variant: 'info', text: __('Pilihanmu sudah tersimpan sebelumnya.'));
        } else {
            $this->dispatch('toast', variant: 'success', text: __(':count beasiswa berhasil ditambahkan ke dashboardmu.', ['count' => $added]));
        }

        $this->redirect(route('dashboard'), navigate: true);
    }

    /**
     * Mark a scholarship as the primary choice.
     */
    public function makePrimary(int $scholarship_id): void
    {
        $this->primary = $scholarship_id;

        if (! in_array($scholarship_id, $this->selected, true)) {
            $this->selected[] = $scholarship_id;
        }
    }

    /**
     * Get the matched scholarships for the mentee profile.
     *
     * @return Collection<int, ScholarshipMatchData>
     */
    #[Computed]
    public function matches(): Collection
    {
        return app(ScholarshipMatchingServiceInterface::class)->match(
            UserProfileData::fromUser($this->user()->loadMissing('cluster')),
        );
    }

    public function render(): View
    {
        return view('livewire.scholarships.recommendation', [
            'matches' => $this->matches,
        ]);
    }

    /**
     * Get the authenticated mentee.
     */
    private function user(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
