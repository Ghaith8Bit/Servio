<?php

namespace Mrclutch\Servio\Supports\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

trait HandlesBase64Image
{
    /**
     * Save a Base64-encoded image to storage.
     *
     * @param string $base64Image
     * @param int $maxSizeInKb Maximum size of the image in kilobytes
     * @return string|null The path to the saved image
     * @throws \Exception
     */
    public function saveBase64Image(string $base64Image, int $maxSizeInKb = 4096): ?string
    {
        // Validate the base64 image format
        $validator = Validator::make(['image' => $base64Image], [
            'image' => 'nullable|string|regex:/^data:image\/(jpeg|png|jpg|gif|svg+xml);base64,/'
        ]);

        if ($validator->fails()) {
            throw new \Exception('Invalid Base64 image format');
        }

        // Extract the image extension
        preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches);
        $extension = $matches[1];

        // Validate the image extension
        if (!in_array($extension, ['jpeg', 'png', 'jpg', 'gif', 'svg+xml'])) {
            throw new \Exception('Unsupported image format');
        }

        // Remove the base64 header to get the actual image data
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);
        $imageData = base64_decode($imageData);

        if ($imageData === false || empty($imageData)) {
            throw new \Exception('Base64 decode failed or image data is empty');
        }

        // Check the size of the image
        $imageSizeInKb = strlen($imageData) / 1024;
        if ($imageSizeInKb > $maxSizeInKb) {
            throw new \Exception('Image size exceeds the maximum allowed size of ' . $maxSizeInKb . ' KB');
        }

        // Generate a unique file name
        $imageName = time() . '_' . Str::random(10) . '.' . $extension;
        $imageDirectory = $this->getImageDirectory();
        $imagePath = $imageDirectory . '/' . $imageName;

        // Save the image to the specified disk
        Storage::disk('public')->put($imagePath, $imageData);

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
        if ($imagePath) {
            // Delete the image from storage
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
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
