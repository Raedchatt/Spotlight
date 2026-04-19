<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { Calendar, Building, MapPin, Search, FilterX, Eye, X, Loader2, Edit, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { toast } from 'vue-sonner';

interface CategoryItem {
    id: number;
    label: string;
}

interface EventData {
    id: number;
    titre: string;
    lieu: string;
    poster_url?: string;
    organisateur_id: number;
    organisateur?: {
        username: string;
        email: string;
    };
    categorie: string;
    categorie_autre?: string;
    statut: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedEvents {
    data: EventData[];
    links: PaginationLink[];
}

const props = defineProps<{
    events?: PaginatedEvents;
    filters?: {
        search?: string;
        category?: string;
        organizer?: string;
    };
    categories?: CategoryItem[];
}>();

const search = ref(props.filters?.search || '');
const category = ref(props.filters?.category || '');
const organizer = ref(props.filters?.organizer || '');

// Autocomplete logic for organizers
const organizerSuggestions = ref<any[]>([]);
const isSearchingOrganizers = ref(false);
const showSuggestions = ref(false);
let searchSuggestionsTimeout: ReturnType<typeof setTimeout> | null = null;

const searchOrganizers = () => {
    if (searchSuggestionsTimeout) clearTimeout(searchSuggestionsTimeout);
    
    if (!organizer.value || organizer.value.length < 2) {
        organizerSuggestions.value = [];
        showSuggestions.value = false;
        return;
    }

    searchSuggestionsTimeout = setTimeout(async () => {
        isSearchingOrganizers.value = true;
        try {
            const res = await axios.get(`/admin/organizers/search?q=${organizer.value}`);
            organizerSuggestions.value = res.data;
            showSuggestions.value = organizerSuggestions.value.length > 0;
        } catch (e) {
            console.error('Failed to search organizers', e);
        } finally {
            isSearchingOrganizers.value = false;
        }
    }, 300);
};

const selectOrganizer = (org: any) => {
    organizer.value = org.username;
    showSuggestions.value = false;
    fetchEvents();
};

let debounceTimeout: ReturnType<typeof setTimeout> | null = null;

const fetchEvents = () => {
    if (debounceTimeout) clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        router.get('/admin/events/all', {
            search: search.value,
            category: category.value,
            organizer: organizer.value
        }, { preserveState: true, preserveScroll: true, replace: true });
    }, 300);
};

watch([search, category], fetchEvents);

const resetFilters = () => {
    search.value = '';
    category.value = '';
    organizer.value = '';
    organizerSuggestions.value = [];
    showSuggestions.value = false;
    
    if (debounceTimeout) clearTimeout(debounceTimeout);
    router.get('/admin/events/all', {}, { preserveState: true, preserveScroll: true });
};

const deleteEvent = (event: any) => {
    if (confirm(`Are you sure you want to delete/cancel the event "${event.titre}"?`)) {
        router.delete(`/admin/events/${event.id}`, {
            onSuccess: () => toast.success('Event deleted successfully.'),
            onError: () => toast.error('Failed to delete event.')
        });
    }
};

const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'ouvert': return 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800/50';
        case 'en_attente': return 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800/50';
        case 'ferme': return 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700';
        case 'annule': return 'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800/50';
        case 'rejete': return 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800/50';
        default: return 'bg-indigo-100 text-indigo-700 border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-400 dark:border-indigo-800/50';
    }
};

const closeSuggestions = () => {
    setTimeout(() => {
        showSuggestions.value = false;
    }, 200);
};
</script>

