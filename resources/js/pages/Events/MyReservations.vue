<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import {
    Search, 
    Calendar, 
    MapPin, 
    Clock,
    X,
    Ticket,
    AlertCircle,
    CheckCircle2,
    XCircle
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import AppHeader from '@/components/AppHeader.vue';
import AppFooter from '@/components/AppFooter.vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'My Reservations', href: '/dashboard/reservations' },
];

const reservations = ref<any[]>([]);
const loading = ref(true);

const fetchReservations = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/my-reservations');
        reservations.value = response.data.reservations;
    } catch (error) {
        console.error('Error fetching reservations:', error);
    } finally {
        loading.value = false;
    }
};

const cancelReservation = async (id: number) => {
    if (!confirm('Voulez-vous vraiment annuler cette réservation ?')) return;

    try {
        await axios.patch(`/api/reservations/${id}/annuler`);
        await fetchReservations();
    } catch (error: any) {
        alert(error.response?.data?.message || 'Une erreur est survenue lors de l\'annulation.');
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
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'TND'
    }).format(price);
};
</script>

<template>
    <Head title="My Reservations - Spotlight" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">My Reservations</h1>
                    <p class="text-muted-foreground">Manage your event bookings and track their status.</p>
                </div>
                <Badge variant="secondary" class="h-8 px-3 flex items-center gap-2">
                    <Ticket class="w-4 h-4" />
                    <span>{{ reservations.length }} Reservations</span>
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
                <h3 class="text-lg font-medium">No reservations found</h3>
                <p class="text-muted-foreground mb-4">You haven't made any reservations yet.</p>
                <Link href="/dashboard/discovery">
                    <Button variant="default" class="bg-blue-600 hover:bg-blue-700">Explore Events</Button>
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
                                {{ res.statut }}
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
                                <span class="font-medium text-foreground">{{ res.nombre_tickets }} Tickets</span>
                            </div>
                        </div>

                        <div class="pt-4 border-t flex justify-between items-center">
                            <div class="flex flex-col">
                                <span class="text-[10px] uppercase font-bold text-muted-foreground tracking-wider">Total Amount</span>
                                <span class="font-bold text-blue-600">
                                    {{ formatPrice(res.nombre_tickets * res.evenement.prix_spectateur) }}
                                </span>
                            </div>
                            
                            <Button 
                                v-if="res.statut !== 'cancelled'"
                                @click="cancelReservation(res.id)" 
                                size="sm" 
                                variant="outline" 
                                class="text-destructive hover:bg-destructive/5 border-destructive"
                            >
                                <XCircle class="w-4 h-4 mr-1" /> Annuler
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
    <AppFooter/>
</template>
