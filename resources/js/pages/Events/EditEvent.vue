<script setup lang="ts">
import { Head, Link, router,usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { 
    ChevronLeft, 
    Save,  
    MapPin, 
    CircleDollarSign,
    Users,
    Loader2,
    Trash2,
    Star,
    X,
    Image as ImageIcon,
    Film,
    AlertCircle
} from 'lucide-vue-next';
import { ref, onMounted, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
// @ts-ignore
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';



const props = defineProps<{
    id: string | number;
}>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Events', href: '/dashboard/events' },
    { title: 'Edit Event', href: `/dashboard/events/${props.id}/edit` },
];

const form = ref({
    titre: '',
    description: '',
    date_debut: '',
    date_fin: '',
    lieu: '',
    prix_spectateur: 0,
    capacite_spectateur: 0,
    categorie: '',
    categorie_autre: '',
    statut: '',
    is_tournoi: false,
    type_tournoi: '',
    prix_participant: 0,
    capacite_participant: 0,
    nombre_equipes: 0,
    joueurs_par_equipe: 0,
    medias: [] as File[],
    poster_url: ''
});

const existingMedias = ref<any[]>([]);
const mediaToDelete = ref<number[]>([]);

const errors = ref<Record<string, string[]>>({});
const processing = ref(false);
const fetching = ref(true);
const categories = ref<{slug: string; label: string}[]>([]);

const fetchCategories = async () => {
    try {
        const res = await axios.get('/web-api/categories');
        categories.value = res.data;
    } catch (e) {
        console.error('Failed to load categories', e);
    }
};

const dateError = computed(() => {
    if (!form.value.date_debut || !form.value.date_fin) return false;
    return new Date(form.value.date_fin) <= new Date(form.value.date_debut);
});

const fetchEvent = async () => {
    try {
        const response = await axios.get(`/web-api/events/${props.id}`); // Note: Need a show endpoint in controller or use search with id
        const event = response.data;
        eventData.value = event;
        
        // Format dates for datetime-local input
        const formatDate = (dateStr: string) => {
            const d = new Date(dateStr);
            return d.toISOString().slice(0, 16);
        };

        form.value = {
            titre: event.titre,
            description: event.description,
            date_debut: formatDate(event.date_debut),
            date_fin: formatDate(event.date_fin),
            lieu: event.lieu,
            prix_spectateur: event.prix_spectateur,
            capacite_spectateur: event.capacite_spectateur,
            categorie: event.categorie,
            categorie_autre: event.categorie_autre ?? '',
            statut: event.statut,
            is_tournoi: event.is_tournoi ?? false,
            type_tournoi: event.type_tournoi ?? '',
            prix_participant: event.prix_participant ?? 0,
            capacite_participant: event.capacite_participant ?? 0,
            nombre_equipes: event.tournoi?.nombre_equipes ?? 0,
            joueurs_par_equipe: event.tournoi?.joueurs_par_equipe ?? 0,
            medias: [] as File[],
            poster_url: event.poster_url ?? ''
        };

        existingMedias.value = event.medias || [];
    } catch (error: any) {
        console.error('Error fetching event:', error);
        if (error.response?.status === 403) {
            toast.error('Your collaboration status does not allow editing this event.');
            router.visit('/dashboard/collaborations');
        } else {
            toast.error('Failed to load event data. Please try again.');
        }
    } finally {
        fetching.value = false;
        // Search location once data is loaded to set map position
        if (form.value.lieu) {
            searchLocation();
        }
    }
};

// Map Logic
const mapContainer = ref<HTMLElement | null>(null);
const map = ref<L.Map | null>(null);
const marker = ref<L.Marker | null>(null);
const isSearchingLocation = ref(false);
const isUpdatingFromMap = ref(false);
const locationSuggestions = ref<any[]>([]);
const isFetchingSuggestions = ref(false);

const initMap = () => {
    if (!mapContainer.value) return;

    const DefaultIcon = L.icon({
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
    });
    L.Marker.prototype.options.icon = DefaultIcon;

    map.value = L.map(mapContainer.value).setView([36.8065, 10.1815], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map.value as any);

    marker.value = L.marker([36.8065, 10.1815], { draggable: true }).addTo(map.value as any);

    marker.value.on('dragend', async () => {
        const position = marker.value?.getLatLng();
        if (position) {
            updateAddressFromCoords(position.lat, position.lng);
        }
    });

    map.value.on('click', (e: any) => {
        const { lat, lng } = e.latlng;
        marker.value?.setLatLng([lat, lng]);
        updateAddressFromCoords(lat, lng);
    });
};

const updateAddressFromCoords = async (lat: number, lng: number) => {
    try {
        const response = await axios.get(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`, { withCredentials: false });
        if (response.data && response.data.display_name) {
            isUpdatingFromMap.value = true;
            form.value.lieu = response.data.display_name;
            setTimeout(() => { isUpdatingFromMap.value = false; }, 500);
        }
    } catch (e) {
        console.error('Reverse geocoding failed', e);
    }
};

const searchLocation = async () => {
    if (!form.value.lieu || form.value.lieu.length < 3) return;
    try {
        const response = await axios.get(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(form.value.lieu)}&limit=1`, { withCredentials: false });
        if (response.data && response.data.length > 0) {
            const { lat, lon } = response.data[0];
            const newPos = new L.LatLng(parseFloat(lat), parseFloat(lon));
            map.value?.setView(newPos, 15);
            marker.value?.setLatLng(newPos);
        }
    } catch (e) {
        console.error('Geocoding failed', e);
    }
};

let geocodeTimeout: any = null;
watch(() => form.value.lieu, (newVal) => {
    if (!newVal || newVal.length < 3 || isSearchingLocation.value || isUpdatingFromMap.value) {
        locationSuggestions.value = [];
        return;
    }

    if (geocodeTimeout) clearTimeout(geocodeTimeout);
    geocodeTimeout = setTimeout(async () => {
        isFetchingSuggestions.value = true;
        try {
            // Added accept-language for better multilingual matching
            const response = await axios.get(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(newVal)}&limit=5&accept-language=fr,en,ar`, { withCredentials: false });
            locationSuggestions.value = response.data;
            
            // Real-time update: move map to first result found immediately
            if (response.data && response.data.length > 0 && !isUpdatingFromMap.value) {
                const { lat, lon } = response.data[0];
                const newPos = new L.LatLng(parseFloat(lat), parseFloat(lon));
                map.value?.setView(newPos, 14); // Zoom out slightly for real-time overview
                marker.value?.setLatLng(newPos);
            }
        } catch (e) {
            console.error('Fetching suggestions failed', e);
        } finally {
            isFetchingSuggestions.value = false;
        }
    }, 300);
});

const selectLocation = (suggestion: any) => {
    const { lat, lon, display_name } = suggestion;
    const newPos = new L.LatLng(parseFloat(lat), parseFloat(lon));
    
    isUpdatingFromMap.value = true;
    form.value.lieu = display_name;
    locationSuggestions.value = [];
    
    map.value?.setView(newPos, 16);
    marker.value?.setLatLng(newPos);
    
    setTimeout(() => { isUpdatingFromMap.value = false; }, 500);
};

const handleLocationKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Enter' && locationSuggestions.value.length > 0) {
        e.preventDefault();
        selectLocation(locationSuggestions.value[0]);
    }
};

const clearSuggestions = () => {
    setTimeout(() => {
        locationSuggestions.value = [];
    }, 200);
};

const pingLocation = () => {
    if (!navigator.geolocation) {
        toast.error('Geolocation is not supported by your browser');
        return;
    }

    isSearchingLocation.value = true;
    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const { latitude, longitude } = position.coords;
            const newPos = new L.LatLng(latitude, longitude);
            
            map.value?.setView(newPos, 16);
            marker.value?.setLatLng(newPos);
            
            await updateAddressFromCoords(latitude, longitude);
            isSearchingLocation.value = false;
        },
        (error) => {
            toast.error('Unable to retrieve your location');
            isSearchingLocation.value = false;
        }
    );
};

const handleFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files) {
        form.value.medias = [...form.value.medias, ...Array.from(input.files)];
    }
};

const removeNewMedia = (index: number) => {
    form.value.medias.splice(index, 1);
};

const removeExistingMedia = (id: number) => {
    mediaToDelete.value.push(id);
    existingMedias.value = existingMedias.value.filter(m => m.id !== id);
};

const setMainPoster = (url: string) => {
    form.value.poster_url = url;
};

const submit = async () => {
    processing.value = true;
    errors.value = {};
    
    try {
        if (!eventData.value?.can_edit) {
            toast.error('You do not have permission to save changes to this event.');
            return;
        }

        const formData = new FormData();
        // Spoof PUT request for Laravel to process FormData
        formData.append('_method', 'PUT');

        Object.entries(form.value).forEach(([key, value]) => {
            if (key === 'medias') {
                (value as File[]).forEach(file => {
                    formData.append('medias[]', file);
                });
            } else if (key === 'categorie_autre') {
                if (form.value.categorie === 'autre' && value) {
                    formData.append(key, String(value));
                }
            } else if (value !== null && value !== '') {
                if (typeof value === 'boolean') {
                    formData.append(key, value ? '1' : '0');
                } else {
                    formData.append(key, String(value));
                }
            }
        });

        mediaToDelete.value.forEach(id => {
            formData.append('media_to_delete[]', String(id));
        });

        await axios.post(`/web-api/events/${props.id}`, formData, {
            headers: { 
                'Content-Type': 'multipart/form-data',
                'X-Requested-With': 'XMLHttpRequest'
             }
        });
        
        toast.success('Event updated successfully!');
        
        // Successful update, redirect to list using Inertia router
        router.visit('/dashboard/events', {
            method: 'get',
            replace: true,
            preserveScroll: false,
            onFinish: () => {
                // Dual-safety: fallback redirect if Inertia transition fails
                setTimeout(() => {
                    if (window.location.pathname !== '/dashboard/events') {
                        window.location.href = '/dashboard/events';
                    }
                }, 1000);
            }
        });
    } catch (error: any) {
        console.error('Error updating event:', error);
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            // Fallback for non-validation errors
            toast.error(error.response?.data?.message || 'An unexpected error occurred. Please try again.');
        }
    } finally {
        processing.value = false;
    }
};

// Collaboration feature logic

const page = usePage();
const auth = computed(() => page.props.auth as any);

const eventData = ref<any>(null);
const collaborators = ref<any[]>([]);
const searchQuery = ref('');
const searchResults = ref<any[]>([]);
const selectedOrganizer = ref<any>(null);
const isInviting = ref(false);

let searchTimeout: any = null;
const searchOrganizers = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    if (!searchQuery.value || searchQuery.value.length < 2) {
        searchResults.value = [];
        return;
    }
    searchTimeout = setTimeout(async () => {
        try {
            const res = await axios.get(`/web-api/organizers/search?q=${searchQuery.value}`);
            searchResults.value = res.data;
        } catch (e) {
            console.error('Search error', e);
        }
    }, 300);
};

const selectOrganizer = (user: any) => {
    selectedOrganizer.value = user;
    searchQuery.value = user.username;
    searchResults.value = [];
};

const fetchCollaborators = async () => {
    // Only allow fetching detailed collaborators for owner or accepted collaborator
    if (eventData.value?.is_managed) {
        try {
            const res = await axios.get(`/web-api/events/${props.id}/collaborators`);
            collaborators.value = res.data;
        } catch (e) {
            console.error('Fetch collaborators error', e);
        }
    }
};

const inviteCollaborator = async () => {
    if (!selectedOrganizer.value) return;
    isInviting.value = true;
    try {
        await axios.post(`/web-api/events/${props.id}/collaborators/invite`, {
            organizer_id: selectedOrganizer.value.id
        });
        toast.success('Invitation sent successfully!');
        selectedOrganizer.value = null;
        searchQuery.value = '';
        fetchCollaborators();
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Failed to send invite.');
    } finally {
        isInviting.value = false;
    }
};

onMounted(async () => {
    await Promise.all([fetchEvent(), fetchCategories()]);
    await fetchCollaborators();
    initMap();
});
</script>

<template>
    <Head title="Edit Event" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto p-6 space-y-8">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <Link href="/dashboard/events" class="flex items-center text-sm text-muted-foreground hover:text-blue-600 transition-colors mb-2">
                        <ChevronLeft class="w-4 h-4 mr-1" />
                        Back to events
                    </Link>
                    <h1 class="text-3xl font-bold tracking-tight">Edit Event</h1>
                    <p class="text-muted-foreground">Modify the details of your event.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Badge v-if="eventData && !eventData.can_edit" variant="outline" class="bg-amber-50 text-amber-600 border-amber-200 uppercase font-black tracking-widest text-[10px]">
                        View-Only Access
                    </Badge>
                    <Button variant="outline" as-child>
                        <Link href="/dashboard/events">Cancel</Link>
                    </Button>
                    <Button @click="submit" :disabled="processing || fetching || (eventData && !eventData.can_edit) || dateError" class="bg-blue-600 hover:bg-blue-700">
                        <Save class="w-4 h-4 mr-2" />
                        {{ processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </div>

            <div v-if="fetching" class="flex flex-col items-center justify-center py-24 space-y-4">
                <Loader2 class="w-12 h-12 text-blue-600 animate-spin" />
                <p class="text-muted-foreground animate-pulse">Loading event details...</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="md:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Event Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Event Title *</label>
                                <Input v-model="form.titre" />
                                <InputError :message="errors.titre?.[0]" />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Description *</label>
                                <textarea v-model="form.description" class="flex min-h-[150px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"></textarea>
                                <InputError :message="errors.description?.[0]" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium">Category *</label>
                                    <select v-model="form.categorie" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                        <option v-for="cat in categories" :key="cat.slug" :value="cat.slug">{{ cat.label }}</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                    <InputError :message="errors.categorie?.[0]" />
                                </div>

                                <!-- Custom category when 'autre' is selected -->
                                <div v-if="form.categorie === 'autre'" class="space-y-2">
                                    <label class="text-sm font-medium">Custom Category *</label>
                                    <Input v-model="form.categorie_autre" placeholder="Enter your custom category name..." />
                                    <InputError :message="errors.categorie_autre?.[0]" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Media Management</CardTitle>
                            <CardDescription>View existing media, set a main poster, or upload new files.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Existing Media -->
                            <div v-if="existingMedias.length > 0" class="space-y-3">
                                <h4 class="text-sm font-medium">Existing Media</h4>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    <div v-for="media in existingMedias" :key="media.id" class="relative group aspect-video rounded-lg overflow-hidden border bg-muted">
                                        <img v-if="media.type === 'image'" :src="media.url" class="w-full h-full object-cover" />
                                        <div v-else class="w-full h-full flex items-center justify-center bg-slate-100 dark:bg-slate-800">
                                            <Film class="w-8 h-8 text-muted-foreground" />
                                        </div>
                                        
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                            <Button 
                                                v-if="media.type === 'image'"
                                                size="icon" 
                                                variant="secondary" 
                                                class="h-8 w-8 rounded-full"
                                                :class="{ 'bg-amber-500 text-white hover:bg-amber-600': form.poster_url === media.url }"
                                                @click="setMainPoster(media.url)"
                                                title="Set as Main Poster"
                                            >
                                                <Star class="w-4 h-4" :class="{ 'fill-current': form.poster_url === media.url }" />
                                            </Button>
                                            <Button 
                                                size="icon" 
                                                variant="destructive" 
                                                class="h-8 w-8 rounded-full"
                                                @click="removeExistingMedia(media.id)"
                                                title="Delete Media"
                                            >
                                                <Trash2 class="w-4 h-4" />
                                            </Button>
                                        </div>

                                        <div v-if="form.poster_url === media.url" class="absolute top-1 left-1">
                                            <Badge class="bg-amber-500 hover:bg-amber-500 text-[8px] h-4 px-1">Main Poster</Badge>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- New Media Upload -->
                            <div class="space-y-4 pt-4 border-t">
                                <h4 class="text-sm font-medium">Add New Media</h4>
                                <div class="flex items-center justify-center w-full">
                                    <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-lg cursor-pointer bg-slate-50 hover:bg-slate-100 dark:bg-slate-900 border-slate-300 dark:border-slate-700">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <svg class="w-8 h-8 mb-3 text-muted-foreground" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                            </svg>
                                            <p class="mb-2 text-sm text-muted-foreground"><span class="font-bold text-foreground">Click to upload</span> or drag and drop</p>
                                            <p class="text-xs text-muted-foreground">PNG, JPG, MP4 or MOV (MAX. 20MB)</p>
                                        </div>
                                        <input id="dropzone-file" type="file" multiple accept="image/*,video/*" class="hidden" @change="handleFileChange" />
                                    </label>
                                </div>
                                
                                <div v-if="form.medias.length > 0" class="space-y-2">
                                    <h5 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">New files to add:</h5>
                                    <div class="space-y-1">
                                        <div v-for="(file, index) in form.medias" :key="index" class="flex items-center justify-between p-2 rounded-md bg-muted/50 text-sm border border-dashed">
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                <ImageIcon v-if="file.type.startsWith('image/')" class="w-4 h-4 shrink-0 text-blue-500" />
                                                <Film v-else class="w-4 h-4 shrink-0 text-purple-500" />
                                                <span class="truncate">{{ file.name }}</span>
                                                <span class="text-[10px] text-muted-foreground whitespace-nowrap">({{ (file.size / 1024 / 1024).toFixed(2) }} MB)</span>
                                            </div>
                                            <button @click="removeNewMedia(index)" class="p-1 hover:text-destructive transition-colors rounded-full hover:bg-destructive/10">
                                                <X class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <InputError :message="errors?.['medias.0']?.[0] || errors?.medias?.[0]" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Location & Time</CardTitle>
                        </CardHeader>
                        <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2 space-y-2">
                                <label class="text-sm font-medium">Venue / Location *</label>
                                                                <div class="relative">
                                    <MapPin class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                    <Input 
                                        v-model="form.lieu" 
                                        class="pl-10" 
                                        @keydown="handleLocationKeydown"
                                        @blur="clearSuggestions"
                                    />
                                    
                                    <!-- Location Suggestions Dropdown -->
                                    <div v-if="locationSuggestions.length > 0" class="absolute z-[100] w-full mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-2xl max-h-64 overflow-y-auto">
                                        <button 
                                            v-for="(s, i) in locationSuggestions" 
                                            :key="i"
                                            type="button"
                                            class="w-full text-left px-4 py-3 hover:bg-blue-50 dark:hover:bg-blue-900/20 border-b border-slate-100 dark:border-slate-800 last:border-0 transition-colors flex items-start gap-3 group"
                                            @mousedown="selectLocation(s)"
                                        >
                                            <div class="mt-1 w-6 h-6 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-100 dark:group-hover:bg-blue-800/50 transition-colors">
                                                <MapPin class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                                            </div>
                                            <div class="flex flex-col flex-1 min-w-0">
                                                <span class="text-sm font-semibold truncate text-slate-900 dark:text-slate-100">{{ s.display_name.split(',')[0] }}</span>
                                                <span class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1 leading-relaxed">{{ s.display_name }}</span>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                                <InputError :message="errors.lieu?.[0]" />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Start Date & Time *</label>
                                <Input v-model="form.date_debut" type="datetime-local" />
                                <InputError :message="errors.date_debut?.[0]" />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">End Date & Time *</label>
                                <Input v-model="form.date_fin" type="datetime-local" :class="{ 'border-red-500': dateError }" />
                                <InputError :message="errors.date_fin?.[0]" />
                                <p v-if="dateError" class="text-xs text-red-500 font-medium flex items-center gap-1">
                                    <AlertCircle class="w-3 h-3" />
                                    End date must be after start date
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Capacity & Pricing</CardTitle>
                        </CardHeader>
                        <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Maximum Capacity *</label>
                                <div class="relative">
                                    <Users class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                    <Input v-model.number="form.capacite_spectateur" type="number" min="0" class="pl-10" />
                                </div>
                                <InputError :message="errors.capacite_spectateur?.[0]" />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Ticket Price (TND) *</label>
                                <div class="relative">
                                    <CircleDollarSign class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                    <Input v-model.number="form.prix_spectateur" type="number" min="0" step="0.01" class="pl-10" />
                                </div>
                                <InputError :message="errors.prix_spectateur?.[0]" />
                            </div> <!-- Close Ticket Price div -->

                            <!-- Tournament Options -->
                            <div class="md:col-span-2 space-y-4 mt-2 p-4 border border-slate-200 dark:border-slate-800 rounded-lg bg-slate-50 dark:bg-slate-900/50">
                                <label class="flex items-center space-x-2 text-sm font-medium cursor-pointer">
                                    <input type="checkbox" v-model="form.is_tournoi" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500" />
                                    <span>Is a Tournament?</span>
                                </label>
                                
                                <div v-if="form.is_tournoi" class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-slate-200 dark:border-slate-800">
                                    <div class="md:col-span-2 space-y-2">
                                        <label class="text-sm font-medium">Tournament Type *</label>
                                        <select v-model="form.type_tournoi" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                            <option value="" disabled>Select type</option>
                                            <option value="equipe">Équipe</option>
                                            <option value="individuel">Individuel</option>
                                        </select>
                                        <InputError :message="errors.type_tournoi?.[0]" />
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">Participant Price (TND) *</label>
                                        <div class="relative">
                                            <CircleDollarSign class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                            <Input v-model.number="form.prix_participant" type="number" min="0" step="0.01" class="pl-10" />
                                        </div>
                                        <InputError :message="errors.prix_participant?.[0]" />
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">
                                            {{ form.type_tournoi === 'equipe' ? 'Number participant in equipe *' : 'Participant Seats *' }}
                                        </label>
                                        <div class="relative">
                                            <Users class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                            <Input v-model.number="form.capacite_participant" type="number" min="0" class="pl-10" />
                                        </div>
                                        <InputError :message="errors.capacite_participant?.[0]" />
                                    </div>

                                    <div v-if="form.type_tournoi === 'equipe'" class="space-y-2">
                                        <label class="text-sm font-medium">Equipe Number *</label>
                                        <div class="relative">
                                            <Users class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                            <Input v-model.number="form.nombre_equipes" type="number" class="pl-10" />
                                        </div>
                                        <InputError :message="errors.nombre_equipes?.[0]" />
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-6">
                    <!-- Map Card -->
                    <Card class="overflow-hidden border-blue-100 shadow-md">
                        <CardHeader class="pb-3 bg-slate-50/50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-blue-600">
                                    <MapPin class="w-4 h-4" />
                                    <CardTitle class="text-sm font-semibold">Event Location</CardTitle>
                                </div>
                                <Button 
                                    variant="outline" 
                                    size="sm" 
                                    class="h-8 gap-1.5 text-xs bg-white"
                                    @click="pingLocation"
                                    :disabled="isSearchingLocation"
                                >
                                    <Sparkles class="w-3.5 h-3.5" :class="{ 'animate-spin': isSearchingLocation }" />
                                    {{ isSearchingLocation ? 'Locating...' : 'Ping My Location' }}
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div ref="mapContainer" class="h-[250px] w-full z-0"></div>
                            <div class="p-3 bg-blue-50/30 border-t border-blue-50">
                                <p class="text-[11px] text-blue-700/70 flex items-center gap-1.5">
                                    <Info class="w-3 h-3" />
                                    Tip: Drag the marker or click on the map to refine the location.
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="bg-blue-50 border-blue-200 shadow-sm">
                        <CardHeader>
                            <CardTitle class="text-sm text-blue-800 flex items-center gap-2">
                                <Info class="w-4 h-4" />
                                Note
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="text-xs text-blue-700 leading-relaxed">
                            Changing the event status to 'Annulé' will notify all registered participants automatically. 
                            If you change the dates, please double check the venue availability.
                        </CardContent>
                    </Card>

                    <!-- Collaborators Management Card -->
                    <Card v-if="eventData?.can_manage_team">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Users class="w-5 h-5 text-blue-600" />
                                Manage Co-Organizers
                            </CardTitle>
                            <CardDescription>{{ eventData?.is_owner ? 'Invite other organizers to help manage your event.' : 'List of team members managing this event.' }}</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Invite New Collaborator (Owner Only) -->
                            <div class="space-y-3 font-semibold" v-if="eventData?.is_owner">
                                <label class="text-sm font-medium">Invite by Name or Email</label>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <Input 
                                            v-model="searchQuery" 
                                            placeholder="Search organizers..." 
                                            @input="searchOrganizers"
                                        />
                                        <!-- Search Results Dropdown -->
                                        <div v-if="searchResults.length > 0 && searchQuery" class="absolute z-10 w-full mt-1 bg-white dark:bg-slate-900 border rounded-md shadow-lg max-h-48 overflow-y-auto">
                                            <div 
                                                v-for="user in searchResults" 
                                                :key="user.id"
                                                class="px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer flex justify-between items-center"
                                                @click="selectOrganizer(user)"
                                            >
                                                <span>{{ user.username }}</span>
                                                <span class="text-xs text-muted-foreground">{{ user.email }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <Button 
                                        @click="inviteCollaborator" 
                                        :disabled="!selectedOrganizer || isInviting"
                                        class="bg-blue-600 hover:bg-blue-700"
                                    >
                                        {{ isInviting ? 'Sending...' : 'Send Invite' }}
                                    </Button>
                                </div>
                                <div v-if="selectedOrganizer" class="flex items-center justify-between p-2 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-md text-sm">
                                    <span>Selected: <strong>{{ selectedOrganizer.username }}</strong></span>
                                    <button @click="selectedOrganizer = null; searchQuery = ''" class="hover:text-blue-900"><X class="w-4 h-4"/></button>
                                </div>
                            </div>

                            <!-- List Current Collaborators -->
                            <div v-if="collaborators.length > 0" class="pt-4 border-t space-y-3">
                                <h4 class="text-sm font-medium text-muted-foreground">Current & Pending Co-Organizers</h4>
                                <div class="space-y-2">
                                    <div v-for="collab in collaborators" :key="collab.id" class="flex items-center justify-between p-3 border rounded-lg bg-card transition-all hover:border-slate-300 dark:hover:border-slate-700">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-sm">{{ collab.organizer.username }}</span>
                                            <span class="text-xs text-muted-foreground">{{ collab.organizer.email }}</span>
                                        </div>
                                        <div>
                                            <Badge v-if="collab.statut === 'accepted'" class="bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/20 border-emerald-200 uppercase tracking-tighter text-[9px] font-black">Accepted</Badge>
                                            <Badge v-else-if="collab.statut === 'pending'" class="bg-amber-500/10 text-amber-600 hover:bg-amber-500/20 border-amber-200 uppercase tracking-tighter text-[9px] font-black">Pending</Badge>
                                            <Badge v-else class="bg-red-500/10 text-red-600 hover:bg-red-500/20 border-red-200 uppercase tracking-tighter text-[9px] font-black">Declined</Badge>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
