<?php

namespace App\Http\Controllers;

use App\Models\ScholarshipApplication;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the private submission screenshot a mentee uploaded as proof.
 *
 * The file lives on the local (non public) disk and is only ever sent to the
 * mentee who owns the application or to an admin holding the
 * `View:ScholarshipApplication` permission, so proofs never become publicly
 * reachable.
 */
class SubmissionProofController extends Controller
{
    /**
     * Download the submission proof of the authenticated mentee.
     */
    public function show(ScholarshipApplication $application): StreamedResponse
    {
        $user = Auth::user();

        $owns_application = $user instanceof User && $application->user_id === $user->id;
        $can_review_application = $user instanceof User && $user->can('View:ScholarshipApplication');

        abort_unless($owns_application || $can_review_application, 403);
        abort_unless($application->hasSubmissionProof(), 404);

        $path = (string) $application->submission_proof;

        abort_unless(Storage::disk('local')->exists($path), 404);

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        $name = 'bukti-pendaftaran-'.$application->id.($extension !== '' ? '.'.$extension : '');

        return Storage::disk('local')->download($path, $name);
    }
}
