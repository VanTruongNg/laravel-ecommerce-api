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
                return Response::badRequest(
                    'Invalid file or filename',
                    [
                        'error' => 'Invalid file or filename'
                    ]
                );
            }

            $path = Storage::disk('s3')->putFileAs('uploads', $file, $filename, 'public');

            return Response::success(
                'File uploaded successfully',
                [
                    'url' => Storage::disk('s3')->url($path)
                ]
            );
        } catch (\Exception $e) {
            return Response::serverError(
                'File upload failed',
                [
                    'error' => $e->getMessage()
                ]
            );
        }
    }
}