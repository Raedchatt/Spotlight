<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { 
    Calendar, 
    MapPin, 
    Clock, 
    Users, 
    Trophy, 
    Ticket, 
    ChevronRight, 
    Star,
    Share2,
    CalendarDays,
    Gamepad2,
    Eye,
    Edit
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import AppFooter from '@/components/AppFooter.vue';
import { useAuthModal } from '@/composables/useAuthModal';
interface EventMedia {
    id: number;
    url: string;
    type: string;
}

interface SimilarEvent {
    id: number;
    titre: string;
    lieu: string;
    date_debut: string;
    categorie: string;
    prix_spectateur: number;
    medias?: EventMedia[];
}

interface Props {
    event: {
        id: number;
        titre: string;
        description: string;
        lieu: string;
        date_debut: string;
        date_fin: string;
        prix_spectateur: number;
        capacite_spectateur: number;
        is_tournoi: boolean;
        type_tournoi?: string;
        prix_participant?: number;
        capacite_participant?: number;
        categorie: string;
        organisateur: {
            id: number;
            name: string;
        };
        medias: EventMedia[];
    };
    stats: {
        total_reserved?: number;
        remaining?: number;
        participant_reserved?: number;
        spectator_reserved?: number;
        participant_remaining?: number;
        spectator_remaining?: number;
    };
    is_reserved: boolean;
    similar_events: SimilarEvent[] | null;
}

const props = defineProps<Props>();
const page = usePage();
const auth = computed(() => page.props.auth as any);

const isOwner = computed(() => {
    return auth.value?.user?.id === props.event.organisateur.id;
});

const selectedTicketType = ref(props.event.is_tournoi ? 'participant' : 'standard');

const getInitials = (name: string) => {
    return name?.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) || '??';
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};

