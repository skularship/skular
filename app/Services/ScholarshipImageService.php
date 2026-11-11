<?php

namespace App\Services;

use App\Contracts\ScholarshipImageServiceInterface;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Resizes scholarship card images with GD and stores them as WebP (JPEG when
 * the server's GD build lacks WebP) on the public disk.
 */
class ScholarshipImageService implements ScholarshipImageServiceInterface
{
    /**
     * Encoder quality for WebP and JPEG output (0-100).
     */
    private const QUALITY = 80;

    /**
     * Downscale an uploaded image and store it on the public disk.
     *
     * @param  UploadedFile  $file  JPEG, PNG or WebP upload.
     * @return string Path of the stored image relative to the disk root.
     *
     * @throws RuntimeException When the file cannot be decoded as an image.
     */
    public function store(UploadedFile $file): string
    {
        $contents = $file->get();
        $source = is_string($contents) && $contents !== '' ? @imagecreatefromstring($contents) : false;

        if (! $source instanceof GdImage) {
            throw new RuntimeException('Gambar tidak bisa dibaca. Unggah file JPG, PNG, atau WebP.');
        }

        $source = $this->applyExifOrientation($source, $file);
        $resized = $this->downscale($source);

        $use_webp = function_exists('imagewebp');
        $extension = $use_webp ? 'webp' : 'jpg';

        ob_start();
        $encoded = $use_webp
            ? imagewebp($resized, null, self::QUALITY)
            : imagejpeg($resized, null, self::QUALITY);
        $binary = (string) ob_get_clean();

        if (! $encoded || $binary === '') {
            throw new RuntimeException('Gambar gagal diproses. Coba unggah file lain.');
        }

        $path = self::DIRECTORY.'/'.Str::uuid()->toString().'.'.$extension;

        Storage::disk(self::DISK)->put($path, $binary, 'public');

        return $path;
    }

    /**
     * Delete a stored card image; missing files are ignored.
     *
     * @param  string|null  $path  Path relative to the disk root.
     */
    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }

    /**
     * Shrink the image so its longest side fits MAX_DIMENSION; smaller images keep their size.
     */
    private function downscale(GdImage $source): GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $ratio = min(1, self::MAX_DIMENSION / max($width, $height));

        $target_width = max(1, (int) round($width * $ratio));
        $target_height = max(1, (int) round($height * $ratio));

        $target = imagecreatetruecolor($target_width, $target_height);

        // Keep PNG/WebP transparency instead of painting it black.
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, (int) imagecolorallocatealpha($target, 0, 0, 0, 127));

        imagecopyresampled($target, $source, 0, 0, 0, 0, $target_width, $target_height, $width, $height);

        return $target;
    }

    /**
     * Rotate phone photos according to their EXIF orientation so they never appear sideways.
     */
    private function applyExifOrientation(GdImage $image, UploadedFile $file): GdImage
    {
        if (! function_exists('exif_read_data') || $file->getMimeType() !== 'image/jpeg') {
            return $image;
        }

        $exif = @exif_read_data($file->getRealPath());
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        return $rotated instanceof GdImage ? $rotated : $image;
    }
}
