<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { 
    Wallet, 
    Check, 
    TrendingUp, 
    Clock, 
    Calendar, 
    ArrowUpRight, 
    Info,
    ChevronLeft,
    ChevronRight,
    Search
} from 'lucide-vue-next';

interface PayoutRecord {
    id: number;
    event_titre: string;
    amount: number;
    currency: string;
    reference: string;
    created_at: string;
}

interface EventSummary {
    id: number;
    titre: string;
    date_fin: string;
    gross_revenue: number;
    status: string;
    is_paid_out: boolean;
    paid_out_at: string | null;
}

const props = defineProps<{
    stats: {
        awaiting_payout: number;
        total_paid: number;
        currency: string;
    };
    events: EventSummary[];
    payout_history: PayoutRecord[];
}>();

const currentTab = ref('overview');
const searchQuery = ref('');

const filteredEvents = computed(() => {
    if (!searchQuery.value) return props.events;
    return props.events.filter(e => 
        e.titre.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

// Pagination
const historyPage = ref(1);
const itemsPerPage = 6;
const totalHistoryPages = computed(() => Math.ceil(props.payout_history.length / itemsPerPage));
const paginatedHistory = computed(() => {
    const start = (historyPage.value - 1) * itemsPerPage;
    return props.payout_history.slice(start, start + itemsPerPage);
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('fr-TN', { 
        style: 'currency', 
        currency: 'DT' // Using DT as per previous dashboard observations
    }).format(amount);
};
</script>

<template>
    <Head title="My Finances" />

    <AppLayout>
        <div class="px-4 py-8 md:px-8 space-y-8 max-w-[1400px] mx-auto">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                        <Wallet class="w-8 h-8 text-indigo-600" />
                        My Finances
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Track your event earnings and automated payouts.</p>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Awaiting Payout Card -->
                <div class="relative group overflow-hidden bg-white dark:bg-neutral-900 rounded-3xl p-8 border border-gray-100 dark:border-neutral-800 shadow-sm transition-all hover:shadow-xl hover:shadow-indigo-500/5">
                    <div class="absolute top-0 right-0 -mt-8 -mr-8 w-32 h-32 bg-indigo-50 dark:bg-indigo-900/20 rounded-full blur-3xl opacity-50 group-hover:scale-125 transition-transform duration-500"></div>
                    
                    <div class="relative z-10 flex items-start justify-between">
                        <div>
                            <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Awaiting Payout</p>
                            <h2 class="text-4xl font-black text-gray-900 dark:text-white">{{ formatCurrency(stats.awaiting_payout) }}</h2>
                            <p class="text-xs text-amber-600 dark:text-amber-400 font-medium mt-2 flex items-center gap-1">
                                <Clock class="w-3 h-3" /> Processed 1 day after event closure
                            </p>
                        </div>
                        <div class="p-4 bg-indigo-600 rounded-2xl text-white shadow-lg shadow-indigo-500/30">
                            <Clock class="w-6 h-6" />
                        </div>
                    </div>
                </div>

                <!-- Total Paid Card -->
                <div class="relative group overflow-hidden bg-white dark:bg-neutral-900 rounded-3xl p-8 border border-gray-100 dark:border-neutral-800 shadow-sm transition-all hover:shadow-xl hover:shadow-emerald-500/5">
                    <div class="absolute top-0 right-0 -mt-8 -mr-8 w-32 h-32 bg-emerald-50 dark:bg-emerald-900/20 rounded-full blur-3xl opacity-50 group-hover:scale-125 transition-transform duration-500"></div>
                    
                    <div class="relative z-10 flex items-start justify-between">
                        <div>
                            <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Total Received</p>
                            <h2 class="text-4xl font-black text-gray-900 dark:text-white">{{ formatCurrency(stats.total_paid) }}</h2>
                            <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium mt-2 flex items-center gap-1">
                                <Check class="w-3 h-3" /> Successfully transferred to Stripe
                            </p>
                        </div>
                        <div class="p-4 bg-emerald-500 rounded-2xl text-white shadow-lg shadow-emerald-500/30">
                            <TrendingUp class="w-6 h-6" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-8 border-b border-gray-200 dark:border-neutral-800 h-12">
                <button 
                    @click="currentTab = 'overview'" 
                    class="relative px-2 font-bold text-sm transition-colors duration-200 flex items-center gap-2"
                    :class="currentTab === 'overview' ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                >
                    Event Revenue
                    <div v-if="currentTab === 'overview'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-indigo-600 rounded-full"></div>
                </button>
                <button 
                    @click="currentTab = 'history'" 
                    class="relative px-2 font-bold text-sm transition-colors duration-200 flex items-center gap-2"
                    :class="currentTab === 'history' ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                >
                    Payout History
                    <div v-if="currentTab === 'history'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-indigo-600 rounded-full"></div>
                </button>
            </div>

            <!-- Tab Content: Overview -->
            <div v-if="currentTab === 'overview'" class="space-y-6">
                <!-- Search Bar -->
                <div class="relative group max-w-md">
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 transition-colors group-focus-within:text-indigo-500" />
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Search event..." 
                        class="w-full pl-11 pr-4 py-3 bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-2xl text-sm transition-all focus:ring-2 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none"
                    />
                </div>

                <div v-if="filteredEvents.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div v-for="event in filteredEvents" :key="event.id" class="bg-white dark:bg-neutral-900 p-6 rounded-3xl border border-gray-100 dark:border-neutral-800 shadow-sm flex items-center justify-between group hover:border-gray-200 dark:hover:border-neutral-700 transition-all">
                        <div class="space-y-2">
                            <h3 class="font-bold text-gray-900 dark:text-white line-clamp-1 group-hover:text-indigo-600 transition-colors">{{ event.titre }}</h3>
                            <div class="flex items-center gap-4 text-xs font-medium text-gray-500">
                                <span class="flex items-center gap-1"><Calendar class="w-3 h-3" /> {{ event.date_fin }}</span>
                                <span v-if="event.is_paid_out" class="flex items-center gap-1 text-emerald-600"><Check class="w-3 h-3" /> Paid Out</span>
                                <span v-else class="flex items-center gap-1 text-amber-600"><Clock class="w-3 h-3" /> Awaiting</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-black text-gray-900 dark:text-white">{{ formatCurrency(event.gross_revenue) }}</p>
                            <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Net Amount</p>
                        </div>
                    </div>
                </div>
                <div v-else class="p-12 text-center bg-gray-50 dark:bg-neutral-900/50 rounded-3xl border-2 border-dashed border-gray-200 dark:border-neutral-800">
                    <p class="text-gray-500">No events found matching your search.</p>
                </div>
            </div>

            <!-- Tab Content: History -->
            <div v-if="currentTab === 'history'" class="bg-white dark:bg-neutral-900 rounded-3xl border border-gray-100 dark:border-neutral-800 overflow-hidden shadow-sm">
                <div v-if="payout_history.length > 0">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase tracking-widest bg-gray-50 dark:bg-neutral-800/50 border-b border-gray-100 dark:border-neutral-800 font-bold">
                            <tr>
                                <th class="px-6 py-4">Event</th>
                                <th class="px-6 py-4">Reference</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-neutral-800">
                            <tr v-for="payout in paginatedHistory" :key="payout.id" class="hover:bg-gray-50 dark:hover:bg-neutral-900/50 transition-colors">
                                <td class="px-6 py-5 font-bold text-gray-900 dark:text-white">{{ payout.event_titre }}</td>
                                <td class="px-6 py-5 font-mono text-xs text-gray-400 truncate max-w-[150px]">{{ payout.reference }}</td>
                                <td class="px-6 py-5 text-gray-500">{{ payout.created_at }}</td>
                                <td class="px-6 py-5 text-right font-black text-emerald-600">{{ formatCurrency(payout.amount) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="totalHistoryPages > 1" class="px-6 py-4 bg-gray-50 dark:bg-neutral-900/50 border-t border-gray-100 dark:border-neutral-800 flex justify-between items-center">
                        <span class="text-xs font-semibold text-gray-400 uppercase">Page {{ historyPage }} / {{ totalHistoryPages }}</span>
                        <div class="flex gap-2">
                            <button @click="historyPage--" :disabled="historyPage === 1" class="p-2 bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl disabled:opacity-30 transition-all hover:border-indigo-500">
                                <ChevronLeft class="w-4 h-4 text-gray-600 dark:text-gray-300" />
                            </button>
                            <button @click="historyPage++" :disabled="historyPage === totalHistoryPages" class="p-2 bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl disabled:opacity-30 transition-all hover:border-indigo-500">
                                <ChevronRight class="w-4 h-4 text-gray-600 dark:text-gray-300" />
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="p-20 text-center flex flex-col items-center gap-4">
                    <div class="p-4 bg-gray-50 dark:bg-neutral-800 rounded-full">
                        <Info class="w-8 h-8 text-gray-300" />
                    </div>
                    <p class="text-gray-500 max-w-xs mx-auto font-medium">No payouts have been processed yet. Your historical earnings will appear here.</p>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
