<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * File Upload Service
 *
 * Reusable service untuk handle file uploads
 * Bisa dipakai untuk Brick, Cement, Sand, Cat, dan material lainnya
 */
class FileUploadService
{
    /**
     * Upload file ke storage
     *
     * @param UploadedFile $file
     * @param string $folder Folder tujuan (e.g., 'bricks', 'cements')
     * @param string $disk Storage disk (default: 'public')
     * @return string Path file yang di-upload
     */
    public function upload(UploadedFile $file, string $folder, string $disk = 'public'): string
    {
        // Generate unique filename dengan timestamp
        $filename = time() . '_' . $file->getClientOriginalName();

        // Upload file ke folder yang ditentukan
        $path = $file->storeAs($folder, $filename, $disk);

        return $path;
    }

    /**
     * Delete file dari storage
     *
     * @param string $path Path file yang akan dihapus
     * @param string $disk Storage disk (default: 'public')
     * @return bool
     */
    public function delete(string $path, string $disk = 'public'): bool
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }

    /**
     * Update file (delete old, upload new)
     *
     * @param UploadedFile $file File baru
     * @param string $folder Folder tujuan
     * @param string|null $oldPath Path file lama yang akan dihapus
     * @param string $disk Storage disk (default: 'public')
     * @return string Path file baru
     */
    public function update(UploadedFile $file, string $folder, ?string $oldPath = null, string $disk = 'public'): string
    {
        // Hapus file lama jika ada
        if ($oldPath) {
            $this->delete($oldPath, $disk);
        }

        // Upload file baru
        return $this->upload($file, $folder, $disk);
    }

    /**
     * Get full URL dari path
     *
     * @param string $path
     * @param string $disk
     * @return string
     */
    public function getUrl(string $path, string $disk = 'public'): string
    {
        return Storage::disk($disk)->url($path);
    }

    /**
     * Check if file exists
     *
     * @param string $path
     * @param string $disk
     * @return bool
     */
    public function exists(string $path, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->exists($path);
    }
}
