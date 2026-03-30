<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Wallet, Check, AlertCircle, Building, CalendarDays, ExternalLink, RefreshCw, ChevronLeft, ChevronRight } from 'lucide-vue-next';

const props = defineProps<{
    pending_payouts: any[];
    payout_history: any[];
}>();

const processingEventId = ref<number | null>(null);
const currentTab = ref('pending');

const pendingPage = ref(1);
const historyPage = ref(1);
const itemsPerPage = 5;

const paginatedPending = computed(() => {
    if (!props.pending_payouts) return [];
    const start = (pendingPage.value - 1) * itemsPerPage;
    return props.pending_payouts.slice(start, start + itemsPerPage);
});

const totalPendingPages = computed(() => Math.ceil((props.pending_payouts?.length || 0) / itemsPerPage));

const paginatedHistory = computed(() => {
    if (!props.payout_history) return [];
    const start = (historyPage.value - 1) * itemsPerPage;
    return props.payout_history.slice(start, start + itemsPerPage);
});

const totalHistoryPages = computed(() => Math.ceil((props.payout_history?.length || 0) / itemsPerPage));

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('fr-TN', { style: 'currency', currency: 'TND' }).format(amount);
};

const handleTransfer = (id: number, stripeAccountId: string | null) => {
    if (!stripeAccountId) {
        alert("This organizer has not connected their Stripe account yet. Payout cannot be processed.");
        return;
    }

    if (confirm("Are you sure you want to transfer these funds to the organizer's Stripe account?")) {
        processingEventId.value = id;
        router.post(`/admin/financials/${id}/pay`, {}, {
            preserveScroll: true,
            onFinish: () => {
                processingEventId.value = null;
            }
        });
    }
};
</script>

