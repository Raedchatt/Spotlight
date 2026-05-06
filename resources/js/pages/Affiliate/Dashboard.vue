<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { 
    Wallet, 
    TrendingUp, 
    Clock, 
    ShoppingCart, 
    MessageSquare, 
    Search,
    Filter,
    Calendar,
    User,
    ChevronRight,
    ArrowUpRight,
    Award
} from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import AffiliateGuide from './AffiliateGuide.vue';


const props = defineProps<{
    stats: {
        balance: number;
        total_earnings: number;
        pending_earnings: number;
        sales_count: number;
    };
    commissions: any[];
    referrals: any[];
    filters: {
        name?: string;
        category?: string;
    };
}>();

const nameFilter = ref(props.filters.name || '');
const categoryFilter = ref(props.filters.category || 'all');

const categories = [
    { value: 'all', label: 'All Categories' },
    { value: 'sportifs', label: 'Sports' },
    { value: 'culturels', label: 'Cultural' },
    { value: 'scientifiques', label: 'Scientific' },
    { value: 'musicaux', label: 'Music' },
    { value: 'commerciaux', label: 'Commercial' },
];

const applyFilters = () => {
    router.get('/affiliate/dashboard', {
        name: nameFilter.value,
        category: categoryFilter.value === 'all' ? null : categoryFilter.value
    }, {
        preserveState: true,
        replace: true
    });
};

watch([nameFilter, categoryFilter], () => {
    applyFilters();
}, { deep: true });

const initials = (name: string) =>
    name.trim().split(/\s+/).slice(0, 2).map(p => p[0]?.toUpperCase() ?? '').join('');

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(val);
};

const breadcrumbs = [{ title: 'Affiliate Dashboard', href: '/affiliate/dashboard' }];

const showGuideModal = ref(false);
</script>

