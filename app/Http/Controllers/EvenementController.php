<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\StatutEvenement;
use App\Enums\CategorieEvenement;
use Illuminate\Validation\Rules\Enum;
use App\Enums\TypeMedia;
use App\Models\Media;
use App\Models\Reservation;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
// use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class EvenementController extends Controller
{
    // List Events
    public function index()
    {
        $events = Evenement::with('medias')->latest()->get();

        return response()->json($events);
    }

    // Show Event Details (Public)
    public function show(Request $request, $id)
    {
        $event = Evenement::with(['organisateur', 'medias'])->findOrFail($id);

        if ($request->wantsJson()) {
            return response()->json($event);
        }

        // Stats calculation
        $stats = [];
        if (!$event->is_tournoi) {
            $totalReserved = Reservation::where('evenement_id', $id)
                ->where('statut', 'confirmed')
                ->count();
            $stats = [
                'total_reserved' => $totalReserved,
                'remaining'      => max(0, $event->capacite_spectateur - $totalReserved),
            ];
        } else {
            $participantReserved = Reservation::where('evenement_id', $id)
                ->where('ticket_type', 'participant')
                ->where('statut', 'confirmed')
                ->count();

            $spectatorReserved = Reservation::where('evenement_id', $id)
                ->where('ticket_type', 'spectator')
                ->where('statut', 'confirmed')
                ->count();

            $stats = [
                'participant_reserved'  => $participantReserved,
                'spectator_reserved'    => $spectatorReserved,
                'participant_remaining' => max(0, $event->capacite_participant - $participantReserved),
                'spectator_remaining'   => max(0, $event->capacite_spectateur - $spectatorReserved),
            ];
        }

        // Check if already reserved by auth user
        $isReserved = false;
        if (Auth::check()) {
            $isReserved = Reservation::where('evenement_id', $id)
                ->where('user_id', Auth::id())
                ->where('statut', 'confirmed')
                ->exists();
        }

        // Similar events (same category, different id, upcoming)
        $similarEvents = Evenement::with('medias')
            ->where('categorie', $event->categorie)
            ->where('id', '!=', $id)
            ->where('date_debut', '>=', now())
            ->take(3)
            ->get();

        return Inertia::render('Events/Show', [
            'event'          => $event,
            'stats'          => $stats,
            'is_reserved'    => $isReserved,
            'similar_events' => $similarEvents->isEmpty() ? null : $similarEvents,
        ]);
    }

    // Create Event
    public function store(Request $request)
    {
        $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'required',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date',
            'lieu' => 'required|string',
            'prix_spectateur' => 'required|numeric',
            'capacite_spectateur' => 'required|integer',
            'categorie' => ['required', new Enum(CategorieEvenement::class)],
        ]);

        $user = Auth::user();
        if ($user->role === \App\Enums\Role::Organisateur) {
            if (!$user->organisateur || !$user->organisateur->rib) {
                return response()->json([
                    'message' => 'Bank information (RIB) is required to create events. Please complete your bank details first.',
                    'require_rib' => true
                ], 422);
            }
        }

        $event = Evenement::create([
            'organisateur_id' => Auth::id(),
            'titre' => $request->titre,
            'description' => $request->description,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'lieu' => $request->lieu,
            'prix_spectateur' => $request->prix_spectateur,
            'capacite_spectateur' => $request->capacite_spectateur,
            'categorie' => $request->categorie,
            // Tournament fields
            'is_tournoi' => $request->has('is_tournoi') ? $request->boolean('is_tournoi') : false,
            'type_tournoi' => $request->type_tournoi ?? null,
            'prix_participant' => $request->prix_participant ?? null,
            'capacite_participant' => $request->capacite_participant ?? null,
            'statut' => StatutEvenement::Ouvert
        ]);

        if ($request->hasFile('medias')) {
            foreach ($request->file('medias') as $file) {
                // Determine type based on mime type
                $mimeType = $file->getMimeType();
                $type = str_starts_with($mimeType, 'video/') ? TypeMedia::Video : TypeMedia::Image;

                // Store the file
                $uploadResult = cloudinary()->uploadApi()->upload($file->getRealPath(), [
                    'folder' => 'events/media',
                    'resource_type' => $type === TypeMedia::Video ? 'video' : 'image'
                ]);
                
                $url = $uploadResult['secure_url'];

                // Create media record
                $event->medias()->create([
                    'url' => $url,
                    'type' => $type,
                ]);

                // Also set as main poster if it's an image and not set yet
                if ($type === TypeMedia::Image && !$event->poster_url) {
                    $event->update(['poster_url' => $url]);
                }
            }
        }

        return response()->json([
            'message' => 'Event created successfully',
            'event' => $event->load('medias')
        ]);
    }

    // Update Event
    public function update(Request $request, $id)
    {
        $event = Evenement::findOrFail($id);

        if ($event->organisateur_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        // Validate input (including tournament fields)
        try {
            $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'required',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date',
            'lieu' => 'required|string',
            'prix_spectateur' => 'required|numeric',
            'capacite_spectateur' => 'required|integer',
            'categorie' => ['required', new Enum(CategorieEvenement::class)],
            'statut' => ['required', new Enum(StatutEvenement::class)],
            'is_tournoi' => 'sometimes|boolean',
            'type_tournoi' => 'exclude_if:is_tournoi,0|required_if:is_tournoi,1|in:equipe,individuel',
            'prix_participant' => 'exclude_if:is_tournoi,0|required_if:is_tournoi,1|numeric|min:0',
            'capacite_participant' => 'exclude_if:is_tournoi,0|required_if:is_tournoi,1|integer|min:1',
            'medias.*' => 'nullable|file|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480', // Allow up to 20MB
            'media_to_delete' => 'nullable|array',
            'media_to_delete.*' => 'integer|exists:media,id',
            'poster_url' => 'nullable|string',
        ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('Event Update Validation Failed:', $e->errors());
            throw $e;
        }

        $event->update([
            'titre' => $request->titre,
            'description' => $request->description,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'lieu' => $request->lieu,
            'prix_spectateur' => $request->prix_spectateur,
            'capacite_spectateur' => $request->capacite_spectateur,
            'categorie' => $request->categorie,
            // Tournament fields
            'is_tournoi' => $request->has('is_tournoi') ? $request->boolean('is_tournoi') : false,
            'type_tournoi' => $request->type_tournoi ?? null,
            'prix_participant' => $request->prix_participant ?? null,
            'capacite_participant' => $request->capacite_participant ?? null,
            'poster_url' => $request->poster_url ?? $event->poster_url,
        ]);

        // Handle media deletion
        if ($request->has('media_to_delete')) {
            $mediaIds = $request->input('media_to_delete');
            $medias = $event->medias()->whereIn('id', $mediaIds)->get();
            
            foreach ($medias as $media) {
                // If it's the poster, clear it
                if ($event->poster_url === $media->url) {
                    $event->update(['poster_url' => null]);
                }
                
                // Try to delete from Cloudinary if possible (needs public_id storage, which we don't have yet, so just delete DB record)
                // For now, we just delete the database record
                $media->delete();
            }
        }

        if ($request->hasFile('medias')) {
            foreach ($request->file('medias') as $file) {
                // Determine type based on mime type
                $mimeType = $file->getMimeType();
                $type = str_starts_with($mimeType, 'video/') ? TypeMedia::Video : TypeMedia::Image;

                // Store the file
                $uploadResult = cloudinary()->uploadApi()->upload($file->getRealPath(), [
                    'folder' => 'events/media',
                    'resource_type' => $type === TypeMedia::Video ? 'video' : 'image'
                ]);

                $url = $uploadResult['secure_url'];

                // Create media record
                $event->medias()->create([
                    'url' => $url,
                    'type' => $type,
                ]);

                // Also set as main poster if it's an image and not set yet
                if ($type === TypeMedia::Image && !$event->poster_url) {
                    $event->update(['poster_url' => $url]);
                }
            }
        }

        return response()->json([
            'message' => 'Event updated successfully',
            'event' => $event->load('medias')
        ]);
    }

    // Delete Event
  public function destroy($id)
{
    $event = Evenement::findOrFail($id);

    if ($event->organisateur_id !== Auth::id()) {
        return response()->json(['message' => 'Unauthorized action.'], 403);
    }

    $event->statut = StatutEvenement::Annule;
    $event->save(); // ✅ Actually persist the change

    return response()->json([
        'message' => 'Event cancelled successfully'
    ], 200);
}

    // Search events
    public function search(Request $request)
    {
        $query = Evenement::with('medias')->withCount('reservations');

        if ($request->has('organisateur_id')) {
            $query->parOrganisateur($request->organisateur_id);
        }

        if ($request->has('titre')) {
            $query->parTitre($request->titre);
        }

        if ($request->has('date')) {
            $query->parDate($request->date);
        }

        if ($request->has('prix_max')) {
            $query->parPrix($request->prix_max);
        }

        if ($request->has('categorie')) {
            $query->parCategorie($request->categorie);
        }

        if ($request->has('statut')) {
            $statuts = explode(',', $request->statut);
            $query->whereIn('statut', $statuts);
        }

        $limit = $request->input('limit');
        if ($limit) {
            return response()->json($query->latest()->limit($limit)->get());
        }

        $perPage = $request->input('per_page');
        if ($perPage) {
            return response()->json($query->latest()->paginate($perPage));
        }

        return response()->json($query->latest()->get());
    }

    // Open reservation
    public function ouvrirReservation($id)
    {
        $event = Evenement::findOrFail($id);
        $event->ouvrirReservation();

        return response()->json([
            'message' => 'Reservations opened successfully',
            'event' => $event
        ]);
    }

    // Close reservation
    public function fermerReservation($id)
    {
        $event = Evenement::findOrFail($id);
        $event->fermerReservation();

        return response()->json([
            'message' => 'Reservations closed successfully',
            'event' => $event
        ]);
    }
}