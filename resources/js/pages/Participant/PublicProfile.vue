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

        <div class="max-w-4xl mx-auto px-4 py-8 space-y-8">
            <!-- 1. HERO SECTION -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden flex flex-col">
                <!-- Cover Banner -->
                <div class="h-48 md:h-56 bg-zinc-100 relative">
                    <div class="absolute inset-0 bg-zinc-200/50"></div>
                </div>

                <!-- Profile Info Bar -->
                <div class="px-6 pb-8 -mt-12 relative flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div class="flex flex-col md:flex-row md:items-end gap-6">
                        <!-- Avatar -->
                        <div class="w-24 h-24 rounded-full border-4 border-white shadow-md bg-zinc-200 flex items-center justify-center overflow-hidden shrink-0">
                            <span class="text-2xl font-bold text-zinc-500">{{ getInitials(props.participant.name) }}</span>
                        </div>

                        <!-- Name & Badge -->
                        <div class="space-y-1 mb-2">
                            <h1 class="text-3xl font-bold text-zinc-900">{{ props.participant.name }}</h1>
                            <div class="flex items-center gap-2">
                                <span class="bg-zinc-100 text-zinc-600 text-xs font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">
                                    Participant
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Side -->
                    <div class="flex flex-col items-start md:items-end gap-3 mb-2">
                        <button class="bg-black hover:bg-zinc-800 text-white px-8 py-2.5 rounded-full font-medium transition-all shadow-lg active:scale-95">
                            Contact
                        </button>
                        <div class="space-y-1 text-sm text-zinc-600">
                            <div v-if="props.participant.phone" class="flex items-center gap-2 md:justify-end">
                                <Phone class="w-4 h-4" /> {{ props.participant.phone }}
                            </div>
                            <div v-if="props.participant.email" class="flex items-center gap-2 md:justify-end">
                                <Mail class="w-4 h-4" /> {{ props.participant.email }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bio -->
                <div v-if="props.participant.about" class="px-6 pb-8 border-t border-zinc-50 pt-6 mt-2">
                    <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-widest mb-3">About</h3>
                    <p class="text-zinc-600 leading-relaxed">
                        {{ props.participant.about }}
                    </p>
                </div>
            </div>

            <!-- 2. STATS ROW -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Events Stats -->
                <div class="bg-white p-6 rounded-2xl shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center shrink-0">
                        <Ticket class="w-6 h-6 text-blue-600" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-zinc-900">{{ props.stats.total_events }}</div>
                        <div class="text-sm text-zinc-500 font-medium">Events joined</div>
                    </div>
                </div>

                <!-- Interests Card -->
                <div class="bg-white p-6 rounded-2xl shadow-sm space-y-4">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 bg-zinc-100 rounded-lg">
                            <Trophy class="w-4 h-4 text-zinc-600" />
                        </div>
                        <h3 class="font-bold text-zinc-900">Interests</h3>
                    </div>

                    <div v-if="props.stats.interests.length > 0" class="flex flex-wrap gap-2">
                        <template v-for="slug in props.stats.interests" :key="slug">
                            <div 
                                v-if="interestMapping[slug]"
                                class="bg-zinc-900 text-white px-3 py-1.5 rounded-full text-xs font-medium flex items-center gap-1.5 transition-colors"
                            >
                                <span>{{ interestMapping[slug].emoji }}</span>
                                <span>{{ interestMapping[slug].label }}</span>
                            </div>
                        </template>
                    </div>
                    <div v-else class="text-sm text-zinc-400 italic">
                        No interests added yet
                    </div>
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
</style>
