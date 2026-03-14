<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Afficher les notifications de l'utilisateur connecté
     */
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderBy('date_envoi', 'desc')
            ->get();

        return response()->json($notifications);
    }

    /**
     * Afficher une notification
     */
    public function show($id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json($notification);
    }

    /**
     * Marquer une notification comme lue
     */
    public function marquerCommeLue($id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->findOrFail($id);

        $notification->marquerCommeLue();

        return response()->json([
            'message' => 'Notification marquée comme lue'
        ]);
    }

    /**
     * Marquer toutes les notifications de l'utilisateur comme lues
     */
    public function marquerToutesCommeLues()
    {
        Notification::marquerTousCommeLue(Auth::id());

        return response()->json([
            'message' => 'Toutes les notifications sont marquées comme lues'
        ]);
    }
}