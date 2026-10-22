<?php

namespace App\Services;

use App\Contracts\TwibbonServiceInterface;
use App\Exceptions\TwibbonException;
use App\Models\ScholarshipApplication;
use App\Models\TwibbonTemplate;
use App\Models\User;
use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TwibbonService implements TwibbonServiceInterface
{
    /**
     * Font files used when a template does not ship its own font.
     *
     * @var array<int, string>
     */
    private const FALLBACK_FONTS = [
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
        '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
        '/System/Library/Fonts/Supplemental/Arial.ttf',
    ];

    /**
     * Render a twibbon for the mentee and return its public disk path.
     *
     * @throws TwibbonException
     */
    public function generate(
        User $user,
        ScholarshipApplication $application,
        TwibbonTemplate $template,
    ): string {
        $disk = Storage::disk('public');

        if (! $disk->exists($template->image_path)) {
            throw new TwibbonException('Gambar template twibbon tidak ditemukan.');
        }

        $canvas = imagecreatefromstring($disk->get($template->image_path));

        if ($canvas === false) {
            throw new TwibbonException('Gambar template twibbon tidak dapat dibaca.');
        }

        $width = imagesx($canvas);
        $height = imagesy($canvas);
        $font = $this->resolveFont($template);
        $values = $this->valuesFor($user, $application);

        foreach ($template->placementsOrDefault() as $placement) {
            $this->drawPlacement($canvas, $placement, $values, $width, $height, $font);
        }

        ob_start();
        imagepng($canvas);
        $binary = ob_get_clean();
        imagedestroy($canvas);

        if (! is_string($binary) || $binary === '') {
            throw new TwibbonException('Twibbon gagal dirender.');
        }

        $path = 'twibbon/results/'.$user->id.'-'.$application->id.'-'.Str::lower(Str::random(8)).'.png';

        if (! $disk->put($path, $binary)) {
            throw new TwibbonException('Twibbon gagal disimpan.');
        }

        return $path;
    }

    /**
     * Get the text values available to a template.
     *
     * @return array<string, string>
     */
    private function valuesFor(User $user, ScholarshipApplication $application): array
    {
        return [
            'user_name' => $user->name,
            'scholarship_name' => $application->scholarship->scholarship_name,
            'institution' => $application->scholarship->institution ?? '',
        ];
    }

    /**
     * Draw a single configured text placement onto the canvas.
     *
     * @param  array<string, mixed>  $placement
     * @param  array<string, string>  $values
     */
    private function drawPlacement(
        GdImage $canvas,
        array $placement,
        array $values,
        int $width,
        int $height,
        ?string $font,
    ): void {
        $key = is_string($placement['key'] ?? null) ? $placement['key'] : '';
        $text = trim($values[$key] ?? '');

        if ($text === '') {
            return;
        }

        $font_size = max(8, (int) ($placement['font_size'] ?? 40));
        $colour = $this->allocateColour($canvas, is_string($placement['color'] ?? null) ? $placement['color'] : '#FFFFFF');
        $align = is_string($placement['align'] ?? null) ? $placement['align'] : 'center';
        $x_ratio = $this->ratio($placement['x'] ?? null);
        $y_ratio = $this->ratio($placement['y'] ?? null);
        $centre_x = (int) round($x_ratio * $width);
        $baseline_y = (int) round($y_ratio * $height);

        if ($font !== null) {
            $box = imagettfbbox($font_size, 0, $font, $text);

            if ($box !== false) {
                $text_width = abs($box[2] - $box[0]);
                $draw_x = $this->alignedX($align, $centre_x, $text_width);

                imagettftext($canvas, $font_size, 0, $draw_x, $baseline_y, $colour, $font, $text);

                return;
            }
        }

        // Built-in bitmap font keeps twibbon generation working without a TTF file.
        $text_width = imagefontwidth(5) * mb_strlen($text);
        $draw_x = $this->alignedX($align, $centre_x, $text_width);

        imagestring($canvas, 5, $draw_x, $baseline_y, $text, $colour);
    }

    /**
     * Resolve the horizontal draw position for the configured alignment.
     */
    private function alignedX(string $align, int $centre_x, int $text_width): int
    {
        return match ($align) {
            'left' => max(0, $centre_x),
            'right' => max(0, $centre_x - $text_width),
            default => max(0, $centre_x - (int) round($text_width / 2)),
        };
    }

    /**
     * Allocate an RGB colour on the canvas from a hex string.
     *
     * @throws TwibbonException
     */
    private function allocateColour(GdImage $canvas, string $hex): int
    {
        [$red, $green, $blue] = $this->parseHexColour($hex);

        $colour = imagecolorallocate($canvas, $red, $green, $blue);

        if ($colour === false) {
            throw new TwibbonException('Warna teks twibbon tidak dapat dialokasikan.');
        }

        return $colour;
    }

    /**
     * Parse a hex colour into clamped RGB channels.
     *
     * @return array{0: int<0, 255>, 1: int<0, 255>, 2: int<0, 255>}
     */
    private function parseHexColour(string $hex): array
    {
        $value = ltrim($hex, '#');

        if (strlen($value) === 3) {
            $value = $value[0].$value[0].$value[1].$value[1].$value[2].$value[2];
        }

        if (strlen($value) !== 6 || ! ctype_xdigit($value)) {
            $value = 'FFFFFF';
        }

        return [
            min(255, max(0, (int) hexdec(substr($value, 0, 2)))),
            min(255, max(0, (int) hexdec(substr($value, 2, 2)))),
            min(255, max(0, (int) hexdec(substr($value, 4, 2)))),
        ];
    }

    /**
     * Clamp a placement ratio into the 0..1 range.
     */
    private function ratio(mixed $value): float
    {
        if (! is_numeric($value)) {
            return 0.5;
        }

        return max(0.0, min(1.0, (float) $value));
    }

    /**
     * Resolve a usable TrueType font path for GD.
     */
    private function resolveFont(TwibbonTemplate $template): ?string
    {
        if (filled($template->font_path)) {
            $disk = Storage::disk('public');

            if ($disk->exists($template->font_path)) {
                $materialised = $this->materialiseFont($disk->get($template->font_path));

                if ($materialised !== null) {
                    return $materialised;
                }
            }
        }

        foreach (self::FALLBACK_FONTS as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Write a font stored on a configured disk to a local path GD can read.
     */
    private function materialiseFont(string $binary): ?string
    {
        $path = storage_path('app/twibbon-font-'.md5($binary).'.ttf');

        if (is_file($path)) {
            return $path;
        }

        return file_put_contents($path, $binary) === false ? null : $path;
    }
}
