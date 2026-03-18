<?php

namespace App\Services;

use App\Models\Billet;
use App\Models\Reservation;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\Storage;

class TicketService
{
    /**
     * Generate a ticket for a reservation.
     */
    public function generate(Reservation $reservation): Billet
    {
        $user = $reservation->user;
        
        // Generate QR Code Content: reservationId + participantId + 2firstAlphaFromEmail
        $emailPrefix = substr($user->email, 0, 2);
        $qrContent = $reservation->id . $user->id . $emailPrefix;

        // Generate QR Code Image
        $result = clone (new Builder(
            writer: new SvgWriter(),
            writerOptions: [],
            data: $qrContent,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        ))->build();

        // Save QR code to storage (optional, but let's store the string for regeneration or just the image path)
        // For simplicity, we'll store the content in the database and generate on the fly or store the base64
        
        return Billet::create([
            'reservation_id' => $reservation->id,
            'codeQR'         => $qrContent, // Storing the raw content
            'dateEmission'   => now(),
            'statut'         => 'valide',
        ]);
    }

    /**
     * Get QR Code as base64 string.
     */
    public function getQrCodeBase64(string $content): string
    {
        $result = clone (new Builder(
            writer: new SvgWriter(),
            data: $content,
            encoding: new Encoding('UTF-8'),
            size: 200,
            margin: 0
        ))->build();

        return $result->getDataUri();
    }
}
