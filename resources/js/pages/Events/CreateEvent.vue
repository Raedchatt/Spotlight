<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import { 
    ChevronLeft, 
    Save, 
    Calendar, 
    MapPin, 
    Info, 
    CircleDollarSign,
    Users
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Events', href: '/dashboard/events' },
    { title: 'Create Event', href: '/dashboard/events/create' },
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
    is_tournoi: false,
    type_tournoi: '',
    prix_participant: 0,
    capacite_participant: 0,
    medias: [] as File[]
});

const errors = ref<Record<string, string[]>>({});
const processing = ref(false);

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
        Object.entries(form.value).forEach(([key, value]) => {
            if (key === 'medias') {
                (value as File[]).forEach(file => {
                    formData.append('medias[]', file);
                });
            } else if (value !== null && value !== '') {
                // For boolean values, send 1 or 0 so Laravel handles it correctly
                if (typeof value === 'boolean') {
                    formData.append(key, value ? '1' : '0');
                } else {
                    formData.append(key, String(value));
                }
            }
        });

        await axios.post('/api/events', formData, {
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
</script>

<template>
    <Head title="Create Event" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto p-6 space-y-8">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <Link href="/dashboard/events" class="flex items-center text-sm text-muted-foreground hover:text-blue-600 transition-colors mb-2">
                        <ChevronLeft class="w-4 h-4 mr-1" />
                        Back to events
                    </Link>
                    <h1 class="text-3xl font-bold tracking-tight">Host an Event</h1>
                    <p class="text-muted-foreground">Fill in the details below to publish your event to the platform.</p>
                </div>
                <Button @click="submit" :disabled="processing" class="bg-blue-600 hover:bg-blue-700">
                    <Save class="w-4 h-4 mr-2" />
                    {{ processing ? 'Publishing...' : 'Publish Event' }}
                </Button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Main Form Section -->
                <div class="md:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-2 text-blue-600 mb-2">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold">1</div>
                                <CardTitle>Basic Information</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Event Title *</label>
                                <Input v-model="form.titre" placeholder="e.g. Summer Beats Festival 2026" />
                                <InputError :message="errors.titre?.[0]" />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Description *</label>
                                <textarea v-model="form.description" placeholder="Describe what makes your event special..." class="flex min-h-[150px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"></textarea>
                                <InputError :message="errors.description?.[0]" />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Category *</label>
                                <select v-model="form.categorie" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                    <option value="" disabled>Select a category</option>
                                    <option value="sportifs">Sportifs</option>
                                    <option value="culturels">Culturels</option>
                                    <option value="scientifiques">Scientifiques</option>
                                    <option value="musicaux">Musicaux</option>
                                    <option value="commerciaux">Commerciaux</option>
                                </select>
                                <InputError :message="errors.categorie?.[0]" />
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-2 text-blue-600 mb-2">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold">2</div>
                                <CardTitle>Location & Time</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2 space-y-2">
                                <label class="text-sm font-medium">Venue / Location *</label>
                                <div class="relative">
                                    <MapPin class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                    <Input v-model="form.lieu" placeholder="Online or Physical Address" class="pl-10" />
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
                            <div class="flex items-center gap-2 text-blue-600 mb-2">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold">3</div>
                                <CardTitle>Media Uploads</CardTitle>
                            </div>
                            <CardDescription>Upload up to 5 images or videos for your event.</CardDescription>
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
                                    <h4 class="text-sm font-medium">Selected files:</h4>
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
                            <div class="flex items-center gap-2 text-blue-600 mb-2">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold">4</div>
                                <CardTitle>Tickets & Capacity</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Maximum Seats *</label>
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
                    
                    <div class="flex justify-end gap-3 pt-4">
                         <Link href="/dashboard/events">
                            <Button variant="ghost">Cancel and go back</Button>
                        </Link>
                        <Button @click="submit" :disabled="processing" class="bg-blue-600 hover:bg-blue-700 min-w-[150px]">
                            {{ processing ? 'Publishing...' : 'Publish Event' }}
                        </Button>
                    </div>
                </div>

                <!-- Guidance Info Section -->
                <div class="space-y-6">
                    <div class="sticky top-6 space-y-6">
                         <Card class="bg-blue-600 text-white overflow-hidden relative">
                            <CardHeader>
                                <CardTitle class="text-lg">Tips for Success</CardTitle>
                                <CardDescription class="text-blue-100">Make your event stand out with these quick tips.</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4 text-sm">
                                <div class="flex gap-3">
                                    <Info class="w-5 h-5 flex-shrink-0" />
                                    <p>Use a catchy title that clearly states what the event is about.</p>
                                </div>
                                <div class="flex gap-3">
                                    <Info class="w-5 h-5 flex-shrink-0" />
                                    <p>High-quality descriptions attract 40% more attendees.</p>
                                </div>
                                <div class="flex gap-3">
                                    <Info class="w-5 h-5 flex-shrink-0" />
                                    <p>Ensure your end date is after the start date.</p>
                                </div>
                            </CardContent>
                            <!-- Decorative background circle -->
                            <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
                        </Card>

                        <div class="p-4 rounded-xl border border-dashed text-center space-y-2">
                            <p class="text-sm text-muted-foreground font-medium">Need help?</p>
                            <p class="text-xs text-muted-foreground">Check out our documentation for organizers.</p>
                            <Button variant="link" size="sm" class="text-blue-600 h-auto p-0">View Guide</Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
