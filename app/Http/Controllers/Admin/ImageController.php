<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\LoreImages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    /**
     * Upload an image for the Markdown editor and return the URL to embed.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', ...LoreImages::FILE_RULES],
        ]);

        $path = LoreImages::store($request->file('image'), 'uploads');

        return response()->json(['url' => LoreImages::publicUrl($path)]);
    }
}
