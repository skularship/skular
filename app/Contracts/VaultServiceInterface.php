<?php

namespace App\Contracts;

use App\Models\ChecklistItem;
use App\Models\ScholarshipApplication;
use App\Models\User;
use App\Models\VaultDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

/**
 * Document vault and per-scholarship checklist for Pro mentees.
 */
interface VaultServiceInterface
{
    /**
     * Store an uploaded file in the mentee's private vault.
     *
     * @throws \App\Exceptions\VaultException When the plan, file count or file size does not allow it.
     */
    public function store(User $user, UploadedFile $file, string $title): VaultDocument;

    /**
     * Delete a document and its file; checklist items that used it are detached.
     */
    public function delete(VaultDocument $document): void;

    /**
     * Create the default checklist for an application the first time it is opened.
     *
     * @return Collection<int, ChecklistItem>
     */
    public function checklistFor(ScholarshipApplication $application): Collection;
}
