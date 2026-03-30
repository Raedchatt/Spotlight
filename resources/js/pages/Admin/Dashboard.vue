<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { 
    Users, 
    Calendar, 
    TrendingUp, 
    Wallet, 
    ArrowUpRight, 
    ExternalLink,
    Search,
    Filter
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
const props = defineProps<{
    stats: {
        total_users: number;
        total_organizers: number;
        total_participants: number;
        total_events: number;
        total_reservations: number;
        total_revenue: number;
        total_commission: number;
    };
    recent_events: Array<{
        id: number;
        titre: string;
        organisateur: string;
        reservations_count: number;
        revenue: number;
        commission: number;
        statut: string;
    }>;
}>();

const searchQuery = ref('');
const filteredEvents = computed(() => {
    return props.recent_events.filter(event => 
        event.titre.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        event.organisateur.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('fr-TN', { style: 'currency', currency: 'TND' }).format(amount);
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayout>
        <div class="px-4 py-8 md:px-8 space-y-8 max-w-[1600px] mx-auto">
            
            <!-- Welcome Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">Admin Overview</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Global platform performance and management.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button class="px-4 py-2 bg-indigo-600 text-white rounded-xl font-bold flex items-center gap-2 hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-500/20">
                        <Filter class="w-4 h-4" /> Filter Range
                    </button>
                </div>
            </div>

            <!-- ── KPI Cards ────────────────────────────────────────────────── -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                
                <!-- Total Users -->
                <div class="bg-white dark:bg-neutral-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-neutral-800 flex flex-col gap-4 group hover:shadow-xl transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <div class="p-3 rounded-2xl bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400">
                            <Users class="w-6 h-6" />
                        </div>
                        <span class="text-xs font-bold text-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 px-2 py-1 rounded-full flex items-center gap-1">
                            <ArrowUpRight class="w-3 h-3" /> +12%
                        </span>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Users</p>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ stats.total_users }}</h3>
                        <p class="text-xs text-gray-500 mt-2">
                           <span class="font-bold text-indigo-500">{{ stats.total_organizers }}</span> Org / 
                           <span class="font-bold text-purple-500">{{ stats.total_participants }}</span> Part
                        </p>
                    </div>
                </div>

                <!-- Total Events -->
                <div class="bg-white dark:bg-neutral-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-neutral-800 flex flex-col gap-4 group hover:shadow-xl transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <div class="p-3 rounded-2xl bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400">
                            <Calendar class="w-6 h-6" />
                        </div>
                        <span class="text-xs font-bold text-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 px-2 py-1 rounded-full flex items-center gap-1">
                            <ArrowUpRight class="w-3 h-3" /> +5%
                        </span>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Events</p>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ stats.total_events }}</h3>
                        <p class="text-xs text-gray-500 mt-2 font-medium">{{ stats.total_reservations }} total reservations</p>
                    </div>
                </div>

                <!-- Total Revenue -->
                <div class="bg-white dark:bg-neutral-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-neutral-800 flex flex-col gap-4 group hover:shadow-xl transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <div class="p-3 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400">
                            <Wallet class="w-6 h-6" />
                        </div>
                        <span class="text-xs font-bold text-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 px-2 py-1 rounded-full flex items-center gap-1">
                            <ArrowUpRight class="w-3 h-3" /> +18%
                        </span>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Revenue</p>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ formatCurrency(stats.total_revenue) }}</h3>
                        <p class="text-xs text-gray-500 mt-2 font-medium">Gross platform volume</p>
                    </div>
                </div>

                <!-- Site Commission -->
                <div class="bg-indigo-600 dark:bg-indigo-700 rounded-3xl p-6 shadow-xl shadow-indigo-500/20 flex flex-col gap-4 group hover:scale-[1.02] transition-all duration-300 text-white">
                    <div class="flex items-center justify-between">
                        <div class="p-3 rounded-2xl bg-white/20 text-white">
                            <TrendingUp class="w-6 h-6" />
                        </div>
                        <span class="text-xs font-extrabold bg-white/20 px-2 py-1 rounded-full">10% FEE</span>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-indigo-100 uppercase tracking-widest">Platform Commission</p>
                        <h3 class="text-2xl font-black mt-1">{{ formatCurrency(stats.total_commission) }}</h3>
                        <p class="text-xs text-indigo-100 mt-2 font-medium italic">Net revenue from fees</p>
                    </div>
                </div>

            </div>

            <!-- ── Events Performance Table ─────────────────────────────────── -->
            <div class="bg-white dark:bg-neutral-900 rounded-3xl border border-gray-100 dark:border-neutral-800 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 dark:border-neutral-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Recent Events Performance</h3>
                    <div class="relative max-w-sm w-full">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Search events or organizers..." 
                            class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-neutral-800 border-none rounded-xl text-sm focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-gray-400 dark:text-gray-500 text-xs uppercase tracking-widest font-bold">
                                <th class="px-6 py-4 text-left">Event</th>
                                <th class="px-6 py-4 text-left">Organizer</th>
                                <th class="px-6 py-4 text-center">Reservations</th>
                                <th class="px-6 py-4 text-right">Revenue</th>
                                <th class="px-6 py-4 text-right">Commission</th>
                                <th class="px-6 py-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-neutral-800">
                            <tr v-for="event in filteredEvents" :key="event.id" class="hover:bg-gray-50/50 dark:hover:bg-neutral-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-900 dark:text-gray-100 text-base">{{ event.titre }}</span>
                                        <span class="text-xs text-gray-500 flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full" :class="event.statut === 'ouvert' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                            {{ event.statut }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-xs">
                                            {{ event.organisateur.charAt(0).toUpperCase() }}
                                        </div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ event.organisateur }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 rounded-full bg-gray-100 dark:bg-neutral-800 text-gray-700 dark:text-gray-300 font-bold">
                                        {{ event.reservations_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">
                                    {{ formatCurrency(event.revenue) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-indigo-600 dark:text-indigo-400 font-bold">
                                        {{ formatCurrency(event.commission) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <Link :href="`/events/${event.id}`" class="text-indigo-600 hover:text-indigo-800 transition-colors bg-indigo-50 dark:bg-indigo-900/20 p-2 rounded-lg inline-block">
                                        <ExternalLink class="w-4 h-4" />
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
