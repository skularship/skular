<?php

namespace App\Enums;

use Carbon\CarbonImmutable;

/**
 * The four public legal documents. Each case knows its page (route name and
 * path), its Indonesian title, and its current version and effective date
 * from config/legal.php.
 */
enum LegalDocument: string
{
    case Privacy = 'privacy';
    case Terms = 'terms';
    case Cookies = 'cookies';
    case Refund = 'refund';

    /**
     * The page title shown to visitors.
     */
    public function label(): string
    {
        return match ($this) {
            self::Privacy => 'Kebijakan Privasi',
            self::Terms => 'Syarat & Ketentuan',
            self::Cookies => 'Kebijakan Cookie',
            self::Refund => 'Kebijakan Pengembalian Dana',
        };
    }

    /**
     * The named route of the page.
     */
    public function routeName(): string
    {
        return 'legal.'.$this->value;
    }

    /**
     * The public path of the page, without a leading slash.
     */
    public function path(): string
    {
        return match ($this) {
            self::Privacy => 'kebijakan-privasi',
            self::Terms => 'syarat-ketentuan',
            self::Cookies => 'kebijakan-cookie',
            self::Refund => 'kebijakan-pengembalian-dana',
        };
    }

    /**
     * The Blade view that holds the document text.
     *
     * @return view-string
     */
    public function view(): string
    {
        return match ($this) {
            self::Privacy => 'legal.privacy',
            self::Terms => 'legal.terms',
            self::Cookies => 'legal.cookies',
            self::Refund => 'legal.refund',
        };
    }

    /**
     * The current version, e.g. "1.0".
     */
    public function version(): string
    {
        return (string) config("legal.documents.{$this->value}.version");
    }

    /**
     * The date the current version takes effect.
     */
    public function effectiveAt(): CarbonImmutable
    {
        return CarbonImmutable::parse((string) config("legal.documents.{$this->value}.effective_at"));
    }
}
