<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';
import type { Evenement, StatutEvenement } from '@/types/event';
import {
    Search, 
    Calendar, 
    MapPin, 
    Trophy,
    Clock,
    X,
    Eye
} from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import Reserver from '@/components/Reserver.vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Discovery', href: '/dashboard/discovery' },
];

const events = ref<Evenement[]>([]);
const loading = ref(true);

const page = usePage();
const auth = computed(() => page.props.auth as any);

// Filters matching EventsList style
const filters = ref({
    titre: '',
    categorie: 'all',
    date: '',
});

const fetchEvents = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.titre) params.append('titre', filters.value.titre);
        if (filters.value.categorie !== 'all') params.append('categorie', filters.value.categorie);
        if (filters.value.date) params.append('date', filters.value.date);
        
        const response = await axios.get(`/api/events/search?${params.toString()}`);
        // Filter strictly for 'ouvert' events for the Discovery page
        events.value = response.data.filter((e: Evenement) => e.statut === 'ouvert');
    } catch (error) {
        console.error('Error fetching discovery events:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchEvents();
});

// Watch filters for auto-search (matching EventsList behavior)
watch(() => filters.value, () => {
    fetchEvents();
}, { deep: true });

const getStatusVariant = (statut: StatutEvenement) => {
    switch (statut) {
        case 'ouvert': return 'default'; 
        default: return 'outline';
    }
};

const getStatusLabel = (statut: StatutEvenement) => {
    return statut.charAt(0).toUpperCase() + statut.slice(1).replace('_', ' ');
};

// Selection for reservation
const selectedEvent = ref<Evenement | null>(null);

const openReservation = (event: Evenement) => {
    selectedEvent.value = event;
};

const closeReservation = () => {
    selectedEvent.value = null;
};

const resetFilters = () => {
    filters.value = {
        titre: '',
        categorie: 'all',
        date: '',
    };
    fetchEvents();
};
</script>

