<?php
namespace App\Http\Controllers;
use App\Models\Message;
use App\Services\AIReformulationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function __construct(
        private AIReformulationService $aiService
    ) {}

    public function envoyer(Request $request)
    {
        $request->validate([
            'contenu'      => 'required|string|max:2000',
            'receiver_id'  => 'required|exists:users,id',
        ]);

        // 1. Reformuler automatiquement avec Claude
        $contenuOriginal   = $request->contenu;
        $contenuReformule  = $this->aiService->reformuler($contenuOriginal);

        // 2. Sauvegarder en DB
        $message = Message::create([
            'contenu'          => $contenuReformule,   // message reformulé
            'contenu_original' => $contenuOriginal,    // message original gardé
            'sender_id'        => Auth::id(),
            'receiver_id'      => $request->receiver_id,
            'lu'               => false,
        ]);

        return response()->json([
            'status'            => true,
            'message'           => $message,
            'contenu_original'  => $contenuOriginal,
            'contenu_reformule' => $contenuReformule,
        ]);
    }

    public function conversation(int $userId)
    {
        $messages = Message::conversation(Auth::id(), $userId)->get();

        return response()->json([
            'status'   => true,
            'messages' => $messages,
        ]);
    }
}