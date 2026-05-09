<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { ChevronLeft, CircleDollarSign, Info, MapPin, Save, Sparkles, Users, AlertCircle } from 'lucide-vue-next';
import { X } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { toast } from 'vue-sonner';
// @ts-ignore
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

import InputError from '@/components/InputError.vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';

// Breadcrumbs
const { t } = useI18n();
const breadcrumbs = [
    { title: t('events.dashboard'), href: '/dashboard' },
    { title: t('events.events'), href: '/dashboard/events' },
    { title: t('events.createEvent'), href: '/dashboard/events/create' },
];

// Types
interface Suggestion {
    prompt: string;
    keywords?: string;
    url: string;
    fallback_url?: string;
    loaded?: boolean;
    error?: boolean;
    isUploading?: boolean;
}

// Auth
const page = usePage();
const auth = computed(() => page.props.auth as any);

const isMissingStripe = computed(() => {
    const role = auth.value.user?.role?.value || auth.value.user?.role;
    return role === 'organisateur' && !auth.value.organisateur_has_stripe;
});

// Form
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
    is_tournoi: false,
    type_tournoi: '',
    prix_participant: 0,
    capacite_participant: 0,
    nombre_equipes: 0,
    joueurs_par_equipe: 0,
    medias: [] as File[],
    ai_media_urls: [] as string[],
    collaborator_ids: [] as number[],
});

const formErrors = ref<Record<string, string[]>>({});
const processing = ref(false);

const dateError = computed(() => {
    if (!form.value.date_debut || !form.value.date_fin) return false;
    return new Date(form.value.date_fin) <= new Date(form.value.date_debut);
});

// AI
const suggestions = ref<Suggestion[]>([]);
const isGenerating = ref(false);
const generateError = ref<string | null>(null);
const generationAttempts = ref(0);
const MAX_ATTEMPTS = 3;

// Categories (dynamic from API)
const categories = ref<{ slug: string; label: string }[]>([]);
const fetchCategories = async () => {
    try {
        const res = await axios.get('/web-api/categories');
        categories.value = res.data;
    } catch (e) {
        console.error('Failed to load categories', e);
    }
};

// Timeout manager
const timeouts = new Set<ReturnType<typeof setTimeout>>();

const clearSuggestedTimeouts = () => {
    timeouts.forEach(t => clearTimeout(t));
    timeouts.clear();
};

const generateIdeas = async () => {
    if (generationAttempts.value >= MAX_ATTEMPTS) {
        generateError.value = t('events.limitReached');
        return;
    }

    if (!form.value.titre) {
        generateError.value = t('events.enterTitleFirst');
        return;
    }

    clearSuggestedTimeouts();
    isGenerating.value = true;
    generateError.value = null;

    try {
        const res = await axios.post('/web-api/ai/suggest-event', {
            titre: form.value.titre,
            description: form.value.description,
            categorie: form.value.categorie
        });

        const newItems = (res.data.suggestions ?? []).map((s: any) => ({
            ...s,
            loaded: false,
            error: false,
            isUploading: false
        }));

        suggestions.value = [...suggestions.value, ...newItems];
        generationAttempts.value++;

        newItems.forEach((s: Suggestion) => {
            const timer = setTimeout(() => {
                if (!s.loaded) {
                    s.url = s.fallback_url || s.url;
                }
            }, 6000);
            timeouts.add(timer);
        });

    } catch {
        generateError.value = t('events.generationFailed');
    } finally {
        isGenerating.value = false;
    }
};

// Upload selected image
const applySuggestion = async (s: Suggestion) => {
    try {
        s.isUploading = true;

        const response = await axios.post('/web-api/ai/upload-image', {
            image_url: s.url,
            prompt: s.prompt,
            keywords: s.keywords
        });

        if (response.data.success) {
            form.value.ai_media_urls.push(response.data.secure_url);
        }
    } catch {
        toast.error(t('events.aiUploadFailed'));
    } finally {
        s.isUploading = false;
    }
};

// Collaborator Search logic

const searchQuery = ref('');
const searchResults = ref<any[]>([]);
const selectedCollaborators = ref<any[]>([]);

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
            // Filter out already selected and the owner
            searchResults.value = res.data.filter((u: any) => 
                u.id !== auth.value.user?.id && 
                !selectedCollaborators.value.some(sel => sel.id === u.id)
            );
        } catch (e) {
            console.error('Search error', e);
        }
    }, 300);
};

