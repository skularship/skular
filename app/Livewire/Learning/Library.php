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
    public function render(LearningPathServiceInterface $learning_path): View
    {
        $user = $this->user();

        // Library progress is read-only here: opening the catalog must never
        // create progress rows or touch the scholarship path.
        /** @var array<int, Checkpoint|null> $checkpoints */
        $checkpoints = $user->checkpoints()->get()->keyBy('module_id')->all();

        $modules = $learning_path->libraryModules()->map(fn (Module $module): array => [
            'module' => $module,
            'checkpoint' => $checkpoints[$module->id] ?? null,
        ]);

        return view('livewire.learning.library', [
            'modules' => $modules,
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
