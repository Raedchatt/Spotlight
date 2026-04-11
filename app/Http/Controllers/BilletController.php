<?php

namespace App\Http\Controllers;

use App\Models\Billet;
use App\Models\Reservation;
use App\Services\TicketService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BilletController extends Controller
{
    protected $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    /**
     * Display the specified ticket.
     */
    public function show(Billet $billet)
    {
        $reservation = $billet->reservation->load(['user', 'evenement']);
        $paiement = $reservation->paiement;
        
        $qrCode = $this->ticketService->getQrCodeBase64($billet->codeQR);

        return Inertia::render('Billets/Show', [
            'billet'      => $billet,
            'reservation' => $reservation,
            'evenement'   => $reservation->evenement,
            'user'        => $reservation->user,
            'paiement'    => $paiement,
            'qrCode'      => $qrCode,
        ]);
    }

    /**
     * Download the ticket as PDF.
     */
    public function downloadPdf(Billet $billet)
    {
        $reservation = $billet->reservation->load(['user', 'evenement']);
        $paiement = $reservation->paiement;
        
        $qrCode = $this->ticketService->getQrCodeBase64($billet->codeQR);

        $pdf = Pdf::loadView('billets.ticket', [
            'billet'      => $billet,
            'reservation' => $reservation,
            'evenement'   => $reservation->evenement,
            'user'        => $reservation->user,
            'paiement'    => $paiement,
            'qrCode'      => $qrCode,
        ]);

        return $pdf->download('ticket-'.$billet->id.'.pdf');
    }
}