const selectOrganizer = (user: any) => {
    selectedCollaborators.value.push(user);
    form.value.collaborator_ids.push(user.id);
    searchQuery.value = '';
    searchResults.value = [];
};

const removeOrganizer = (index: number) => {
    selectedCollaborators.value.splice(index, 1);
    form.value.collaborator_ids.splice(index, 1);
};

// File upload
const handleFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files) {
        form.value.medias = Array.from(input.files);
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

    // Fix for Leaflet default icon issues in build tools
    const DefaultIcon = L.icon({
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
    });
    L.Marker.prototype.options.icon = DefaultIcon;

    map.value = L.map(mapContainer.value).setView([36.8065, 10.1815], 13); // Default to Tunis

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

    // Handle map clicks
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
        toast.error(t('events.geoNotSupported'));
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
            toast.error(t('events.unableToRetrieveLocation'));
            isSearchingLocation.value = false;
        }
    );
};

// Original submit logic
const submit = async () => {
    if (processing.value || isMissingStripe.value) return;

    processing.value = true;
    formErrors.value = {};

    try {
        const formData = new FormData();
        Object.keys(form.value).forEach(key => {
            const value = (form.value as any)[key];
            if (key === 'medias') {
                value.forEach((f: File) => formData.append('medias[]', f));
            } else if (key === 'ai_media_urls') {
                value.forEach((u: string) => formData.append('ai_media_urls[]', u));
            } else if (key === 'collaborator_ids') {
                value.forEach((id: number) => formData.append('collaborator_ids[]', String(id)));
            } else if (key === 'categorie_autre') {
                if (form.value.categorie === 'autre') {
                    formData.append(key, String(value));
                }
            } else {
                formData.append(key, String(value));
            }
        });

        await axios.post('/web-api/events', formData);
        toast.success(t('events.eventPublishedSuccess'));
        
        setTimeout(() => {
            router.visit('/dashboard/events');
        }, 100);

    } catch (error: any) {
        if (error.response?.data?.errors) {
            formErrors.value = error.response.data.errors;
        } else {
            toast.error(t('events.unexpectedError'));
        }
    } finally {
        processing.value = false;
    }
};

onMounted(() => {
    fetchCategories();
    initMap();
});

onUnmounted(() => {
    clearSuggestedTimeouts();
});
</script>

