<?php

namespace App\Http\Controllers;

use App\Models\Sponsor;
use Illuminate\Http\Request;

class SponsorController extends Controller
{
    /**
     * List all sponsors (for select dropdown in event form).
     */
    public function index()
    {
        $sponsors = Sponsor::orderBy('nom')->get(['id', 'nom', 'logo']);
        return response()->json($sponsors);
    }

    /**
     * Create a new sponsor (admin / manual creation).
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom'  => 'required|string|max:255',
            'logo' => 'nullable|file|mimes:jpeg,png,jpg,webp,svg|max:5120',
        ]);

        $logoUrl = null;

        if ($request->hasFile('logo')) {
            $uploadResult = cloudinary()->uploadApi()->upload(
                $request->file('logo')->getRealPath(),
                ['folder' => 'sponsors', 'resource_type' => 'image']
            );
            $logoUrl = $uploadResult['secure_url'];
        }

        $sponsor = Sponsor::create([
            'nom'  => $request->input('nom'),
            'logo' => $logoUrl,
        ]);

        return response()->json($sponsor, 201);
    }
}
