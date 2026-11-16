<?php

namespace App\Console\Commands;

use App\Enums\LegalDocument;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Go-live guard: renders every legal page and reports the "[ISI" and
 * "[KEPUTUSAN" markers still on it. Fails in production (or with --strict)
 * so the pages cannot ship with undecided facts.
 */
class CheckLegalPages extends Command
{
    /**
     * @var string
     */
    protected $signature = 'legal:check {--strict : Fail on markers outside production too}';

    /**
     * @var string
     */
    protected $description = 'Check the rendered legal pages for unfilled [ISI]/[KEPUTUSAN] markers';

    /**
     * Render each legal page and look for placeholder markers.
     *
     * @return int SUCCESS when no marker is left, or outside production without --strict.
     */
    public function handle(Kernel $kernel): int
    {
        $markers = (array) config('legal.placeholder_markers');
        $found = [];

        // Render without persisting a session row for these internal requests.
        config(['session.driver' => 'array']);

        foreach (LegalDocument::cases() as $document) {
            $request = Request::create('/'.$document->path());
            $response = $kernel->handle($request);
            $html = (string) $response->getContent();
            $kernel->terminate($request, $response);

            if ($response->getStatusCode() !== 200) {
                $found[] = "/{$document->path()}: status {$response->getStatusCode()}";

                continue;
            }

            foreach ($this->markersIn($html, $markers) as $marker) {
                $found[] = "/{$document->path()}: {$marker}";
            }
        }

        if ($found === []) {
            $this->info('Halaman legal bersih dari penanda [ISI]/[KEPUTUSAN].');

            return self::SUCCESS;
        }

        foreach (array_unique($found) as $line) {
            $this->line($line);
        }

        $must_fail = app()->environment('production') || (bool) $this->option('strict');

        if ($must_fail) {
            $this->error('Halaman legal masih memuat penanda yang belum diisi. Isi config/legal.php (env LEGAL_*) sebelum tayang.');

            return self::FAILURE;
        }

        $this->warn('Penanda di atas wajib diisi sebelum production.');

        return self::SUCCESS;
    }

    /**
     * Every distinct marker text in the page, e.g. "[ISI: email privasi]".
     *
     * @param  array<int, mixed>  $markers
     * @return array<int, string>
     */
    private function markersIn(string $html, array $markers): array
    {
        $hits = [];

        foreach ($markers as $marker) {
            $marker = (string) $marker;
            $offset = 0;

            while (($position = strpos($html, $marker, $offset)) !== false) {
                $hits[] = Str::before(substr($html, $position, 120), ']').']';
                $offset = $position + strlen($marker);
            }
        }

        return array_values(array_unique($hits));
    }
}
