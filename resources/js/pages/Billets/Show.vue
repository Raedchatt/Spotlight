<script setup>
import AppHeader from '@/components/AppHeader.vue';
import { Head, Link } from '@inertiajs/vue3';

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
</script>

<template>
    <Head title="Votre Billet" />

    <AppLayout>
        <div class="py-12 bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-2xl rounded-3xl border border-gray-100 dark:border-gray-700">
                    <div class="p-8 sm:p-12">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12 border-b border-gray-100 dark:border-gray-700 pb-8">
                            <div>
                                <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tight mb-2">
                                    SPOTLIGHT <span class="text-rose-600">.</span>
                                </h1>
                                <p class="text-gray-500 dark:text-gray-400 font-medium">Billet Électronique Officiel</p>
                            </div>
                            <div class="mt-4 md:mt-0 flex gap-4">
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
                                    @click="window.print()"
                                    class="inline-flex items-center px-6 py-3 bg-white dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 text-gray-700 dark:text-white font-bold rounded-2xl hover:bg-gray-50 transition-all text-sm"
                                >
                                    Imprimer
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                            <div class="space-y-8">
                                <div>
                                    <h2 class="text-xs font-bold text-rose-600 uppercase tracking-widest mb-4">Détails de l'évènement</h2>
                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ evenement.titre }}</h3>
                                    <div class="flex items-center text-gray-600 dark:text-gray-400 mb-2">
                                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        {{ formatDate(evenement.date_debut) }}
                                    </div>
                                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        {{ evenement.lieu }}
                                    </div>
                                </div>

                                <div class="bg-gray-50 dark:bg-gray-700/50 p-6 rounded-2xl border border-gray-100 dark:border-gray-600">
                                    <h2 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">Informations Réservation</h2>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <p class="text-gray-500 dark:text-gray-400">Titulaire</p>
                                            <p class="font-bold text-gray-900 dark:text-white">{{ user.username }}</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 dark:text-gray-400">Type</p>
                                            <p class="font-bold text-gray-900 dark:text-white capitalize">{{ reservation.ticket_type }}</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 dark:text-gray-400">Quantité</p>
                                            <p class="font-bold text-gray-900 dark:text-white">{{ reservation.nombre_tickets }} place(s)</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 dark:text-gray-400">Montant payé</p>
                                            <p class="font-bold text-rose-600">
                                                <template v-if="paiement">
                                                    {{ paiement.montant.toLocaleString('fr-FR') }} {{ paiement.currency.toUpperCase() }}
                                                </template>
                                                <template v-else>
                                                    GRATUIT
                                                </template>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col items-center justify-center bg-gray-900 dark:bg-black rounded-3xl p-8 text-white shadow-inner relative overflow-hidden group">
                                <div class="absolute inset-0 bg-gradient-to-br from-rose-600/20 to-transparent opacity-50"></div>
                                
                                <div class="relative z-10 bg-white p-4 rounded-2xl mb-6 shadow-2xl transform group-hover:scale-105 transition-transform duration-500">
                                    <img :src="qrCode" alt="Ticket QR Code" class="w-48 h-48" />
                                </div>
                                
                                <div class="relative z-10 text-center">
                                    <p class="text-xs font-bold text-rose-500 uppercase tracking-tighter mb-1">Code de validation</p>
                                    <p class="text-2xl font-mono tracking-[0.3em] font-black">{{ billet.codeQR }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-12 text-center text-gray-400 text-xs">
                            <p>Ce billet est unique et ne peut être utilisé qu'une seule fois.</p>
                            <p class="mt-1">• Date d'émission : {{ formatDate(billet.dateEmission) }} •</p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 text-center">
                    <Link 
                        href="/discovery" 
                        class="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors text-sm font-medium"
                    >
                        ← Retour aux évènements
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    .py-12 { padding: 0 !important; background: white !important; }
    .shadow-2xl, .bg-gray-50, .max-w-4xl, .sm:px-6, .py-3 { box-shadow: none !important; margin: 0 !important; width: 100% !important; max-width: none !important; }
    button, a[href*="download"], .mt-8 { display: none !important; }
    .bg-white { border: 2px solid #eee !important; }
}
</style>
