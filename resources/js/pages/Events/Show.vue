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
    ChevronLeft,
    Star,
    Share2,
    CalendarDays,
    Gamepad2,
    Eye,
    Edit,
    PlayCircle,
    Copy,
    CheckCircle
} from 'lucide-vue-next';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import AppHeader from '@/components/AppHeader.vue';
import AppFooter from '@/components/AppFooter.vue';
import { useAuthModal } from '@/composables/useAuthModal';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button/index';
import { Badge } from '@/components/ui/badge/index';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import Reserver from '@/components/Reserver.vue';

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
            username: string;
            organisateur?: {
                nom_organisation?: string;
            };
        };
        collaborateurs?: Array<{
            id: number;
            statut: string;
            organizer: {
                id: number;
                username: string;
                name: string;
                organisateur?: {
                    nom_organisation?: string;
                };
            };
        }>;
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
    is_pending_collaborator?: boolean;
    is_collaborator?: boolean;
    similar_events: SimilarEvent[] | null;
}

const props = defineProps<Props>();
const page = usePage();
const auth = computed(() => page.props.auth as any);

const isOwner = computed(() => {
    return auth.value?.user?.id === props.event.organisateur.id;
});

// true for both the owner and accepted co-organizers
const canManage = computed(() => isOwner.value || !!props.is_collaborator);

const isReseller = computed(() => auth.value?.user?.role === 'revendeur');

const breadcrumbs = [
    { title: 'Discovery', href: '/discovery' },
    { title: props.event.titre, href: `/events/${props.event.id}` },
];

const selectedTicketType = ref(props.event.is_tournoi ? 'participant' : 'standard');

const getInitials = (name: string) => {
    return name?.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) || '??';
};

const organizerName = computed(() => {
    return props.event.organisateur?.organisateur?.nom_organisation || props.event.organisateur?.name || 'Unknown Organizer';
});

const eventTeam = computed(() => {
    const team = [];
    
    // 1. Add Main Organizer
    team.push({
        id: props.event.organisateur.id,
        name: props.event.organisateur.organisateur?.nom_organisation || props.event.organisateur.username || props.event.organisateur.name || 'Unknown Organizer',
        role: 'Host / Owner',
        isOwner: true,
        isYou: auth.value?.user?.id === props.event.organisateur.id
    });
    
    // 2. Add Accepted Collaborators
    if (props.event.collaborateurs && props.event.collaborateurs.length > 0) {
        const accepted = props.event.collaborateurs.filter(c => c.statut === 'accepted');
        for (const collab of accepted) {
            team.push({
                id: collab.organizer.id,
                name: collab.organizer.organisateur?.nom_organisation || collab.organizer.username || collab.organizer.name || 'Unknown Organizer',
                role: 'Co-Organizer',
                isOwner: false,
                isYou: auth.value?.user?.id === collab.organizer.id
            });
        }
    }
    
    return team;
});

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

// --- Media Carousel ---
const currentMediaIndex = ref(0);
const allMedias = computed(() => props.event.medias ?? []);
const currentMedia = computed(() => allMedias.value[currentMediaIndex.value] ?? null);

const prevMedia = () => {
    if (allMedias.value.length <= 1) return;
    currentMediaIndex.value = (currentMediaIndex.value - 1 + allMedias.value.length) % allMedias.value.length;
    resetTimer();
};

const nextMedia = () => {
    if (allMedias.value.length <= 1) return;
    currentMediaIndex.value = (currentMediaIndex.value + 1) % allMedias.value.length;
    resetTimer();
};

// Auto-advance every 5 seconds
let timer: ReturnType<typeof setInterval> | null = null;

const startTimer = () => {
    if (allMedias.value.length <= 1) return;
    timer = setInterval(() => {
        currentMediaIndex.value = (currentMediaIndex.value + 1) % allMedias.value.length;
    }, 5000);
};

const resetTimer = () => {
    if (timer) clearInterval(timer);
    startTimer();
};

onMounted(startTimer);
onUnmounted(() => { if (timer) clearInterval(timer); });

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
    
    // Check if user is an organizer
    if (auth.value.user.role === 'organisateur') return 'Organizers Cannot Reserve';
    if (auth.value.user.role === 'admin') return 'Admins Cannot Reserve';
    if (isReseller.value) return copied.value ? 'Link Copied!' : 'Copy Referral Link';

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

import axios from 'axios';

const isReserving = ref(false);
const showReservationModal = ref(false);

const onReservationSuccess = (data: any) => {
    // If it's a free event or payment was confirmed immediately
    if (!data.checkout_url) {
        showReservationModal.value = false;
        // The modal component already shows a success message, 
        // but we can add extra logic here if needed.
        setTimeout(() => location.reload(), 2000);
    }
};

