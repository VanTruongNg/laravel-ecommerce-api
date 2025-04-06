<?php

namespace App\UploadService;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\utils\Response;

class UploaderService extends Controller
{
    public function uploadFile($file, $filename)
    {
        try {
            if (!$file instanceof \Illuminate\Http\UploadedFile || empty($filename)) {
                throw new \InvalidArgumentException('Invalid file or filename');
            }

            $path = Storage::disk('s3')->putFileAs('uploads', $file, $filename, 'public');
            
            return [
                'success' => true,
                'data' => [
                    'url' => Storage::disk('s3')->url($path)
                ]
            ];
        } catch (\Exception $e) {
            throw new \Exception('File upload failed: ' . $e->getMessage());
        }
    }
}