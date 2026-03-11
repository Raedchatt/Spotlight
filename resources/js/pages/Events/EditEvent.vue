<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
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
    Film
} from 'lucide-vue-next';
import { ref, onMounted } from 'vue';

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
    statut: '',
    is_tournoi: false,
    type_tournoi: '',
    prix_participant: 0,
    capacite_participant: 0,
    medias: [] as File[],
    poster_url: ''
});

const existingMedias = ref<any[]>([]);
const mediaToDelete = ref<number[]>([]);

const errors = ref<Record<string, string[]>>({});
const processing = ref(false);
const fetching = ref(true);

const fetchEvent = async () => {
    try {
        const response = await axios.get(`/web-api/events/${props.id}`); // Note: Need a show endpoint in controller or use search with id
        const event = response.data;
        
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
            statut: event.statut,
            is_tournoi: event.is_tournoi ?? false,
            type_tournoi: event.type_tournoi ?? '',
            prix_participant: event.prix_participant ?? 0,
            capacite_participant: event.capacite_participant ?? 0,
            medias: [] as File[],
            poster_url: event.poster_url ?? ''
        };

        existingMedias.value = event.medias || [];
    } catch (error) {
        console.error('Error fetching event:', error);
    } finally {
        fetching.value = false;
    }
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
        const formData = new FormData();
        // Spoof PUT request for Laravel to process FormData
        formData.append('_method', 'PUT');

        Object.entries(form.value).forEach(([key, value]) => {
            if (key === 'medias') {
                (value as File[]).forEach(file => {
                    formData.append('medias[]', file);
                });
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
            alert(error.response?.data?.message || 'An unexpected error occurred. Please try again.');
        }
    } finally {
        processing.value = false;
    }
};

onMounted(() => {
    fetchEvent();
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
                <div class="flex gap-3">
                    <Button variant="outline" as-child>
                        <Link href="/dashboard/events">Cancel</Link>
                    </Button>
                    <Button @click="submit" :disabled="processing || fetching" class="bg-blue-600 hover:bg-blue-700">
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
                                        <option value="sportifs">Sportifs</option>
                                        <option value="culturels">Culturels</option>
                                        <option value="scientifiques">Scientifiques</option>
                                        <option value="musicaux">Musicaux</option>
                                        <option value="commerciaux">Commerciaux</option>
                                    </select>
                                    <InputError :message="errors.categorie?.[0]" />
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="text-sm font-medium">Status *</label>
                                    <select v-model="form.statut" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                        <option value="ouvert">Ouvert</option>
                                        <option value="ferme">Fermé</option>
                                        <option value="encours">En Cours</option>
                                        <option value="en_attente">En Attente</option>
                                        <option value="annule">Annulé</option>
                                    </select>
                                    <InputError :message="errors.statut?.[0]" />
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
                                    <Input v-model="form.lieu" class="pl-10" />
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
                                <Input v-model="form.date_fin" type="datetime-local" />
                                <InputError :message="errors.date_fin?.[0]" />
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
                                    <Input v-model.number="form.capacite_spectateur" type="number" class="pl-10" />
                                </div>
                                <InputError :message="errors.capacite_spectateur?.[0]" />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Ticket Price (TND) *</label>
                                <div class="relative">
                                    <CircleDollarSign class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                    <Input v-model.number="form.prix_spectateur" type="number" step="0.01" class="pl-10" />
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
                                            <Input v-model.number="form.prix_participant" type="number" step="0.01" class="pl-10" />
                                        </div>
                                        <InputError :message="errors.prix_participant?.[0]" />
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium">Participant Seats *</label>
                                        <div class="relative">
                                            <Users class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                            <Input v-model.number="form.capacite_participant" type="number" class="pl-10" />
                                        </div>
                                        <InputError :message="errors.capacite_participant?.[0]" />
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-6">
                    <Card class="bg-blue-50 border-blue-200">
                        <CardHeader>
                            <CardTitle class="text-sm text-blue-800">Note</CardTitle>
                        </CardHeader>
                        <CardContent class="text-xs text-blue-700 leading-relaxed">
                            Changing the event status to 'Annulé' will notify all registered participants automatically. 
                            If you change the dates, please double check the venue availability.
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