<template>
    <Head title="Admin Financials" />

    <AppLayout>
        <div class="px-4 py-8 md:px-8 space-y-8 max-w-[1200px] mx-auto">
            
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">Payouts & Financials</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Manage payouts to organizers for successfully completed events.</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-4 border-b border-gray-200 dark:border-neutral-800">
                <button 
                    @click="currentTab = 'pending'" 
                    class="py-3 px-4 font-bold text-sm border-b-2 transition"
                    :class="currentTab === 'pending' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                >
                    Pending Payouts ({{ pending_payouts?.length || 0 }})
                </button>
                <button 
                    @click="currentTab = 'history'" 
                    class="py-3 px-4 font-bold text-sm border-b-2 transition"
                    :class="currentTab === 'history' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                >
                    Payout History ({{ payout_history?.length || 0 }})
                </button>
            </div>

            <!-- Tab 1: Pending Payouts -->
            <div v-if="currentTab === 'pending'" class="space-y-4">
                <div v-if="pending_payouts && pending_payouts.length > 0" class="space-y-4">
                    <div v-for="event in paginatedPending" :key="event.id" class="bg-white dark:bg-neutral-900 shadow-sm border border-gray-100 dark:border-neutral-800 rounded-3xl p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 transition hover:shadow-md">
                        
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center justify-center p-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl">
                                    <Wallet class="w-5 h-5" />
                                </span>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ event.titre }}</h3>
                            </div>
                            
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-y-3 gap-x-6 text-sm">
                                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                    <Building class="w-4 h-4 text-gray-400" />
                                    <span>Organizer: <span class="font-bold">{{ event.organisateur?.name || 'Unknown' }}</span></span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                    <CalendarDays class="w-4 h-4 text-gray-400" />
                                    <span>Ended: {{ event.date_fin }}</span>
                                </div>
                                
                                <div v-if="!event.organisateur?.stripe_account_id" class="flex items-center gap-2 text-rose-500 font-medium md:col-span-2 mt-2 bg-rose-50 dark:bg-rose-900/20 px-3 py-2 rounded-lg">
                                    <AlertCircle class="w-4 h-4" />
                                    <span>Organizer has not connected a Stripe Account. Payout disabled.</span>
                                </div>
                                <div v-else class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-medium md:col-span-2 mt-2 bg-emerald-50 dark:bg-emerald-900/20 px-3 py-2 rounded-lg">
                                    <Check class="w-4 h-4" />
                                    <span>Stripe Connected ({{ event.organisateur.stripe_account_id }})</span>
                                </div>
                            </div>
                        </div>

                        <!-- Financials & Action -->
                        <div class="w-full md:w-auto flex flex-col items-end gap-3 bg-gray-50 dark:bg-neutral-800/50 p-4 rounded-2xl border border-gray-100 dark:border-neutral-700">
                            <div class="w-full flex justify-between gap-8 text-sm">
                                <span class="text-gray-500">Gross Rev:</span>
                                <span class="font-medium">{{ formatCurrency(event.revenue) }}</span>
                            </div>
                            <div class="w-full flex justify-between gap-8 text-sm text-amber-600 dark:text-amber-500">
                                <span>Platform Fee:</span>
                                <span>- {{ formatCurrency(event.commission) }}</span>
                            </div>
                            <div class="w-full flex justify-between gap-8 text-lg font-black text-emerald-600 dark:text-emerald-400 border-t border-gray-200 dark:border-neutral-700 pt-2 mt-1">
                                <span>Net Payout:</span>
                                <span>{{ formatCurrency(event.net_payout) }}</span>
                            </div>

                            <button
                                @click="handleTransfer(event.id, event.organisateur?.stripe_account_id)"
                                :disabled="processingEventId === event.id || !event.organisateur?.stripe_account_id || event.net_payout <= 0"
                                class="w-full mt-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold rounded-xl flex items-center justify-center gap-2 transition"
                            >
                                <RefreshCw v-if="processingEventId === event.id" class="w-4 h-4 animate-spin" />
                                <ExternalLink v-else class="w-4 h-4" />
                                {{ processingEventId === event.id ? 'Transferring...' : 'Transfer via Stripe' }}
                            </button>
                        </div>
                    </div>

                    <!-- Pending Pagination Controls -->
                    <div v-if="totalPendingPages > 1" class="flex justify-between items-center bg-white dark:bg-neutral-900 p-4 rounded-2xl border border-gray-100 dark:border-neutral-800 mt-4 shadow-sm">
                        <span class="text-sm font-medium text-gray-500">Page {{ pendingPage }} of {{ totalPendingPages }}</span>
                        <div class="flex gap-2">
                            <button @click="pendingPage--" :disabled="pendingPage === 1" class="p-2 border border-gray-200 dark:border-neutral-700 rounded-xl hover:bg-gray-50 dark:hover:bg-neutral-800 disabled:opacity-50 transition-colors">
                                <ChevronLeft class="w-4 h-4 text-gray-600 dark:text-gray-300"/>
                            </button>
                            <button @click="pendingPage++" :disabled="pendingPage === totalPendingPages" class="p-2 border border-gray-200 dark:border-neutral-700 rounded-xl hover:bg-gray-50 dark:hover:bg-neutral-800 disabled:opacity-50 transition-colors">
                                <ChevronRight class="w-4 h-4 text-gray-600 dark:text-gray-300"/>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-gray-50 dark:bg-neutral-800/50 rounded-3xl p-12 text-center border-2 border-dashed border-gray-200 dark:border-neutral-700 flex flex-col items-center justify-center gap-4">
                    <div class="w-16 h-16 bg-white dark:bg-neutral-800 rounded-full flex items-center justify-center shadow-sm">
                        <Check class="w-8 h-8 text-emerald-500" />
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">All payouts completed</h3>
                        <p class="text-gray-500 dark:text-gray-400 mt-2">There are no pending events waiting for payouts.</p>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Payout History -->
            <div v-if="currentTab === 'history'" class="bg-white dark:bg-neutral-900 rounded-3xl border border-gray-100 dark:border-neutral-800 shadow-sm overflow-hidden">
                <div v-if="payout_history && payout_history.length > 0" class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase tracking-widest bg-gray-50 dark:bg-neutral-800/50 border-b border-gray-100 dark:border-neutral-800">
                            <tr>
                                <th class="px-6 py-4">Event & Organizer</th>
                                <th class="px-6 py-4">Paid Out At</th>
                                <th class="px-6 py-4">Stripe Account</th>
                                <th class="px-6 py-4 text-right">Net Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-neutral-800/50">
                            <tr v-for="event in paginatedHistory" :key="event.id" class="hover:bg-gray-50/50 dark:hover:bg-neutral-800/30">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900 dark:text-white text-base">{{ event.titre }}</div>
                                    <div class="text-gray-500 mt-0.5">by {{ event.organisateur?.name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <Check class="w-4 h-4 text-emerald-500" />
                                        {{ event.paid_out_at }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-gray-500">
                                    {{ event.organisateur?.stripe_account_id }}
                                </td>
                                <td class="px-6 py-4 text-right font-black text-emerald-600 dark:text-emerald-400 text-base">
                                    {{ formatCurrency(event.net_payout) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- History Pagination Controls -->
                    <div v-if="totalHistoryPages > 1" class="flex justify-between items-center p-4 border-t border-gray-100 dark:border-neutral-800 bg-gray-50/50 dark:bg-neutral-900/50">
                        <span class="text-sm font-medium text-gray-500">Page {{ historyPage }} of {{ totalHistoryPages }}</span>
                        <div class="flex gap-2">
                            <button @click="historyPage--" :disabled="historyPage === 1" class="p-2 border border-gray-200 dark:border-neutral-700 rounded-xl hover:bg-white dark:hover:bg-neutral-800 disabled:opacity-50 transition-colors bg-white dark:bg-neutral-900">
                                <ChevronLeft class="w-4 h-4 text-gray-600 dark:text-gray-300"/>
                            </button>
                            <button @click="historyPage++" :disabled="historyPage === totalHistoryPages" class="p-2 border border-gray-200 dark:border-neutral-700 rounded-xl hover:bg-white dark:hover:bg-neutral-800 disabled:opacity-50 transition-colors bg-white dark:bg-neutral-900">
                                <ChevronRight class="w-4 h-4 text-gray-600 dark:text-gray-300"/>
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="p-12 text-center">
                    <p class="text-gray-500 dark:text-gray-400">No payout history available yet.</p>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
