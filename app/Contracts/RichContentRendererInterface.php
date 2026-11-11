<?php

namespace App\Contracts;

/**
 * Turns curriculum Markdown (Inti Materi, Ringkasan, Kata Pengantar) into
 * safe, brand-styled HTML for the `.sk-prose` content style.
 */
interface RichContentRendererInterface
{
    /**
     * Render Markdown to sanitised HTML.
     *
     * Raw HTML in the source is stripped, unsafe links (javascript:, data:)
     * are dropped and external links get rel="noopener noreferrer". Plain
     * text without markup keeps its paragraphs and line breaks, so legacy
     * material renders as before.
     *
     * Markdown headings are shifted so they nest under the page outline:
     * `#` becomes an <h{$heading_level}> with class "sk-h1", `##` the next
     * level with "sk-h2", and `###` or deeper "sk-h3" (capped at <h6>).
     *
     * @param  string|null  $markdown  Stored material; null or blank yields ''.
     * @param  int  $heading_level  HTML level used for a Markdown `#` (2-6).
     * @return string HTML that is safe to echo unescaped.
     */
    public function toHtml(?string $markdown, int $heading_level = 4): string;
}
