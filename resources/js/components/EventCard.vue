<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { 
    Calendar, 
    MapPin, 
    Trophy, 
    Eye,
    Copy,
    Check
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { computed, ref } from 'vue';
import { useAuthModal } from '@/composables/useAuthModal';
import type { Evenement, StatutEvenement } from '@/types/event';

interface Props {
    event: Evenement;
    showAction?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
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

const isReseller = computed(() => auth.value.user?.role === 'revendeur');
const copied = ref(false);

const handleAction = (event: Evenement) => {
    if (!auth.value.user) {
        openLogin();
        return;
    }
    
    if (isReseller.value) {
        copyReferralLink();
        return;
    }

    emit('book', event);
};

const copyReferralLink = () => {
    const user = auth.value.user;
    if (!user) return;
    
    const referralCode = user.revendeur?.referral_code;
    
    if (!referralCode) {
        console.error('Referral code missing for reseller:', user);
        return;
    }

    const link = `${window.location.origin}/events/${props.event.id}?ref=${referralCode}`;
    
    // Attempt to copy using the clipboard API
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(link).then(() => {
            copied.value = true;
            setTimeout(() => {
                copied.value = false;
            }, 2000);
        }).catch(err => {
            console.error('Clipboard API copy failed:', err);
            fallbackCopy(link);
        });
    } else {
        fallbackCopy(link);
    }
};

const fallbackCopy = (text: string) => {
    try {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        
        // Ensure textarea is not visible but part of the DOM
        textArea.style.position = "fixed";
        textArea.style.left = "-9999px";
        textArea.style.top = "0";
        
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        const successful = document.execCommand('copy');
        document.body.removeChild(textArea);
        
        if (successful) {
            copied.value = true;
            setTimeout(() => {
                copied.value = false;
            }, 2000);
        } else {
            throw new Error('execCommand copy returned false');
        }
    } catch (err) {
        console.error('Fallback copy failed:', err);
    }
};

const bannerImage = computed(() => {
    const imageMedia = props.event.medias?.find((m: any) => m.type === 'image');
    return imageMedia?.url || 'https://picsum.photos/seed/fallback/800/600';
});
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
            :style="{ backgroundImage: `url(${bannerImage})` }"
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
                        {{ event.capacite_spectateur - (Number(event.total_tickets_reserved) || 0) }} seats left
                    </span>
                </div>
                
                <Button 
                    v-if="showAction" 
                    @click="handleAction(event)" 
                    size="sm" 
                    :class="[
                        'font-semibold px-4 rounded-lg transition-all active:scale-95 shadow-sm',
                        isReseller 
                            ? (copied ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-amber-600 hover:bg-amber-700 text-white') 
                            : 'bg-blue-600 hover:bg-blue-700 text-white'
                    ]"
                >
                    <template v-if="isReseller">
                        <div class="flex items-center gap-2">
                            <component :is="copied ? Check : Copy" class="w-4 h-4" />
                            {{ copied ? 'Copied!' : 'Copy Link' }}
                        </div>
                    </template>
                    <template v-else>
                        {{ auth.user ? 'Book Now' : 'Login to Book' }}
                    </template>
                </Button>
            </div>
        </div>
    </div>
</template>
