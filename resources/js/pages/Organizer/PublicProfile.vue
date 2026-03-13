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
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="props.organizer.name + ' - Profil Organisateur'" />

        <div class="max-w-6xl mx-auto px-4 py-8 space-y-8">
            <!-- 1. HERO SECTION -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden flex flex-col">
                <!-- Cover Banner -->
                <div class="h-48 md:h-56 bg-zinc-100 relative">
                    <div class="absolute inset-0 bg-gradient-to-b from-black/10 to-transparent"></div>
                    <!-- Placeholder cover (can be replaced with dynamic cover if available) -->
                </div>

                <!-- Profile Info Bar -->
                <div class="px-6 pb-8 -mt-12 relative flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div class="flex flex-col md:flex-row md:items-end gap-6">
                        <!-- Avatar -->
                        <div class="w-24 h-24 rounded-full border-4 border-white shadow-md bg-zinc-200 flex items-center justify-center overflow-hidden shrink-0">
                            <img v-if="props.organizer.logo" :src="props.organizer.logo" class="w-full h-full object-cover" />
                            <span v-else class="text-2xl font-bold text-zinc-500">{{ getInitials(props.organizer.name) }}</span>
                        </div>

                        <!-- Name & Badge -->
                        <div class="space-y-1 mb-2">
                            <h1 class="text-3xl font-bold text-zinc-900">{{ props.organizer.name }}</h1>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">
                                    Organisateur
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Side -->
                    <div class="flex flex-col items-start md:items-end gap-3 mb-2">
                        <Link 
                            v-if="$page.props.auth.user && $page.props.auth.user.id !== props.organizer.id"
                            :href="`/messages/${props.organizer.id}`"
                            class="bg-black hover:bg-zinc-800 text-white px-8 py-2.5 rounded-full font-medium transition-all shadow-lg active:scale-95 text-center"
                        >
                            Contact
                        </Link>
                        <div class="space-y-1 text-sm text-zinc-600">
                            <div v-if="props.organizer.phone" class="flex items-center gap-2 md:justify-end">
                                <Phone class="w-4 h-4" /> {{ props.organizer.phone }}
                            </div>
                            <div v-if="props.organizer.email" class="flex items-center gap-2 md:justify-end">
                                <Mail class="w-4 h-4" /> {{ props.organizer.email }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bio -->
                <div v-if="props.organizer.about" class="px-6 pb-8">
                    <p class="text-zinc-600 max-w-3xl leading-relaxed">
                        {{ props.organizer.about }}
                    </p>
                </div>
            </div>

            <!-- 2. STATS ROW -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white p-6 rounded-2xl shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center shrink-0">
                        <CalendarDays class="w-6 h-6 text-blue-600" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-zinc-900">{{ props.stats.total_events }}</div>
                        <div class="text-sm text-zinc-500 font-medium">Events created</div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center shrink-0">
                        <Star class="w-6 h-6 text-yellow-600" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-zinc-900">
                            {{ props.stats.average_rating ? props.stats.average_rating.toFixed(1) : 'No ratings yet' }}
                        </div>
                        <div class="text-sm text-zinc-500 font-medium">Average rating</div>
                    </div>
                </div>
            </div>

            <!-- 3. UPCOMING EVENTS SECTION -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-zinc-900">Upcoming Events</h2>
                    <Link href="/dashboard/events" class="text-sm font-semibold text-zinc-500 hover:text-black flex items-center gap-1 transition-colors">
                        See all <ChevronRight class="w-4 h-4" />
                    </Link>
                </div>

                <div v-if="props.upcoming_events.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Link 
                        v-for="event in props.upcoming_events" 
                        :key="event.id" 
                        :href="`/events/${event.id}`"
                        class="bg-white rounded-2xl shadow-sm overflow-hidden group hover:shadow-md transition-all duration-300 flex flex-col"
                    >
                        <!-- Event Image -->
                        <div class="h-40 bg-zinc-100 relative overflow-hidden">
                            <img v-if="event.medias?.[0]" :src="event.medias[0].url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <Calendar class="w-10 h-10 text-zinc-300" />
                            </div>
                            
                            <!-- Category Badge -->
                            <div class="absolute top-3 left-3">
                                <span class="bg-white/90 backdrop-blur-sm text-zinc-900 text-[10px] font-bold px-2 py-1 rounded-lg uppercase tracking-wider">
                                    {{ event.categorie }}
                                </span>
                            </div>
                        </div>

                        <!-- Event Content -->
                        <div class="p-5 space-y-3">
                            <h3 class="font-bold text-lg text-zinc-900 line-clamp-1 group-hover:text-blue-600 transition-colors">
                                {{ event.titre }}
                            </h3>
                            
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 text-zinc-500 text-sm">
                                    <MapPin class="w-4 h-4 shrink-0" />
                                    <span class="line-clamp-1">{{ event.lieu }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-zinc-500 text-sm">
                                    <CalendarDays class="w-4 h-4 shrink-0" />
                                    <span>{{ formatDate(event.date_debut) }}</span>
                                </div>
                            </div>

                            <div class="mt-6 flex flex-col gap-4">
                                <div class="pt-4 border-t border-zinc-50 flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">{{ event.categorie }}</span>
                                    <div v-if="event.reservations_count !== undefined" class="text-[10px] font-bold text-blue-600 flex items-center gap-1">
                                        <Trophy class="w-3 h-3" /> {{ event.reservations_count }} Reservations
                                    </div>
                                </div>
                                
                                <div class="w-full py-2.5 rounded-xl bg-zinc-900 text-white text-xs font-bold uppercase tracking-widest text-center group-hover:bg-blue-600 transition-all duration-300 shadow-lg shadow-zinc-900/10 group-hover:shadow-blue-600/20">
                                    View Details
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>

                <div v-else class="bg-white p-12 rounded-3xl shadow-sm border border-dashed border-zinc-200 flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-zinc-50 rounded-full flex items-center justify-center mb-4">
                        <Calendar class="w-8 h-8 text-zinc-300" />
                    </div>
                    <h3 class="text-lg font-bold text-zinc-900">No upcoming events</h3>
                    <p class="text-zinc-500 mt-1">This organizer hasn't scheduled any new events yet.</p>
                </div>
            </div>

            <!-- Footer Link -->
            <div class="flex justify-center pt-4">
                <Link href="/dashboard/events" class="text-sm font-semibold text-zinc-400 hover:text-zinc-900 flex items-center gap-1 transition-colors">
                    Explore events <ChevronRight class="w-4 h-4" />
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.rounded-2xl {
    border-radius: 1rem;
}
.rounded-3xl {
    border-radius: 1.5rem;
}
</style>