const formatTime = (dateString: string) => {
    return new Date(dateString).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const coverImage = computed(() => {
    const img = props.event.medias.find(m => m.type === 'image');
    return img ? img.url : null;
});

const progressPercentage = (reserved: number, total: number) => {
    if (total === 0) return 0;
    return Math.min(100, (reserved / total) * 100);
};

const mapUrl = computed(() => {
    return `https://maps.google.com/maps?q=${encodeURIComponent(props.event.lieu)}&output=embed`;
});

const isFullyBooked = computed(() => {
    if (!props.event.is_tournoi) {
        return (props.stats.remaining ?? 0) <= 0;
    } else {
        if (selectedTicketType.value === 'participant') {
            return (props.stats.participant_remaining ?? 0) <= 0;
        } else {
            return (props.stats.spectator_remaining ?? 0) <= 0;
        }
    }
});

const reserveButtonText = computed(() => {
    if (!auth.value.user) return 'Login to Reserve';
    
    if (props.is_reserved) return 'Already Reserved';
    
    if (!props.event.is_tournoi) {
        if (isFullyBooked.value) return 'Fully Booked';
        return 'Reserve Now';
    } else {
        if (selectedTicketType.value === 'participant') {
            if ((props.stats.participant_remaining ?? 0) <= 0) return 'Participants Full';
            return 'Reserve as Participant';
        } else {
            if ((props.stats.spectator_remaining ?? 0) <= 0) return 'Spectators Full';
            return 'Reserve as Spectator';
        }
    }
});

const { openLogin } = useAuthModal();

const handleReserve = () => {
    if (!auth.value.user) {
        openLogin();
        return;
    }
    // Reservation logic would go here
    console.log('Reserving:', selectedTicketType.value);
};
</script>

<template>
    <AppLayout>
        <Head :title="props.event.titre" />

        <div class="min-h-screen bg-zinc-50 pb-12">
            <!-- 1. HERO SECTION -->
            <div class="relative h-72 md:h-96 w-full overflow-hidden bg-zinc-900">
                <img 
                    v-if="coverImage" 
                    :src="coverImage" 
                    class="w-full h-full object-cover opacity-60"
                />
                <div v-else class="w-full h-full bg-gradient-to-br from-zinc-700 to-zinc-900 flex items-center justify-center">
                    <Calendar class="w-20 h-20 text-zinc-600" />
                </div>

                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                
                <div class="absolute bottom-0 left-0 w-full p-6 md:p-12 max-w-7xl mx-auto right-0">
                    <div class="space-y-4">
                        <span class="inline-flex items-center gap-1.5 bg-white/20 backdrop-blur-md text-white border border-white/30 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                            <component :is="props.event.is_tournoi ? Trophy : Star" class="w-3.5 h-3.5" />
                            {{ props.event.is_tournoi ? 'Tournament' : props.event.categorie }}
                        </span>
                        
                        <h1 class="text-3xl md:text-5xl font-bold text-white max-w-4xl leading-tight">
                            {{ props.event.titre }}
                        </h1>

                        <div class="flex flex-wrap items-center gap-6 text-white/90 text-sm md:text-base">
                            <div class="flex items-center gap-2">
                                <MapPin class="w-5 h-5 text-blue-400" />
                                {{ props.event.lieu }}
                            </div>
                            <div class="flex items-center gap-2">
                                <CalendarDays class="w-5 h-5 text-blue-400" />
                                {{ formatDate(props.event.date_debut) }}
                            </div>
                        </div>
                    </div>

                    <!-- Owner Action Controls -->
                    <div v-if="isOwner" class="absolute top-6 right-6 md:right-12 flex gap-3">
                        <Link :href="`/dashboard/events/${props.event.id}/edit`">
                            <Button class="bg-white/10 hover:bg-white/20 backdrop-blur-md text-white border border-white/30 font-bold">
                                <Edit class="w-4 h-4 mr-2" />
                                Edit Event
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- 2. MAIN CONTENT -->
            <div class="max-w-7xl mx-auto px-4 md:px-12 -mt-8 relative z-10">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- LEFT COLUMN -->
                    <div class="lg:col-span-2 space-y-8">
                        
                        <!-- Section 1: About -->
                        <div class="bg-white rounded-2xl shadow-sm p-8 border border-zinc-100">
                            <h2 class="text-xl font-bold text-zinc-900 mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-6 bg-blue-600 rounded-full"></span>
                                About this Event
                            </h2>
                            <div class="prose prose-zinc max-w-none text-zinc-600 leading-relaxed whitespace-pre-line">
                                {{ props.event.description }}
                            </div>
                        </div>

                        <!-- Section 2: Date & Time -->
                        <div class="bg-white rounded-2xl shadow-sm p-8 border border-zinc-100">
                            <h2 class="text-xl font-bold text-zinc-900 mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-6 bg-blue-600 rounded-full"></span>
                                Date & Time
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center shrink-0">
                                        <Calendar class="w-5 h-5 text-blue-600" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-zinc-400 uppercase tracking-wider mb-1">Start</div>
                                        <div class="text-zinc-900 font-semibold">{{ formatDate(props.event.date_debut) }}</div>
                                        <div class="text-zinc-500 text-sm">{{ formatTime(props.event.date_debut) }}</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 bg-zinc-50 rounded-xl flex items-center justify-center shrink-0">
                                        <Clock class="w-5 h-5 text-zinc-400" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-zinc-400 uppercase tracking-wider mb-1">End</div>
                                        <div class="text-zinc-900 font-semibold">{{ formatDate(props.event.date_fin) }}</div>
                                        <div class="text-zinc-500 text-sm">{{ formatTime(props.event.date_fin) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Location -->
                        <div class="bg-white rounded-2xl shadow-sm p-8 border border-zinc-100">
                            <h2 class="text-xl font-bold text-zinc-900 mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-6 bg-blue-600 rounded-full"></span>
                                Location
                            </h2>
                            <div class="space-y-6">
                                <div class="flex items-center gap-3 text-zinc-700">
                                    <MapPin class="w-5 h-5 text-zinc-400" />
                                    <span class="font-medium">{{ props.event.lieu }}</span>
                                </div>
                                
                                <div class="aspect-video w-full rounded-xl overflow-hidden grayscale contrast-125 border border-zinc-100 shadow-inner">
                                    <iframe
                                        width="100%"
                                        height="100%"
                                        frameborder="0"
                                        style="border:0"
                                        :src="mapUrl"
                                        allowfullscreen
                                    ></iframe>
                                </div>
                            </div>
                        </div>

                        <!-- Section 4: Capacity -->
                        <div class="bg-white rounded-2xl shadow-sm p-8 border border-zinc-100">
                            <h2 class="text-xl font-bold text-zinc-900 mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-6 bg-blue-600 rounded-full"></span>
                                Capacity
                            </h2>
                            
                            <!-- Normal Event Capacity -->
                            <div v-if="!props.event.is_tournoi" class="space-y-4">
                                <div class="flex justify-between items-end">
                                    <div class="flex items-center gap-2 text-zinc-900 font-bold">
                                        <Users class="w-5 h-5 text-blue-600" />
                                        Attendees
                                    </div>
                                    <div class="text-sm text-zinc-500">
                                        <span class="font-bold text-zinc-900">{{ props.stats.total_reserved }}</span> / {{ props.event.capacite_spectateur }} spots
                                    </div>
                                </div>
                                <div class="w-full bg-zinc-100 rounded-full h-3 overflow-hidden">
                                    <div 
                                        class="bg-blue-600 h-full transition-all duration-1000" 
                                        :style="{ width: progressPercentage(props.stats.total_reserved ?? 0, props.event.capacite_spectateur) + '%' }"
                                    ></div>
                                </div>
                                <div class="flex justify-between text-xs font-bold uppercase tracking-wider text-zinc-400">
                                    <span>{{ props.stats.total_reserved }} registered</span>
                                    <span :class="(props.stats.remaining ?? 0) <= 5 ? 'text-red-500' : ''">{{ props.stats.remaining }} remaining</span>
                                </div>
                            </div>

                            <!-- Tournament Event Capacity -->
                            <div v-else class="space-y-10">
                                <!-- Participants -->
                                <div class="space-y-4">
                                    <div class="flex justify-between items-end">
                                        <div class="flex items-center gap-2 text-zinc-900 font-bold">
                                            <Gamepad2 class="w-5 h-5 text-blue-600" />
                                            Participants
                                        </div>
                                        <div class="text-sm text-zinc-500">
                                            <span class="font-bold text-zinc-900">{{ props.stats.participant_reserved }}</span> / {{ props.event.capacite_participant }} slots
                                        </div>
                                    </div>
                                    <div class="w-full bg-zinc-100 rounded-full h-3 overflow-hidden">
                                        <div 
                                            class="bg-blue-600 h-full transition-all duration-1000" 
                                            :style="{ width: progressPercentage(props.stats.participant_reserved ?? 0, props.event.capacite_participant ?? 1) + '%' }"
                                        ></div>
                                    </div>
                                    <div class="flex justify-between text-xs font-bold uppercase tracking-wider text-zinc-400">
                                        <span>{{ props.stats.participant_reserved }} players</span>
                                        <span :class="(props.stats.participant_remaining ?? 0) <= 3 ? 'text-red-500' : ''">{{ props.stats.participant_remaining }} remaining</span>
                                    </div>
                                </div>

                                <!-- Spectators -->
                                <div class="space-y-4">
                                    <div class="flex justify-between items-end">
                                        <div class="flex items-center gap-2 text-zinc-900 font-bold">
                                            <Eye class="w-5 h-5 text-orange-500" />
                                            Spectators
                                        </div>
                                        <div class="text-sm text-zinc-500">
                                            <span class="font-bold text-zinc-900">{{ props.stats.spectator_reserved }}</span> / {{ props.event.capacite_spectateur }} spots
                                        </div>
                                    </div>
                                    <div class="w-full bg-zinc-100 rounded-full h-3 overflow-hidden">
                                        <div 
                                            class="bg-orange-500 h-full transition-all duration-1000" 
                                            :style="{ width: progressPercentage(props.stats.spectator_reserved ?? 0, props.event.capacite_spectateur) + '%' }"
                                        ></div>
                                    </div>
                                    <div class="flex justify-between text-xs font-bold uppercase tracking-wider text-zinc-400">
                                        <span>{{ props.stats.spectator_reserved }} registered</span>
                                        <span :class="(props.stats.spectator_remaining ?? 0) <= 10 ? 'text-red-500' : ''">{{ props.stats.spectator_remaining }} remaining</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN (Sticky Sidebar) -->
                    <div class="space-y-6">
                        <div class="sticky top-6 space-y-6">
                            
                            <!-- Ticket / Management Card -->
                            <div class="bg-white rounded-2xl shadow-lg border border-zinc-100 overflow-hidden">
                                <div class="p-1 h-2" :class="isOwner ? 'bg-zinc-900' : 'bg-blue-600'"></div>
                                
                                <!-- Owner View: Management Card -->
                                <div v-if="isOwner" class="p-8">
                                    <h3 class="text-lg font-bold text-zinc-900 mb-6">Event Management</h3>
                                    
                                    <div class="space-y-6">
                                        <div class="flex items-center justify-between p-4 bg-zinc-50 rounded-xl border border-zinc-100">
                                            <div class="text-sm font-bold text-zinc-400 uppercase tracking-wider">Status</div>
                                            <Badge variant="outline" class="capitalize font-bold text-blue-600 border-blue-100 bg-blue-50/50">
                                                Active
                                            </Badge>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="p-4 bg-zinc-50 rounded-xl border border-zinc-100">
                                                <div class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Total Revenue</div>
                                                <div class="text-lg font-black text-zinc-900">
                                                    {{ ((props.stats.spectator_reserved ?? 0) * props.event.prix_spectateur) + ((props.stats.participant_reserved ?? 0) * (props.event.prix_participant ?? 0)) }} TND
                                                </div>
                                            </div>
                                            <div class="p-4 bg-zinc-50 rounded-xl border border-zinc-100">
                                                <div class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Reservations</div>
                                                <div class="text-lg font-black text-zinc-900">
                                                    {{ (props.stats.total_reserved ?? 0) + (props.stats.participant_reserved ?? 0) + (props.stats.spectator_reserved ?? 0) }}
                                                </div>
                                            </div>
                                        </div>

                                        <Link :href="`/dashboard/events/${props.event.id}/edit`" class="block w-full">
                                            <Button class="w-full py-6 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-white font-bold flex items-center justify-center gap-2">
                                                <Edit class="w-4 h-4" />
                                                Edit Specifications
                                            </Button>
                                        </Link>

                                        <p class="text-[10px] text-center text-zinc-400 font-bold uppercase tracking-widest">
                                            Last sync: Just now
                                        </p>
                                    </div>
                                </div>

                                <!-- Participant View: Ticket Card -->
                                <div v-else class="p-8">
                                    <h3 v-if="props.event.is_tournoi" class="text-lg font-bold text-zinc-900 mb-6">Choose your ticket</h3>
                                    
                                    <!-- Normal Event Pricing -->
                                    <div v-if="!props.event.is_tournoi" class="mb-8">
                                        <div class="text-3xl font-black text-zinc-900">{{ props.event.prix_spectateur }} TND <span class="text-sm font-semibold text-zinc-400">/ ticket</span></div>
                                        <div class="mt-2 flex items-center gap-2 text-sm text-zinc-500 font-medium">
                                            <Users class="w-4 h-4" />
                                            {{ props.stats.remaining }} spots remaining
                                        </div>
                                    </div>

                                    <!-- Tournament Ticket Selection -->
                                    <div v-else class="space-y-4 mb-8">
                                        <!-- Participant Option -->
                                        <label 
                                            :class="[
                                                'relative flex flex-col p-4 rounded-xl border-2 transition-all cursor-pointer group',
                                                selectedTicketType === 'participant' ? 'border-blue-600 bg-blue-50/30' : 'border-zinc-100 hover:border-zinc-200'
                                            ]"
                                        >
                                            <input type="radio" v-model="selectedTicketType" value="participant" class="sr-only" />
                                            <div class="flex justify-between items-start mb-1">
                                                <div class="flex items-center gap-2 font-bold text-zinc-900">
                                                    <div :class="['w-4 h-4 rounded-full border-2 flex items-center justify-center transition-colors', selectedTicketType === 'participant' ? 'border-blue-600 bg-blue-600' : 'border-zinc-300']">
                                                        <div class="w-1.5 h-1.5 rounded-full bg-white" v-if="selectedTicketType === 'participant'"></div>
                                                    </div>
                                                    🎮 Participant
                                                </div>
                                                <span class="font-bold text-blue-600">{{ props.event.prix_participant }} TND</span>
                                            </div>
                                            <p class="text-xs text-zinc-500 ml-6">Play in the tournament</p>
                                            <div class="text-[10px] uppercase tracking-wider font-bold mt-2 ml-6 text-zinc-400 group-hover:text-zinc-600">
                                                {{ props.stats.participant_remaining }} slots left
                                            </div>
                                        </label>

                                        <!-- Spectator Option -->
                                        <label 
                                            :class="[
                                                'relative flex flex-col p-4 rounded-xl border-2 transition-all cursor-pointer group',
                                                selectedTicketType === 'spectator' ? 'border-blue-600 bg-blue-50/30' : 'border-zinc-100 hover:border-zinc-200'
                                            ]"
                                        >
                                            <input type="radio" v-model="selectedTicketType" value="spectator" class="sr-only" />
                                            <div class="flex justify-between items-start mb-1">
                                                <div class="flex items-center gap-2 font-bold text-zinc-900">
                                                    <div :class="['w-4 h-4 rounded-full border-2 flex items-center justify-center transition-colors', selectedTicketType === 'spectator' ? 'border-blue-600 bg-blue-600' : 'border-zinc-300']">
                                                        <div class="w-1.5 h-1.5 rounded-full bg-white" v-if="selectedTicketType === 'spectator'"></div>
                                                    </div>
                                                    👁️ Spectator
                                                </div>
                                                <span class="font-bold text-zinc-900">{{ props.event.prix_spectateur }} TND</span>
                                            </div>
                                            <p class="text-xs text-zinc-500 ml-6">Watch the tournament</p>
                                            <div class="text-[10px] uppercase tracking-wider font-bold mt-2 ml-6 text-zinc-400 group-hover:text-zinc-600">
                                                {{ props.stats.spectator_remaining }} spots left
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Action Button -->
                                    <button 
                                        @click="handleReserve"
                                        :disabled="props.is_reserved || isFullyBooked"
                                        :class="[
                                            'w-full py-4 rounded-xl font-bold transition-all active:scale-95 flex items-center justify-center gap-2 shadow-lg mb-4',
                                            (props.is_reserved || isFullyBooked) 
                                                ? 'bg-zinc-200 text-zinc-500 cursor-not-allowed shadow-none' 
                                                : 'bg-black hover:bg-zinc-800 text-white shadow-zinc-800/10'
                                        ]"
                                    >
                                        <Ticket class="w-5 h-5" v-if="!isFullyBooked && !props.is_reserved" />
                                        {{ reserveButtonText }}
                                    </button>

                                    <div class="flex items-center justify-center gap-4 text-xs font-semibold text-zinc-400 uppercase tracking-widest">
                                        <span class="flex items-center gap-1"><Share2 class="w-3.5 h-3.5" /> Share</span>
                                        <span class="w-1 h-1 bg-zinc-200 rounded-full"></span>
                                        <span>Report Event</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Organizer Card -->
                            <div class="bg-white rounded-2xl shadow-sm border border-zinc-100 p-6 flex flex-col items-center text-center">
                                <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-zinc-100 to-zinc-200 flex items-center justify-center font-bold text-xl text-zinc-500 mb-4 border border-white shadow-sm">
                                    {{ getInitials(props.event.organisateur.name) }}
                                </div>
                                <h4 class="font-bold text-zinc-900 text-lg">
                                    <template v-if="isOwner">
                                        You <span class="text-zinc-400 font-normal text-sm">(Owner)</span>
                                    </template>
                                    <Link 
                                        v-else 
                                        :href="`/organizer/${props.event.organisateur.id}`"
                                        class="hover:text-blue-600 transition-colors duration-200"
                                    >
                                        {{ props.event.organisateur.name }}
                                    </Link>
                                </h4>
                                <span class="text-xs font-bold text-blue-600 uppercase tracking-widest mt-1 mb-6">Organisateur</span>
                                
                                <Link 
                                    v-if="!isOwner"
                                    :href="`/organizer/${props.event.organisateur.id}`"
                                    class="w-full py-2.5 rounded-lg border-2 border-zinc-900 text-zinc-900 font-bold hover:bg-zinc-900 hover:text-white transition-all text-sm"
                                >
                                    View Profile
                                </Link>
                                <div v-else class="w-full text-zinc-400 text-xs font-medium">
                                    You are managing this event
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

            <!-- 3. BOTTOM SECTION: Similar Events -->
            <div v-if="props.similar_events && props.similar_events.length > 0" class="max-w-7xl mx-auto px-4 md:px-12 mt-20">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-2xl font-bold text-zinc-900">Similar Events</h2>
                    <Link href="/dashboard/events" class="text-sm font-bold text-zinc-400 hover:text-zinc-900 flex items-center gap-1 transition-colors group">
                        See all <ChevronRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <Link 
                        v-for="sim in props.similar_events" 
                        :key="sim.id"
                        :href="`/events/${sim.id}`"
                        class="bg-white rounded-2xl shadow-sm overflow-hidden group border border-transparent hover:border-zinc-200 transition-all duration-300"
                    >
                        <div class="relative h-44 bg-zinc-100 overflow-hidden">
                            <img 
                                v-if="sim.medias?.[0]" 
                                :src="sim.medias[0].url" 
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <Calendar class="w-10 h-10 text-zinc-300" />
                            </div>
                            <div class="absolute top-4 left-4">
                                <span class="bg-white/90 backdrop-blur-md text-zinc-900 text-[10px] font-bold px-2 py-1 rounded-lg uppercase tracking-wider shadow-sm">
                                    {{ sim.categorie }}
                                </span>
                            </div>
                        </div>
                        <div class="p-6 flex-1 flex flex-col justify-between">
                            <div class="space-y-4">
                                <h3 class="font-bold text-lg text-zinc-900 line-clamp-1 transition-colors">{{ sim.titre }}</h3>
                                
                                <div class="space-y-2 text-sm text-zinc-500">
                                    <div class="flex items-center gap-2">
                                        <MapPin class="w-4 h-4 shrink-0" />
                                        <span class="line-clamp-1">{{ sim.lieu }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <CalendarDays class="w-4 h-4 shrink-0" />
                                        <span>{{ formatDate(sim.date_debut) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex flex-col gap-4">
                                <div class="pt-4 border-t border-zinc-50 flex items-center justify-between">
                                    <div class="text-lg font-bold text-zinc-900">{{ sim.prix_spectateur }} TND</div>
                                    <div class="text-blue-600 font-bold transition-transform group-hover:translate-x-1">
                                        <ChevronRight class="w-5 h-5" />
                                    </div>
                                </div>
                                <div class="w-full py-2.5 rounded-xl bg-zinc-900 text-white text-xs font-bold uppercase tracking-widest text-center group-hover:bg-blue-600 transition-all duration-300 shadow-lg shadow-zinc-900/10 group-hover:shadow-blue-600/20">
                                    View Details
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>

        </div>
    </AppLayout>
    <AppFooter/>
</template>

<style scoped>
.whitespace-pre-line {
    white-space: pre-line;
}
</style>
