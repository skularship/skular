<?php

namespace App\Livewire\Scholarships;

use App\Enums\EducationLevel;
use App\Enums\LocationScope;
use App\Enums\ScholarshipStatus;
use App\Models\Scholarship;
use App\Models\User;
use App\Services\ContentCache;
use App\Services\ScholarshipApplicationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * A mentee-facing catalog of every scholarship they can still prepare for.
 *
 * Draft and archived scholarships are never listed. Closed scholarships are
 * hidden by default and can be revealed with an explicit filter.
 */
#[Title('Semua Beasiswa')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'jenjang', except: '')]
    public string $level = '';

    #[Url(as: 'cakupan', except: '')]
    public string $scope = '';

    #[Url(as: 'lokasi', except: '')]
    public string $location = '';

    #[Url(as: 'ditutup', except: false)]
    public bool $include_closed = false;

    #[Url(as: 'urut', except: 'deadline')]
    public string $sort = 'deadline';

    /**
     * Reset to the first page whenever a filter changes.
     */
    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'level', 'scope', 'location', 'include_closed', 'sort'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->level = '';
        $this->scope = '';
        $this->location = '';
        $this->include_closed = false;
        $this->sort = 'deadline';
        $this->resetPage();
    }

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $selected_ids = array_map('intval', $user->scholarshipApplications()->pluck('scholarship_id')->all());
        $selected_count = count($selected_ids);

        return view('livewire.scholarships.index', [
            'scholarships' => $this->query()->paginate(9),
            'levels' => EducationLevel::cases(),
            'scopes' => LocationScope::cases(),
            'locations' => $this->locations(),
            'selected_ids' => $selected_ids,
            'selected_count' => $selected_count,
            'max_selections' => ScholarshipApplicationService::MAX_SELECTIONS,
            'at_limit' => $selected_count >= ScholarshipApplicationService::MAX_SELECTIONS,
        ]);
    }

    /**
     * Build the filtered, sorted scholarship query.
     *
     * @return Builder<Scholarship>
     */
    private function query(): Builder
    {
        return Scholarship::query()
            ->whereIn('status', $this->include_closed
                ? [ScholarshipStatus::Active, ScholarshipStatus::Closed]
                : [ScholarshipStatus::Active])
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';

                $query->where(function (Builder $inner) use ($term): void {
                    $inner->where('scholarship_name', 'like', $term)
                        ->orWhere('institution', 'like', $term);
                });
            })
            ->when($this->level !== '', fn (Builder $query) => $query->where('level', $this->level))
            ->when($this->scope !== '', fn (Builder $query) => $query->where('scope', $this->scope))
            ->when($this->location !== '', fn (Builder $query) => $query->where('location', $this->location))
            ->when($this->sort === 'name', fn (Builder $query) => $query->orderBy('scholarship_name'))
            ->when($this->sort === 'deadline', fn (Builder $query) => $query
                ->orderByRaw('closing_date is null')
                ->orderBy('closing_date'))
            ->orderBy('id');
    }

    /**
     * Get the distinct locations offered for the location filter.
     *
     * @return array<int, string>
     */
    private function locations(): array
    {
        /** @var array<int, string> $locations */
        $locations = ContentCache::remember(
            'scholarship-locations',
            fn (): array => Scholarship::query()
                ->whereNotNull('location')
                ->where('location', '!=', '')
                ->distinct()
                ->orderBy('location')
                ->pluck('location')
                ->all(),
        );

        return $locations;
    }
}
