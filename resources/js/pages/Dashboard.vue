<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Eye, RefreshCw, TrendingUp, Clock, Wallet } from 'lucide-vue-next';

const breadcrumbs = [{ title: 'Dashboard', href: '/dashboard' }];

const page = usePage();
const { t } = useI18n();
const auth = computed(() => page.props.auth as any);
const dashboardData = computed(() => (page.props as any).dashboardData as DashboardData | null);

// ── Types ──────────────────────────────────────────────────────────────────
interface CategoryBreakdown {
    category: string;
    count: number;
    percent: number;
}
interface EventRow {
    id: number;
    titre: string;
    categorie: string;
    lieu: string;
    statut: string;
    revenue: number;
}
interface Participant {
    id: number;
    username: string;
    avatar: string | null;
    event_count: number;
}
interface DashboardData {
    totalReceived: number;
    pendingPayout: number;
    categoryBreakdown: CategoryBreakdown[];
    currentEvents: EventRow[];
    activeParticipants: Participant[];
    totalEvents: number;
}

// ── Category filter ────────────────────────────────────────────────────────
const activeCategory = ref<string>('all');
const filteredEvents = computed<EventRow[]>(() => {
    if (!dashboardData.value) return [];
    const events = dashboardData.value.currentEvents;
    if (activeCategory.value === 'all') return events;
    return events.filter(e => e.categorie === activeCategory.value);
});

const allCategories = computed<string[]>(() => {
    if (!dashboardData.value) return [];
    return [...new Set(dashboardData.value.currentEvents.map(e => e.categorie))];
});

// ── Donut chart ────────────────────────────────────────────────────────────
const CHART_COLORS = ['#6366f1', '#ec4899', '#f59e0b', '#10b981', '#3b82f6'];

const donutSegments = computed(() => {
    if (!dashboardData.value || dashboardData.value.categoryBreakdown.length === 0) return [];
    const R = 52;
    const circumference = 2 * Math.PI * R;
    let cumulative = 0;
    return dashboardData.value.categoryBreakdown.map((cat, i) => {
        const frac = cat.count / dashboardData.value!.totalEvents;
        const dashLen = frac * circumference;
        const gapLen = circumference - dashLen;
        const dashOffset = -(cumulative * circumference / dashboardData.value!.totalEvents);
        const seg = {
            color: CHART_COLORS[i % CHART_COLORS.length],
            dashLen,
            gapLen,
            dashOffset,
            ...cat,
        };
        cumulative += cat.count;
        return seg;
    });
});

// ── Avatar ─────────────────────────────────────────────────────────────────
const initials = (name: string) =>
    name.trim().split(/\s+/).slice(0, 2).map(p => p[0]?.toUpperCase() ?? '').join('');

