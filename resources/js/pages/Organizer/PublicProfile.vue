<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Calendar, Star, MapPin, Mail, Phone, ExternalLink, CalendarDays, ChevronRight, Trophy } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';

interface Event {
    id: number;
    titre: string;
    lieu: string;
    date_debut: string;
    categorie: string;
    statut: string;
    reservations_count?: number;
    medias?: { url: string }[];
}

interface Props {
    organizer: {
        id: number;
        name: string;
        email: string;
        phone?: string;
        about?: string;
        city?: string;
        logo?: string;
    };
    stats: {
        total_events: number;
        average_rating?: number | null;
    };
    upcoming_events: Event[];
}

const props = defineProps<Props>();

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="props.organizer.name + ' - Organizer Profile'" />

        <div class="min-h-screen bg-slate-50/50 dark:bg-black transition-colors duration-500">
            <div class="max-w-6xl mx-auto px-4 py-8 sm:py-12 lg:py-16 space-y-12">
                
                <!-- 1. HERO SECTION -->
                <div class="relative group">
                    <div class="absolute -inset-1 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-[2.5rem] blur opacity-20 group-hover:opacity-30 transition duration-1000 group-hover:duration-200"></div>
                    
                    <div class="relative bg-white dark:bg-neutral-900 rounded-[2.5rem] shadow-2xl overflow-hidden border border-gray-100 dark:border-neutral-800">
                        <!-- Cover Banner -->
                        <div class="h-48 sm:h-64 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 relative overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                            <div class="absolute -right-20 -top-20 w-80 h-80 bg-blue-500/10 rounded-full blur-3xl"></div>
                            <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl"></div>
                            
                            <!-- Floating Stats Decoration -->
                            <div class="absolute bottom-6 right-8 hidden md:flex items-center gap-6">
                                <div class="px-4 py-2 rounded-2xl bg-white/5 backdrop-blur-md border border-white/10 flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                                    <span class="text-xs font-black text-white uppercase tracking-widest">Verified Organizer</span>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Info -->
                        <div class="px-6 sm:px-12 pb-12 -mt-16 sm:-mt-24 relative flex flex-col md:flex-row md:items-end justify-between gap-8">
                            <div class="flex flex-col sm:flex-row items-center sm:items-end gap-8 text-center sm:text-left">
                                <!-- Logo/Avatar -->
                                <div class="relative group/logo">
                                    <div class="absolute -inset-2 bg-white dark:bg-neutral-900 rounded-[2.5rem]"></div>
                                    <div class="w-32 h-32 sm:w-44 sm:h-44 rounded-[2.5rem] border-4 border-white dark:border-neutral-800 shadow-2xl bg-white dark:bg-neutral-800 flex items-center justify-center overflow-hidden shrink-0 relative z-10 transition-all duration-500 group-hover/logo:scale-105 group-hover/logo:rotate-3">
                                        <img v-if="props.organizer.logo" :src="props.organizer.logo" class="w-full h-full object-cover" />
                                        <span v-else class="text-4xl sm:text-6xl font-black text-indigo-600 dark:text-indigo-400">
                                            {{ getInitials(props.organizer.name) }}
                                        </span>
                                    </div>
                                    <div class="absolute -bottom-2 -right-2 p-3 bg-white dark:bg-neutral-900 rounded-2xl z-20 shadow-xl border border-gray-100 dark:border-neutral-800">
                                        <Star class="w-5 h-5 text-yellow-500 fill-yellow-500" />
                                    </div>
                                </div>

                                <!-- Name & Badges -->
                                <div class="space-y-3 mb-2 sm:pb-2">
                                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-gray-900 dark:text-white tracking-tight">
                                        {{ props.organizer.name }}
                                    </h1>
                                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-4">
                                        <span class="bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[10px] sm:text-xs font-black px-4 py-1.5 rounded-full uppercase tracking-[0.1em] ring-1 ring-blue-500/20">
                                            Official Organizer
                                        </span>
                                        <div v-if="props.organizer.city" class="flex items-center gap-1.5 text-gray-400 dark:text-gray-500 text-xs font-bold">
                                            <MapPin class="w-4 h-4 text-rose-500" />
                                            {{ props.organizer.city }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Side -->
                            <div class="flex flex-col items-center md:items-end gap-4 mb-2 sm:pb-2 w-full md:w-auto">
                                <Link 
                                    v-if="$page.props.auth.user && $page.props.auth.user.id !== props.organizer.id"
                                    :href="`/messages/${props.organizer.id}`"
                                    class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-12 py-4 rounded-2xl font-black transition-all shadow-xl shadow-indigo-500/20 hover:scale-[1.02] active:scale-[0.98] text-center flex items-center justify-center gap-2 group/btn"
                                >
                                    <Mail class="w-5 h-5 group-hover/btn:translate-x-1 transition-transform" />
                                    Send Inquiry
                                </Link>
                                <div class="flex flex-wrap justify-center md:justify-end gap-6 text-xs font-bold text-gray-400 dark:text-gray-500">
                                    <div v-if="props.organizer.phone" class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                        <div class="p-1.5 rounded-lg bg-gray-50 dark:bg-neutral-800"><Phone class="w-3.5 h-3.5" /></div> {{ props.organizer.phone }}
                                    </div>
                                    <div v-if="props.organizer.email" class="flex items-center gap-2 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                        <div class="p-1.5 rounded-lg bg-gray-50 dark:bg-neutral-800"><Mail class="w-3.5 h-3.5" /></div> {{ props.organizer.email }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bio -->
                        <div v-if="props.organizer.about" class="px-6 sm:px-12 pb-12 pt-8 border-t border-gray-50 dark:border-neutral-800/50 bg-gray-50/20 dark:bg-neutral-800/10">
                            <h3 class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4">About Organization</h3>
                            <p class="text-gray-700 dark:text-gray-400 text-lg sm:text-xl font-medium leading-relaxed max-w-4xl">
                                {{ props.organizer.about }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 2. STATS GRID -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-white dark:bg-neutral-900 p-8 rounded-[2rem] shadow-xl border border-gray-100 dark:border-neutral-800 flex items-center gap-6 group">
                        <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center text-blue-600 dark:text-blue-400 transition-transform group-hover:scale-110">
                            <CalendarDays class="w-8 h-8" />
                        </div>
                        <div>
                            <div class="text-sm font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Total Events</div>
                            <div class="text-3xl font-black text-gray-900 dark:text-white tracking-tighter">{{ props.stats.total_events }}</div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-neutral-900 p-8 rounded-[2rem] shadow-xl border border-gray-100 dark:border-neutral-800 flex items-center gap-6 group">
                        <div class="w-16 h-16 bg-yellow-50 dark:bg-yellow-900/30 rounded-2xl flex items-center justify-center text-yellow-600 dark:text-yellow-400 transition-transform group-hover:scale-110">
                            <Star class="w-8 h-8 fill-current" />
                        </div>
                        <div>
                            <div class="text-sm font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Avg. Rating</div>
                            <div class="text-3xl font-black text-gray-900 dark:text-white tracking-tighter">
                                {{ props.stats.average_rating ? props.stats.average_rating.toFixed(1) : 'New' }}
                            </div>
                        </div>
                    </div>

                    <!-- Additional Context Card -->
                    <div class="hidden lg:flex bg-gradient-to-br from-slate-900 to-indigo-950 p-8 rounded-[2rem] shadow-xl items-center gap-6 text-white overflow-hidden relative">
                        <div class="relative z-10">
                            <div class="text-4xl font-black tracking-tighter mb-1">Top Tier</div>
                            <div class="text-xs font-bold opacity-60 uppercase tracking-widest">Performance Award</div>
                        </div>
                        <Trophy class="absolute -right-4 bottom-0 w-24 h-24 opacity-10 rotate-12" />
                    </div>
                </div>

                <!-- 3. UPCOMING EVENTS SECTION -->
                <div class="space-y-8">
                    <div class="flex items-center justify-between px-2">
                        <div class="space-y-1">
                            <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Active Events</h2>
                            <p class="text-sm font-bold text-gray-400">Scheduled by the organizer</p>
                        </div>
                        <Link href="/dashboard/events" class="flex items-center gap-2 text-sm font-black text-indigo-600 hover:text-indigo-700 uppercase tracking-widest transition-colors group">
                            Full Catalog <ChevronRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                        </Link>
                    </div>

                    <div v-if="props.upcoming_events.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <Link 
                            v-for="event in props.upcoming_events" 
                            :key="event.id" 
                            :href="`/events/${event.id}`"
                            class="bg-white dark:bg-neutral-900 rounded-[2rem] shadow-xl border border-gray-100 dark:border-neutral-800 overflow-hidden group hover:shadow-2xl transition-all duration-500 flex flex-col h-full hover:-translate-y-2"
                        >
                            <!-- Event Image -->
                            <div class="h-56 bg-gray-50 dark:bg-neutral-800 relative overflow-hidden">
                                <img v-if="event.medias?.[0]" :src="event.medias[0].url" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-1000" />
                                <div v-else class="w-full h-full flex items-center justify-center">
                                    <Calendar class="w-16 h-16 text-gray-200 dark:text-neutral-700" />
                                </div>
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-60 group-hover:opacity-80 transition-opacity"></div>
                                
                                <!-- Status & Category Badges -->
                                <div class="absolute top-4 left-4 flex flex-col gap-2">
                                    <span class="bg-white/95 dark:bg-neutral-900/95 backdrop-blur-sm text-gray-900 dark:text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest ring-1 ring-black/5 flex items-center gap-1.5">
                                        <span v-if="event.statut === 'encours'" class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                                        </span>
                                        {{ event.statut?.replace('_', ' ') || event.categorie }}
                                    </span>
                                </div>
                            </div>

                            <!-- Event Content -->
                            <div class="p-8 flex-1 flex flex-col space-y-6">
                                <h3 class="font-black text-2xl text-gray-900 dark:text-white leading-tight group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                    {{ event.titre }}
                                </h3>
                                
                                <div class="space-y-3">
                                    <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400 text-sm font-bold">
                                        <div class="p-2 rounded-xl bg-gray-50 dark:bg-neutral-800"><MapPin class="w-4 h-4 text-rose-500" /></div>
                                        <span class="truncate">{{ event.lieu }}</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400 text-sm font-bold">
                                        <div class="p-2 rounded-xl bg-gray-50 dark:bg-neutral-800"><CalendarDays class="w-4 h-4 text-blue-500" /></div>
                                        <span>{{ formatDate(event.date_debut) }}</span>
                                    </div>
                                </div>

                                <div class="mt-auto pt-8 flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 py-3 rounded-2xl bg-indigo-600 text-white flex items-center justify-center transform group-hover:rotate-12 transition-transform">
                                            <ChevronRight class="w-5 h-5" />
                                        </div>
                                        <span class="text-xs font-black uppercase tracking-widest text-gray-400 group-hover:text-indigo-600 transition-colors">Details</span>
                                    </div>
                                    <div v-if="event.reservations_count !== undefined" class="flex flex-col items-end">
                                        <span class="text-xl font-black text-gray-900 dark:text-white">{{ event.reservations_count }}</span>
                                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Spots Booked</span>
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <div v-else class="bg-white dark:bg-neutral-900 p-20 rounded-[3rem] shadow-xl border-4 border-dashed border-gray-100 dark:border-neutral-800 flex flex-col items-center text-center">
                        <div class="w-24 h-24 bg-gray-50 dark:bg-neutral-800 rounded-full flex items-center justify-center mb-6">
                            <Calendar class="w-12 h-12 text-gray-300 dark:text-neutral-700" />
                        </div>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">No upcoming events</h3>
                        <p class="text-gray-400 font-bold mt-2 max-w-sm">This organizer is currently planning their next big experience. Please check back later!</p>
                    </div>
                </div>

                <!-- Global Explore Link -->
                <div class="flex justify-center pt-8">
                    <Link href="/dashboard/events" class="flex flex-col items-center gap-2 group">
                        <span class="text-xs font-black text-gray-400 uppercase tracking-[0.3em] group-hover:text-indigo-600 transition-colors">Discover more experiences</span>
                        <div class="w-1 h-8 bg-gray-100 dark:bg-neutral-800 rounded-full group-hover:bg-indigo-600 transition-colors"></div>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* No additional styles needed as we use Tailwind's arbitrary values and standard classes */
</style>
