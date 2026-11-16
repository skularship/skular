<?php

namespace App\Livewire\Vault;

use App\Contracts\EntitlementServiceInterface;
use App\Contracts\VaultServiceInterface;
use App\Exceptions\VaultException;
use App\Models\User;
use App\Models\VaultDocument;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * "Vault Dokumen": private storage for application documents. Pro can add files;
 * Gratis sees the page locked with an upgrade button, and a mentee whose Pro ended
 * can still download and delete what is already there.
 */
#[Title('Vault Dokumen')]
class Index extends Component
{
    use WithFileUploads;

    /** @var TemporaryUploadedFile|null */
    public $file;

    public string $title = '';

    /**
     * Hide the page while Premium is switched off.
     */
    public function mount(): void
    {
        abort_unless(config('pricing.enabled'), 404);
    }

    /**
     * Validate and store the chosen file in the vault.
     */
    public function save(VaultServiceInterface $vault): void
    {
        $user = $this->user();

        $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'file' => ['required', 'file', 'mimes:'.implode(',', (array) config('plans.vault.mimes')), 'max:'.(int) config('plans.vault.max_kb')],
        ], [
            'title.required' => __('Beri nama dokumen dulu.'),
            'file.required' => __('Pilih file dulu.'),
            'file.mimes' => __('Format file harus PDF, JPG, PNG, DOC, atau DOCX.'),
            'file.max' => __('Ukuran file maksimal :mb MB.', ['mb' => (int) config('plans.vault.max_kb') / 1024]),
        ]);

        try {
            $vault->store($user, $this->file, $this->title);
        } catch (VaultException $exception) {
            $this->addError('file', $exception->getMessage());

            return;
        }

        $this->reset('file', 'title');
    }

    /**
     * Delete one of the mentee's own documents.
     */
    public function delete(int $document_id, VaultServiceInterface $vault): void
    {
        $document = VaultDocument::query()->where('user_id', $this->user()->id)->findOrFail($document_id);

        $vault->delete($document);
    }

    /**
     * Render the vault and the mentee's scholarships (links to their checklists).
     *
     * @return View The livewire.vault.index view.
     */
    public function render(EntitlementServiceInterface $entitlements): View
    {
        $user = $this->user();

        return view('livewire.vault.index', [
            'can_upload' => $entitlements->can($user, 'vault'),
            'documents' => $user->vaultDocuments()->latest('id')->get(),
            'applications' => $user->scholarshipApplications()->with('scholarship')->orderByDesc('is_primary')->orderBy('id')->get(),
            'max_files' => (int) config('plans.vault.max_files'),
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