<template>
    <Head title="All Events" />

    <AppLayout>
        <div class="px-4 py-8 md:px-8 space-y-8 max-w-[1400px] mx-auto">
            
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">All Events</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Manage all platform events from one place.</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="relative z-20 bg-white/70 dark:bg-neutral-900/70 backdrop-blur-sm p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-neutral-800 flex flex-col md:flex-row gap-4 items-center">
                <div class="flex-1 relative w-full">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Search by title..."
                        class="w-full pl-10 pr-4 py-2 bg-gray-50/50 dark:bg-neutral-800/50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 dark:text-white"
                    >
                </div>
                
                <div class="w-full md:w-48">
                    <select v-model="category" class="w-full px-4 py-2 bg-gray-50/50 dark:bg-neutral-800/50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 dark:text-white appearance-none cursor-pointer">
                        <option value="">Categories</option>
                        <option v-for="cat in (categories as CategoryItem[])" :key="cat.id" :value="cat.label.toLowerCase()">
                            {{ cat.label }}
                        </option>
                    </select>
                </div>
                
                <div class="w-full md:w-72 relative">
                    <Building class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input 
                        v-model="organizer" 
                        type="text" 
                        placeholder="Organizer..."
                        @input="searchOrganizers"
                        @focus="showSuggestions = organizerSuggestions.length > 0"
                        @blur="closeSuggestions"
                        class="w-full pl-10 pr-10 py-2 bg-gray-50/50 dark:bg-neutral-800/50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 dark:text-white"
                    >
                    <div v-if="isSearchingOrganizers" class="absolute right-3 top-1/2 -translate-y-1/2">
                        <Loader2 class="w-4 h-4 text-gray-400 animate-spin" />
                    </div>
                    <button v-else-if="organizer" @click="organizer = ''; fetchEvents()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <X class="w-4 h-4" />
                    </button>

                    <div v-if="showSuggestions" class="absolute z-50 w-full mt-2 bg-white dark:bg-neutral-800 border border-gray-100 dark:border-neutral-700 rounded-2xl shadow-xl max-h-60 overflow-y-auto">
                        <div 
                            v-for="org in organizerSuggestions" 
                            :key="org.id"
                            @mousedown="selectOrganizer(org)"
                            class="px-4 py-3 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer flex flex-col border-b border-gray-50 dark:border-neutral-700 last:border-0"
                        >
                            <span class="font-bold text-gray-900 dark:text-white text-sm">{{ org.username }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 text-left">{{ org.email }}</span>
                        </div>
                    </div>
                </div>

                <button 
                    @click="resetFilters"
                    class="p-2.5 bg-gray-100 dark:bg-neutral-800 hover:bg-gray-200 dark:hover:bg-neutral-700 text-gray-700 dark:text-gray-300 rounded-xl transition flex items-center justify-center min-w-[50px]"
                    title="Reset Filters"
                >
                    <FilterX class="w-5 h-5" />
                </button>
            </div>

            <!-- Table -->
            <div class="bg-white/70 dark:bg-neutral-900/70 backdrop-blur-sm rounded-3xl border border-gray-100 dark:border-neutral-800 shadow-sm overflow-hidden min-h-[400px]">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-[11px] text-gray-400 dark:text-gray-500 uppercase tracking-widest bg-gray-50/50 dark:bg-neutral-800/30 border-b border-gray-100 dark:border-neutral-800">
                            <tr>
                                <th class="px-6 py-4 font-black">Event</th>
                                <th class="px-6 py-4 font-black">Organizer</th>
                                <th class="px-6 py-4 font-black text-center">Category</th>
                                <th class="px-6 py-4 font-black text-center">Status</th>
                                <th class="px-6 py-4 font-black text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-neutral-800/30">
                            <tr v-for="event in events?.data" :key="event.id" class="hover:bg-gray-50/50 dark:hover:bg-neutral-800/20 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-neutral-800 overflow-hidden flex-shrink-0 border border-gray-200/50 dark:border-neutral-700/50 shadow-sm">
                                            <img v-if="event.poster_url" :src="event.poster_url" class="w-full h-full object-cover" alt="">
                                            <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                                                <Calendar class="w-4 h-4" />
                                            </div>
                                        </div>
                                        <div class="max-w-[250px]">
                                            <div class="font-black text-gray-900 dark:text-white truncate" :title="event.titre">{{ event.titre }}</div>
                                            <div class="text-[10px] text-gray-500 dark:text-gray-500 mt-0.5 truncate flex items-center gap-1.5">
                                                <MapPin class="w-3 h-3" />
                                                {{ event.lieu }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <Link 
                                        :href="`/organizer/${event.organisateur_id}`"
                                        class="inline-flex flex-col hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                                    >
                                        <span class="font-bold text-gray-900 dark:text-white">{{ event.organisateur?.username }}</span>
                                        <span class="text-xs text-gray-400 dark:text-gray-600">{{ event.organisateur?.email }}</span>
                                    </Link>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-lg bg-gray-50 dark:bg-neutral-800/50 text-gray-600 dark:text-gray-400 border border-gray-100 dark:border-neutral-700">
                                        {{ event.categorie === 'autre' ? event.categorie_autre : event.categorie }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="['px-3 py-1 text-[11px] font-bold rounded-full border shadow-sm', getStatusBadgeClass(event.statut)]">
                                        {{ event.statut.toUpperCase() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <Link :href="`/events/${event.id}`" target="_blank" class="p-2 text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="View Public Page">
                                            <Eye class="w-4 h-4" />
                                        </Link>
                                        <Link :href="`/dashboard/events/${event.id}/edit`" class="p-2 text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/20 rounded-lg transition-colors" title="Modify Event">
                                            <Edit class="w-4 h-4" />
                                        </Link>
                                        <button @click="deleteEvent(event)" class="p-2 text-rose-500 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-900/20 rounded-lg transition-colors" title="Delete/Cancel Event">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!events?.data || events.data.length === 0">
                                <td colspan="5" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="p-4 bg-gray-50 dark:bg-neutral-800 rounded-full">
                                            <Search class="w-8 h-8 text-gray-300 dark:text-gray-600" />
                                        </div>
                                        <p class="font-bold text-gray-500 dark:text-gray-400">No events found matching your criteria.</p>
                                        <button @click="resetFilters" class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">Clear all filters</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="events?.links && events.links.length > 3" class="flex justify-center mt-6 pb-20">
                <div class="flex gap-2">
                    <template v-for="(link, p) in events.links" :key="p">
                        <div v-if="link.url === null" class="px-4 py-2 border border-gray-200 dark:border-neutral-700 rounded-xl text-sm font-medium opacity-50 cursor-not-allowed bg-gray-50 dark:bg-neutral-800 text-gray-500 dark:text-gray-400" v-html="link.label"></div>
                        <Link v-else :href="link.url" class="px-4 py-2 border rounded-xl text-sm font-medium transition" :class="[link.active ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-500/20' : 'bg-white dark:bg-neutral-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-neutral-700 hover:bg-gray-50 dark:hover:bg-neutral-700']" v-html="link.label"></Link>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
