<?php
// app/Http/Controllers/MediaUploadController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class MediaUploadController extends Controller
{
    // Upload image (event poster)
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120', // 5MB max
        ]);

        // Upload to Cloudinary
        $uploadedFile = cloudinary()->uploadApi()->upload(
            $request->file('image')->getRealPath(),
            [
                'folder' => 'spotlight/posters', // organized folder
                'transformation' => [
                    'width'   => 1200,
                    'height'  => 630,
                    'crop'    => 'fill',         // auto crop for event cards
                    'quality' => 'auto',          // auto optimize quality
                    'fetch_format' => 'auto',     // auto best format (webp etc)
                ]
            ]
        );

        return response()->json([
            'url'       => $uploadedFile['secure_url'],  // https URL
            'public_id' => $uploadedFile['public_id'],    // to delete later
        ]);
    }

    // Upload video
    public function uploadVideo(Request $request)
    {
        $request->validate([
            'video' => 'required|mimetypes:video/mp4,video/avi,video/quicktime|max:102400', // 100MB
        ]);

        $uploadedFile = cloudinary()->uploadApi()->upload(
            $request->file('video')->getRealPath(),
            [
                'folder'             => 'spotlight/videos',
                'resource_type'      => 'video',
                'eager'              => [           // generate preview thumbnail
                    ['width' => 400, 'height' => 300, 'crop' => 'pad', 'format' => 'jpg']
                ],
                'eager_async'        => true,
            ]
        );

        return response()->json([
            'url'       => $uploadedFile['secure_url'],
            'public_id' => $uploadedFile['public_id'],
        ]);
    }

    // Delete media (when event is deleted)
    public function deleteMedia(Request $request)
    {
        $request->validate([
            'public_id' => 'required|string',
        ]);

        cloudinary()->uploadApi()->destroy($request->public_id);

        return response()->json(['message' => 'Deleted successfully']);
    }
}