<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class ImageService
{
    /**
     * Save an uploaded image to a specified folder and return its path.
     *
     * @param string $folder
     * @param UploadedFile $image
     * @return string
     */
    public function saveImage(string $folder, UploadedFile $image): string
    {
        $image->store('/', $folder);
        $filename = $image->hashName();
        return 'images/' . $folder . '/' . $filename;
    }
}
