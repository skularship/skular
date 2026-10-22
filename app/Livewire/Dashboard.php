<?php

namespace App\Livewire;

use App\Contracts\LearningPathServiceInterface;
use App\Contracts\ProgressServiceInterface;
use App\Contracts\ScholarshipApplicationServiceInterface;
use App\Contracts\ScholarshipPathServiceInterface;
use App\Data\ProgressSummaryData;
use App\Enums\ApplicationStatus;
use App\Models\ScholarshipApplication;
use App\Models\User;
use App\Services\ScholarshipApplicationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The mentee home: what they are preparing, what to do next, and how far they are.
 *
 * All numbers come from ProgressService, which scopes Inti progress to the
 * mentee and Template progress to the active scholarship.
 *
 * @property-read array<string, mixed> $next_action
 */
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render(
        ProgressServiceInterface $progress_service,
        ScholarshipApplicationServiceInterface $application_service,
        ScholarshipPathServiceInterface $path_service,
        LearningPathServiceInterface $learning_path,
    ): View {
        $user = $this->user();

        // Load the mentee queue once; active, next, history and counts are all
        // derived from this collection instead of querying the same rows again.
        $queue = $application_service->orderedApplications($user);
        $active = $application_service->activeApplicationFrom($queue);
        $next = $path_service->nextApplicationFrom($queue, $active);
        $history = $path_service->historyFrom($queue);

        // One curriculum + checkpoint + fase read for both the summary and the
        // roadmap.
        $overview = $progress_service->overview($user, $active?->scholarship);
        $summary = $overview->summary;

        // A path is only finished when a scholarship is actually in focus.
        $path_completed = $active instanceof ScholarshipApplication && $summary->isFinished();
        $selected_count = $queue->count();

        /** @var array<int, string> $learning_states */
        $learning_states = [];

        foreach ($queue as $application) {
            $learning_states[$application->id] = match (true) {
                $application->hasCompletedLearning() => 'completed',
                $active instanceof ScholarshipApplication && $application->id === $active->id => 'active',
                default => 'queued',
            };
        }

        return view('livewire.dashboard', [
            'user' => $user,
            'summary' => $summary,
            'roadmap' => $overview->roadmap,
            'queue' => $queue,
            'learning_states' => $learning_states,
            'active_application' => $active,
            'next_application' => $next,
            'next_templates' => $next instanceof ScholarshipApplication
                ? $learning_path->templateModulesFor($next->scholarship)->take(3)
                : new Collection,
            'path_completed' => $path_completed,
            'completed_paths' => $history,
            'completed_applications' => $queue
                ->where('status', ApplicationStatus::ResultReported)
                ->values(),
            'selected_count' => $selected_count,
            'max_selections' => ScholarshipApplicationService::MAX_SELECTIONS,
            'at_limit' => $selected_count >= ScholarshipApplicationService::MAX_SELECTIONS,
            'next_action' => $this->nextAction($user, $summary, $active, $next, $path_completed, $history->isNotEmpty()),
        ]);
    }

    /**
     * Finish the active learning path and open the next queued scholarship.
     *
     * The backend validates completion again, so a stale button can never skip
     * an unfinished path. Repeating the call is a no-op.
     */
    public function advanceToNextScholarship(
        ScholarshipPathServiceInterface $path_service,
        ScholarshipApplicationServiceInterface $application_service,
    ): void {
        $user = $this->user();
        $active = $application_service->activeApplication($user);

        try {
            $next = $path_service->advance($user, $active?->id);
        } catch (ValidationException $exception) {
            $this->addError('transition', $exception->validator->errors()->first('path'));

            return;
        }

        if ($next instanceof ScholarshipApplication) {
            $this->dispatch(
                'toast',
                variant: 'success',
                text: __('Fondasi Inti-mu tetap tersimpan. Jalur :name sudah dibuka.', ['name' => $next->scholarship->scholarship_name]),
            );

            return;
        }

        $this->dispatch(
            'toast',
            variant: 'info',
            text: __('Semua jalur beasiswa yang kamu pilih sudah selesai. Fondasi belajarmu tetap tersimpan.'),
        );
    }

    /**
     * Derive the single most useful next step from real mentee state.
     *
     * Nothing here is hardcoded: every branch is driven by database status.
     *
     * @return array{icon: string, title: string, description: string, label: string|null, url: string|null, action: string|null, tone: string}
     */
    private function nextAction(
        User $user,
        ProgressSummaryData $summary,
        ?ScholarshipApplication $active,
        ?ScholarshipApplication $next,
        bool $path_completed,
        bool $has_completed_paths,
    ): array {
        if (! $user->hasCompletedProfile()) {
            return [
                'icon' => 'identification',
                'title' => __('Lengkapi profiling dulu'),
                'description' => __('Isi 8 langkah profiling supaya Skular bisa mencocokkanmu dengan beasiswa yang tepat.'),
                'label' => __('Mulai profiling'),
                'url' => route('profiling'),
                'action' => null,
                'tone' => 'brand',
            ];
        }

        if (! $active instanceof ScholarshipApplication) {
            if ($has_completed_paths) {
                return [
                    'icon' => 'check-badge',
                    'title' => __('Semua jalur beasiswa selesai'),
                    'description' => __('Tidak ada beasiswa lain di antreanmu. Fondasi dan riwayat belajarmu tetap tersimpan di Skular.'),
                    'label' => __('Lihat Beasiswa Saya'),
                    'url' => route('scholarships.tracker'),
                    'action' => null,
                    'tone' => 'teal',
                ];
            }

            return [
                'icon' => 'academic-cap',
                'title' => __('Pilih beasiswa untuk disiapkan'),
                'description' => __('Belum ada beasiswa aktif. Pilih maksimal :count beasiswa lalu mulai belajar.', ['count' => ScholarshipApplicationService::MAX_SELECTIONS]),
                'label' => __('Lihat rekomendasi'),
                'url' => route('profiling.recommendation'),
                'action' => null,
                'tone' => 'brand',
            ];
        }

        if ($path_completed) {
            if ($next instanceof ScholarshipApplication) {
                return [
                    'icon' => 'arrow-trending-up',
                    'title' => __('Persiapan :name selesai', ['name' => $active->scholarship->scholarship_name]),
                    'description' => __('Fondasi belajarmu tetap tersimpan. Lanjutkan ke :next tanpa mengulang Modul Inti.', ['next' => $next->scholarship->scholarship_name]),
                    'label' => __('Lanjut ke beasiswa berikutnya'),
                    'url' => null,
                    'action' => 'advance',
                    'tone' => 'teal',
                ];
            }

            return [
                'icon' => 'check-badge',
                'title' => __('Semua jalur beasiswa selesai'),
                'description' => __('Tidak ada beasiswa lain di antreanmu. Fondasi dan riwayat belajarmu tetap tersimpan di Skular.'),
                'label' => __('Lihat Beasiswa Saya'),
                'url' => route('scholarships.tracker'),
                'action' => null,
                'tone' => 'teal',
            ];
        }

        if ($summary->current_module_id !== null) {
            return [
                'icon' => 'play',
                'title' => __('Lanjutkan modul :module', ['module' => $summary->current_module_title]),
                'description' => __('Kerjakan modul berikutnya pada jalur :name.', ['name' => $active->scholarship->scholarship_name]),
                'label' => __('Lanjutkan belajar'),
                'url' => route('modules.show', $summary->current_module_id),
                'action' => null,
                'tone' => 'brand',
            ];
        }

        if ($active->status === ApplicationStatus::Submitted) {
            return [
                'icon' => 'flag',
                'title' => __('Laporkan hasil :name', ['name' => $active->scholarship->scholarship_name]),
                'description' => __('Pendaftaranmu sudah dikonfirmasi. Begitu pengumuman keluar, laporkan hasilnya di halaman Beasiswa Saya.'),
                'label' => __('Buka Beasiswa Saya'),
                'url' => route('scholarships.tracker'),
                'action' => null,
                'tone' => 'teal',
            ];
        }

        return [
            'icon' => 'sparkles',
            'title' => __('Siapkan berkas beasiswamu'),
            'description' => __('Periksa kembali dokumen dan jadwal pendaftaran beasiswa aktifmu.'),
            'label' => __('Lihat detail beasiswa'),
            'url' => route('scholarships.show', $active->scholarship),
            'action' => null,
            'tone' => 'teal',
        ];
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
