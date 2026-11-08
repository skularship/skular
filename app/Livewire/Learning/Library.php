<?php

namespace App\Livewire\Learning;

use App\Contracts\LearningPathServiceInterface;
use App\Models\Checkpoint;
use App\Models\Module;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The Perpustakaan: optional training modules open to every mentee,
 * independent of the scholarship roadmap.
 */
#[Title('Perpustakaan')]
class Library extends Component
{
    /**
     * Render the optional Perpustakaan module library (route library.index).
     *
     * @return View The livewire.learning.library view.
     */
    public function render(LearningPathServiceInterface $learning_path): View
    {
        $user = $this->user();

        $modules = $learning_path->libraryModules();

        // Library progress is read-only here: opening the catalog must never
        // create progress rows or touch the scholarship path. Only the
        // checkpoints of the catalogised modules are read, not the whole
        // checkpoint history of the mentee.
        /** @var array<int, Checkpoint> $checkpoints */
        $checkpoints = $user->checkpoints()
            ->whereIn('module_id', $modules->pluck('id')->all())
            ->get()
            ->keyBy('module_id')
            ->all();

        return view('livewire.learning.library', [
            'modules' => $modules->map(fn (Module $module): array => [
                'module' => $module,
                'checkpoint' => $checkpoints[$module->id] ?? null,
            ]),
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
