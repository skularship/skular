<?php

namespace App\Services;

use App\Contracts\RichContentRendererInterface;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\MarkdownConverter;

/**
 * CommonMark-based renderer for curriculum material.
 *
 * Bound as a singleton: the CommonMark environment is built once per
 * request and every rendered text is memoised, so a Pahami page with many
 * Inti Materi columns converts each text only once. A shared cache is not
 * used on purpose: with the database cache store a cache hit costs a query,
 * which is slower than converting a few kilobytes of Markdown.
 */
class RichContentRenderer implements RichContentRendererInterface
{
    /**
     * Rendered HTML of this request, keyed by heading level and source hash.
     *
     * @var array<string, string>
     */
    private array $rendered = [];

    private ?MarkdownConverter $converter = null;

    /**
     * Render Markdown to sanitised HTML.
     *
     * @param  string|null  $markdown  Stored material; null or blank yields ''.
     * @param  int  $heading_level  HTML level used for a Markdown `#` (2-6).
     * @return string HTML that is safe to echo unescaped.
     */
    public function toHtml(?string $markdown, int $heading_level = 4): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", (string) $markdown));

        if ($text === '') {
            return '';
        }

        $heading_level = max(2, min(6, $heading_level));
        $key = $heading_level.':'.hash('xxh128', $text);

        return $this->rendered[$key] ??= $this->shiftHeadings(
            $this->converter()->convert($text)->getContent(),
            $heading_level,
        );
    }

    /**
     * Map <h1>-<h6> from Markdown onto the page outline and add the brand classes.
     */
    private function shiftHeadings(string $html, int $heading_level): string
    {
        return (string) preg_replace_callback(
            '/<(\/?)h([1-6])>/',
            function (array $match) use ($heading_level): string {
                $depth = min(3, (int) $match[2]);
                $tag = 'h'.min(6, $heading_level + $depth - 1);

                return $match[1] === '/' ? "</{$tag}>" : "<{$tag} class=\"sk-h{$depth}\">";
            },
            $html,
        );
    }

    /**
     * Build the CommonMark converter once.
     */
    private function converter(): MarkdownConverter
    {
        if ($this->converter instanceof MarkdownConverter) {
            return $this->converter;
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        $environment = new Environment([
            // Never trust HTML stored in the database.
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
            // Legacy material is plain text typed in a textarea: a single
            // line break must stay a line break, not merge into one line.
            'renderer' => ['soft_break' => "<br>\n"],
            'external_link' => [
                'internal_hosts' => is_string($host) ? [$host] : [],
                'open_in_new_window' => true,
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new ExternalLinkExtension);

        return $this->converter = new MarkdownConverter($environment);
    }
}