<template>
    <Head :title="t('events.createEvent')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto p-6 space-y-8">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <Link href="/dashboard/events" class="flex items-center text-sm text-muted-foreground hover:text-blue-600 transition-colors mb-2">
                        <ChevronLeft class="w-4 h-4 mr-1" />
                        {{ t('events.backToEvents') }}
                    </Link>
                    <h1 class="text-3xl font-bold tracking-tight">{{ t('events.hostEvent') }}</h1>
                    <p class="text-muted-foreground">{{ t('events.hostEventDesc') }}</p>
                </div>
                <Button @click="submit" :disabled="processing || isMissingStripe || dateError" class="bg-blue-600 hover:bg-blue-700">
                    <Save class="w-4 h-4 mr-2" />
                    {{ processing ? t('events.publishing') : t('events.publishEvent') }}
                </Button>
            </div>

            <!-- Stripe Connect Missing Warning -->
            <div v-if="isMissingStripe" class="bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900 rounded-xl p-4 flex gap-4 items-center">
                <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center flex-shrink-0">
                    <AlertCircle class="w-6 h-6 text-red-600 dark:text-red-400" />
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-red-900 dark:text-red-300">{{ t('events.stripeAccountRequired') }}</h3>
                    <p class="text-sm text-red-800 dark:text-red-400">{{ t('events.stripeAccountDesc') }}</p>
                </div>
                <Link href="/settings/profile">
                    <Button variant="outline" size="sm" class="border-red-200 hover:bg-red-50 dark:border-red-900 dark:hover:bg-red-900/30 text-red-700 dark:text-red-400">
                        {{ t('events.connectStripeNow') }}
                    </Button>
                </Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Main Form Section -->
                <div class="md:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-2 text-blue-600 mb-2">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold">1</div>
                                <CardTitle>{{ t('events.basicInfo') }}</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ t('events.eventTitle') }} *</label>
                                <Input v-model="form.titre" :placeholder="t('events.eventTitlePlaceholder')" />
                                <InputError :message="formErrors?.titre?.[0]" />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ t('events.description') }} *</label>
                                <textarea v-model="form.description" :placeholder="t('events.descriptionPlaceholder')" class="flex min-h-[150px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"></textarea>
                                <InputError :message="formErrors?.description?.[0]" />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ t('events.category') }} *</label>
                                <select v-model="form.categorie" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                    <option value="" disabled>{{ t('events.selectCategory') }}</option>
                                    <option v-for="cat in categories" :key="cat.slug" :value="cat.slug">{{ cat.label }}</option>
                                    <option value="autre">{{ t('events.other') }}</option>
                                </select>
                                <InputError :message="formErrors?.categorie?.[0]" />
                            </div>

                            <!-- Custom category input when 'autre' is selected -->
                            <div v-if="form.categorie === 'autre'" class="space-y-2">
                                <label class="text-sm font-medium">{{ t('events.customCategory') }} *</label>
                                <Input v-model="form.categorie_autre" :placeholder="t('events.customCategoryPlaceholder')" />
                                <InputError :message="formErrors?.categorie_autre?.[0]" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-2 text-blue-600 mb-2">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold">2</div>
                                <CardTitle>{{ t('events.locationTime') }}</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2 space-y-2">
                                <label class="text-sm font-medium">{{ t('events.venueLocation') }} *</label>
                                <div class="relative">
                                    <MapPin class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                    <Input 
                                        v-model="form.lieu" 
                                        :placeholder="t('events.venuePlaceholder')" 
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
                                <InputError :message="formErrors?.lieu?.[0]" />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ t('events.startDate') }} *</label>
                                <Input v-model="form.date_debut" type="datetime-local" />
                                <InputError :message="formErrors?.date_debut?.[0]" />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ t('events.endDate') }} *</label>
                                <Input v-model="form.date_fin" type="datetime-local" :class="{ 'border-red-500': dateError }" />
                                <InputError :message="formErrors?.date_fin?.[0]" />
                                <p v-if="dateError" class="text-xs text-red-500 font-medium flex items-center gap-1">
                                    <AlertCircle class="w-3 h-3" />
                                    {{ t('events.endDateError') }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-2 text-blue-600 mb-2">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold">3</div>
                                <CardTitle>{{ t('events.mediaUploads') }}</CardTitle>
                            </div>
                            <div class="mb-4 flex items-center justify-between">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="gap-2 border-purple-300 text-purple-700 hover:bg-purple-50 hover:border-purple-400 transition-all"
                                    :disabled="isGenerating || generationAttempts >= MAX_ATTEMPTS"
                                    @click.stop="generateIdeas"
                                >
                                    <Sparkles class="w-4 h-4" :class="{ 'animate-spin': isGenerating }" />
                                    {{ isGenerating ? t('events.generating') : (generationAttempts >= MAX_ATTEMPTS ? t('events.limitReached') : `✨ ${t('events.generateCovers')}`) }}
                                </Button>
                                <span v-if="generationAttempts < MAX_ATTEMPTS" class="text-[10px] font-medium text-purple-600 bg-purple-100 px-2 py-0.5 rounded-full uppercase tracking-tighter">
                                    {{ t('events.attemptsLeft', { count: MAX_ATTEMPTS - generationAttempts }) }}
                                </span>
                            </div>
                            <CardDescription>{{ t('events.mediaUploadDesc') }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <!-- AI Suggestions Panel -->
                                <div v-if="suggestions.length > 0" class="space-y-3 mb-6 p-4 rounded-xl bg-purple-50/50 dark:bg-purple-950/10 border border-purple-100 dark:border-purple-900/30">
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs font-semibold text-purple-600 uppercase tracking-wide">✨ {{ t('events.aiSuggestionHistory') }}</p>
                                        <button @click="suggestions = []; generationAttempts = 0" class="text-xs text-muted-foreground hover:text-foreground">{{ t('common.clearAll') }}</button>
                                    </div>
                                    <div class="flex gap-4 overflow-x-auto pb-4 scrollbar-thin">
                                        <button
                                            v-for="(s, i) in suggestions"
                                            :key="i"
                                            type="button"
                                            class="relative flex-shrink-0 w-64 aspect-video rounded-lg overflow-hidden group bg-slate-200"
                                            @click="applySuggestion(s)"
                                        >
                                            <div v-if="s.isUploading" class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center text-white z-10">
                                                <div class="w-6 h-6 border-2 border-white border-t-transparent rounded-full animate-spin mb-2"></div>
                                                <span class="text-[10px]">{{ t('events.uploading') }}</span>
                                            </div>
                                            <div v-if="!s.loaded && !s.error && !s.isUploading" class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 gap-2">
                                                <Sparkles class="w-8 h-8 animate-pulse text-purple-500" />
                                                <span class="text-[10px]">{{ t('events.generatingVision') }}</span>
                                            </div>
                                            <img 
                                                v-if="!s.error"
                                                :src="s.url" 
                                                alt="AI Suggestion" 
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform" 
                                                @load="s.loaded = true"
                                                @error="(e) => {
                                                    const target = e.target as HTMLImageElement;
                                                    if (s.fallback_url && target.src !== s.fallback_url) {
                                                        target.src = s.fallback_url;
                                                    }
                                                    s.error = false;
                                                }"
                                            />
                                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                                <span class="text-white text-xs font-medium px-2 py-1 bg-purple-600 rounded">{{ t('events.useThisCover') }}</span>
                                            </div>
                                        </button>
                                    </div>
                                    <p v-if="generateError" class="text-xs text-red-500 mt-2">{{ generateError }}</p>
                                </div>

                                <div class="flex items-center justify-center w-full">
                                    <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-lg cursor-pointer bg-slate-50 dark:bg-slate-900/50 hover:bg-slate-100 dark:hover:bg-slate-800 border-slate-300 dark:border-slate-700">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <p class="mb-2 text-sm text-muted-foreground"><span class="font-bold text-foreground">{{ t('events.clickToUpload') }}</span> {{ t('events.orDragAndDrop') }}</p>
                                            <p class="text-xs text-muted-foreground">{{ t('events.supportedFormats') }}</p>
                                        </div>
                                        <input id="dropzone-file" type="file" multiple accept="image/*,video/*" class="hidden" @change="handleFileChange" />
                                    </label>
                                </div>
                                
                                <div v-show="form.medias.length > 0 || form.ai_media_urls.length > 0" class="flex flex-col space-y-2 mt-4">
                                    <h4 class="text-sm font-medium">{{ t('events.selectedMedia') }}:</h4>
                                    <ul class="text-sm space-y-2">
                                        <li v-for="(file, idx) in form.medias" :key="'file-'+idx" class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg">
                                            <span class="truncate flex-1 text-xs">{{ file.name }}</span>
                                            <button @click="form.medias.splice(idx, 1)" class="text-slate-400 hover:text-red-500">✕</button>
                                        </li>
                                        <li v-for="(url, idx) in form.ai_media_urls" :key="'ai-'+idx" class="flex items-center gap-2 p-2 bg-purple-50 rounded-lg">
                                            <img :src="url" class="w-8 h-8 object-cover rounded" />
                                            <span class="truncate flex-1 text-xs italic">{{ t('events.aiGeneratedCover') }}</span>
                                            <button @click="form.ai_media_urls.splice(idx, 1)" class="text-slate-400 hover:text-red-500">✕</button>
                                        </li>
                                    </ul>
                                </div>
                                <InputError :message="formErrors?.medias?.[0]" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-2 text-blue-600 mb-2">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold">4</div>
                                <CardTitle>{{ t('events.ticketsCapacity') }}</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ t('events.maxSeats') }} *</label>
                                <div class="relative">
                                    <Users class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                    <Input v-model.number="form.capacite_spectateur" type="number" min="0" class="pl-10" />
                                </div>
                                <InputError :message="formErrors?.capacite_spectateur?.[0]" />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">{{ t('events.ticketPrice') }} *</label>
                                <div class="relative">
                                    <CircleDollarSign class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                    <Input v-model.number="form.prix_spectateur" type="number" min="0" step="0.01" class="pl-10" />
                                </div>
                                <InputError :message="formErrors?.prix_spectateur?.[0]" />
                            </div>

                            <!-- Tournament Options -->
                            <div class="md:col-span-2 space-y-4 mt-2 p-4 border border-slate-200 dark:border-slate-800 rounded-lg bg-slate-50 dark:bg-slate-900/50">
                                <label class="flex items-center space-x-2 text-sm font-medium cursor-pointer">
                                    <input type="checkbox" v-model="form.is_tournoi" class="w-4 h-4 text-blue-600 rounded border-gray-300 dark:border-gray-600 dark:bg-slate-800 focus:ring-blue-500" />
                                    <span>{{ t('events.isTournament') }}</span>
                                </label>
                                
                                <div v-if="form.is_tournoi" class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-slate-200 dark:border-slate-800">
                                    <div class="md:col-span-2 space-y-2">
                                        <label class="text-sm font-medium">{{ t('events.tournamentType') }} *</label>
                                        <select v-model="form.type_tournoi" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                                            <option value="" disabled>{{ t('events.selectType') }}</option>
                                            <option value="equipe">{{ t('events.teamType') }}</option>
                                            <option value="individuel">{{ t('events.individualType') }}</option>
                                        </select>
                                        <InputError :message="formErrors?.type_tournoi?.[0]" />
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">{{ t('events.participantPrice') }} *</label>
                                        <Input v-model.number="form.prix_participant" type="number" min="0" step="0.01" />
                                        <InputError :message="formErrors?.prix_participant?.[0]" />
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">
                                            {{ form.type_tournoi === 'equipe' ? t('events.numParticipantsInTeam') : t('events.participantSeats') }} *
                                        </label>
                                        <Input v-model.number="form.capacite_participant" type="number" min="0" />
                                        <InputError :message="formErrors?.capacite_participant?.[0]" />
                                    </div>

                                    <div v-if="form.type_tournoi === 'equipe'" class="space-y-2">
                                        <label class="text-sm font-medium">{{ t('events.equipeNumber') }} *</label>
                                        <Input v-model.number="form.nombre_equipes" type="number" />
                                        <InputError :message="formErrors?.nombre_equipes?.[0]" />
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Co-Organizers Section -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-2 text-blue-600 mb-2">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold">5</div>
                                <CardTitle>{{ t('events.coOrganizersOptional') }}</CardTitle>
                            </div>
                            <CardDescription>{{ t('events.coOrganizersDesc') }}</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-3">
                                <label class="text-sm font-medium">{{ t('events.searchByNameEmail') }}</label>
                                <div class="relative">
                                    <Input 
                                        v-model="searchQuery" 
                                        :placeholder="t('events.searchOrganizersPlaceholder')" 
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
                                
                                <div v-if="selectedCollaborators.length > 0" class="pt-2 space-y-2">
                                    <h5 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{{ t('events.selectedCoOrganizers') }}:</h5>
                                    <div class="flex flex-wrap gap-2">
                                        <div v-for="(collab, idx) in selectedCollaborators" :key="collab.id" class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/30 rounded-full text-sm">
                                            <span>{{ collab.username }}</span>
                                            <button @click="removeOrganizer(idx)" class="hover:text-red-500 transition-colors">
                                                <X class="w-3 h-3" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <div class="flex justify-end gap-3 pt-4">
                        <Link href="/dashboard/events">
                            <Button variant="ghost">{{ t('common.cancel') }}</Button>
                        </Link>
                        <Button @click="submit" :disabled="processing || isMissingStripe" class="bg-blue-600 hover:bg-blue-700 min-w-[150px]">
                            {{ processing ? t('events.publishing') : t('events.publishEvent') }}
                        </Button>
                    </div>
                </div>

                <!-- Guidance Info Section & Map -->
                <div class="space-y-6">
                    <Card class="bg-blue-600 text-white shadow-lg overflow-hidden relative">
                        <div class="absolute top-0 right-0 p-4 opacity-10">
                            <Sparkles class="w-16 h-16" />
                        </div>
                        <CardHeader>
                            <CardTitle class="text-lg">{{ t('events.tipsForSuccess') }}</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 text-sm relative z-10">
                            <div class="flex gap-3">
                                <Info class="w-5 h-5 flex-shrink-0" />
                                <p>{{ t('events.tipTitle') }}</p>
                            </div>
                            <div class="flex gap-3">
                                <Info class="w-5 h-5 flex-shrink-0" />
                                <p>{{ t('events.tipDescription') }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Map Card -->
                    <Card class="overflow-hidden border-blue-100 shadow-md">
                        <CardHeader class="pb-3 bg-slate-50/50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-blue-600">
                                    <MapPin class="w-4 h-4" />
                                    <CardTitle class="text-sm font-semibold">{{ t('events.eventLocation') }}</CardTitle>
                                </div>
                                <Button 
                                    variant="outline" 
                                    size="sm" 
                                    class="h-8 gap-1.5 text-xs bg-white"
                                    @click="pingLocation"
                                    :disabled="isSearchingLocation"
                                >
                                    <Sparkles class="w-3.5 h-3.5" :class="{ 'animate-spin': isSearchingLocation }" />
                                    {{ isSearchingLocation ? t('events.locating') : t('events.pingLocation') }}
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div ref="mapContainer" class="h-[300px] w-full z-0"></div>
                            <div class="p-3 bg-blue-50/30 border-t border-blue-50">
                                <p class="text-[11px] text-blue-700/70 flex items-center gap-1.5">
                                    <Info class="w-3 h-3" />
                                    {{ t('events.mapTip') }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>