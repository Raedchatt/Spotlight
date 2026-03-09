<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';

import axios from 'axios';

import {
    Search, 
    Plus, 
    Edit, 
    Trash2, 
    Calendar, 
    MapPin,  
    X,
    Trophy,
    Eye
} from 'lucide-vue-next';
import { ref, onMounted, watch, computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Evenement,StatutEvenement } from '@/types/event';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Events', href: '/dashboard/events' },
];

const events = ref<Evenement[]>([]);
const loading = ref(true);

const page = usePage();
const auth = computed(() => page.props.auth as any);

// Filters
const filters = ref({
    titre: '',
    categorie: 'all',
    date: '',
    statut: 'all'
});

const fetchEvents = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.titre) params.append('titre', filters.value.titre);
        if (filters.value.categorie !== 'all') params.append('categorie', filters.value.categorie);
        if (filters.value.date) params.append('date', filters.value.date);
        
        const response = await axios.get(`/api/events/search?${params.toString()}`);
        events.value = response.data;
    } catch (error) {
        console.error('Error fetching events:', error);
    } finally {
        loading.value = false;
    }
};

const deleteEvent = async (id: number) => {
    if (!confirm('Are you sure you want to delete this event?')) return;
    
    try {
        await axios.delete(`/api/events/${id}`);
        events.value = events.value.filter(e => e.id !== id);
    } catch (error) {
        console.error('Error deleting event:', error);
    }
};

const resetFilters = () => {
    filters.value = {
        titre: '',
        categorie: 'all',
        date: '',
        statut: 'all'
    };
    fetchEvents();
};

onMounted(() => {
    fetchEvents();
});

// Watch filters for auto-search
watch(() => filters.value, () => {
    // Optional: Debounce search
    fetchEvents();
}, { deep: true });

const getStatusVariant = (statut: StatutEvenement) => {
    switch (statut) {
        case 'ouvert': return 'default'; 
        case 'ferme': return 'secondary';
        case 'encours': return 'outline';
        case 'en_attente': return 'secondary';
        case 'annule': return 'destructive';
        default: return 'outline';
    }
};

const getStatusLabel = (statut: StatutEvenement) => {
    return statut.charAt(0).toUpperCase() + statut.slice(1).replace('_', ' ');
};

</script>

<template>
    <Head title="Events Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">My Hosted Events</h1>
                    <p class="text-muted-foreground">Manage and track your published events and their status.</p>
                </div>
                <Link v-if="auth.user.role !== 'participant'" href="/dashboard/events/create">
                    <Button class="bg-blue-600 hover:bg-blue-700">
                        <Plus class="w-4 h-4 mr-2" />
                        Create New Event
                    </Button>
                </Link>
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
                        <option value="sportifs">Sportifs</option>
                        <option value="culturels">Culturels</option>
                        <option value="scientifiques">Scientifiques</option>
                        <option value="musicaux">Musicaux</option>
                        <option value="commerciaux">Commerciaux</option>
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
                <h3 class="text-lg font-medium">No events found</h3>
                <p class="text-muted-foreground">Try adjusting your filters or create a new event.</p>
                <Link v-if="auth.user.role !== 'participant'" href="/dashboard/events/create" class="mt-4 block">
                    <Button variant="outline">Create your first event</Button>
                </Link>
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
                            <Badge :variant="getStatusVariant(event.statut)" class="capitalize shadow-sm w-fit">
                                {{ getStatusLabel(event.statut) }}
                            </Badge>
                            <Badge v-if="event.is_tournoi" variant="default" class="bg-amber-500 hover:bg-amber-600 shadow-sm w-fit border-0">
                                <Trophy class="w-3 h-3 mr-1" /> Tournament
                            </Badge>
                        </div>
                        <div class="absolute absolute bottom-4 right-4 flex gap-2" v-if="event.organisateur_id === auth.user.id">
                             <Link :href="`/dashboard/events/${event.id}`">
                                <Button size="icon" variant="secondary" class="group/btn h-8 w-8 rounded-full shadow-md hover:bg-black/20 backdrop-blur-sm bg-white/80">
                                    <Eye class="w-4 h-4 text-black group-hover/btn:text-white transition-colors" />
                                </Button>
                             </Link>
                             <Link :href="`/dashboard/events/${event.id}/edit`">
                                <Button size="icon" variant="secondary" class="group/btn h-8 w-8 rounded-full shadow-md hover:bg-black/20 backdrop-blur-sm bg-white/80">
                                    <Edit class="w-4 h-4 text-black group-hover/btn:text-white transition-colors" />
                                </Button>
                             </Link>
                            <Button @click="deleteEvent(event.id)" size="icon" variant="destructive" class="h-8 w-8 rounded-full shadow-md opacity-0 group-hover:opacity-100 transition-opacity ">
                                <Trash2 class="w-4 h-4" />
                            </Button>
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

                        <div class="pt-4 border-t flex justify-between items-center text-sm">
                            <div class="font-medium text-blue-600">
                                {{ event.prix_spectateur > 0 ? `${event.prix_spectateur} TND` : 'Free' }}
                            </div>
                            <div class="text-muted-foreground">
                                {{ event.capacite_spectateur }} seats available
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
