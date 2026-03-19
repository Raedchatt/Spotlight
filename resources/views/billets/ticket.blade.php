<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Billet - {{ $evenement->titre }}</title>
    <style>
        body { 
            font-family: 'DejaVu Sans', sans-serif; 
            color: #1f2937; 
            margin: 0;
            padding: 20px;
            background-color: #f9fafb;
        }
        .ticket {
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header { 
            background-color: #111827; 
            color: #ffffff;
            padding: 25px 30px;
            border-bottom: 5px solid #e11d48;
        }
        .header h1 { 
            margin: 0 0 5px 0; 
            font-size: 32px;
            font-weight: 900;
            letter-spacing: 2px;
        }
        .header .subtitle {
            margin: 0;
            color: #9ca3af;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
        }
        .dot { color: #e11d48; }
        .content {
            padding: 30px;
        }
        .col-left {
            float: left;
            width: 65%;
        }
        .col-right {
            float: right;
            width: 30%;
            text-align: center;
            border-left: 2px dashed #e5e7eb;
            padding-left: 20px;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        .section-title {
            font-size: 11px;
            color: #e11d48;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            font-weight: bold;
        }
        .event-title {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 15px 0;
            color: #111827;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .info-row {
            margin-bottom: 8px;
            font-size: 14px;
            color: #4b5563;
        }
        .info-row strong {
            color: #1f2937;
            display: inline-block;
            width: 90px;
        }
        .reservation-box {
            background-color: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        .reservation-box .info-row strong {
            width: 120px;
        }
        .total-price { 
            font-size: 18px; 
            font-weight: bold; 
            color: #e11d48;
            margin-top: 10px;
            display: inline-block;
        }
        .qr-section {
            padding-top: 10px;
        }
        .qr-label {
            font-size: 10px; 
            color: #6b7280; 
            text-transform: uppercase; 
            margin-bottom: 10px; 
            font-weight: bold;
            letter-spacing: 1px;
        }
        .qr-code img { 
            width: 140px; 
            height: 140px; 
            border: 1px solid #e5e7eb;
            padding: 5px;
            border-radius: 8px;
            background: #fff;
        }
        .qr-text {
            font-family: monospace;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 3px;
            margin-top: 15px;
            color: #111827;
            background-color: #f3f4f6;
            padding: 8px;
            border-radius: 6px;
        }
        .footer { 
            text-align: center; 
            font-size: 11px; 
            color: #6b7280; 
            padding: 15px 30px;
            border-top: 2px dashed #e5e7eb;
            background-color: #ffffff;
        }
        .footer p { margin: 4px 0; }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <h1>SPOTLIGHT<span class="dot">.</span></h1>
            <p class="subtitle">Billet Électronique Officiel</p>
        </div>

        <div class="content clearfix">
            <div class="col-left">
                <div class="section-title">Détails de l'évènement</div>
                <h2 class="event-title">{{ $evenement->titre }}</h2>
                
                <div class="info-row">
                    <strong>Date :</strong> {{ \Carbon\Carbon::parse($evenement->date_debut)->format('d/m/Y H:i') }}
                </div>
                <div class="info-row">
                    <strong>Lieu :</strong> {{ $evenement->lieu }}
                </div>

                <div class="reservation-box">
                    <div class="section-title" style="color:#6b7280;">Informations Réservation</div>
                    <div class="info-row"><strong>Titulaire :</strong> {{ $user->username }}</div>
                    <div class="info-row"><strong>Type :</strong> <span style="text-transform: capitalize;">{{ $reservation->ticket_type }}</span></div>
                    <div class="info-row"><strong>Quantité :</strong> {{ $reservation->nombre_tickets }} place(s)</div>
                    <div class="info-row" style="margin-top: 15px; border-top: 1px solid #e5e7eb; padding-top: 15px;">
                        <strong>Montant payé :</strong>
                        <span class="total-price">
                            @if($paiement)
                                {{ number_format($paiement->montant, 2) }} TND
                            @else
                                GRATUIT
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-right">
                <div class="qr-section">
                    <div class="qr-label">Code d'entrée</div>
                    <div class="qr-code">
                        <img src="{{ $qrCode }}" alt="Code QR">
                    </div>
                    <div class="qr-text">{{ $billet->codeQR }}</div>
                    <div style="font-size: 10px; color:#9ca3af; margin-top: 15px; text-transform: uppercase; font-weight: bold;">Valide pour 1 entrée</div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Ce billet est unique et ne peut être utilisé qu'une seule fois.</p>
            <p>Généré le {{ \Carbon\Carbon::parse($billet->dateEmission)->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
