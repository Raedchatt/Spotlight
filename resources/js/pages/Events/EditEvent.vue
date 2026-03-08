<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { 
    ChevronLeft, 
    Save,  
    MapPin, 
    CircleDollarSign,
    Users,
    Loader2
} from 'lucide-vue-next';
import { ref, onMounted } from 'vue';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
    medias: [] as File[]
});

const errors = ref<Record<string, string[]>>({});
const processing = ref(false);
const fetching = ref(true);

const fetchEvent = async () => {
    try {
        const response = await axios.get(`/api/events/${props.id}`); // Note: Need a show endpoint in controller or use search with id
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
            medias: [] as File[] // Keep empty initially, as we are appending new medias
        };
    } catch (error) {
        console.error('Error fetching event:', error);
    } finally {
        fetching.value = false;
    }
};

const handleFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files) {
        form.value.medias = Array.from(input.files);
    }
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

        await axios.post(`/api/events/${props.id}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        router.visit('/dashboard/events');
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            console.error('An unexpected error occurred:', error);
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
                            <CardTitle>Media Uploads</CardTitle>
                            <CardDescription>Upload additional images or videos. (Existing media not shown here yet)</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
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
                                
                                <div v-if="form.medias.length > 0" class="flex flex-col space-y-2 mt-4">
                                    <h4 class="text-sm font-medium">Selected files to add:</h4>
                                    <ul class="text-sm text-muted-foreground list-disc list-inside">
                                        <li v-for="file in form.medias" :key="file.name">{{ file.name }} ({{ (file.size / 1024 / 1024).toFixed(2) }} MB)</li>
                                    </ul>
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