<template>
    <Head title="Discovery - Explore Events" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Discovery</h1>
                    <p class="text-muted-foreground">Find and book your next amazing experience.</p>
                </div>
                <Badge variant="secondary" class="h-8 px-3 flex items-center gap-2">
                    <Calendar class="w-4 h-4" />
                    <span>{{ events.length }} Events Available</span>
                </Badge>
            </div>

            <!-- Filters Section -->
            <div class="bg-card border rounded-xl p-4 shadow-sm flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px] space-y-1.5">
                    <label class="text-sm font-medium">Search by title</label>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input v-model="filters.titre" placeholder="Event name..." class="pl-10" />
                    </div>
                </div>

                <div class="w-48 space-y-1.5">
                    <label class="text-sm font-medium">Category</label>
                    <select v-model="filters.categorie" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                        <option value="all">All Categories</option>
                        <option value="sportifs">Sports</option>
                        <option value="culturels">Cultural</option>
                        <option value="scientifiques">Scientific</option>
                        <option value="musicaux">Musical</option>
                        <option value="commerciaux">Commercial</option>
                    </select>
                </div>

                <div class="w-48 space-y-1.5">
                    <label class="text-sm font-medium">Date</label>
                    <Input v-model="filters.date" type="date" />
                </div>

                <Button variant="outline" @click="resetFilters" class="h-10">
                    <X class="w-4 h-4 mr-2" />
                    Reset
                </Button>
            </div>

            <!-- Events List -->
            <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Skeleton Loader -->
                <div v-for="n in 6" :key="n" class="h-64 rounded-xl bg-muted animate-pulse border" />
            </div>

            <div v-else-if="events.length === 0" class="text-center py-12 bg-muted/20 rounded-xl border border-dashed">
                <div class="mx-auto w-12 h-12 rounded-full bg-muted flex items-center justify-center mb-4">
                    <Search class="w-6 h-6 text-muted-foreground" />
                </div>
                <h3 class="text-lg font-medium">No open events found</h3>
                <p class="text-muted-foreground">Try adjusting your filters.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="event in events" :key="event.id" 
                    class="group bg-card border rounded-xl overflow-hidden hover:shadow-lg transition-all duration-300 relative"
                    :class="{ 'ring-2 ring-amber-500 shadow-amber-500/10': event.is_tournoi }">
                    
                    <!-- Event Banner Image -->
                    <div 
                        class="h-40 relative bg-cover bg-center"
                        :class="!event.medias?.length ? 'bg-gradient-to-br from-blue-500/10 to-purple-500/10' : ''"
                        :style="event.medias?.length ? { backgroundImage: `url(${event.medias[0].url})` } : {}"
                    >
                        <div class="absolute top-4 left-4 flex flex-col gap-2">
                            <Badge :variant="getStatusVariant(event.statut)" class="capitalize shadow-sm w-fit bg-blue-600 hover:bg-blue-700 border-0 text-white">
                                {{ getStatusLabel(event.statut) }}
                            </Badge>
                            <Badge v-if="event.is_tournoi" variant="default" class="bg-amber-500 hover:bg-amber-600 shadow-sm w-fit border-0 font-bold">
                                <Trophy class="w-3 h-3 mr-1" /> TOURNAMENT
                            </Badge>
                        </div>
                        <div class="absolute bottom-4 right-4 animate-in fade-in zoom-in duration-300">
                             <Link :href="`/events/${event.id}`">
                                <Button size="icon" variant="secondary" class="group/btn h-8 w-8 rounded-full shadow-md hover:bg-black/20 backdrop-blur-sm bg-white/80 transition-all active:scale-90">
                                    <Eye class="w-4 h-4 text-black group-hover/btn:text-white transition-colors" />
                                </Button>
                             </Link>
                        </div>
                    </div>

                    <div class="p-5 space-y-4">
                        <div class="space-y-1">
                            <h2 class="text-xl font-bold line-clamp-1 capitalize">{{ event.titre }}</h2>
                            <Badge variant="outline" class="text-[10px] uppercase font-bold tracking-wider text-muted-foreground">
                                {{ event.categorie }}
                            </Badge>
                        </div>

                        <div class="space-y-3 text-sm text-muted-foreground">
                            <div class="flex items-center gap-2">
                                <Calendar class="w-4 h-4" />
                                <span>{{ new Date(event.date_debut).toLocaleDateString() }} - {{ new Date(event.date_debut).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <MapPin class="w-4 h-4" />
                                <span class="line-clamp-1">{{ event.lieu }}</span>
                            </div>
                        </div>

                        <div class="pt-4 border-t flex justify-between items-center">
                            <div class="flex flex-col">
                                <span class="font-bold text-blue-600">
                                    <template v-if="event.is_tournoi">
                                        {{ event.prix_spectateur > 0 ? `${event.prix_spectateur} TND` : 'Free' }}
                                        <span class="text-[9px] text-muted-foreground ml-1">(Spectator)</span>
                                    </template>
                                    <template v-else>
                                        {{ event.prix_spectateur > 0 ? `${event.prix_spectateur} TND` : 'Free' }}
                                    </template>
                                </span>
                                <span class="text-[10px] text-muted-foreground">
                                    {{ event.capacite_spectateur }} seats left
                                </span>
                            </div>
                            
                            <Button @click="openReservation(event)" size="sm" class="bg-blue-600 hover:bg-blue-700 font-semibold px-4 rounded-lg transition-all active:scale-95 shadow-sm">
                                Book Now
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reservation Modal Backdrop -->
        <div v-if="selectedEvent" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm transition-all duration-300" @click.self="closeReservation">
            <div class="relative w-full max-w-sm transform transition-all duration-300 scale-100">
                <button @click="closeReservation" class="absolute -top-12 right-0 text-white hover:text-gray-200 p-2 bg-black/20 rounded-full transition-colors">
                    <X class="w-6 h-6" />
                </button>
                <Reserver :event="selectedEvent" @reservation-success="closeReservation" />
            </div>
        </div>
    </AppLayout>
</template>
