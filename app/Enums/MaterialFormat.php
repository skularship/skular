<?php

namespace App\Enums;

/**
 * Storage format of a curriculum text.
 *
 * - Markdown: typed in the Markdown editor (and legacy plain text); rendered by CommonMark.
 * - Html: produced by the admin rich editor; rendered through the allowlist sanitizer.
 */
enum MaterialFormat: string
{
    case Markdown = 'markdown';
    case Html = 'html';
}
