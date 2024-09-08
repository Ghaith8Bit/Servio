<?php

namespace Mrclutch\Servio\Supports\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HandlesUploadedImage
{
    /**
     * Save an uploaded image to storage.
     *
     * @param UploadedFile $file
     * @param int $maxSizeInKb Maximum size of the image in kilobytes
     * @return string|null The path to the saved image
     * @throws \Exception
     */
    public function saveImage(UploadedFile $file, int $maxSizeInKb = 4096): ?string
    {
        // Validate the image extension
        $extension = $file->getClientOriginalExtension();
        if (!in_array($extension, ['jpeg', 'png', 'jpg', 'gif', 'svg'])) {
            throw new \Exception('Unsupported image format');
        }

        // Check the size of the image
        $imageSizeInKb = $file->getSize() / 1024;
        if ($imageSizeInKb > $maxSizeInKb) {
            throw new \Exception('Image size exceeds the maximum allowed size of ' . $maxSizeInKb . ' KB');
        }

        // Generate a unique file name
        $imageName = time() . '_' . Str::random(10) . '.' . $extension;
        $imageDirectory = $this->getImageDirectory();
        $imagePath = $imageDirectory . '/' . $imageName;

        // Save the image to the specified disk
        Storage::disk('public')->putFileAs($imageDirectory, $file, $imageName);

        return $imagePath;
    }

    /**
     * Delete the image from storage.
     *
     * @param string|null $imagePath The path to the image to delete
     * @return void
     */
    public function deleteImage(?string $imagePath = null): void
    {
        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
    }

    /**
     * Get the image URL from the stored image path.
     *
     * @param string|null $imagePath
     * @return string|null
     */
    public function getImageUrl(?string $imagePath): ?string
    {
        if ($imagePath) {
            return Storage::disk('public')->url($imagePath);
        }

        return null;
    }

    /**
     * Get the image directory where images will be stored.
     *
     * @return string
     */
    protected function getImageDirectory(): string
    {
        return property_exists($this, 'imageDirectory') ? $this->imageDirectory : 'images';
    }
}
