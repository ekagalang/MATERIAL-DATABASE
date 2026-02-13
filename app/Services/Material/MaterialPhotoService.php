<?php

namespace App\Services\Material;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MaterialPhotoService
{
    public function upload(?UploadedFile $photo, string $directory, ?string $oldPath = null): ?string
    {
        if (!$photo || !$photo->isValid()) {
            return null;
        }

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $filename = time() . '_' . $photo->getClientOriginalName();
        $path = $photo->storeAs($directory, $filename, 'public');

        return $path ?: null;
    }

    public function delete(?string $path): void
    {
        if (!$path) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