<template>
    <Head title="Affiliate Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full px-4 py-8 md:px-8 space-y-8 max-w-[1600px] mx-auto">
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400 font-bold text-sm uppercase tracking-wider">
                        <Award class="w-4 h-4" />
                        Affiliate Program
                    </div>
                    <h1 class="text-4xl font-black text-slate-900 dark:text-white">Partner Hub</h1>
                    <p class="text-slate-500 dark:text-slate-400 max-w-md">Premium management for our top performers.</p>
                </div>

                <div class="w-full md:w-80 shrink-0">
                    <div class="p-5 rounded-2xl bg-indigo-50/50 dark:bg-indigo-900/10 border border-indigo-100/50 dark:border-indigo-500/10">
                        <h4 class="text-sm font-bold text-indigo-900 dark:text-indigo-300 mb-2">Need help?</h4>
                        <p class="text-xs text-indigo-700/70 dark:text-indigo-400/70 leading-relaxed mb-4">Check our affiliate guidelines to maximize your reach and earnings.</p>
                        <button @click="showGuideModal = true" class="w-full py-2.5 rounded-xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-500/20">
                            Affiliate Guide
                        </button>
                    </div>
                </div>
            </div>
            <!-- Stats Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 ">
                <!-- Total Earnings -->
                <div class="bg-white dark:bg-neutral-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-neutral-800 hover:shadow-lg transition-all duration-300 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform">
                            <TrendingUp class="w-6 h-6" />
                        </div>
                        <ArrowUpRight class="w-5 h-5 text-emerald-500 opacity-0 group-hover:opacity-100 transition-opacity" />
                    </div>
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Total Earnings</p>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-gray-900 dark:text-white">{{ formatCurrency(stats.total_earnings) }}</span>
                        <span class="text-sm font-bold text-gray-400">DT</span>
                    </div>
                </div>
                <!-- Pending -->
                <div class="bg-white dark:bg-neutral-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-neutral-800 hover:shadow-lg transition-all duration-300 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform">
                            <Clock class="w-6 h-6" />
                        </div>
                    </div>
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Pending Approval</p>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-gray-900 dark:text-white">{{ formatCurrency(stats.pending_earnings) }}</span>
                        <span class="text-sm font-bold text-gray-400">DT</span>
                    </div>
                </div>

                <!-- Sales Count -->
                <div class="bg-white dark:bg-neutral-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-neutral-800 hover:shadow-lg transition-all duration-300 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform">
                            <ShoppingCart class="w-6 h-6" />
                        </div>
                    </div>
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Total Sales</p>
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-black text-gray-900 dark:text-white">{{ stats.sales_count }}</span>
                        <span class="text-sm font-bold text-gray-400">Tickets</span>
                    </div>
                </div>
            </div>

            <!-- Main Content: Referrals and Recent Commissions -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                
                <!-- Referrals Table (2 Cols) -->
                <div class="xl:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-neutral-900 rounded-3xl border border-gray-100 dark:border-neutral-800 shadow-sm overflow-hidden flex flex-col">
                        <div class="px-6 py-6 border-b border-gray-100 dark:border-neutral-800 space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">My Referrals</h3>
                                <div class="px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold ring-1 ring-emerald-500/20">
                                    Confirmed Only
                                </div>
                            </div>
                            
                            <!-- Filters -->
                            <div class="flex flex-col sm:flex-row items-center gap-4">
                                <div class="relative w-full sm:w-64">
                                    <Search class="absolute left-3 top-3 w-4 h-4 text-gray-400" />
                                    <Input 
                                        v-model="nameFilter" 
                                        placeholder="Search by name..." 
                                        class="pl-10 h-10 rounded-xl"
                                    />
                                </div>
                                <div class="w-full sm:w-48">
                                    <select
                                        v-model="categoryFilter"
                                        class="w-full h-10 px-3 rounded-xl border border-gray-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 text-sm text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-shadow"
                                    >
                                        <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                                            {{ cat.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto min-h-[400px]">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-xs text-gray-400 uppercase tracking-widest font-semibold border-b border-gray-50 dark:border-neutral-800/50">
                                        <th class="px-6 py-4 text-left">Participant</th>
                                        <th class="px-6 py-4 text-left">Event & Category</th>
                                        <th class="px-6 py-4 text-left">Date</th>
                                        <th class="px-6 py-4 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-neutral-800/50">
                                    <tr v-if="referrals.length === 0">
                                        <td colspan="4" class="px-6 py-20 text-center">
                                            <div class="flex flex-col items-center justify-center text-gray-400">
                                                <Filter class="w-12 h-12 mb-4 opacity-20" />
                                                <p class="text-lg font-medium">No confirmed referrals found.</p>
                                                <p class="text-sm">Try adjusting your filters or promote more events!</p>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-for="referral in referrals" :key="referral.id" class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors group">
                                        <td class="px-6 py-5">
                                            <Link :href="`/participant/${referral.participant.id}`" class="flex items-center gap-3 hover:opacity-80 transition-opacity group/part">
                                                <div class="shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/40 dark:to-purple-900/40 flex items-center justify-center border border-indigo-50 dark:border-indigo-800/50 group-hover/part:scale-105 transition-transform">
                                                    <img v-if="referral.participant.avatar" :src="referral.participant.avatar" class="w-full h-full rounded-full object-cover" />
                                                    <span v-else class="text-xs font-bold text-indigo-700 dark:text-indigo-400">{{ initials(referral.participant.name) }}</span>
                                                </div>
                                                <span class="font-bold text-gray-900 dark:text-gray-100 group-hover/part:text-indigo-600 dark:group-hover/part:text-indigo-400">{{ referral.participant.name }}</span>
                                            </Link>
                                        </td>
                                        <td class="px-6 py-5">
                                            <div class="flex flex-col">
                                                <Link :href="`/events/${referral.event.id}`" class="font-bold text-gray-950 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">{{ referral.event.titre }}</Link>
                                                <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400 capitalize bg-indigo-50 dark:bg-indigo-900/20 px-2 py-0.5 rounded w-fit mt-1">{{ referral.event.categorie }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5">
                                            <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                                <Calendar class="w-3.5 h-3.5" />
                                                <span class="font-medium">{{ referral.date }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 text-center">
                                            <Link :href="`/messages/${referral.participant.id}`" class="inline-flex">
                                                <button class="flex items-center gap-2 px-4 py-2 rounded-xl bg-white dark:bg-neutral-800 border border-gray-100 dark:border-neutral-700 hover:bg-slate-900 dark:hover:bg-white hover:text-white dark:hover:text-black transition-all shadow-sm font-bold group/btn active:scale-95">
                                                    <MessageSquare class="w-4 h-4 transition-transform group-hover/btn:rotate-12" />
                                                    <span>Text</span>
                                                </button>
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Commissions (1 Col) -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-neutral-900 rounded-3xl border border-gray-100 dark:border-neutral-800 shadow-sm p-6 lg:p-8 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-8">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Earnings History</h3>
                            <Link href="#" class="text-xs font-bold text-indigo-600 hover:underline">View All</Link>
                        </div>

                        <div v-if="commissions.length === 0" class="flex-1 flex flex-col items-center justify-center text-center py-12 text-gray-400">
                            <ShoppingCart class="w-12 h-12 mb-4 opacity-10" />
                            <p class="font-medium">No earnings yet.</p>
                        </div>

                        <div class="flex flex-col gap-6">
                            <div v-for="comm in commissions" :key="comm.id" class="relative group">
                                <div class="flex items-start gap-4">
                                    <div :class="[
                                        'w-10 h-10 rounded-xl shrink-0 flex items-center justify-center transition-colors shadow-sm',
                                        comm.status === 'approved' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600' : 'bg-amber-50 dark:bg-amber-900/20 text-amber-600'
                                    ]">
                                        <Wallet class="w-5 h-5" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2 mb-1">
                                            <p class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate">{{ comm.evenement.titre }}</p>
                                            <span class="font-black text-gray-900 dark:text-white shrink-0">+{{ formatCurrency(comm.commission_revendeur) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <p class="text-xs text-gray-400 font-medium">{{ new Date(comm.created_at).toLocaleDateString('fr-FR') }}</p>
                                            <span :class="[
                                                'text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-tighter ring-1',
                                                comm.status === 'approved' ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 ring-emerald-500/20' : 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 ring-amber-500/20'
                                            ]">
                                                {{ comm.status }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        

                    </div>
                </div>

            </div>
        </div>
        <AffiliateGuide :is-open="showGuideModal" @close="showGuideModal = false" />
    </AppLayout>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