const copied = ref(false);

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
        }
    } catch (err) {
        console.error('Fallback copy failed:', err);
    }
};

const handleReserveClick = () => {
    if (!auth.value.user) {
        openLogin();
        return;
    }

    if (isReseller.value) {
        copyReferralLink();
        return;
    }

    if (auth.value.user.role !== 'participant') {
        alert('Only participants can reserve events.');
        return;
    }

    showReservationModal.value = true;
};

import { router } from '@inertiajs/vue3';

const handleCollaboration = async (action: 'accept' | 'reject') => {
    try {
        await axios.post(`/web-api/events/${props.event.id}/collaborators/${action}`);
        router.reload({ only: ['auth', 'is_pending_collaborator'] });
        alert(`Invitation ${action}ed successfully.`);
        if (action === 'accept') {
            router.visit('/dashboard/collaborations');
        }
    } catch (error: any) {
        console.error(`Error processing collaboration ${action}:`, error);
        alert(error.response?.data?.message || `Failed to ${action} collaboration.`);
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="props.event.titre" />
        
        <div class="min-h-screen bg-background pb-12">

            <!-- Pending Collaboration Banner -->
            <div v-if="props.is_pending_collaborator" class="w-full bg-blue-600 text-white shadow-lg relative z-20">
                <div class="max-w-7xl mx-auto px-4 md:px-12 py-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                            <Users class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <h3 class="font-bold text-white leading-tight">You've been invited to co-organize this event!</h3>
                            <p class="text-sm text-blue-100">Accept this invitation to manage this event together with the owner.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <Button variant="outline" class="bg-transparent border-white text-white hover:bg-white hover:text-blue-600 border-2" @click="handleCollaboration('reject')">Decline</Button>
                        <Button class="bg-white text-blue-600 hover:bg-zinc-100 font-bold px-6" @click="handleCollaboration('accept')">Accept Invitation</Button>
                    </div>
                </div>
            </div>

            <!-- 1. HERO SECTION -->
            <div class="relative h-72 md:h-[500px] w-full overflow-hidden bg-zinc-900">

                <!-- No media fallback -->
                <div v-if="allMedias.length === 0" class="w-full h-full bg-gradient-to-br from-zinc-700 to-zinc-900 flex items-center justify-center">
                    <Calendar class="w-20 h-20 text-zinc-600" />
                </div>

                <!-- Carousel -->
                <template v-else>
                    <!-- Current media: Image -->
                    <img
                        v-if="currentMedia?.type === 'image'"
                        :src="currentMedia.url"
                        :key="'img-' + currentMedia.id"
                        @error="(e) => (e.target as HTMLImageElement).src = 'https://picsum.photos/seed/fallback/1200/800'"
                        class="w-full h-full object-cover opacity-70 transition-opacity duration-500"
                    />

                    <!-- Current media: Video -->
                    <video
                        v-else-if="currentMedia?.type === 'video'"
                        :src="currentMedia.url"
                        :key="'vid-' + currentMedia.id"
                        class="w-full h-full object-cover opacity-80"
                        autoplay
                        muted
                        loop
                        playsinline
                    />

                    <!-- Left Arrow -->
                    <button
                        v-if="allMedias.length > 1"
                        @click.stop="prevMedia"
                        class="absolute left-4 top-1/2 -translate-y-1/2 z-20 flex items-center justify-center w-11 h-11 rounded-full bg-black/40 hover:bg-black/70 text-white backdrop-blur-sm border border-white/20 transition-all duration-200 hover:scale-110"
                        aria-label="Previous media"
                    >
                        <ChevronLeft class="w-6 h-6" />
                    </button>

                    <!-- Right Arrow -->
                    <button
                        v-if="allMedias.length > 1"
                        @click.stop="nextMedia"
                        class="absolute right-4 top-1/2 -translate-y-1/2 z-20 flex items-center justify-center w-11 h-11 rounded-full bg-black/40 hover:bg-black/70 text-white backdrop-blur-sm border border-white/20 transition-all duration-200 hover:scale-110"
                        aria-label="Next media"
                    >
                        <ChevronRight class="w-6 h-6" />
                    </button>

                    <!-- Dot Indicators -->
                    <div v-if="allMedias.length > 1" class="absolute bottom-20 md:bottom-24 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2">
                        <button
                            v-for="(_, idx) in allMedias"
                            :key="idx"
                            @click="currentMediaIndex = idx"
                            :class="[
                                'transition-all duration-300 rounded-full border border-white/40',
                                idx === currentMediaIndex
                                    ? 'w-6 h-2.5 bg-white'
                                    : 'w-2.5 h-2.5 bg-white/40 hover:bg-white/70'
                            ]"
                        />
                    </div>

                    <!-- Video badge -->
                    <div v-if="currentMedia?.type === 'video'" class="absolute top-5 right-5 z-20 flex items-center gap-1.5 bg-black/50 backdrop-blur-sm text-white text-xs font-bold px-3 py-1.5 rounded-full border border-white/20">
                        <PlayCircle class="w-3.5 h-3.5" /> VIDEO
                    </div>

                    <!-- Media counter badge -->
                    <div v-if="allMedias.length > 1" class="absolute top-5 left-5 z-20 bg-black/50 backdrop-blur-sm text-white text-xs font-bold px-3 py-1.5 rounded-full border border-white/20">
                        {{ currentMediaIndex + 1 }} / {{ allMedias.length }}
                    </div>
                </template>

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

                    <!-- Owner / Co-Organizer Action Controls -->
                    <div v-if="canManage" class="absolute top-6 right-6 md:right-12 flex gap-3">
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
                        <div class="bg-card rounded-2xl shadow-sm p-8 border border-border">
                            <h2 class="text-xl font-bold text-foreground mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-6 bg-blue-600 rounded-full"></span>
                                About this Event
                            </h2>
                            <div class="prose prose-zinc dark:prose-invert max-w-none text-muted-foreground leading-relaxed whitespace-pre-line">
                                {{ props.event.description }}
                            </div>
                        </div>

                        <!-- Section 2: Date & Time -->
                        <div class="bg-card rounded-2xl shadow-sm p-8 border border-border">
                            <h2 class="text-xl font-bold text-foreground mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-6 bg-blue-600 rounded-full"></span>
                                Date & Time
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center shrink-0">
                                        <Calendar class="w-5 h-5 text-blue-600" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-muted-foreground uppercase tracking-wider mb-1">Start</div>
                                        <div class="text-foreground font-semibold">{{ formatDate(props.event.date_debut) }}</div>
                                        <div class="text-muted-foreground text-sm">{{ formatTime(props.event.date_debut) }}</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 bg-zinc-50 rounded-xl flex items-center justify-center shrink-0">
                                        <Clock class="w-5 h-5 text-zinc-400" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-muted-foreground uppercase tracking-wider mb-1">End</div>
                                        <div class="text-foreground font-semibold">{{ formatDate(props.event.date_fin) }}</div>
                                        <div class="text-muted-foreground text-sm">{{ formatTime(props.event.date_fin) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Location -->
                        <div class="bg-card rounded-2xl shadow-sm p-8 border border-border">
                            <h2 class="text-xl font-bold text-foreground mb-6 flex items-center gap-2">
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
                        <div class="bg-card rounded-2xl shadow-sm p-8 border border-border">
                            <h2 class="text-xl font-bold text-foreground mb-6 flex items-center gap-2">
                                <span class="w-1.5 h-6 bg-blue-600 rounded-full"></span>
                                Capacity
                            </h2>
                            
                            <!-- Normal Event Capacity -->
                            <div v-if="!props.event.is_tournoi" class="space-y-4">
                                <div class="flex justify-between items-end">
                                    <div class="flex items-center gap-2 text-foreground font-bold">
                                        <Users class="w-5 h-5 text-blue-600" />
                                        Attendees
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        <span class="font-bold text-foreground">{{ props.stats.total_reserved }}</span> / {{ props.event.capacite_spectateur }} spots
                                    </div>
                                </div>
                                <div class="w-full bg-muted rounded-full h-3 overflow-hidden">
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
                                        <div class="flex items-center gap-2 text-foreground font-bold">
                                            <Gamepad2 class="w-5 h-5 text-blue-600" />
                                            Participants
                                        </div>
                                        <div class="text-sm text-muted-foreground">
                                            <span class="font-bold text-foreground">{{ props.stats.participant_reserved }}</span> / {{ props.event.capacite_participant }} slots
                                        </div>
                                    </div>
                                    <div class="w-full bg-muted rounded-full h-3 overflow-hidden">
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
                                        <div class="flex items-center gap-2 text-foreground font-bold">
                                            <Eye class="w-5 h-5 text-orange-500" />
                                            Spectators
                                        </div>
                                        <div class="text-sm text-muted-foreground">
                                            <span class="font-bold text-foreground">{{ props.stats.spectator_reserved }}</span> / {{ props.event.capacite_spectateur }} spots
                                        </div>
                                    </div>
                                    <div class="w-full bg-muted rounded-full h-3 overflow-hidden">
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
                            <div class="bg-card rounded-2xl shadow-lg border border-border overflow-hidden">
                                <div class="p-1 h-2" :class="isOwner ? 'bg-foreground' : (props.is_collaborator ? 'bg-violet-600' : 'bg-blue-600')"></div>
                                
                                <!-- Owner View: Management Card -->
                                <div v-if="isOwner" class="p-8">
                                    <h3 class="text-lg font-bold text-foreground mb-6">Event Management</h3>
                                    
                                    <div class="space-y-6">
                                        <div class="flex items-center justify-between p-4 bg-muted/30 rounded-xl border border-border">
                                            <div class="text-sm font-bold text-muted-foreground uppercase tracking-wider">Status</div>
                                            <Badge variant="outline" class="capitalize font-bold text-blue-600 border-blue-100 bg-blue-50/50">
                                                Active
                                            </Badge>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="p-4 bg-muted/30 rounded-xl border border-border">
                                                <div class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest mb-1">Total Revenue</div>
                                                <div class="text-lg font-black text-foreground">
                                                    {{ ((props.stats.spectator_reserved ?? 0) * props.event.prix_spectateur) + ((props.stats.participant_reserved ?? 0) * (props.event.prix_participant ?? 0)) }} TND
                                                </div>
                                            </div>
                                            <div class="p-4 bg-muted/30 rounded-xl border border-border">
                                                <div class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest mb-1">Reservations</div>
                                                <div class="text-lg font-black text-foreground">
                                                    {{ (props.stats.total_reserved ?? 0) + (props.stats.participant_reserved ?? 0) + (props.stats.spectator_reserved ?? 0) }}
                                                </div>
                                            </div>
                                        </div>

                                        <Link :href="`/dashboard/events/${props.event.id}/edit`" class="block w-full">
                                            <Button class="w-full py-6 rounded-xl bg-primary hover:bg-primary/90 text-primary-foreground font-bold flex items-center justify-center gap-2">
                                                <Edit class="w-4 h-4" />
                                                Edit Specifications
                                            </Button>
                                        </Link>

                                        <p class="text-[10px] text-center text-zinc-400 font-bold uppercase tracking-widest">
                                            Last sync: Just now
                                        </p>
                                    </div>
                                </div>

                                <!-- Co-Organizer View: Lightweight Management Card -->
                                <div v-else-if="props.is_collaborator" class="p-8">
                                    <h3 class="text-lg font-bold text-foreground mb-2">Co-Organizer Panel</h3>
                                    <p class="text-sm text-muted-foreground mb-6">You are a co-organizer of this event. You can edit event details.</p>
                                    <Link :href="`/dashboard/events/${props.event.id}/edit`" class="block w-full">
                                        <Button class="w-full py-5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-bold flex items-center justify-center gap-2">
                                            <Edit class="w-4 h-4" />
                                            Edit Event
                                        </Button>
                                    </Link>
                                    <Link href="/dashboard/collaborations" class="block mt-3">
                                        <Button variant="outline" class="w-full">
                                            My Collaborations
                                        </Button>
                                    </Link>
                                </div>

                                <!-- Participant View: Ticket Card -->
                                <div v-else class="p-8">
                                    <h3 v-if="props.event.is_tournoi" class="text-lg font-bold text-foreground mb-6">Choose your ticket</h3>
                                    
                                    <!-- Normal Event Pricing -->
                                    <div v-if="!props.event.is_tournoi" class="mb-8">
                                        <div class="text-3xl font-black text-foreground">{{ props.event.prix_spectateur }} TND <span class="text-sm font-semibold text-muted-foreground">/ ticket</span></div>
                                        <div class="mt-2 flex items-center gap-2 text-sm text-muted-foreground font-medium">
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
                                                selectedTicketType === 'participant' ? 'border-blue-600 bg-blue-50/30' : 'border-border hover:border-accent'
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
                                                selectedTicketType === 'spectator' ? 'border-blue-600 bg-blue-50/30' : 'border-border hover:border-accent'
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
                                        @click="handleReserveClick"
                                        :disabled="(auth.user && auth.user.role !== 'participant') || props.is_reserved || isFullyBooked"
                                        :class="[
                                            'w-full py-4 rounded-xl font-bold transition-all active:scale-95 flex items-center justify-center gap-2 shadow-lg mb-4',
                                            ((auth.user && auth.user.role !== 'participant') || props.is_reserved || isFullyBooked) 
                                                ? 'bg-muted text-muted-foreground cursor-not-allowed shadow-none' 
                                                : 'bg-primary hover:bg-primary/90 text-primary-foreground shadow-primary/10'
                                        ]"
                                    >
                                        <Ticket class="w-5 h-5" v-if="!isFullyBooked && !props.is_reserved && (!auth.user || auth.user.role === 'participant')" />
                                        <template v-if="isReseller">
                                            <component :is="copied ? CheckCircle : Copy" class="w-5 h-5" />
                                        </template>
                                        {{ reserveButtonText }}
                                    </button>

                                    <!-- Reservation Modal -->
                                    <Dialog v-model:open="showReservationModal">
                                        <DialogContent class="sm:max-w-[425px] p-0 border-none bg-transparent shadow-none overflow-visible">
                                            <Reserver 
                                                :event="props.event" 
                                                @reservation-success="onReservationSuccess"
                                            />
                                        </DialogContent>
                                    </Dialog>

                                    <div class="flex items-center justify-center gap-4 text-xs font-semibold text-zinc-400 uppercase tracking-widest">
                                        <span class="flex items-center gap-1"><Share2 class="w-3.5 h-3.5" /> Share</span>
                                        <span class="w-1 h-1 bg-zinc-200 rounded-full"></span>
                                        <span>Report Event</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Hosted By Area -->
                            <div class="bg-card rounded-2xl shadow-sm border border-border overflow-hidden">
                                <div class="bg-muted/30 px-6 py-4 border-b border-border">
                                    <h3 class="font-bold flex items-center gap-2">
                                        <Users class="w-4 h-4 text-blue-600" />
                                        Hosted By
                                    </h3>
                                </div>
                                <div class="p-4 space-y-2">
                                    <div v-for="member in eventTeam" :key="member.id" class="flex items-center gap-4 p-2 rounded-xl hover:bg-muted/50 transition-colors">
                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center font-bold border shadow-sm shrink-0" :class="member.isOwner ? 'bg-gradient-to-br from-blue-50 to-blue-100 text-blue-600 border-blue-200' : 'bg-gradient-to-br from-zinc-50 to-zinc-100 text-zinc-600 border-zinc-200'">
                                            {{ getInitials(member.name) }}
                                        </div>
                                        <div class="flex-1 text-left">
                                            <h4 class="font-bold text-sm text-foreground leading-tight">
                                                <template v-if="member.isYou">You</template>
                                                <Link v-else :href="`/organizer/${member.id}`" class="hover:text-blue-600 transition-colors duration-200">
                                                    {{ member.name }}
                                                </Link>
                                            </h4>
                                            <div class="text-[10px] font-bold uppercase tracking-widest mt-0.5" :class="member.isOwner ? 'text-blue-600' : 'text-violet-600'">
                                                {{ member.role }}
                                            </div>
                                        </div>
                                        <Link v-if="!member.isYou" :href="`/organizer/${member.id}`">
                                            <Button variant="outline" size="icon" class="h-8 w-8 rounded-full">
                                                <ChevronRight class="w-4 h-4" />
                                            </Button>
                                        </Link>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

            <!-- 3. BOTTOM SECTION: Similar Events -->
            <div v-if="props.similar_events && props.similar_events.length > 0" class="max-w-7xl mx-auto px-4 md:px-12 mt-20">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-2xl font-bold text-foreground">Similar Events</h2>
                    <Link href="/dashboard/events" class="text-sm font-bold text-muted-foreground hover:text-foreground flex items-center gap-1 transition-colors group">
                        See all <ChevronRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <Link 
                        v-for="sim in props.similar_events" 
                        :key="sim.id"
                        :href="`/events/${sim.id}`"
                        class="bg-card rounded-2xl shadow-sm overflow-hidden group border border-transparent hover:border-border transition-all duration-300"
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
                                <h3 class="font-bold text-lg text-foreground line-clamp-1 transition-colors">{{ sim.titre }}</h3>
                                
                                <div class="space-y-2 text-sm text-muted-foreground">
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
                                <div class="pt-4 border-t border-border flex items-center justify-between">
                                    <div class="text-lg font-bold text-foreground">{{ sim.prix_spectateur }} TND</div>
                                    <div class="text-blue-600 font-bold transition-transform group-hover:translate-x-1">
                                        <ChevronRight class="w-5 h-5" />
                                    </div>
                                </div>
                                <div class="w-full py-2.5 rounded-xl bg-primary text-primary-foreground text-xs font-bold uppercase tracking-widest text-center group-hover:bg-blue-600 transition-all duration-300 shadow-lg shadow-primary/10 group-hover:shadow-blue-600/20">
                                    View Details
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
.whitespace-pre-line {
    white-space: pre-line;
}
</style>
