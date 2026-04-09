<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { 
     Check, AlertCircle, Building, CalendarDays, ExternalLink, 
    RefreshCw, User as UserIcon,  
} from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import AppLayout from '@/layouts/AppLayout.vue';

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const props = defineProps<{
    organizerPayouts: any[];
    affiliatePayouts: any[];
    history: any[];
}>();

const processingId = ref<number | null>(null);
const currentTab = ref('organizers');

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('fr-TN', { style: 'currency', currency: 'TND' }).format(amount);
};

const handleOrganizerPayout = (id: number, stripeAccountId: string | null) => {
    if (!stripeAccountId) {
        toast.error('This organizer has not connected their Stripe account yet.');
        return;
    }

    if (confirm("Transfer funds to the organizer's Stripe account?")) {
        processingId.value = id;
        router.post(`/admin/financials/organizer/${id}/pay`, {}, {
            preserveScroll: true,
            onSuccess: () => toast.success('Stripe transfer completed successfully.'),
            onError: () => toast.error('Stripe transfer failed. Please try again.'),
            onFinish: () => processingId.value = null
        });
    }
};

const handleAffiliatePayout = (id: number) => {
    if (confirm("Confirm payout for this affiliate? This will update their balance.")) {
        processingId.value = id;
        router.post(`/admin/financials/affiliate/${id}/approve`, {}, {
            preserveScroll: true,
            onSuccess: () => toast.success('Affiliate payout approved successfully.'),
            onError: () => toast.error('Failed to approve affiliate payout.'),
            onFinish: () => processingId.value = null
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
                    <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">Admin Financials</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Manage payouts once events have concluded (1-day grace period).</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-4 border-b border-gray-200 dark:border-neutral-800 overflow-x-auto pb-px">
                <button 
                    @click="currentTab = 'organizers'" 
                    class="py-3 px-4 font-bold text-sm border-b-2 transition whitespace-nowrap"
                    :class="currentTab === 'organizers' ? 'border-indigo-600 text-indigo-600 font-black' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                >
                    Organizer Payouts ({{ organizerPayouts.length }})
                </button>
                <button 
                    @click="currentTab = 'affiliates'" 
                    class="py-3 px-4 font-bold text-sm border-b-2 transition whitespace-nowrap"
                    :class="currentTab === 'affiliates' ? 'border-indigo-600 text-indigo-600 font-black' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                >
                    Affiliate Payouts ({{ affiliatePayouts.length }})
                </button>
                <button 
                    @click="currentTab = 'history'" 
                    class="py-3 px-4 font-bold text-sm border-b-2 transition whitespace-nowrap"
                    :class="currentTab === 'history' ? 'border-indigo-600 text-indigo-600 font-black' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                >
                    Payout History ({{ history.length }})
                </button>
            </div>

            <!-- TAB: Organizer Payouts -->
            <div v-if="currentTab === 'organizers'" class="space-y-4">
                <div v-if="organizerPayouts.length > 0" class="grid gap-4">
                    <div v-for="item in organizerPayouts" :key="item.id" 
                        class="bg-white dark:bg-neutral-900 border border-gray-100 dark:border-neutral-800 rounded-3xl p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-sm hover:shadow-md transition">
                        
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <Building class="w-5 h-5 text-gray-400" />
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ item.titre }}</h3>
                            </div>
                            <div class="flex flex-wrap gap-4 text-sm text-gray-500">
                                <span class="flex items-center gap-1"><UserIcon class="w-4 h-4" /> {{ item.organisateur.name }}</span>
                                <span class="flex items-center gap-1"><CalendarDays class="w-4 h-4" /> Ended: {{ item.date_fin }}</span>
                            </div>
                            <div v-if="!item.organisateur.stripe_account_id" class="px-3 py-1 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 rounded-lg inline-flex items-center gap-2 text-xs font-semibold">
                                <AlertCircle class="w-4 h-4" /> No Stripe Account Connected
                            </div>
                        </div>

                        <div class="flex flex-col md:items-end gap-3 px-4 py-3 bg-gray-50 dark:bg-neutral-800/50 rounded-2xl border border-gray-100 dark:border-neutral-700 min-w-[200px]">
                            <div class="text-xs text-gray-500 uppercase font-black">Net Payout</div>
                            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
                                {{ formatCurrency(item.net_payout) }}
                            </div>
                            <button
                                @click="handleOrganizerPayout(item.id, item.organisateur.stripe_account_id)"
                                :disabled="processingId === item.id || !item.organisateur.stripe_account_id"
                                class="w-full mt-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold rounded-xl flex items-center justify-center gap-2 transition text-sm"
                            >
                                <RefreshCw v-if="processingId === item.id" class="w-4 h-4 animate-spin" />
                                <ExternalLink v-else class="w-4 h-4" />
                                Pay via Stripe
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Empty State -->
                <div v-else class="py-20 text-center bg-gray-50 dark:bg-neutral-800/30 rounded-3xl border-2 border-dashed border-gray-200 dark:border-neutral-800">
                    <p class="text-gray-500">No organizer payouts pending.</p>
                </div>
            </div>

            <!-- TAB: Affiliate Payouts -->
            <div v-if="currentTab === 'affiliates'" class="space-y-4">
                <div v-if="affiliatePayouts.length > 0" class="grid gap-4">
                    <div v-for="item in affiliatePayouts" :key="item.id" 
                        class="bg-white dark:bg-neutral-900 border border-gray-100 dark:border-neutral-800 rounded-3xl p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-sm">
                        
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <UserIcon class="w-5 h-5 text-indigo-500" />
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ item.reseller_name }}</h3>
                            </div>
                            <div class="text-sm text-gray-500">
                                For event: <span class="font-bold text-gray-700 dark:text-gray-300">{{ item.event_title }}</span>
                            </div>
                            <div class="text-xs text-gray-400">Event ended: {{ item.date_fin }}</div>
                        </div>

                        <div class="flex flex-col md:items-end gap-3 px-4 py-3 bg-gray-50 dark:bg-neutral-800/50 rounded-2xl border border-gray-100 dark:border-neutral-700 min-w-[200px]">
                            <div class="text-xs text-gray-500 uppercase font-black">Commission</div>
                            <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400">
                                {{ formatCurrency(item.amount) }}
                            </div>
                            <button
                                @click="handleAffiliatePayout(item.id)"
                                :disabled="processingId === item.id"
                                class="w-full mt-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold rounded-xl flex items-center justify-center gap-2 transition text-sm"
                            >
                                <Check v-if="processingId !== item.id" class="w-4 h-4" />
                                {{ processingId === item.id ? 'Processing...' : 'Approve & Payout' }}
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="py-20 text-center bg-gray-50 dark:bg-neutral-800/30 rounded-3xl border-2 border-dashed border-gray-200 dark:border-neutral-800">
                    <p class="text-gray-500">No affiliate payouts pending.</p>
                </div>
            </div>

            <!-- TAB: History -->
            <div v-if="currentTab === 'history'" class="bg-white dark:bg-neutral-900 rounded-3xl border border-gray-100 dark:border-neutral-800 shadow-sm overflow-hidden">
                <div v-if="history.length > 0" class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase tracking-widest bg-gray-50 dark:bg-neutral-800/50 border-b border-gray-100 dark:border-neutral-800">
                            <tr>
                                <th class="px-6 py-4">Type</th>
                                <th class="px-6 py-4">Recipient</th>
                                <th class="px-6 py-4">Event</th>
                                <th class="px-6 py-4">Amount</th>
                                <th class="px-6 py-4">Date Paid</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-neutral-800/50">
                            <tr v-for="(log, idx) in history" :key="idx" class="hover:bg-gray-50/50 dark:hover:bg-neutral-800/30">
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] uppercase font-black tracking-tighter" 
                                        :class="log.type === 'Organizer' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700'">
                                        {{ log.type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">{{ log.recipient }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ log.event }}</td>
                                <td class="px-6 py-4 font-black text-emerald-600 dark:text-emerald-400">{{ formatCurrency(log.amount) }}</td>
                                <td class="px-6 py-4 text-gray-400 text-xs">{{ log.date }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="p-12 text-center text-gray-500">
                    No payout history found.
                </div>
            </div>

        </div>
    </AppLayout>
</template>
