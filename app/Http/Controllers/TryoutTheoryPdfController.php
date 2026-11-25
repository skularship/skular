<?php

namespace App\Http\Controllers;

use App\Contracts\ActivityTrackerInterface;
use App\Contracts\EntitlementServiceInterface;
use App\Enums\ActivityName;
use App\Models\Tryout;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Feeds the theory PDF of a Try Out to the in-page viewer (resources/js/pdf-viewer.js),
 * Pro only. The PDF is never offered as a download: the request must come
 * from the viewer (X-Skular-Viewer header, so opening the URL in the address
 * bar returns 404 instead of the browser's PDF viewer with its save button),
 * and the body is XOR-masked with a fresh key sent in X-Skular-Key, so the
 * network response is not a usable .pdf file. Nothing is cached.
 */
class TryoutTheoryPdfController extends Controller
{
    /**
     * Header the viewer sends with its fetch().
     */
    public const VIEWER_HEADER = 'X-Skular-Viewer';

    /**
     * Response header carrying the base64 XOR key.
     */
    public const KEY_HEADER = 'X-Skular-Key';

    /**
     * Length in bytes of the per-request mask key.
     */
    private const KEY_BYTES = 32;

    /**
     * Return the masked PDF bytes for the viewer.
     */
    public function __invoke(Request $request, Tryout $tryout, EntitlementServiceInterface $entitlements, ActivityTrackerInterface $tracker): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);
        abort_unless($tryout->status->isPublished(), 404);
        abort_unless($entitlements->can($user, 'tryout_theory'), 403);
        abort_unless($request->header(self::VIEWER_HEADER) === '1', 404);

        $disk = Storage::disk((string) config('tryout.theory_pdf.disk', 'local'));
        $path = (string) $tryout->theory_pdf_path;

        abort_unless($path !== '' && $disk->exists($path), 404);

        $bytes = (string) $disk->get($path);
        $key = random_bytes(self::KEY_BYTES);
        $mask = str_repeat($key, intdiv(strlen($bytes), self::KEY_BYTES) + 1);

        $tracker->track(ActivityName::TryoutTheoryPdfViewed, ['tryout_id' => $tryout->id], $user);

        return response($bytes ^ substr($mask, 0, strlen($bytes)), 200, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow',
            self::KEY_HEADER => base64_encode($key),
        ]);
    }
}
