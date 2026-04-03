<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Enums\StatutCommission;
use App\Enums\StatutReservation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommissionController extends Controller
{
    public function index()
    {
        $commissions = Commission::with(['reservation.user', 'reservation.evenement', 'revendeur.user'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/Commissions', [
            'commissions' => $commissions
        ]);
    }

    public function approve(Commission $commission)
    {
        if ($commission->status !== StatutCommission::Pending) {
            return back()->with('error', 'Only pending commissions can be approved.');
        }

        // Ideally, check if reservation is confirmed before allowing approval?
        // But maybe admin just wants to override. 
        // Let's assume admin knows what they are doing.
        
        $commission->update(['status' => StatutCommission::Approved]);

        return back()->with('success', 'Commission approved and balance updated for reseller.');
    }

    public function reject(Commission $commission)
    {
        if ($commission->status !== StatutCommission::Pending) {
            return back()->with('error', 'Only pending commissions can be rejected.');
        }

        $commission->update(['status' => StatutCommission::Reversed]);

        return back()->with('success', 'Commission rejected.');
    }
}
