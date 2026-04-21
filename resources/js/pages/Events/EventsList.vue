<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';

import axios from 'axios';
import { toast } from 'vue-sonner';

import {
    Search,
    Plus,
    Pencil,
    Calendar,
    MapPin,
    X,
    Trophy,
    Eye,
    Users,
    ChevronLeft,
    ChevronRight,
    Trash2,
} from 'lucide-vue-next';
import { ref, onMounted, watch, computed } from 'vue';
import CancelEventButton from '@/components/organizer/CancelEventButton.vue';
import EventManageModal from '@/components/organizer/EventManageModal.vue';
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
const categories = ref<{slug: string; label: string}[]>([]);

const fetchCategories = async () => {
    try {
        const res = await axios.get('/web-api/categories');
        categories.value = res.data;
    } catch (e) {
        console.error('Failed to load categories', e);
    }
};

const page = usePage();
const auth = computed(() => page.props.auth as any);

// Management Modal State
const selectedEventId = ref<number | null>(null);
const showManageModal = ref(false);

const openManageModal = (id: number) => {
    selectedEventId.value = id;
    showManageModal.value = true;
};

// Filters
const filters = ref({
    titre: '',
    categorie: 'all',
    date: '',
    statut: 'all',
    page: 1
});

// Pagination state
const pagination = ref({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 9
});

const fetchEvents = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (auth.value.user?.id) {
            params.append('organisateur_id', auth.value.user.id.toString());
        }
        if (filters.value.titre) params.append('titre', filters.value.titre);
        if (filters.value.categorie !== 'all') params.append('categorie', filters.value.categorie);
        if (filters.value.date) params.append('date', filters.value.date);

        // Always exclude cancelled events
        params.append('statut', 'ouvert,ferme,encours,en_attente');
        
        // Pagination params
        params.append('page', filters.value.page.toString());
        params.append('per_page', '9');
        
        const response = await axios.get(`/web-api/events/search?${params.toString()}`);
        
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
            pagination.value.total = events.value.length;
        }
    } catch (error) {
        console.error('Error fetching events:', error);
        toast.error('Failed to load events. Please try again.');
    } finally {
        loading.value = false;
    }
};

const handlePageChange = (newPage: number) => {
    if (newPage >= 1 && newPage <= pagination.value.lastPage) {
        filters.value.page = newPage;
    }
};

const deleteEvent = async (id: number) => {
    if (!confirm('Are you sure you want to delete this event?')) return;
    
    try {
        await axios.delete(`/web-api/events/${id}`);
        events.value = events.value.filter(e => e.id !== id);
        toast.success('Event deleted successfully.');
    } catch (error: any) {
        console.error('Error deleting event:', error);
        toast.error(error.response?.data?.message || 'Failed to delete event.');
    }
};

const resetFilters = () => {
    filters.value = {
        titre: '',
        categorie: 'all',
        date: '',
        statut: 'all',
        page: 1
    };
    fetchEvents();
};

onMounted(() => {
    fetchCategories();
    fetchEvents();
});

// Watch filters for auto-search
watch(() => [filters.value.titre, filters.value.categorie, filters.value.date], () => {
    filters.value.page = 1; // Reset to page 1 on search change
    fetchEvents();
});

