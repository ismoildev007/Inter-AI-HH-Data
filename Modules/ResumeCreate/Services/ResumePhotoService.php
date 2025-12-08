<?php

namespace Modules\ResumeCreate\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ResumePhotoService
{
    protected string $disk = 'public';

    public function store(UploadedFile $file): string
    {
        $path = $file->store('resume_photos', $this->disk);

        return $path;
    }

    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
        }
    }
}

