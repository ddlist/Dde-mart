<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Images;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/*
 * DDE-Mart API — uploads (original). Authenticated only; images into an
 * allowlisted folder; returns the public URL. 8 MB cap.
 */
class UploadController extends Controller
{
    public const FOLDERS = ['avatars', 'documents', 'chat', 'reviews'];

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'image', 'max:8192'],
            'folder' => ['nullable', 'string', 'in:'.implode(',', self::FOLDERS)],
        ]);

        $path = $request->file('file')->store($validated['folder'] ?? 'avatars', 'public');

        return response()->json(['data' => [
            'path' => $path,
            'url' => Images::url($path),
        ]], 201);
    }
}
