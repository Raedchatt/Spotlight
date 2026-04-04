<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Ticket, Trophy, Mail, Phone, ChevronRight, User as UserIcon } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';

interface Props {
    participant: {
        id: number;
        name: string;
        email: string;
        phone?: string;
        about?: string;
    };
    stats: {
        total_events: number;
        interests: string[];
    };
}

const props = defineProps<Props>();

const interestMapping: Record<string, { label: string; emoji: string }> = {
    music:    { label: "Music",   emoji: "🎵" },
    sports:   { label: "Sports",  emoji: "⚽" },
    art:      { label: "Art",     emoji: "🎨" },
    tech:     { label: "Tech",    emoji: "💻" },
    theater:  { label: "Theater", emoji: "🎭" },
    food:     { label: "Food",    emoji: "🍕" },
    cinema:   { label: "Cinema",  emoji: "🎬" },
    gaming:   { label: "Gaming",  emoji: "🎮" },
};

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
};
</script>

<template>
    <AppLayout>
        <Head :title="props.participant.name + ' - Profil Participant'" />

        <div class="min-h-screen bg-slate-50/50 dark:bg-black transition-colors duration-500">
            <div class="max-w-5xl mx-auto px-4 py-8 sm:py-12 lg:py-16 space-y-8">
                
                <!-- 1. HERO SECTION -->
                <div class="relative group">
                    <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-[2rem] blur opacity-25 group-hover:opacity-40 transition duration-1000 group-hover:duration-200"></div>
                    
                    <div class="relative bg-white dark:bg-neutral-900 rounded-[2rem] shadow-2xl overflow-hidden border border-gray-100 dark:border-neutral-800 transition-all duration-300">
                        <!-- Cover Banner -->
                        <div class="h-40 sm:h-56 lg:h-64 bg-gradient-to-br from-indigo-600 via-purple-700 to-slate-900 relative">
                            <div class="absolute inset-0 bg-white/5 backdrop-blur-[1px]"></div>
                            <div class="absolute top-0 right-0 p-8 opacity-20">
                                <Trophy class="w-32 h-32 text-white rotate-12" />
                            </div>
                        </div>

                        <!-- Profile Info Bar -->
                        <div class="px-6 sm:px-10 pb-10 -mt-16 sm:-mt-20 relative flex flex-col md:flex-row md:items-end justify-between gap-8">
                            <div class="flex flex-col sm:flex-row items-center sm:items-end gap-6 text-center sm:text-left">
                                <!-- Avatar -->
                                <div class="relative group/avatar">
                                    <div class="absolute -inset-2 bg-white dark:bg-neutral-900 rounded-full"></div>
                                    <div class="w-32 h-32 sm:w-40 sm:h-40 rounded-full border-4 border-white dark:border-neutral-800 shadow-2xl bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900 dark:to-purple-900 flex items-center justify-center overflow-hidden shrink-0 relative z-10 transition-transform group-hover/avatar:scale-105 duration-500">
                                        <span class="text-4xl sm:text-5xl font-black text-indigo-600 dark:text-indigo-400 select-none">
                                            {{ getInitials(props.participant.name) }}
                                        </span>
                                    </div>
                                    <div class="absolute bottom-2 right-2 w-8 h-8 bg-emerald-500 border-4 border-white dark:border-neutral-900 rounded-full z-20 shadow-lg"></div>
                                </div>

                                <!-- Name & Badge -->
                                <div class="space-y-2 mb-2 sm:pb-2">
                                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-gray-900 dark:text-white tracking-tight">
                                        {{ props.participant.name }}
                                    </h1>
                                    <div class="flex items-center justify-center sm:justify-start gap-3">
                                        <span class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-[10px] sm:text-xs font-black px-3 py-1 rounded-full uppercase tracking-widest ring-1 ring-indigo-500/20">
                                            Participant
                                        </span>
                                        <div class="flex items-center gap-1 text-gray-400 dark:text-gray-500 text-xs font-bold px-2 py-1 rounded-full bg-gray-50 dark:bg-neutral-800 border border-gray-100 dark:border-neutral-700">
                                            <UserIcon class="w-3 h-3" />
                                            Active Member
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Side -->
                            <div class="flex flex-col items-center md:items-end gap-4 mb-2 sm:pb-2">
                                <Link 
                                    v-if="$page.props.auth.user && $page.props.auth.user.id !== props.participant.id"
                                    :href="`/messages/${props.participant.id}`"
                                    class="w-full sm:w-auto bg-slate-900 dark:bg-white hover:bg-black dark:hover:bg-neutral-200 text-white dark:text-black px-10 py-3.5 rounded-2xl font-bold transition-all shadow-xl hover:shadow-indigo-500/20 active:scale-95 text-center flex items-center justify-center gap-2 group/btn"
                                >
                                    <Mail class="w-5 h-5 group-hover/btn:translate-x-1 transition-transform" />
                                    Send Message
                                </Link>
                                <div class="flex flex-wrap justify-center md:justify-end gap-4 text-xs font-bold text-gray-500 dark:text-gray-400">
                                    <div v-if="props.participant.phone" class="flex items-center gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                        <Phone class="w-4 h-4" /> {{ props.participant.phone }}
                                    </div>
                                    <div v-if="props.participant.email" class="flex items-center gap-1.5 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                        <Mail class="w-4 h-4" /> {{ props.participant.email }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bio Section -->
                        <div v-if="props.participant.about" class="px-6 sm:px-10 pb-10 pt-10 border-t border-gray-50 dark:border-neutral-800/50 bg-gray-50/30 dark:bg-neutral-800/20">
                            <h3 class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-4">Biography</h3>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed text-base sm:text-lg font-medium max-w-4xl italic">
                                "{{ props.participant.about }}"
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 2. CONTENT GRID -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- Stats Section (4 Cols) -->
                    <div class="lg:col-span-4 space-y-6">
                        <div class="bg-white dark:bg-neutral-900 p-8 rounded-[2rem] shadow-xl border border-gray-100 dark:border-neutral-800 flex items-center justify-between group overflow-hidden relative">
                            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-indigo-500/5 rounded-full blur-2xl group-hover:bg-indigo-500/10 transition-colors"></div>
                            
                            <div class="relative z-10 flex items-center gap-6">
                                <div class="w-16 h-16 bg-indigo-50 dark:bg-indigo-900/40 rounded-2xl flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform duration-500">
                                    <Ticket class="w-8 h-8 text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <div class="space-y-1">
                                    <div class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter">{{ props.stats.total_events }}</div>
                                    <div class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Events Joined</div>
                                </div>
                            </div>
                        </div>

                        <!-- Mini Cards or Info placeholder -->
                        <div class="bg-gradient-to-br from-indigo-600 to-purple-700 p-8 rounded-[2rem] shadow-xl text-white relative overflow-hidden group">
                           <div class="relative z-10 space-y-4">
                               <h4 class="text-sm font-black uppercase tracking-widest opacity-60">Account Status</h4>
                               <p class="text-xl font-bold leading-tight">Member since {{ new Date().getFullYear() - 1 }}</p>
                               <div class="h-1 w-12 bg-white/30 rounded-full"></div>
                               <p class="text-xs font-medium opacity-80 leading-relaxed">Active participant in the Spotlight community, contributing to local vibrant events.</p>
                           </div>
                           <Trophy class="absolute -right-4 -bottom-4 w-32 h-32 opacity-10 rotate-12 group-hover:scale-110 transition-transform duration-1000" />
                        </div>
                    </div>

                    <!-- Interests Section (8 Cols) -->
                    <div class="lg:col-span-8 bg-white dark:bg-neutral-900 p-8 sm:p-10 rounded-[2rem] shadow-xl border border-gray-100 dark:border-neutral-800 space-y-8">
                        <div class="flex items-center justify-between border-b border-gray-50 dark:border-neutral-800/50 pb-6">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-gray-50 dark:bg-neutral-800 rounded-2xl">
                                    <Trophy class="w-6 h-6 text-gray-900 dark:text-white" />
                                </div>
                                <h3 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">Personal Interests</h3>
                            </div>
                        </div>

                        <div v-if="props.stats.interests.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <template v-for="slug in props.stats.interests" :key="slug">
                                <div 
                                    v-if="interestMapping[slug]"
                                    class="bg-gray-50 dark:bg-neutral-800/50 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 p-5 rounded-3xl transition-all duration-300 group/item border border-transparent hover:border-indigo-100 dark:hover:border-indigo-500/20 hover:translate-y-[-4px]"
                                >
                                    <div class="text-3xl mb-3 transition-transform group-hover/item:scale-110 duration-500">{{ interestMapping[slug].emoji }}</div>
                                    <div class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider group-hover/item:text-indigo-600 dark:group-hover/item:text-indigo-400">{{ interestMapping[slug].label }}</div>
                                    <div class="text-[10px] font-bold text-gray-400 mt-1 opacity-0 group-hover/item:opacity-100 transition-opacity">Explore More</div>
                                </div>
                            </template>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-16 text-center space-y-4">
                            <div class="w-16 h-16 bg-gray-50 dark:bg-neutral-800 rounded-full flex items-center justify-center">
                                <Trophy class="w-8 h-8 text-gray-200 dark:text-neutral-700" />
                            </div>
                            <div>
                                <p class="text-gray-900 dark:text-white font-bold">No interests shared yet</p>
                                <p class="text-sm text-gray-400">Discover events to start building your profile!</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer CTA -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-6 pt-12">
                    <Link href="/dashboard/events" class="group flex items-center gap-3 bg-white dark:bg-neutral-900 px-8 py-4 rounded-2xl shadow-xl hover:shadow-indigo-500/10 border border-gray-100 dark:border-neutral-800 transition-all active:scale-95">
                        <span class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest">Explore Upcoming Events</span>
                        <div class="p-1.5 bg-indigo-50 dark:bg-indigo-900/40 rounded-lg group-hover:translate-x-1 transition-transform">
                            <ChevronRight class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* No additional styles needed as we use Tailwind's arbitrary values and standard classes */
</style>
