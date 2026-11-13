<?php

namespace App\Support;

use App\Models\Faq;

/**
 * Single source for the absolute URLs Skular publishes to crawlers.
 *
 * Every URL is built from the configured canonical host, never from the
 * incoming Host header, so a request through localhost or a preview domain
 * cannot leak into a canonical tag, a sitemap entry, or a social preview.
 */
class Seo
{
    /**
     * Canonical host without a trailing slash.
     */
    public static function baseUrl(): string
    {
        return rtrim((string) config('seo.url'), '/');
    }

    /**
     * Build an absolute canonical URL for a public path.
     */
    public static function url(string $path = '/'): string
    {
        $path = '/'.ltrim($path, '/');

        return $path === '/' ? self::baseUrl().'/' : self::baseUrl().rtrim($path, '/');
    }

    /**
     * Canonical URL for the current request: production host, no query string.
     */
    public static function canonical(): string
    {
        return self::url(request()->path());
    }

    /**
     * Absolute URL of the shared social preview image.
     */
    public static function imageUrl(): string
    {
        return self::url((string) config('seo.image.path'));
    }

    /**
     * FAQPage JSON-LD for exactly the FAQs a page renders.
     *
     * Pass the same collection the view loops over (never a separate query),
     * so Google only sees questions and answers that are visible on the page.
     * Returns null when nothing is shown, so no empty FAQPage is emitted.
     *
     * @param  iterable<Faq>  $faqs  Published FAQs as displayed on the page.
     * @return array{'@context': string, '@type': string, mainEntity: list<array{'@type': string, name: string, acceptedAnswer: array{'@type': string, text: string}}>}|null
     */
    public static function faqPage(iterable $faqs): ?array
    {
        $entities = [];

        foreach ($faqs as $faq) {
            $entities[] = [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => self::faqAnswerText($faq->answer),
                ],
            ];
        }

        if ($entities === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    /**
     * The visible text of an FAQ answer.
     *
     * Starts from the exact sanitized HTML that x-faq.accordion renders, so
     * the structured data can never contain text the visitor cannot read.
     * Block boundaries become spaces before the tags are stripped, so words in
     * adjacent paragraphs or list items are not glued together.
     */
    public static function faqAnswerText(string $answer): string
    {
        $html = (string) str($answer)->sanitizeHtml();
        $html = (string) preg_replace('#<(br|/p|/li|/h[1-6]|/div|/blockquote|/tr|/td|/th)\b[^>]*>#i', '$0 ', $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
