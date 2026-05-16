<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { Calendar, Building, MapPin, Search, FilterX, Eye, X, Loader2, Edit, Trash2, Plus } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { toast } from 'vue-sonner';
import { useI18n } from 'vue-i18n';

interface CategoryItem {
    id: number;
    slug: string;
    label: any;
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

const { t, locale } = useI18n();

const getCategoryLabel = (cat: CategoryItem) => {
    if (!cat.label) return cat.slug;
    if (typeof cat.label === 'string') return cat.label;
    if (typeof cat.label === 'object') {
        return cat.label[locale.value] || cat.label['en'] || Object.values(cat.label)[0] || cat.slug;
    }
    return cat.slug;
};

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
    if (confirm(t('events.confirmDeleteEvent', { title: event.titre }))) {
        router.delete(`/admin/events/${event.id}`, {
            onSuccess: () => toast.success(t('events.eventDeletedSuccess')),
            onError: () => toast.error(t('events.eventDeletedError'))
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

// ── Create Event Modal ──
const showCreateModal = ref(false);
const createProcessing = ref(false);
const createErrors = ref<Record<string, string[]>>({});

// Organizer picker for new event
const newEventOrganizer = ref<any>(null);
const orgSearchQuery = ref('');
const orgSearchResults = ref<any[]>([]);
const isSearchingOrg = ref(false);
let orgSearchTimeout: ReturnType<typeof setTimeout> | null = null;

const searchOrgForCreate = () => {
    if (orgSearchTimeout) clearTimeout(orgSearchTimeout);
    if (!orgSearchQuery.value || orgSearchQuery.value.length < 2) {
        orgSearchResults.value = [];
        return;
    }
    orgSearchTimeout = setTimeout(async () => {
        isSearchingOrg.value = true;
        try {
            const res = await axios.get(`/admin/organizers/search?q=${orgSearchQuery.value}`);
            orgSearchResults.value = res.data;
        } catch (e) {
            console.error('Failed to search organizers', e);
        } finally {
            isSearchingOrg.value = false;
        }
    }, 300);
};

const pickOrganizer = (org: any) => {
    newEventOrganizer.value = org;
    orgSearchQuery.value = '';
    orgSearchResults.value = [];
};

const clearPickedOrganizer = () => {
    newEventOrganizer.value = null;
};

// Categories for the create form
const createCategories = ref<{ slug: string; label: string }[]>([]);
const fetchCreateCategories = async () => {
    try {
        const res = await axios.get('/web-api/categories');
        createCategories.value = res.data;
    } catch (e) {
        console.error('Failed to load categories', e);
    }
};

const newEvent = ref({
    titre: '',
    description: '',
    date_debut: '',
    date_fin: '',
    lieu: '',
    prix_spectateur: 0,
    capacite_spectateur: 0,
    categorie: '',
    categorie_autre: '',
    is_tournoi: false,
    type_tournoi: '',
    prix_participant: 0,
    capacite_participant: 0,
    nombre_equipes: 0,
    joueurs_par_equipe: 0,
});

const openCreateModal = () => {
    fetchCreateCategories();
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    newEventOrganizer.value = null;
    orgSearchQuery.value = '';
    orgSearchResults.value = [];
    createErrors.value = {};
    newEvent.value = {
        titre: '',
        description: '',
        date_debut: '',
        date_fin: '',
        lieu: '',
        prix_spectateur: 0,
        capacite_spectateur: 0,
        categorie: '',
        categorie_autre: '',
        is_tournoi: false,
        type_tournoi: '',
        prix_participant: 0,
        capacite_participant: 0,
        nombre_equipes: 0,
        joueurs_par_equipe: 0,
    };
};

const submitCreateEvent = async () => {
    if (createProcessing.value) return;
    if (!newEventOrganizer.value) {
        toast.error(t('events.selectOrganizerFirst'));
        return;
    }

    createProcessing.value = true;
    createErrors.value = {};

    try {
        const formData = new FormData();
        formData.append('organisateur_id', String(newEventOrganizer.value.id));
        Object.keys(newEvent.value).forEach(key => {
            const value = (newEvent.value as any)[key];
            if (key === 'categorie_autre') {
                if (newEvent.value.categorie === 'autre') {
                    formData.append(key, String(value));
                }
            } else {
                formData.append(key, String(value));
            }
        });

        await axios.post('/web-api/events', formData);
        toast.success(t('events.eventCreatedOnBehalf', { username: newEventOrganizer.value.username }));
        closeCreateModal();
        // Refresh the events list
        router.reload({ only: ['events'] });
    } catch (error: any) {
        if (error.response?.data?.errors) {
            createErrors.value = error.response.data.errors;
        } else {
            toast.error(error.response?.data?.message || t('events.failedToCreateEvent'));
        }
    } finally {
        createProcessing.value = false;
    }
};
</script>

<template>
    <Head :title="t('events.allEvents')" />

    <AppLayout>
        <div class="px-4 py-8 md:px-8 space-y-8 max-w-[1400px] mx-auto w-full min-w-0">
            
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">{{ t('events.allEvents') }}</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">{{ t('events.allEventsDesc') }}</p>
                </div>
                <button 
                    @click="openCreateModal" 
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-2xl shadow-lg shadow-indigo-500/20 transition-all active:scale-95"
                >
                    <Plus class="w-5 h-5" />
                    {{ t('events.createEvent') }}
                </button>
            </div>

            <!-- Filters -->
            <div class="relative z-20 bg-white/70 dark:bg-neutral-900/70 backdrop-blur-sm p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-neutral-800 flex flex-col md:flex-row gap-4 items-center w-full">
                <div class="flex-1 relative w-full min-w-0">
                    <Search class="absolute start-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input 
                        v-model="search" 
                        type="text" 
                        :placeholder="t('events.searchByTitle')"
                        class="w-full ps-10 pe-4 py-2 bg-gray-50/50 dark:bg-neutral-800/50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 dark:text-white"
                    >
                </div>
                
                <div class="w-full md:w-48 flex-shrink-0">
                    <select v-model="category" class="w-full px-4 py-2 bg-gray-50/50 dark:bg-neutral-800/50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 dark:text-white appearance-none cursor-pointer">
                        <option value="">{{ t('events.categories') }}</option>
                        <option v-for="cat in (categories as CategoryItem[])" :key="cat.id" :value="cat.slug">
                            {{ getCategoryLabel(cat) }}
                        </option>
                    </select>
                </div>
                
                <div class="w-full md:w-72 relative flex-shrink-0">
                    <Building class="absolute start-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input 
                        v-model="organizer" 
                        type="text" 
                        :placeholder="t('events.organizerPlaceholder')"
                        @input="searchOrganizers"
                        @focus="showSuggestions = organizerSuggestions.length > 0"
                        @blur="closeSuggestions"
                        class="w-full ps-10 pe-10 py-2 bg-gray-50/50 dark:bg-neutral-800/50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 dark:text-white"
                    >
                    <div v-if="isSearchingOrganizers" class="absolute end-3 top-1/2 -translate-y-1/2">
                        <Loader2 class="w-4 h-4 text-gray-400 animate-spin" />
                    </div>
                    <button v-else-if="organizer" @click="organizer = ''; fetchEvents()" class="absolute end-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
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
            <div class="bg-white/70 dark:bg-neutral-900/70 backdrop-blur-sm rounded-3xl border border-gray-100 dark:border-neutral-800 shadow-sm overflow-hidden min-h-[400px] w-full">
                <div class="overflow-x-auto w-full">
                    <table class="w-full text-sm text-start">
                        <thead class="text-[11px] text-gray-400 dark:text-gray-500 uppercase tracking-widest bg-gray-50/50 dark:bg-neutral-800/30 border-b border-gray-100 dark:border-neutral-800">
                            <tr>
                                <th class="px-6 py-4 font-black text-start">{{ t('events.event') }}</th>
                                <th class="px-6 py-4 font-black text-start">{{ t('events.organizer') }}</th>
                                <th class="px-6 py-4 font-black text-center">{{ t('events.category') }}</th>
                                <th class="px-6 py-4 font-black text-center">{{ t('events.status') }}</th>
                                <th class="px-6 py-4 font-black text-end">{{ t('common.actions') }}</th>
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
                                        {{ event.categorie === 'autre' ? event.categorie_autre : t(`categories.${event.categorie.toLowerCase()}`) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="['px-3 py-1 text-[11px] font-bold rounded-full border shadow-sm', getStatusBadgeClass(event.statut)]">
                                        {{ t(`events.status_${event.statut}`).toUpperCase() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-end">
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
                                        <p class="font-bold text-gray-500 dark:text-gray-400">{{ t('events.noEventsFoundCriteria') }}</p>
                                        <button @click="resetFilters" class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">{{ t('events.clearAllFilters') }}</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="events?.links && events.links.length > 3" class="flex justify-center mt-6 pb-20 w-full">
                <div class="flex gap-2 flex-wrap justify-center">
                    <template v-for="(link, p) in events.links" :key="p">
                        <div v-if="link.url === null" class="px-3 sm:px-4 py-2 border border-gray-200 dark:border-neutral-700 rounded-xl text-xs sm:text-sm font-medium opacity-50 cursor-not-allowed bg-gray-50 dark:bg-neutral-800 text-gray-500 dark:text-gray-400 whitespace-nowrap" v-html="link.label"></div>
                        <Link v-else :href="link.url" class="px-3 sm:px-4 py-2 border rounded-xl text-xs sm:text-sm font-medium transition whitespace-nowrap" :class="[link.active ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-500/20' : 'bg-white dark:bg-neutral-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-neutral-700 hover:bg-gray-50 dark:hover:bg-neutral-700']" v-html="link.label"></Link>
                    </template>
                </div>
            </div>
        </div>
        <!-- ── Create Event Modal ── -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showCreateModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeCreateModal"></div>

                    <!-- Modal -->
                    <div class="relative bg-white dark:bg-neutral-900 rounded-3xl shadow-2xl border border-gray-100 dark:border-neutral-800 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                        <!-- Header -->
                        <div class="sticky top-0 z-10 flex items-center justify-between px-8 py-5 bg-white dark:bg-neutral-900 border-b border-gray-100 dark:border-neutral-800 rounded-t-3xl">
                            <div>
                                <h2 class="text-xl font-extrabold text-gray-900 dark:text-white">{{ t('events.createEventAdmin') }}</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ t('events.createEventAdminDesc') }}</p>
                            </div>
                            <button @click="closeCreateModal" class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-neutral-800 transition-colors">
                                <X class="w-5 h-5 text-gray-500" />
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="p-8 space-y-6">
                            <!-- Step 1: Select Organizer -->
                            <div class="space-y-3">
                                <label class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <Building class="w-4 h-4 text-indigo-500" />
                                    {{ t('events.selectOrganizer') }} *
                                </label>
                                <div v-if="newEventOrganizer" class="flex items-center justify-between p-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-2xl">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold">{{ newEventOrganizer.username?.charAt(0)?.toUpperCase() }}</div>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">{{ newEventOrganizer.username }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ newEventOrganizer.email }}</p>
                                        </div>
                                    </div>
                                    <button @click="clearPickedOrganizer" class="p-1.5 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/20 text-red-500 transition-colors">
                                        <X class="w-4 h-4" />
                                    </button>
                                </div>
                                <div class="relative">
                                    <Building class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                    <input 
                                        v-model="orgSearchQuery" 
                                        type="text" 
                                        :placeholder="t('events.searchOrganizersByName')"
                                        @input="searchOrgForCreate"
                                        class="w-full ps-10 pe-4 py-2.5 bg-gray-50/50 dark:bg-neutral-800/50 border border-gray-200 dark:border-neutral-700 rounded-xl focus:ring-2 focus:ring-indigo-500 dark:text-white text-sm"
                                    >
                                    <div v-if="isSearchingOrg" class="absolute end-3 top-1/2 -translate-y-1/2">
                                        <Loader2 class="w-4 h-4 text-gray-400 animate-spin" />
                                    </div>
                                    <div v-if="orgSearchResults.length > 0" class="absolute z-50 w-full mt-1 bg-white dark:bg-neutral-800 border border-gray-100 dark:border-neutral-700 rounded-2xl shadow-xl max-h-48 overflow-y-auto">
                                        <div 
                                            v-for="org in orgSearchResults" 
                                            :key="org.id"
                                            @click="pickOrganizer(org)"
                                            class="px-4 py-3 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer flex flex-col border-b border-gray-50 dark:border-neutral-700 last:border-0"
                                        >
                                            <span class="font-bold text-gray-900 dark:text-white text-sm">{{ org.username }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ org.email }}</span>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="createErrors.organisateur_id" class="text-xs text-red-500 font-medium">{{ createErrors.organisateur_id[0] }}</p>
                            </div>

                            <hr class="border-gray-100 dark:border-neutral-800" />

                            <!-- Event Details -->
                            <div class="space-y-4">
                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('events.eventTitle') }} *</label>
                                    <input v-model="newEvent.titre" type="text" :placeholder="t('events.eventTitlePlaceholder')" class="w-full px-4 py-2.5 border border-gray-200 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-sm dark:text-white focus:ring-2 focus:ring-indigo-500" />
                                    <p v-if="createErrors.titre" class="text-xs text-red-500">{{ createErrors.titre[0] }}</p>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('events.description') }} *</label>
                                    <textarea v-model="newEvent.description" rows="3" :placeholder="t('events.eventDescriptionPlaceholder')" class="w-full px-4 py-2.5 border border-gray-200 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-sm dark:text-white focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                                    <p v-if="createErrors.description" class="text-xs text-red-500">{{ createErrors.description[0] }}</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('events.startDate') }} *</label>
                                        <input v-model="newEvent.date_debut" type="datetime-local" class="w-full px-4 py-2.5 border border-gray-200 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-sm dark:text-white focus:ring-2 focus:ring-indigo-500" />
                                        <p v-if="createErrors.date_debut" class="text-xs text-red-500">{{ createErrors.date_debut[0] }}</p>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('events.endDate') }} *</label>
                                        <input v-model="newEvent.date_fin" type="datetime-local" class="w-full px-4 py-2.5 border border-gray-200 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-sm dark:text-white focus:ring-2 focus:ring-indigo-500" />
                                        <p v-if="createErrors.date_fin" class="text-xs text-red-500">{{ createErrors.date_fin[0] }}</p>
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('events.location') }} *</label>
                                    <div class="relative">
                                        <MapPin class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                        <input v-model="newEvent.lieu" type="text" :placeholder="t('events.eventVenueAddress')" class="w-full ps-10 pe-4 py-2.5 border border-gray-200 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-sm dark:text-white focus:ring-2 focus:ring-indigo-500" />
                                    </div>
                                    <p v-if="createErrors.lieu" class="text-xs text-red-500">{{ createErrors.lieu[0] }}</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('events.category') }} *</label>
                                        <select v-model="newEvent.categorie" class="w-full px-4 py-2.5 border border-gray-200 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-sm dark:text-white focus:ring-2 focus:ring-indigo-500">
                                            <option value="" disabled>{{ t('events.selectCategory') }}</option>
                                            <option v-for="cat in createCategories" :key="cat.slug" :value="cat.slug">{{ cat.label }}</option>
                                            <option value="autre">{{ t('events.other') }}</option>
                                        </select>
                                        <p v-if="createErrors.categorie" class="text-xs text-red-500">{{ createErrors.categorie[0] }}</p>
                                    </div>
                                    <div v-if="newEvent.categorie === 'autre'" class="space-y-1.5">
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('events.customCategory') }} *</label>
                                        <input v-model="newEvent.categorie_autre" type="text" :placeholder="t('events.enterCategoryName')" class="w-full px-4 py-2.5 border border-gray-200 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-sm dark:text-white focus:ring-2 focus:ring-indigo-500" />
                                        <p v-if="createErrors.categorie_autre" class="text-xs text-red-500">{{ createErrors.categorie_autre[0] }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('events.maxSeats') }} *</label>
                                        <input v-model.number="newEvent.capacite_spectateur" type="number" min="0" class="w-full px-4 py-2.5 border border-gray-200 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-sm dark:text-white focus:ring-2 focus:ring-indigo-500" />
                                        <p v-if="createErrors.capacite_spectateur" class="text-xs text-red-500">{{ createErrors.capacite_spectateur[0] }}</p>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('events.ticketPrice') }} *</label>
                                        <input v-model.number="newEvent.prix_spectateur" type="number" min="0" step="0.01" class="w-full px-4 py-2.5 border border-gray-200 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-sm dark:text-white focus:ring-2 focus:ring-indigo-500" />
                                        <p v-if="createErrors.prix_spectateur" class="text-xs text-red-500">{{ createErrors.prix_spectateur[0] }}</p>
                                    </div>
                                </div>

                                <!-- Tournament Toggle -->
                                <div class="mt-2 p-4 border border-gray-200 dark:border-neutral-700 rounded-2xl bg-gray-50/50 dark:bg-neutral-800/30">
                                    <label class="flex items-center gap-3 text-sm font-medium cursor-pointer">
                                        <input type="checkbox" v-model="newEvent.is_tournoi" class="w-4 h-4 text-indigo-600 rounded border-gray-300 dark:border-neutral-600 dark:bg-neutral-800 focus:ring-indigo-500" />
                                        <span class="text-gray-900 dark:text-white">{{ t('events.isTournament') }}</span>
                                    </label>

                                    <div v-if="newEvent.is_tournoi" class="mt-4 pt-4 border-t border-gray-200 dark:border-neutral-700 space-y-4">
                                        <div class="space-y-1.5">
                                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('events.tournamentType') }} *</label>
                                            <select v-model="newEvent.type_tournoi" class="w-full px-4 py-2.5 border border-gray-200 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-sm dark:text-white focus:ring-2 focus:ring-indigo-500">
                                                <option value="" disabled>{{ t('events.selectType') }}</option>
                                                <option value="equipe">{{ t('events.teamType') }}</option>
                                                <option value="individuel">{{ t('events.individualType') }}</option>
                                            </select>
                                            <p v-if="createErrors.type_tournoi" class="text-xs text-red-500">{{ createErrors.type_tournoi[0] }}</p>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="space-y-1.5">
                                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('events.participantPrice') }} *</label>
                                                <input v-model.number="newEvent.prix_participant" type="number" min="0" step="0.01" class="w-full px-4 py-2.5 border border-gray-200 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-sm dark:text-white focus:ring-2 focus:ring-indigo-500" />
                                                <p v-if="createErrors.prix_participant" class="text-xs text-red-500">{{ createErrors.prix_participant[0] }}</p>
                                            </div>
                                            <div class="space-y-1.5">
                                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ newEvent.type_tournoi === 'equipe' ? t('events.playersPerTeam') : t('events.participantSeats') }} *
                                                </label>
                                                <input v-model.number="newEvent.capacite_participant" type="number" min="0" class="w-full px-4 py-2.5 border border-gray-200 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-sm dark:text-white focus:ring-2 focus:ring-indigo-500" />
                                                <p v-if="createErrors.capacite_participant" class="text-xs text-red-500">{{ createErrors.capacite_participant[0] }}</p>
                                            </div>
                                        </div>

                                        <div v-if="newEvent.type_tournoi === 'equipe'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="space-y-1.5">
                                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('events.numberOfTeams') }} *</label>
                                                <input v-model.number="newEvent.nombre_equipes" type="number" min="0" class="w-full px-4 py-2.5 border border-gray-200 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-sm dark:text-white focus:ring-2 focus:ring-indigo-500" />
                                                <p v-if="createErrors.nombre_equipes" class="text-xs text-red-500">{{ createErrors.nombre_equipes[0] }}</p>
                                            </div>
                                            <div class="space-y-1.5">
                                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ t('events.playersPerTeam') }} *</label>
                                                <input v-model.number="newEvent.joueurs_par_equipe" type="number" min="0" class="w-full px-4 py-2.5 border border-gray-200 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-sm dark:text-white focus:ring-2 focus:ring-indigo-500" />
                                                <p v-if="createErrors.joueurs_par_equipe" class="text-xs text-red-500">{{ createErrors.joueurs_par_equipe[0] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="sticky bottom-0 z-10 flex items-center justify-end gap-3 px-8 py-5 bg-gray-50 dark:bg-neutral-800/50 border-t border-gray-100 dark:border-neutral-800 rounded-b-3xl">
                            <button @click="closeCreateModal" class="px-5 py-2.5 text-sm font-bold text-gray-700 dark:text-gray-300 bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl hover:bg-gray-50 dark:hover:bg-neutral-700 transition-colors">
                                {{ t('common.cancel') }}
                            </button>
                            <button 
                                @click="submitCreateEvent" 
                                :disabled="createProcessing"
                                class="px-6 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 rounded-xl shadow-lg shadow-indigo-500/20 transition-all active:scale-95 flex items-center gap-2"
                            >
                                <Loader2 v-if="createProcessing" class="w-4 h-4 animate-spin" />
                                <Plus v-else class="w-4 h-4" />
                                {{ createProcessing ? t('events.creating') : t('events.createEvent') }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active {
    transition: all 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
    opacity: 0;
}
.modal-enter-from > div:last-child, .modal-leave-to > div:last-child {
    transform: scale(0.95) translateY(10px);
}
</style>
