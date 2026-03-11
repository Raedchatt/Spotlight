<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { 
    Calendar, 
    MapPin, 
    Trophy, 
    Eye 
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { computed } from 'vue';
import { useAuthModal } from '@/composables/useAuthModal';
import type { Evenement, StatutEvenement } from '@/types/event';

interface Props {
    event: Evenement;
    showAction?: boolean;
}

withDefaults(defineProps<Props>(), {
    showAction: true
});

const emit = defineEmits<{
    (e: 'book', event: Evenement): void;
}>();

const getStatusVariant = (statut: StatutEvenement) => {
    switch (statut) {
        case 'ouvert': return 'default'; 
        case 'valide': return 'default';
        case 'encours': return 'outline';
        case 'ferme': return 'secondary';
        case 'annule': return 'destructive';
        default: return 'outline';
    }
};

const getStatusLabel = (statut: StatutEvenement) => {
    return statut.charAt(0).toUpperCase() + statut.slice(1).replace('_', ' ');
};

const page = usePage();
const auth = computed(() => page.props.auth as any);
const { openLogin } = useAuthModal();

const handleAction = (event: Evenement) => {
    if (!auth.value.user) {
        openLogin();
        return;
    }
    emit('book', event);
};
</script>

<template>
    <div 
        class="group bg-card border rounded-xl overflow-hidden hover:shadow-lg transition-all duration-300 relative h-full flex flex-col"
        :class="{ 'ring-2 ring-amber-500 shadow-amber-500/10': event.is_tournoi }"
    >
        <!-- Event Banner Image -->
        <div 
            class="h-40 relative bg-cover bg-center shrink-0"
            :class="!event.medias?.length ? 'bg-gradient-to-br from-blue-500/10 to-purple-500/10' : ''"
            :style="event.medias?.length ? { backgroundImage: `url(${event.medias[0].url})` } : {}"
        >
            <div class="absolute top-4 left-4 flex flex-col gap-2">
                <Badge :variant="getStatusVariant(event.statut)" class="capitalize shadow-sm w-fit bg-blue-600 hover:bg-blue-700 border-0 text-white">
                    {{ getStatusLabel(event.statut) }}
                </Badge>
                <Badge v-if="event.is_tournoi" variant="default" class="bg-amber-500 hover:bg-amber-600 shadow-sm w-fit border-0 font-bold">
                    <Trophy class="w-3 h-3 mr-1" /> TOURNAMENT
                </Badge>
            </div>
            <div class="absolute bottom-4 right-4 animate-in fade-in zoom-in duration-300">
                 <Link :href="`/events/${event.id}`">
                    <Button size="icon" variant="secondary" class="group/btn h-8 w-8 rounded-full shadow-md hover:bg-accent/20 backdrop-blur-sm bg-background/80 transition-all active:scale-90">
                        <Eye class="w-4 h-4 text-foreground transition-colors" />
                    </Button>
                 </Link>
            </div>
        </div>

        <div class="p-5 space-y-4 flex-grow flex flex-col">
            <div class="space-y-1">
                <h2 class="text-xl font-bold line-clamp-1 capitalize">{{ event.titre }}</h2>
                <Badge variant="outline" class="text-[10px] uppercase font-bold tracking-wider text-muted-foreground">
                    {{ event.categorie }}
                </Badge>
            </div>

            <div class="space-y-3 text-sm text-muted-foreground flex-grow">
                <div class="flex items-center gap-2">
                    <Calendar class="w-4 h-4" />
                    <span>{{ new Date(event.date_debut).toLocaleDateString() }} - {{ new Date(event.date_debut).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <MapPin class="w-4 h-4" />
                    <span class="line-clamp-1">{{ event.lieu }}</span>
                </div>
            </div>

            <div class="pt-4 border-t flex justify-between items-center shrink-0">
                <div class="flex flex-col">
                    <span class="font-bold text-blue-600 text-lg">
                        <template v-if="event.is_tournoi">
                            {{ event.prix_spectateur > 0 ? `${event.prix_spectateur} TND` : 'Free' }}
                            <span class="text-[9px] text-muted-foreground ml-1">(Spectator)</span>
                        </template>
                        <template v-else>
                            {{ event.prix_spectateur > 0 ? `${event.prix_spectateur} TND` : 'Free' }}
                        </template>
                    </span>
                    <span class="text-[10px] text-muted-foreground">
                        {{ event.capacite_spectateur - (event.reservations_count || 0) }} seats left
                    </span>
                </div>
                
                <Button v-if="showAction" @click="handleAction(event)" size="sm" class="bg-blue-600 hover:bg-blue-700 font-semibold px-4 rounded-lg transition-all active:scale-95 shadow-sm">
                    {{ auth.user ? 'Book Now' : 'Login to Book' }}
                </Button>
            </div>
        </div>
    </div>
</template>
