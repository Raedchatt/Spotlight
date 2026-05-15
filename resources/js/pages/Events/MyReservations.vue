<script setup lang="ts">

import { Head, Link } from '@inertiajs/vue3';

import axios from 'axios';
import { toast } from 'vue-sonner';
import { 
    Calendar, 
    MapPin, 
    Clock,
    Ticket,
    AlertCircle,
    CheckCircle2,
    XCircle,
    CreditCard
} from 'lucide-vue-next';
import { ref, onMounted} from 'vue';
import { useI18n } from 'vue-i18n';
//import AppHeader from '@/components/AppHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';

const reservations = ref<any[]>([]);
const loading = ref(true);
const { t } = useI18n();

const fetchReservations = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/web-api/my-reservations');
        reservations.value = response.data.reservations;
    } catch (error) {
        console.error('Error fetching reservations:', error);
    } finally {
        loading.value = false;
    }
};

const cancelReservation = async (id: number) => {
    if (!confirm(t('events.confirmCancelReservation'))) return;

    try {
        await axios.patch(`/web-api/reservations/${id}/annuler`);
        toast.success(t('events.reservationCancelled'));
        await fetchReservations();
    } catch (error: any) {
        toast.error(error.response?.data?.message || t('events.errorCancelReservation'));
    }
};

const checkoutReservation = async (id: number) => {
    try {
        const response = await axios.post(`/web-api/paiement/checkout/${id}`);
        if (response.data.checkout_url) {
            window.location.href = response.data.checkout_url;
        }
    } catch (error: any) {
        toast.error(error.response?.data?.message || t('events.errorCheckout'));
    }
};

onMounted(() => {
    fetchReservations();
});

const getStatusVariant = (statut: string) => {
    switch (statut) {
        case 'confirmed': return 'default'; // Blue in our theme usually
        case 'pending': return 'secondary';
        case 'cancelled': return 'destructive';
        default: return 'outline';
    }
};

const getStatusIcon = (statut: string) => {
    switch (statut) {
        case 'confirmed': return CheckCircle2;
        case 'pending': return Clock;
        case 'cancelled': return XCircle;
        default: return AlertCircle;
    }
};

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'TND'
    }).format(price);
};

const isEventEnded = (dateString: string) => {
    return new Date(dateString) < new Date();
};
</script>

<template>
    <Head :title="`${t('events.myReservations')} - Spotlight`" />

    <AppLayout>
        <div class="p-6 space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ t('events.myReservations') }}</h1>
                    <p class="text-muted-foreground">{{ t('events.myReservationsDesc') }}</p>
                </div>
                <Badge variant="secondary" class="h-8 px-3 flex items-center gap-2">
                    <Ticket class="w-4 h-4" />
                    <span>{{ t('events.reservationsCount', { count: reservations.length }) }}</span>
                </Badge>
            </div>

            <!-- Reservations List -->
            <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Skeleton Loader -->
                <div v-for="n in 6" :key="n" class="h-64 rounded-xl bg-muted animate-pulse border" />
            </div>

            <div v-else-if="reservations.length === 0" class="text-center py-12 bg-muted/20 rounded-xl border border-dashed">
                <div class="mx-auto w-12 h-12 rounded-full bg-muted flex items-center justify-center mb-4">
                    <Ticket class="w-6 h-6 text-muted-foreground" />
                </div>
                <h3 class="text-lg font-medium">{{ t('events.noReservationsFound') }}</h3>
                <p class="text-muted-foreground mb-4">{{ t('events.noReservationsDesc') }}</p>
                <Link href="/dashboard/discovery">
                    <Button variant="default" class="bg-blue-600 hover:bg-blue-700">{{ t('events.exploreEvents') }}</Button>
                </Link>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="res in reservations" :key="res.id" 
                    class="group bg-card border rounded-xl overflow-hidden hover:shadow-lg transition-all duration-300 relative flex flex-col">
                    
                    <div class="p-5 space-y-4 flex-1">
                        <div class="flex justify-between items-start gap-2">
                             <div class="space-y-1">
                                <h2 class="text-xl font-bold line-clamp-1 capitalize">{{ res.evenement.titre }}</h2>
                                <Badge variant="outline" class="text-[10px] uppercase font-bold tracking-wider text-muted-foreground">
                                    ID: #{{ res.id }}
                                </Badge>
                             </div>
                             <Badge :variant="getStatusVariant(res.statut)" class="capitalize flex items-center gap-1 shadow-sm px-2" :class="res.statut === 'confirmed' ? 'bg-blue-600 hover:bg-blue-700 border-0 text-white' : ''">
                                <component :is="getStatusIcon(res.statut)" class="w-3 h-3" />
                                {{ t(`events.status_${res.statut}`) }}
                             </Badge>
                        </div>

                        <div class="space-y-3 text-sm text-muted-foreground">
                            <div class="flex items-center gap-2">
                                <Calendar class="w-4 h-4" />
                                <span>{{ new Date(res.evenement.date_debut).toLocaleDateString() }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <MapPin class="w-4 h-4" />
                                <span class="line-clamp-1">{{ res.evenement.lieu }}</span>
                            </div>
                             <div class="flex items-center gap-2">
                                <Ticket class="w-4 h-4" />
                                <span class="font-medium text-foreground">{{ res.nombre_tickets }} {{ t('events.tickets') }}</span>
                            </div>
                        </div>

                        <div class="pt-4 border-t flex justify-between items-center">
                            <div class="flex flex-col">
                                <span class="text-[10px] uppercase font-bold text-muted-foreground tracking-wider">{{ t('events.totalAmount') }}</span>
                                <span class="font-bold text-blue-600">
                                    {{ formatPrice(res.nombre_tickets * (res.ticket_type === 'participant' ? (res.evenement.prix_participant ?? res.evenement.prix_spectateur) : res.evenement.prix_spectateur)) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <a v-if="res.statut === 'confirmed' && res.billets && res.billets.length > 0" 
                                   :href="`/tickets/${res.billets[0].id}`" 
                                   target="_blank">
                                    <Button 
                                        type="button"
                                        size="sm" 
                                        variant="default" 
                                        class="bg-blue-600 hover:bg-blue-700"
                                    >
                                        <Ticket class="w-4 h-4 mr-1" /> {{ t('events.ticket') }}
                                    </Button>
                                </a>
                                <Button
                                    v-if="res.statut === 'pending'"
                                    @click="checkoutReservation(res.id)"
                                    size="sm"
                                    variant="default"
                                    class="bg-green-600 hover:bg-green-700 text-white"
                                >
                                    <CreditCard class="w-4 h-4 mr-1" /> {{ t('events.checkout') }}
                                </Button>
                                <Button 
                                    v-if="res.statut !== 'cancelled'"
                                    @click="cancelReservation(res.id)"
                                    :disabled="isEventEnded(res.evenement.date_fin)"
                                    size="sm" 
                                    variant="outline" 
                                    class="text-destructive hover:bg-destructive/5 border-destructive disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <XCircle class="w-4 h-4 mr-1" /> {{ t('common.cancel') }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
