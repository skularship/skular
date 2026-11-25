<?php

namespace App\Http\Controllers;

use App\Contracts\ActivityTrackerInterface;
use App\Contracts\IqAttemptServiceInterface;
use App\Contracts\IqResultServiceInterface;
use App\Enums\ActivityName;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Feeds the Tes IQ material slides (PDF) to the in-page viewer
 * (resources/js/pdf-viewer.js), paid access only. Same protection as
 * TryoutTheoryPdfController: only fetch() calls with the viewer header get
 * an answer, the bytes are XOR-masked with a fresh key, nothing is cached,
 * so the slides are never offered as a download.
 */
class IqMaterialPdfController extends Controller
{
    /**
     * Length in bytes of the per-request mask key.
     */
    private const KEY_BYTES = 32;

    /**
     * Return the masked PDF bytes for the viewer.
     */
    public function __invoke(Request $request, IqAttemptServiceInterface $attempts, IqResultServiceInterface $results, ActivityTrackerInterface $tracker): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);
        abort_unless($results->canSeeResults($user), 403);
        abort_unless($request->header(TryoutTheoryPdfController::VIEWER_HEADER) === '1', 404);

        $test = $attempts->currentTest();
        $disk = Storage::disk((string) config('iq.material_pdf.disk', 'local'));
        $path = (string) $test?->material_pdf_path;

        abort_unless($path !== '' && $disk->exists($path), 404);

        $bytes = (string) $disk->get($path);
        $key = random_bytes(self::KEY_BYTES);
        $mask = str_repeat($key, intdiv(strlen($bytes), self::KEY_BYTES) + 1);

        $tracker->track(ActivityName::IqMaterialViewed, ['iq_test_id' => $test?->id], $user);

        return response($bytes ^ substr($mask, 0, strlen($bytes)), 200, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
            TryoutTheoryPdfController::KEY_HEADER => base64_encode($key),
        ]);
    }
}
