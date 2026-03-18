<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Billet - {{ $evenement->titre }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; color: #333; }
        .ticket {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            border: 2px solid #000;
            padding: 20px;
            position: relative;
        }
        .header { text-align: center; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #1a56db; }
        .details { margin-bottom: 20px; }
        .details div { margin-bottom: 5px; }
        .qr-code { text-align: center; margin-top: 20px; }
        .qr-code img { width: 150px; height: 150px; }
        .footer { text-align: center; font-size: 12px; color: #777; margin-top: 30px; }
        .price { font-size: 20px; font-weight: bold; color: #e11d48; }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <h1>SPOTLIGHT</h1>
            <p>Billet Officiel</p>
        </div>

        <div class="details">
            <div><strong>Évènement :</strong> {{ $evenement->titre }}</div>
            <div><strong>Date :</strong> {{ $evenement->date_debut->format('d/m/Y H:i') }}</div>
            <div><strong>Lieu :</strong> {{ $evenement->lieu }}</div>
            <br>
            <div><strong>Participant :</strong> {{ $user->username }}</div>
            <div><strong>Type de ticket :</strong> {{ ucfirst($reservation->ticket_type) }}</div>
            <div><strong>Nombre de places :</strong> {{ $reservation->nombre_tickets }}</div>
        </div>

        <div class="price">
            @if($paiement)
                PRIX TOTAL : {{ number_format($paiement->montant, 2) }} {{ strtoupper($paiement->currency) }}
            @else
                PRIX TOTAL : GRATUIT
            @endif
        </div>

        <div class="qr-code">
            <img src="{{ $qrCode }}" alt="Code QR">
            <p>{{ $billet->codeQR }}</p>
        </div>

        <div class="footer">
            <p>Présentez ce QR Code à l'entrée de l'évènement.</p>
            <p>Généré le {{ $billet->dateEmission->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
