<script setup>
import AppHeader from '@/components/AppHeader.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

const props = defineProps({
    billet: Object,
    reservation: Object,
    evenement: Object,
    user: Object,
    paiement: Object,
    qrCode: String,
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const printTicket = () => {
    window.print();
};
</script>

<template>
    <!-- Screen View -->
    <div class="print:hidden">
        <Head title="Votre Billet" />

        <AppLayout>
            <div class="py-12 bg-gray-50 dark:bg-gray-900 min-h-screen">
                <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div class="mb-6 print:hidden">
                        <Link 
                            href="/discovery" 
                            class="inline-flex items-center text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors text-sm font-bold gap-2 bg-white dark:bg-gray-800 px-4 py-2 rounded-xl shadow-sm hover:shadow-md border border-gray-100 dark:border-gray-700 w-fit"
                        >
                            <ArrowLeft class="w-4 h-4" />
                            Retour aux évènements
                        </Link>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-2xl rounded-3xl border border-gray-100 dark:border-gray-700 ticket-wrapper">
                        <div class="p-8 sm:p-12 relative">
                            <!-- Decorative Top Border -->
                            <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-rose-500 via-rose-600 to-rose-500 print:hidden"></div>
                            
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12 border-b border-gray-100 dark:border-gray-700 pb-8">
                                <div>
                                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tight mb-2">
                                        SPOTLIGHT <span class="text-rose-600">.</span>
                                    </h1>
                                    <p class="text-gray-500 dark:text-gray-400 font-medium tracking-wide text-sm uppercase">Billet Électronique Officiel</p>
                                </div>
                                <div class="mt-4 md:mt-0 flex gap-4 print:hidden">
                                    <a 
                                        :href="`/tickets/${billet.id}/download`"
                                        class="inline-flex items-center px-6 py-3 bg-gray-900 dark:bg-white dark:text-gray-900 text-white font-bold rounded-2xl hover:scale-105 transition-all shadow-lg text-sm"
                                    >
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        Télécharger PDF
                                    </a>
                                    <button 
                                        @click="printTicket"
                                        class="inline-flex items-center px-6 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 text-gray-700 dark:text-white font-bold rounded-2xl hover:bg-gray-50 transition-all text-sm"
                                    >
                                        Imprimer
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 relative">
                                <!-- Dashed Divider -->
                                <div class="hidden md:block absolute left-1/2 top-0 bottom-0 border-r-2 border-dashed border-gray-200 dark:border-gray-700 -ml-px print:border-gray-300"></div>
                                
                                <div class="space-y-8 md:pr-6">
                                    <div>
                                        <h2 class="text-xs font-bold text-rose-600 uppercase tracking-widest mb-4">Détails de l'évènement</h2>
                                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2 uppercase">{{ evenement.titre }}</h3>
                                        <div class="flex items-center text-gray-600 dark:text-gray-300 mb-2 font-medium">
                                            <svg class="w-5 h-5 mr-2 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            {{ formatDate(evenement.date_debut) }}
                                        </div>
                                        <div class="flex items-center text-gray-600 dark:text-gray-300 font-medium">
                                            <svg class="w-5 h-5 mr-2 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            {{ evenement.lieu }}
                                        </div>
                                    </div>

                                    <div class="bg-gray-50/80 dark:bg-gray-700/50 p-6 rounded-2xl border border-gray-100 dark:border-gray-600 shadow-sm backdrop-blur-sm print:bg-white print:border-gray-200 text-sm">
                                        <h2 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">Informations Réservation</h2>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <p class="text-gray-500 dark:text-gray-400">Titulaire</p>
                                                <p class="font-bold text-gray-900 dark:text-white text-base">{{ user.username }}</p>
                                            </div>
                                            <div>
                                                <p class="text-gray-500 dark:text-gray-400">Type</p>
                                                <p class="font-bold text-gray-900 dark:text-white capitalize text-base">{{ reservation.ticket_type }}</p>
                                            </div>
                                            <div>
                                                <p class="text-gray-500 dark:text-gray-400">Quantité</p>
                                                <p class="font-bold text-gray-900 dark:text-white text-base">{{ reservation.nombre_tickets }} place(s)</p>
                                            </div>
                                            <div>
                                                <p class="text-gray-500 dark:text-gray-400">Montant payé</p>
                                                <p class="font-bold text-rose-600 text-lg">
                                                    <template v-if="paiement">
                                                        {{ paiement.montant.toLocaleString('fr-FR') }} TND
                                                    </template>
                                                    <template v-else>
                                                        GRATUIT
                                                    </template>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col items-center justify-center bg-gray-900 dark:bg-gray-800 rounded-3xl p-8 text-white shadow-inner relative overflow-hidden group md:ml-6 print:bg-white print:border print:border-gray-200">
                                    <div class="absolute inset-0 bg-gradient-to-br from-rose-600/20 to-transparent opacity-50 print:hidden"></div>
                                    
                                    <div class="relative z-10 bg-white p-4 rounded-2xl mb-6 shadow-2xl transform group-hover:scale-105 transition-transform duration-500 print:shadow-none print:border print:border-gray-300">
                                        <img :src="qrCode" alt="Ticket QR Code" class="w-48 h-48" />
                                    </div>
                                    
                                    <div class="relative z-10 text-center">
                                        <p class="text-xs font-bold text-rose-500 uppercase tracking-tighter mb-1">Code de validation</p>
                                        <p class="text-2xl font-mono tracking-[0.3em] font-black print:text-black">{{ billet.codeQR }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-12 text-center text-gray-400 text-xs">
                                <p>Ce billet est unique et ne peut être utilisé qu'une seule fois.</p>
                                <p class="mt-1">• Date d'émission : {{ formatDate(billet.dateEmission) }} •</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8 text-center print:hidden">
                        <Link 
                            href="/discovery" 
                            class="inline-flex items-center text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors text-sm font-medium gap-2"
                        >
                            <ArrowLeft class="w-4 h-4" />
                            Retour aux évènements
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    </div>

    <!-- Print Only View (Exact same layout as PDF) -->
    <div class="hidden print:block print-ticket-container">
        <div class="p-header">
            <h1>SPOTLIGHT<span class="p-dot">.</span></h1>
            <p class="p-subtitle">Billet Électronique Officiel</p>
        </div>

        <div class="p-content p-clearfix">
            <div class="p-col-left">
                <div class="p-section-title">Détails de l'évènement</div>
                <h2 class="p-event-title">{{ evenement.titre }}</h2>
                
                <div class="p-info-row">
                    <strong>Date :</strong> {{ formatDate(evenement.date_debut) }}
                </div>
                <div class="p-info-row">
                    <strong>Lieu :</strong> {{ evenement.lieu }}
                </div>

                <div class="p-reservation-box">
                    <div class="p-section-title" style="color:#6b7280;">Informations Réservation</div>
                    <div class="p-info-row"><strong>Titulaire :</strong> {{ user.username }}</div>
                    <div class="p-info-row"><strong>Type :</strong> <span style="text-transform: capitalize;">{{ reservation.ticket_type }}</span></div>
                    <div class="p-info-row"><strong>Quantité :</strong> {{ reservation.nombre_tickets }} place(s)</div>
                    <div class="p-info-row" style="margin-top: 15px; border-top: 1px solid #e5e7eb; padding-top: 15px;">
                        <strong>Montant payé :</strong>
                        <span class="p-total-price">
                            <template v-if="paiement">
                                {{ paiement.montant.toLocaleString('fr-FR') }} TND
                            </template>
                            <template v-else>
                                GRATUIT
                            </template>
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-col-right">
                <div class="p-qr-section">
                    <div class="p-qr-label">Code d'entrée</div>
                    <div class="p-qr-code">
                        <img :src="qrCode" alt="Code QR">
                    </div>
                    <div class="p-qr-text">{{ billet.codeQR }}</div>
                    <div style="font-size: 10px; color:#9ca3af; margin-top: 15px; text-transform: uppercase; font-weight: bold;">Valide pour 1 entrée</div>
                </div>
            </div>
        </div>

        <div class="p-footer">
            <p>Ce billet est unique et ne peut être utilisé qu'une seule fois.</p>
            <p>Généré le {{ formatDate(billet.dateEmission) }}</p>
        </div>
    </div>
</template>

<style scoped>
.ticket-wrapper {
    filter: drop-shadow(0 25px 25px rgba(0, 0, 0, 0.15)) drop-shadow(0 5px 10px rgba(0, 0, 0, 0.05));
}
.dark .ticket-wrapper {
    filter: drop-shadow(0 25px 25px rgba(0, 0, 0, 0.5));
}

@media print {
    @page { margin: 15mm; size: auto; }
    
    html, body {
        height: 100vh;
        margin: 0 !important;
        padding: 0 !important;
        background-color: transparent !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Hide the AppLayout and everything in it */
    .print\:hidden {
        display: none !important;
    }

    /* Show the print only layout */
    .hidden.print\:block {
        display: block !important;
    }

    /* Exactly matches ticket.blade.php CSS properties */
    .print-ticket-container {
        position: relative;
        width: 100%;
        max-width: 700px;
        margin: 0 auto;
        background-color: #ffffff !important;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        color: #1f2937;
        font-family: 'DejaVu Sans', sans-serif, system-ui;
        page-break-inside: avoid;
    }
    
    .p-header { 
        background-color: #111827 !important; 
        color: #ffffff !important;
        padding: 25px 30px;
        border-bottom: 5px solid #e11d48 !important;
        text-align: center;
    }
    .p-header h1 { 
        margin: 0 0 5px 0; 
        font-size: 32px;
        font-weight: 900;
        letter-spacing: 2px;
    }
    .p-subtitle {
        margin: 0;
        color: #9ca3af !important;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: bold;
    }
    .p-dot { color: #e11d48 !important; }
    .p-content { padding: 30px; }
    .p-col-left { float: left; width: 65%; }
    .p-col-right {
        float: right;
        width: 30%;
        text-align: center;
        border-left: 2px dashed #e5e7eb;
        padding-left: 20px;
    }
    .p-clearfix::after { content: ""; clear: both; display: table; }
    .p-section-title {
        font-size: 11px;
        color: #e11d48 !important;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 12px;
        font-weight: bold;
    }
    .p-event-title {
        font-size: 24px;
        font-weight: bold;
        margin: 0 0 15px 0;
        color: #111827 !important;
        text-transform: uppercase;
        line-height: 1.2;
    }
    .p-info-row { margin-bottom: 8px; font-size: 14px; color: #4b5563 !important; }
    .p-info-row strong { color: #1f2937 !important; display: inline-block; width: 90px; }
    .p-reservation-box {
        background-color: #f3f4f6 !important;
        padding: 20px;
        border-radius: 8px;
        margin-top: 30px;
    }
    .p-reservation-box .p-info-row strong { width: 120px; }
    .p-total-price { 
        font-size: 18px !important; 
        font-weight: bold !important; 
        color: #e11d48 !important;
        margin-top: 10px;
        display: inline-block;
    }
    .p-qr-section { padding-top: 10px; }
    .p-qr-label {
        font-size: 10px; 
        color: #6b7280 !important; 
        text-transform: uppercase; 
        margin-bottom: 10px; 
        font-weight: bold;
        letter-spacing: 1px;
    }
    .p-qr-code img { 
        width: 140px; 
        height: 140px; 
        border: 1px solid #e5e7eb;
        padding: 5px;
        border-radius: 8px;
        background: #fff !important;
    }
    .p-qr-text {
        font-family: monospace;
        font-size: 18px;
        font-weight: bold;
        letter-spacing: 3px;
        margin-top: 15px;
        color: #111827 !important;
        background-color: #f3f4f6 !important;
        padding: 8px;
        border-radius: 6px;
    }
    .p-footer { 
        text-align: center; 
        font-size: 11px; 
        color: #6b7280 !important; 
        padding: 15px 30px;
        border-top: 2px dashed #e5e7eb;
        background-color: #ffffff !important;
    }
    .p-footer p { margin: 4px 0; }
}
</style>