// Watch page separately
watch(() => filters.value.page, () => {
    fetchEvents();
});

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
                        <option v-for="cat in categories" :key="cat.slug" :value="cat.slug">{{ cat.label }}</option>
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

            <div v-else class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="event in events" :key="event.id" 
                        class="group bg-card border rounded-xl overflow-hidden hover:shadow-lg transition-all duration-300 relative"
                        :class="{ 'ring-2 ring-amber-500 shadow-amber-500/10': event.is_tournoi }">
                        <!-- Event Banner Image -->
                        <div 
                        class="h-40 relative bg-cover bg-center transition-all duration-500 group-hover:scale-105"
                        :style="{ 
                            backgroundImage: `url(${event.poster_url || (event.medias && event.medias.length > 0 
                                ? event.medias.find(m => m.type === 'image')?.url || event.medias[0].url 
                                : 'https://picsum.photos/seed/fallback/800/600')})` 
                        }"
                    >
                            <div class="absolute top-4 left-4 flex flex-col gap-2">
                                <Badge 
                                    :variant="getStatusVariant(event.statut)" 
                                    :class="[
                                        'capitalize shadow-sm w-fit border-0 font-bold flex items-center gap-1.5 px-2.5 py-0.5',
                                        event.statut === 'encours' 
                                            ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' 
                                            : (event.statut === 'ouvert' || event.statut === 'valide' 
                                                ? 'bg-blue-600 hover:bg-blue-700 text-white' 
                                                : '')
                                    ]"
                                >
                                    <span v-if="event.statut === 'encours'" class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                                    </span>
                                    {{ getStatusLabel(event.statut) }}
                                </Badge>
                                <Badge v-if="event.is_tournoi" variant="default" class="bg-amber-500 hover:bg-amber-600 shadow-sm w-fit border-0">
                                    <Trophy class="w-3 h-3 mr-1" /> Tournament
                                </Badge>
                                <Badge v-if="event.organisateur_id !== auth?.user?.id" variant="secondary" class="shadow-sm w-fit">
                                    <Users class="w-3 h-3 mr-1" /> Co-Organizer
                                </Badge>
                            </div>
                                <div class="absolute bottom-4 right-4 flex gap-2" v-if="event.organisateur_id === auth?.user?.id || true">
                                    <Button 
                                        size="icon" 
                                        variant="secondary" 
                                        class="group/btn h-8 w-8 rounded-full shadow-md hover:bg-black/20 backdrop-blur-sm bg-white/80 transition-all hover:scale-110"
                                        @click="openManageModal(event.id)"
                                    >
                                        <Eye class="w-4 h-4 text-black group-hover/btn:text-white transition-colors" />
                                    </Button>
                                    <Link :href="`/dashboard/events/${event.id}/edit`">
                                    <Button size="icon" variant="secondary" class="group/btn h-8 w-8 rounded-full shadow-md hover:bg-black/20 backdrop-blur-sm bg-white/80 transition-all hover:scale-110">
                                        <Pencil class="w-4 h-4 text-black group-hover/btn:text-white transition-colors" />
                                    </Button>
                                </Link>
                                <CancelEventButton 
                                    v-if="event.organisateur_id === auth.user.id && event.statut !== 'annule'"
                                    :event-id="event.id"
                                    :event-title="event.titre"
                                    @cancelled="fetchEvents"
                                />
                                <Button 
                                    v-if="event.organisateur_id === auth.user.id && (event.statut === 'annule' || event.statut === 'en_attente')"
                                    size="icon" 
                                    variant="secondary" 
                                    class="group/btn h-8 w-8 rounded-full shadow-md hover:bg-black/20 backdrop-blur-sm bg-white/80 transition-all hover:scale-110 hover:text-red-600"
                                    @click="deleteEvent(event.id)"
                                >
                                    <Trash2 class="w-4 h-4 transition-colors" />
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

                            <div class="pt-4 border-t space-y-4">
                                <div class="flex justify-between items-center text-sm">
                                    <div class="font-medium text-blue-600">
                                        {{ event.prix_spectateur > 0 ? `${event.prix_spectateur} TND` : 'Free' }}
                                    </div>
                                    <div class="text-muted-foreground">
                                        {{ event.capacite_spectateur }} seats available
                                    </div>
                                </div>
                                
                                <Link :href="`/events/${event.id}`" class="block w-full">
                                    <Button variant="outline" class="w-full border-zinc-200 hover:bg-zinc-50 font-bold text-xs uppercase tracking-widest transition-all duration-300 group-hover:bg-zinc-900 group-hover:text-white group-hover:border-zinc-900">
                                        View Public Page
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </div>
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
        
        <!-- Management Modal -->
        <EventManageModal 
            v-model:open="showManageModal" 
            :event-id="selectedEventId" 
        />
    </AppLayout>
</template>
