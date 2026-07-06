<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageConverter
{
    /**
     * Store an uploaded image as a WebP file on the public disk and
     * return its relative path. Optionally resize to a maximum width.
     */
    public static function storeAsWebp(UploadedFile $file, string $directory, int $maxWidth = 1920, int $quality = 82): string
    {
        $source = static::createImage($file);

        $width = imagesx($source);
        $height = imagesy($source);

        if ($maxWidth > 0 && $width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) round($height * ($maxWidth / $width));
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $resized;
        }

        ob_start();
        imagewebp($source, null, $quality);
        $contents = ob_get_clean();
        imagedestroy($source);

        $path = trim($directory, '/') . '/' . Str::uuid7() . '.webp';
        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    /**
     * Store an uploaded file as-is on the public disk (used for favicons,
     * where formats like .ico / .png / .svg must be preserved).
     */
    public static function storeOriginal(UploadedFile $file, string $directory): string
    {
        return $file->store(trim($directory, '/'), 'public');
    }

    /**
     * Delete a previously stored file from the public disk (ignores nulls).
     */
    public static function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    protected static function createImage(UploadedFile $file): \GdImage
    {
        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($image === false) {
            throw new \RuntimeException('Format gambar tidak didukung.');
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $image;
    }
}
