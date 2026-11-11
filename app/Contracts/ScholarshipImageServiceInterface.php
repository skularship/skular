<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Stores the optional card image of a scholarship as a small, web-ready file.
 */
interface ScholarshipImageServiceInterface
{
    /**
     * Longest side, in pixels, of the stored card image.
     */
    public const MAX_DIMENSION = 512;

    /**
     * Largest upload accepted by the admin form, in kilobytes (5 MB).
     */
    public const MAX_UPLOAD_KB = 5120;

    /**
     * Disk that serves card images to mentees.
     */
    public const DISK = 'public';

    /**
     * Directory on the disk that holds card images.
     */
    public const DIRECTORY = 'scholarships';

    /**
     * Downscale an uploaded image and store it on the public disk.
     *
     * The original upload is never kept, so a 5 MB photo becomes a file of a
     * few dozen kilobytes that every recommendation card can load cheaply.
     *
     * @param  UploadedFile  $file  JPEG, PNG or WebP upload.
     * @return string Path of the stored image relative to the disk root.
     *
     * @throws RuntimeException When the file cannot be decoded as an image.
     */
    public function store(UploadedFile $file): string;

    /**
     * Delete a stored card image; missing files are ignored.
     *
     * @param  string|null  $path  Path relative to the disk root.
     */
    public function delete(?string $path): void;
}
