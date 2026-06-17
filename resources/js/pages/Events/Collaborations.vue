<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';

import axios from 'axios';
import { toast } from 'vue-sonner';

import {
    Search,
    Pencil,
    Calendar,
    MapPin,
    Trophy,
    Eye,
    Users
} from 'lucide-vue-next';
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import EventManageModal from '@/components/organizer/EventManageModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Evenement, StatutEvenement } from '@/types/event';

const { t } = useI18n();

const breadcrumbs = [
    { title: t('events.dashboardNav'), href: '/dashboard' },
    { title: t('events.collaborations'), href: '/dashboard/collaborations' },
];

const events = ref<Evenement[]>([]);
const loading = ref(true);

const page = usePage();
const auth = computed(() => page.props.auth as any);

// Management Modal State
const selectedEventId = ref<number | null>(null);
const showManageModal = ref(false);

const openManageModal = (id: number) => {
    selectedEventId.value = id;
    showManageModal.value = true;
};

const fetchCollaborations = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/web-api/my-collaborations');
        events.value = response.data.data;
    } catch (error) {
        console.error('Error fetching collaborations:', error);
        toast.error(t('events.failedToLoadCollaborations'));
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchCollaborations();
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
    return t(`events.status_${statut}`);
};

</script>

<template>
    <Head :title="t('events.myCollaborations')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ t('events.myCollaborations') }}</h1>
                    <p class="text-muted-foreground">{{ t('events.myCollaborationsDesc') }}</p>
                </div>
            </div>

            <!-- Events List -->
            <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Skeleton Loader -->
                <div v-for="n in 6" :key="n" class="h-64 rounded-xl bg-muted animate-pulse border" />
            </div>

            <div v-else-if="events.length === 0" class="text-center py-12 bg-muted/20 rounded-xl border border-dashed">
                <div class="mx-auto w-12 h-12 rounded-full bg-muted flex items-center justify-center mb-4">
                    <Users class="w-6 h-6 text-muted-foreground" />
                </div>
                <h3 class="text-lg font-medium">{{ t('events.noCollaborationsYet') }}</h3>
                <p class="text-muted-foreground">{{ t('events.noCollaborationsDesc') }}</p>
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
                                <Badge :variant="getStatusVariant(event.statut)" class="capitalize shadow-sm w-fit">
                                    {{ getStatusLabel(event.statut) }}
                                </Badge>
                                <Badge v-if="event.is_tournoi" variant="default" class="bg-amber-500 hover:bg-amber-600 shadow-sm w-fit border-0">
                                    <Trophy class="w-3 h-3 mr-1" /> {{ t('events.tournament') }}
                                </Badge>
                                <Badge variant="secondary" class="shadow-sm w-fit">
                                    <Users class="w-3 h-3 mr-1" /> {{ t('events.coOrganizer') }}
                                </Badge>
                            </div>
                            <div class="absolute bottom-4 right-4 flex gap-2">
                                <Button 
                                    size="icon" 
                                    variant="secondary" 
                                    class="group/btn h-8 w-8 rounded-full shadow-md hover:bg-black/20 backdrop-blur-sm bg-white/80 transition-all hover:scale-110"
                                    @click="openManageModal(event.id)"
                                >
                                    <Eye class="w-4 h-4 text-black group-hover/btn:text-white transition-colors" />
                                </Button>
                            </div>
                        </div>

                        <div class="p-5 space-y-4 relative bg-card">
                            <div class="space-y-1">
                                <h2 class="text-xl font-bold line-clamp-1 capitalize">{{ event.titre }}</h2>
                                <Badge variant="outline" class="text-[10px] uppercase font-bold tracking-wider text-muted-foreground">
                                    {{ event.categorie === 'autre' && event.categorie_autre ? event.categorie_autre : event.categorie }}
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
                                        {{ event.prix_spectateur > 0 ? `${event.prix_spectateur} TND` : t('common.free') }}
                                    </div>
                                    <div class="text-muted-foreground text-xs flex items-center gap-1">
                                        <span>{{ t('events.hostedByLabel') }}:</span>
                                        <span class="font-medium text-foreground">{{ event.organisateur?.username || t('events.unknown') }}</span>
                                    </div>
                                </div>
                                
                                <Link :href="`/events/${event.id}`" class="block w-full">
                                    <Button variant="outline" class="w-full border-zinc-200 hover:bg-zinc-50 font-bold text-xs uppercase tracking-widest transition-all duration-300 group-hover:bg-zinc-900 group-hover:text-white group-hover:border-zinc-900">
                                        {{ t('events.viewPublicPage') }}
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </div>
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
