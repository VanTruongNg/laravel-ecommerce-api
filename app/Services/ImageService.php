<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ImageService
{
    /**
     * Upload image to S3 and return the URL.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string
     */
    public function uploadImage($file)
    {
        try {
            $folder = config('filesystems.s3_folder', 'anhhungxalo');

            $path = $file->store($folder, 's3');

            Storage::disk('s3')->setVisibility($path, 'public');

            $url = Storage::disk('s3')->url($path);

            return $url;
        } catch (\Exception $e) {
            \Log::error('Failed to upload image: ' . $e->getMessage());
            throw new \RuntimeException('Failed to upload image. Please try again later.');
        }
    }
}
