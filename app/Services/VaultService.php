<?php

namespace App\Services;

use App\Contracts\EntitlementServiceInterface;
use App\Contracts\VaultServiceInterface;
use App\Exceptions\VaultException;
use App\Models\ChecklistItem;
use App\Models\ScholarshipApplication;
use App\Models\User;
use App\Models\VaultDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Stores mentee documents on the private disk and builds the default checklist.
 * Uploading needs an active Pro plan; reading and deleting stay possible after Pro ends.
 */
class VaultService implements VaultServiceInterface
{
    /**
     * Inject the plan rules.
     */
    public function __construct(private readonly EntitlementServiceInterface $entitlements) {}

    /**
     * Store an uploaded file in the mentee's private vault.
     *
     * @throws VaultException When the plan or the file count does not allow it.
     */
    public function store(User $user, UploadedFile $file, string $title): VaultDocument
    {
        if (! $this->entitlements->can($user, 'vault')) {
            throw VaultException::proRequired();
        }

        $max = (int) config('plans.vault.max_files');

        if (VaultDocument::query()->where('user_id', $user->id)->count() >= $max) {
            throw VaultException::limitReached($max);
        }

        $path = $file->store('vault/'.$user->id, 'local');

        return VaultDocument::query()->create([
            'user_id' => $user->id,
            'title' => $title,
            'path' => (string) $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size_kb' => (int) ceil($file->getSize() / 1024),
        ]);
    }

    /**
     * Delete a document and its file; checklist items that used it are detached.
     */
    public function delete(VaultDocument $document): void
    {
        Storage::disk('local')->delete($document->path);

        $document->delete();
    }

    /**
     * Create the default checklist for an application the first time it is opened.
     *
     * @return Collection<int, ChecklistItem>
     */
    public function checklistFor(ScholarshipApplication $application): Collection
    {
        if (! $application->checklistItems()->exists()) {
            $labels = [
                __('Formulir pendaftaran terisi'),
                __('CV terbaru'),
                __('Esai atau motivation letter'),
                __('Transkrip nilai'),
                __('KTP atau kartu identitas'),
                __('Surat rekomendasi'),
            ];

            if ($application->scholarship->sktm_require) {
                $labels[] = __('SKTM atau bukti kondisi ekonomi');
            }

            foreach (array_values($labels) as $position => $label) {
                $application->checklistItems()->create(['label' => $label, 'position' => $position]);
            }
        }

        return $application->checklistItems()->with('document')->orderBy('position')->orderBy('id')->get();
    }
}
