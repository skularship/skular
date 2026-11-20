<?php

namespace App\Contracts;

use App\Enums\MaterialFormat;

/**
 * Turns curriculum material into safe, brand-styled HTML for the `.sk-prose`
 * content style: Markdown (Kata Pengantar, Ringkasan, legacy Inti Materi) and
 * rich editor HTML (Inti Materi columns saved from the admin WYSIWYG editor).
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

    /**
     * Render stored material in its storage format to sanitised HTML.
     *
     * Markdown goes through toHtml(). HTML is cleaned with a strict allowlist
     * (paragraphs, headings, emphasis, lists, quotes, rules, links, tables,
     * spans): scripts and event handlers are removed, links keep only http,
     * https and mailto, and a span keeps only a color and font family from
     * config/content.php, rewritten as an inline style generated here.
     * Headings are shifted exactly like Markdown headings. HTML without any
     * visible text yields ''.
     *
     * @param  string|null  $content  Stored material; null or blank yields ''.
     * @param  MaterialFormat|string  $format  Storage format; unknown values fall back to Markdown.
     * @param  int  $heading_level  HTML level used for a top-level heading (2-6).
     * @return string HTML that is safe to echo unescaped.
     */
    public function toHtmlFromFormat(?string $content, MaterialFormat|string $format, int $heading_level = 4): string;

    /**
     * Convert stored Markdown to the HTML the rich editor starts from.
     *
     * Used when an admin opens a legacy Markdown column in the WYSIWYG editor.
     * Headings are not shifted (`#` stays <h1>) and raw HTML is stripped.
     *
     * @param  string|null  $markdown  Legacy Markdown or plain text.
     * @return string Editor HTML; '' for blank input.
     */
    public function markdownToEditorHtml(?string $markdown): string;
}