</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>

        <!-- ── Organizer Dashboard ─────────────────────────────────────────── -->
        <div v-if="dashboardData" class="w-full px-4 py-8 md:px-8 space-y-8 max-w-[1600px] mx-auto">
            
            <!-- Hero Banner -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-800 text-white p-8 md:p-10 shadow-xl shadow-indigo-500/20">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-10 -mb-10 w-48 h-48 bg-purple-500/20 rounded-full blur-2xl"></div>
                
                <div class="relative z-10">
                    <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight mb-2">{{ t('dashboard.welcomeBack') }}</h1>
                    <p class="text-indigo-100 text-lg md:text-xl font-medium max-w-xl">
                        {{ t('dashboard.activeEventsMsg', { count: dashboardData.totalEvents }) }}
                    </p>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════
                 ROW 1 — Category chart + KPI cards
            ═══════════════════════════════════════════════════════════════ -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

                <!-- KPI Cards (Takes 1 Column) ─────────────────────── -->
                <div class="xl:col-span-1 flex flex-col gap-6">
                    <!-- Total Received -->
                    <div class="flex-1 relative group rounded-3xl p-8 overflow-hidden bg-white dark:bg-neutral-900 shadow-sm border border-gray-100 dark:border-neutral-800 hover:shadow-xl hover:shadow-emerald-500/10 transition-all duration-300">
                        <div class="absolute top-0 right-0 -mt-16 -mr-16 w-48 h-48 bg-emerald-50 dark:bg-emerald-900/10 rounded-full blur-3xl transition-transform group-hover:scale-110"></div>
                        <div class="relative z-10 flex items-start justify-between w-full mb-8">
                            <div class="w-16 h-16 rounded-2xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                <Wallet class="w-8 h-8 transition-transform group-hover:scale-110 duration-300" />
                            </div>
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 text-xs font-semibold">
                                <TrendingUp class="w-3.5 h-3.5" /> All Time
                            </span>
                        </div>
                        <div class="relative z-10">
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1 tracking-wide uppercase">{{ t('dashboard.receivedRevenue') }}</p>
                            <div class="flex items-baseline gap-2">
                                <p class="text-3xl lg:text-4xl xl:text-5xl font-black tracking-tight text-gray-900 dark:text-white truncate">
                                    {{ dashboardData.totalReceived.toLocaleString('fr-FR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) }}
                                </p>
                                <span class="text-xl font-bold text-gray-400 dark:text-gray-500">Dt</span>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1 italic">{{ t('dashboard.revenueNote') }}</p>
                        </div>
                    </div>

                    <!-- Pending Payout -->
                    <div class="flex-1 relative group rounded-3xl p-8 overflow-hidden bg-white dark:bg-neutral-900 shadow-sm border border-gray-100 dark:border-neutral-800 hover:shadow-xl hover:shadow-amber-500/10 transition-all duration-300">
                        <div class="absolute top-0 right-0 -mt-16 -mr-16 w-48 h-48 bg-amber-50 dark:bg-amber-900/10 rounded-full blur-3xl transition-transform group-hover:scale-110"></div>
                        <div class="relative z-10 flex items-start justify-between w-full mb-8">
                            <div class="w-16 h-16 rounded-2xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                                <Clock class="w-8 h-8 transition-transform group-hover:-rotate-12 duration-300" />
                            </div>
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 text-xs font-semibold">
                                Pending
                            </span>
                        </div>
                        <div class="relative z-10">
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1 tracking-wide uppercase">{{ t('dashboard.pendingPayout') }}</p>
                            <div class="flex items-baseline gap-2">
                                <p class="text-3xl lg:text-4xl xl:text-5xl font-black tracking-tight text-gray-900 dark:text-white truncate">
                                    {{ dashboardData.pendingPayout.toLocaleString('fr-FR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) }}
                                </p>
                                <span class="text-xl font-bold text-gray-400 dark:text-gray-500">Dt</span>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1 italic">{{ t('dashboard.pendingNote') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Most Used Category (Donut Chart) Takes 2 Columns ─────────────────────────────────────── -->
                <div class="xl:col-span-2 bg-white dark:bg-neutral-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-neutral-800 flex flex-col justify-between h-full">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white mb-6">{{ t('dashboard.categoryBreakdown') }}</h3>

                        <div v-if="dashboardData.categoryBreakdown.length > 0" class="flex flex-col items-center">
                            <!-- Donut -->
                            <div class="relative mb-6">
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-2xl font-black text-gray-900 dark:text-white">{{ dashboardData.totalEvents }}</span>
                                    <span class="text-xs font-semibold text-gray-500">{{ t('common.events') }}</span>
                                </div>
                                <svg width="180" height="180" viewBox="0 0 140 140" class="drop-shadow-md">
                                    <!-- Background ring -->
                                    <circle cx="70" cy="70" r="52" fill="none" class="stroke-gray-100 dark:stroke-neutral-800" stroke-width="14" />
                                    <!-- Segments -->
                                    <circle
                                        v-for="(seg, i) in donutSegments"
                                        :key="i"
                                        cx="70" cy="70" r="52"
                                        fill="none"
                                        :stroke="seg.color"
                                        stroke-width="14"
                                        stroke-linecap="round"
                                        :stroke-dasharray="`${seg.dashLen} ${seg.gapLen}`"
                                        :stroke-dashoffset="seg.dashOffset"
                                        style="transform: rotate(-90deg); transform-origin: 70px 70px; transition: stroke-dasharray 1s ease-out, stroke-dashoffset 1s ease-out;"
                                    />
                                </svg>
                            </div>

                            <!-- Legend row -->
                            <div class="w-full space-y-3 px-2">
                                <div
                                    v-for="(cat, i) in dashboardData.categoryBreakdown"
                                    :key="cat.category"
                                    class="flex items-center justify-between w-full p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-neutral-800/50 transition-colors"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="w-3 h-3 rounded-full shadow-sm" :style="{ backgroundColor: CHART_COLORS[i % CHART_COLORS.length] }"></span>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200 capitalize w-24 truncate">{{ cat.category }}</span>
                                    </div>
                                    <span class="text-xs font-bold bg-gray-100 dark:bg-neutral-800 text-gray-700 dark:text-gray-300 px-2 py-1 rounded-md">{{ cat.percent }}%</span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="flex flex-col items-center justify-center py-16 text-gray-400 h-full">
                            <div class="w-16 h-16 rounded-full bg-gray-50 dark:bg-neutral-800 flex items-center justify-center mb-3">
                                <TrendingUp class="w-8 h-8 opacity-50" />
                            </div>
                            <p class="text-sm font-medium">{{ t('dashboard.noEventsYet') }}</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ═══════════════════════════════════════════════════════════════
                 ROW 2 — Current Events & Participants Split
            ═══════════════════════════════════════════════════════════════ -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Current Events Table (2 Cols) ─────────────────────────────────── -->
                <div class="lg:col-span-2 bg-white dark:bg-neutral-900 rounded-3xl border border-gray-100 dark:border-neutral-800 shadow-sm overflow-hidden flex flex-col">
                    <!-- Header -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-6 py-5 border-b border-gray-100 dark:border-neutral-800 gap-4">
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ t('dashboard.currentEvents') }}</h3>
                            <span class="flex h-2.5 w-2.5 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                            </span>
                        </div>
                        <!-- Pills + refresh -->
                        <div class="flex items-center gap-2 flex-wrap pb-1 sm:pb-0 overflow-x-auto scrollbar-hide w-full sm:w-auto">
                            <button
                                v-for="cat in allCategories"
                                :key="cat"
                                @click="activeCategory = activeCategory === cat ? 'all' : cat"
                                :class="[
                                    'px-4 py-1.5 rounded-full text-xs font-bold capitalize transition-all whitespace-nowrap',
                                    activeCategory === cat
                                        ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20'
                                        : 'bg-gray-50 dark:bg-neutral-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-neutral-700'
                                ]"
                            >
                                {{ cat }}
                            </button>
                            <Link href="/dashboard" class="ml-auto sm:ml-2 shrink-0">
                                <button class="w-8 h-8 rounded-full bg-gray-50 dark:bg-neutral-800 flex items-center justify-center hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-600 transition-all hover:rotate-180 duration-500">
                                    <RefreshCw class="w-4 h-4 text-gray-500 hover:text-indigo-600" />
                                </button>
                            </Link>
                        </div>
                    </div>

                    <!-- Table List -->
                    <div class="overflow-x-auto flex-1 p-2">
                        <table class="w-full text-sm border-separate border-spacing-y-2">
                            <thead>
                                <tr class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-widest font-semibold">
                                    <th class="px-6 py-3 text-left font-semibold">{{ t('dashboard.eventName') }}</th>
                                    <th class="px-6 py-3 text-left font-semibold">{{ t('dashboard.category') }}</th>
                                    <th class="px-6 py-3 text-left font-semibold whitespace-nowrap">{{ t('dashboard.revenue') }}</th>
                                    <th class="px-6 py-3 text-center font-semibold">{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="filteredEvents.length === 0">
                                    <td colspan="4" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center gap-3">
                                            <div class="w-12 h-12 rounded-full bg-gray-50 dark:bg-neutral-800/50 flex items-center justify-center">
                                                <Clock class="w-6 h-6 text-gray-400" />
                                            </div>
                                            <span class="text-gray-500">{{ t('dashboard.noEventsActive') }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr
                                    v-for="event in filteredEvents"
                                    :key="event.id"
                                    class="group bg-transparent hover:bg-gray-50/80 dark:hover:bg-neutral-800/50 transition-colors"
                                >
                                    <td class="px-6 py-4 rounded-l-2xl">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-gray-900 dark:text-gray-100 text-base truncate max-w-[250px]">{{ event.titre }}</span>
                                            <span class="text-xs text-gray-500 truncate max-w-[250px] flex items-center gap-1 mt-1">
                                                <div class="w-1.5 h-1.5 rounded-full bg-gray-300"></div> {{ event.lieu }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold capitalize bg-gray-100 dark:bg-neutral-800 text-gray-700 dark:text-gray-300">
                                            {{ event.categorie === 'autre' && event.categorie_autre ? event.categorie_autre : event.categorie }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                            {{ event.revenue.toLocaleString('fr-FR') }} <span class="text-gray-400">DT</span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center rounded-r-2xl">
                                        <Link :href="`/events/${event.id}`" class="inline-block">
                                            <button class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-white dark:bg-neutral-800 shadow-sm border border-gray-100 dark:border-neutral-700 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-all duration-300">
                                                <Eye class="w-4 h-4 text-gray-500 hover:text-white transition-colors" />
                                            </button>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Active Participants (1 Col) ────────────────────────────────────── -->
                <div class="lg:col-span-1 bg-white dark:bg-neutral-900 rounded-3xl border border-gray-100 dark:border-neutral-800 shadow-sm p-6 lg:p-8 flex flex-col">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ t('dashboard.activeUsers') }}</h3>
                        <span class="text-xs font-bold text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 px-2.5 py-1 rounded-full">{{ t('dashboard.top', { count: dashboardData.activeParticipants.length }) }}</span>
                    </div>

                    <div v-if="dashboardData.activeParticipants.length === 0" class="flex-1 flex flex-col items-center justify-center text-center py-8 text-gray-400">
                        <div class="w-16 h-16 rounded-full bg-gray-50 dark:bg-neutral-800 flex items-center justify-center mb-3">
                            <Clock class="w-8 h-8 opacity-50" />
                        </div>
                        <p class="text-sm font-medium">{{ t('dashboard.noRegistrations') }}</p>
                    </div>

                    <div v-else class="flex flex-col gap-4 overflow-y-auto max-h-[400px] pr-2 scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-neutral-700"
                    >
                        <div
                            v-for="(participant, index) in dashboardData.activeParticipants"
                            :key="participant.id"
                            class="flex items-center p-3 rounded-2xl hover:bg-gray-50 dark:hover:bg-neutral-800/50 transition-colors group"
                        >
                            <!-- Rank -->
                            <div class="w-6 text-xs font-bold text-gray-400 group-hover:text-indigo-500 transition-colors">
                                #{{ index + 1 }}
                            </div>

                            <!-- Avatar -->
                            <div class="relative w-11 h-11 shrink-0 ml-1">
                                <img v-if="participant.avatar" :src="participant.avatar" :alt="participant.username" class="w-full h-full rounded-full object-cover shadow-sm" />
                                <div v-else class="w-full h-full rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/40 dark:to-purple-900/40 text-indigo-700 dark:text-indigo-300 font-bold flex items-center justify-center text-sm shadow-sm border border-indigo-50 dark:border-indigo-900/50">
                                    {{ initials(participant.username) }}
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="ml-4 min-w-0 flex-1">
                                <p class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ participant.username }}</p>
                                <p class="text-xs text-gray-500 font-medium">
                                    {{ participant.event_count }} <span class="text-gray-400">{{ t('common.tickets') }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
