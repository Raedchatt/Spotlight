<script setup lang="ts">

import { Head } from '@inertiajs/vue3';

import axios from 'axios';

import {
    Search, 
    Calendar, 
    X,
} from 'lucide-vue-next';
import { ref, onMounted, watch} from 'vue';
import EventCard from '@/components/EventCard.vue';
import Reserver from '@/components/Reserver.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';

import type { Evenement } from '@/types/event';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Discovery', href: '/dashboard/discovery' },
];

const events = ref<Evenement[]>([]);
const loading = ref(true);

//const page = usePage();
//const auth = computed(() => page.props.auth as any);

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
        
        // Request 'ouvert', 'valide', 'encours', and 'en_attente' statuses from the backend
        params.append('statut', 'ouvert,valide,encours,en_attente');
        
        const response = await axios.get(`/api/events/search?${params.toString()}`);
        events.value = response.data;
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

// Selection for reservation

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
                <EventCard 
                    v-for="event in events" 
                    :key="event.id" 
                    :event="event" 
                    @book="openReservation" 
                />
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
