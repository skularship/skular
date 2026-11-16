<?php

namespace App\Livewire\Checklist;

use App\Contracts\EntitlementServiceInterface;
use App\Contracts\VaultServiceInterface;
use App\Models\ChecklistItem;
use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Checklist berkas for one chosen scholarship. Owner only, Pro only; Gratis sees it locked.
 */
#[Title('Checklist Berkas')]
class Show extends Component
{
    public ScholarshipApplication $application;

    public string $new_label = '';

    /**
     * Load the application, only if it belongs to the mentee.
     */
    public function mount(ScholarshipApplication $application): void
    {
        abort_unless(config('pricing.enabled'), 404);
        abort_unless($application->user_id === $this->user()->id, 404);

        $this->application = $application->load('scholarship');
    }

    /**
     * Tick or untick an item.
     */
    public function toggle(int $item_id, EntitlementServiceInterface $entitlements): void
    {
        $item = $this->item($item_id, $entitlements);
        $item->update(['is_done' => ! $item->is_done]);
    }

    /**
     * Add a custom item.
     */
    public function add(EntitlementServiceInterface $entitlements): void
    {
        abort_unless($entitlements->can($this->user(), 'checklist'), 403);

        $this->validate(['new_label' => ['required', 'string', 'max:120']], ['new_label.required' => __('Tulis nama berkasnya dulu.')]);

        $position = (int) $this->application->checklistItems()->max('position') + 1;
        $this->application->checklistItems()->create(['label' => $this->new_label, 'position' => $position]);

        $this->reset('new_label');
    }

    /**
     * Remove an item.
     */
    public function remove(int $item_id, EntitlementServiceInterface $entitlements): void
    {
        $this->item($item_id, $entitlements)->delete();
    }

    /**
     * Attach one of the mentee's vault documents to an item, or detach it with null.
     */
    public function attach(int $item_id, ?int $document_id, EntitlementServiceInterface $entitlements): void
    {
        $item = $this->item($item_id, $entitlements);

        if ($document_id !== null) {
            abort_unless($this->user()->vaultDocuments()->whereKey($document_id)->exists(), 404);
        }

        $item->update(['vault_document_id' => $document_id]);
    }

    /**
     * Render the checklist, or the locked preview for Gratis.
     *
     * @return View The livewire.checklist.show view.
     */
    public function render(EntitlementServiceInterface $entitlements, VaultServiceInterface $vault): View
    {
        $unlocked = $entitlements->can($this->user(), 'checklist');

        return view('livewire.checklist.show', [
            'unlocked' => $unlocked,
            'items' => $unlocked ? $vault->checklistFor($this->application) : collect(),
            'documents' => $this->user()->vaultDocuments()->latest('id')->get(),
        ]);
    }

    /**
     * Find one of this application's items, refusing the action when the plan does not include it.
     */
    private function item(int $item_id, EntitlementServiceInterface $entitlements): ChecklistItem
    {
        abort_unless($entitlements->can($this->user(), 'checklist'), 403);

        return $this->application->checklistItems()->findOrFail($item_id);
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
