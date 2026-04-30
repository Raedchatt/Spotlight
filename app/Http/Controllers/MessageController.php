<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Services\AIReformulationService;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Database\Eloquent\Builder;

class MessageController extends Controller
{
    public function __construct(
        private AIReformulationService $aiService
    ) {}

    public function envoyer(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Message sending request received', $request->all());
        
        $request->validate([
            'contenu'      => 'required|string|max:2000',
            'receiver_id'  => 'required|exists:users,id',
        ]);

        $contenuOriginal   = $request->contenu;
        $contenuReformule  = $this->aiService->reformuler($contenuOriginal);

        \Illuminate\Support\Facades\Log::info('Message reformulated', [
            'original' => $contenuOriginal,
            'reformulated' => $contenuReformule
        ]);

        $message = Message::create([
            'contenu'          => $contenuReformule,
            'contenu_original' => $contenuOriginal,
            'sender_id'        => Auth::id(),
            'receiver_id'      => $request->receiver_id,
            'lu'               => false,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'status'            => true,
            'message'           => $message,
            'contenu_original'  => $contenuOriginal,
            'contenu_reformule' => $contenuReformule,
        ]);
    }

    public function conversation($identifier)
    {
        $user = Auth::user();

        if ($user->isAdmin() && str_contains((string)$identifier, '_')) {
            [$user1Id, $user2Id] = explode('_', (string)$identifier);
            $messages = Message::where(function (Builder $query) use ($user1Id, $user2Id) {
                $query->where('sender_id', '=', $user1Id)
                      ->where('receiver_id', '=', $user2Id);
            })->orWhere(function (Builder $query) use ($user1Id, $user2Id) {
                $query->where('sender_id', '=', $user2Id)
                      ->where('receiver_id', '=', $user1Id);
            })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();
        } else {
            $userId = (int)$identifier;
            $authId = $user->id;
            $messages = Message::where(function (Builder $query) use ($userId, $authId) {
                $query->where('sender_id', '=', $userId)
                      ->where('receiver_id', '=', $authId);
            })->orWhere(function (Builder $query) use ($userId, $authId) {
                $query->where('sender_id', '=', $authId)
                      ->where('receiver_id', '=', $userId);
            })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();
        }

        return response()->json([
            'status'   => true,
            'messages' => $messages,
        ]);
    }

    public function index()
    {
        $conversations = $this->getConversationList();

        return Inertia::render('Messages/Index', [
            'conversations' => $conversations
        ]);
    }

    public function show($identifier)
    {
        $user = Auth::user();
        
        if ($user->isAdmin() && str_contains((string)$identifier, '_')) {
            [$user1Id, $user2Id] = explode('_', (string)$identifier);
            $user1 = User::findOrFail($user1Id);
            $user2 = User::findOrFail($user2Id);
            
            // Create a pseudo-user to satisfy the frontend component
            $otherUser = new User();
            $otherUser->id = $identifier;
            $otherUser->username = $user1->username . ' & ' . $user2->username;
            
            $messages = Message::where(function (Builder $query) use ($user1Id, $user2Id) {
                $query->where('sender_id', '=', $user1Id)
                      ->where('receiver_id', '=', $user2Id);
            })->orWhere(function (Builder $query) use ($user1Id, $user2Id) {
                $query->where('sender_id', '=', $user2Id)
                      ->where('receiver_id', '=', $user1Id);
            })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();
        } else {
            $userId = (int)$identifier;
            $otherUser = User::with('organisateur')->findOrFail($userId);
            $authId = Auth::id();
            
            // Mark messages as read only if it's not the admin spying
            Message::where('sender_id', '=', $userId)
                ->where('receiver_id', '=', $authId)
                ->where('lu', '=', false)
                ->update(['lu' => true]);

            $messages = Message::where(function (Builder $query) use ($userId, $authId) {
                $query->where('sender_id', '=', $userId)
                      ->where('receiver_id', '=', $authId);
            })->orWhere(function (Builder $query) use ($userId, $authId) {
                $query->where('sender_id', '=', $authId)
                      ->where('receiver_id', '=', $userId);
            })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();
        }

        $conversations = $this->getConversationList();

        return Inertia::render('Messages/Show', [
            'otherUser' => $otherUser,
            'initialMessages' => $messages,
            'conversations' => $conversations
        ]);
    }

    public function recent()
    {
        $conversations = $this->getConversationList(6);

        return response()->json([
            'status' => true,
            'conversations' => $conversations
        ]);
    }

    private function getConversationList(?int $limit = null)
    {
        $user = Auth::user();
        
        if ($user->isAdmin()) {
            $query = Message::with(['sender.organisateur', 'receiver.organisateur'])
                ->latest()
                ->get()
                ->groupBy(function ($message) {
                    $min = min($message->sender_id, $message->receiver_id);
                    $max = max($message->sender_id, $message->receiver_id);
                    return "{$min}_{$max}";
                });
                
            if ($limit) {
                $query = $query->take($limit);
            }

            return $query->map(function ($messages) {
                $lastMessage = $messages->first();
                $min = min($lastMessage->sender_id, $lastMessage->receiver_id);
                $max = max($lastMessage->sender_id, $lastMessage->receiver_id);
                
                return [
                    'id' => "{$min}_{$max}",
                    'name' => $lastMessage->sender->username . ' & ' . $lastMessage->receiver->username,
                    'avatar' => null,
                    'last_message' => $lastMessage->contenu,
                    'last_message_time' => $lastMessage->created_at->diffForHumans(),
                    'unread_count' => 0,
                ];
            })->values();
        }

        $userId = $user->id;
        
        $query = Message::where('sender_id', '=', $userId)
            ->orWhere('receiver_id', '=', $userId)
            ->with(['sender.organisateur', 'receiver.organisateur'])
            ->latest()
            ->get()
            ->groupBy(function ($message) use ($userId) {
                return $message->sender_id === $userId ? $message->receiver_id : $message->sender_id;
            });
            
        if ($limit) {
            $query = $query->take($limit);
        }

        return $query->map(function ($messages) use ($userId) {
            $lastMessage = $messages->first();
            $otherUser = $lastMessage->sender_id === $userId ? $lastMessage->receiver : $lastMessage->sender;
            
            return [
                'id' => $otherUser->id,
                'name' => $otherUser->username,
                'avatar' => $otherUser->avatar_url ?? null,
                'last_message' => $lastMessage->contenu,
                'last_message_time' => $lastMessage->created_at->diffForHumans(),
                'unread_count' => $messages->where('receiver_id', $userId)->where('lu', false)->count(),
            ];
        })->values();
    }
}