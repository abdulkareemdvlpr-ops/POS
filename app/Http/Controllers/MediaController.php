<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    /**
     * Serve a file straight from the "public" storage disk.
     *
     * This avoids relying on the storage:link symlink, which can be lost
     * when the project is zipped/copied to another machine (Windows zip
     * tools do not preserve symlinks), causing uploaded images such as
     * the pharmacy logo to appear broken/never load.
     */
    public function show(string $path): StreamedResponse
    {
        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        $fullPath = $disk->path($path);

        return response()->stream(function () use ($fullPath) {
            readfile($fullPath);
        }, 200, [
            'Content-Type'   => $disk->mimeType($path) ?: 'application/octet-stream',
            'Content-Length' => $disk->size($path),
            'Cache-Control'  => 'public, max-age=604800',
        ]);
    }
}
