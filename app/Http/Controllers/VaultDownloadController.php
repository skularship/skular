<?php

namespace App\Http\Controllers;

use App\Models\VaultDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sends a vault file to its owner only. Files live on the private disk and have no public URL.
 */
class VaultDownloadController extends Controller
{
    /**
     * Download the document, or 404 when it belongs to someone else or the file is gone.
     */
    public function __invoke(Request $request, VaultDocument $document): StreamedResponse
    {
        abort_unless(config('pricing.enabled'), 404);
        abort_unless($document->user_id === $request->user()?->id, 404);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }
}
