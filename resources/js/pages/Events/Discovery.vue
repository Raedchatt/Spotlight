<script setup lang="ts">

import { Head } from '@inertiajs/vue3';

import axios from 'axios';

import {
    Search, 
    Calendar, 
    X,
    ChevronLeft,
    ChevronRight
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
    { title: 'Discovery', href: '/discovery' },
];

const events = ref<Evenement[]>([]);
const loading = ref(true);

// Pagination state
const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 9
});

// Filters matching EventsList style
const filters = ref({
    titre: '',
    categorie: 'all',
    date: '',
    page: 1,
});

const fetchEvents = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.titre) params.append('titre', filters.value.titre);
        if (filters.value.categorie !== 'all') params.append('categorie', filters.value.categorie);
        if (filters.value.date) params.append('date', filters.value.date);
        
        // Pagination params
        params.append('page', filters.value.page.toString());
        params.append('per_page', '9');
        
        // Request 'ouvert', 'valide', 'encours', and 'en_attente' statuses from the backend
        params.append('statut', 'ouvert,valide,encours,en_attente');
        
        const response = await axios.get(`/api/events/search?${params.toString()}`);
        
        // Handle paginated response
        if (response.data.data) {
            events.value = response.data.data;
            pagination.value = {
                currentPage: response.data.current_page,
                lastPage: response.data.last_page,
                total: response.data.total,
                perPage: response.data.per_page
            };
        } else {
            events.value = response.data;
        }
    } catch (error) {
        console.error('Error fetching discovery events:', error);
    } finally {
        loading.value = false;
    }
};

const handlePageChange = (newPage: number) => {
    if (newPage >= 1 && newPage <= pagination.value.lastPage) {
        filters.value.page = newPage;
    }
};

onMounted(() => {
    fetchEvents();
});

// Watch filters for auto-search
watch(() => [filters.value.titre, filters.value.categorie, filters.value.date], () => {
    filters.value.page = 1; // Reset to page 1 when criteria change
    fetchEvents();
});

// Watch page separately
watch(() => filters.value.page, () => {
    fetchEvents();
});

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
        page: 1,
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
                    <span>{{ pagination.total }} Events Available</span>
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
                <div v-for="n in 6" :key="n" class="h-[400px] rounded-xl bg-muted/50 animate-pulse border border-border" />
            </div>

            <div v-else-if="events.length === 0" class="text-center py-12 bg-muted/20 rounded-xl border border-dashed">
                <div class="mx-auto w-12 h-12 rounded-full bg-muted flex items-center justify-center mb-4">
                    <Search class="w-6 h-6 text-muted-foreground" />
                </div>
                <h3 class="text-lg font-medium">No open events found</h3>
                <p class="text-muted-foreground">Try adjusting your filters.</p>
            </div>

            <div v-else class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <EventCard 
                        v-for="event in events" 
                        :key="event.id" 
                        :event="event" 
                        @book="openReservation" 
                    />
                </div>

                <!-- Pagination Controls -->
                <div v-if="pagination.lastPage > 1" class="flex items-center justify-center gap-2 pt-6">
                    <Button 
                        variant="outline" 
                        size="sm" 
                        :disabled="pagination.currentPage === 1"
                        @click="handlePageChange(pagination.currentPage - 1)"
                    >
                        <ChevronLeft class="w-4 h-4 mr-2" /> Previous
                    </Button>
                    
                    <div class="flex items-center gap-1">
                        <Button 
                            v-for="p in pagination.lastPage" 
                            :key="p"
                            :variant="p === pagination.currentPage ? 'default' : 'outline'"
                            size="sm"
                            class="w-9"
                            @click="handlePageChange(p)"
                        >
                            {{ p }}
                        </Button>
                    </div>

                    <Button 
                        variant="outline" 
                        size="sm" 
                        :disabled="pagination.currentPage === pagination.lastPage"
                        @click="handlePageChange(pagination.currentPage + 1)"
                    >
                        Next <ChevronRight class="w-4 h-4 ml-2" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Reservation Modal Backdrop -->
        <div v-if="selectedEvent" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-background/80 backdrop-blur-sm transition-all duration-300" @click.self="closeReservation">
            <div class="relative w-full max-w-sm transform transition-all duration-300 scale-100">
                <button @click="closeReservation" class="absolute -top-12 right-0 text-white hover:text-gray-200 p-2 bg-black/20 rounded-full transition-colors">
                    <X class="w-6 h-6" />
                </button>
                <Reserver :event="selectedEvent" @reservation-success="closeReservation" />
            </div>
        </div>
    </AppLayout>
</template>
