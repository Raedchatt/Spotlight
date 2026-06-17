<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { Search, FilterX, Plus, X, Loader2, Ticket, Users, Calendar, Eye, Ban } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { toast } from 'vue-sonner';
import { useI18n } from 'vue-i18n';

interface ReservationUser {
    id: number;
    username: string;
    email: string;
}

interface ReservationEvent {
    id: number;
    titre: string;
    date_debut: string;
    lieu: string;
    is_tournoi: boolean;
    poster_url?: string;
}

interface ReservationData {
    id: number;
    user_id: number;
    evenement_id: number;
    ticket_type: string | null;
    nombre_tickets: number;
    statut: string;
    note: string | null;
    created_at: string;
    user: ReservationUser;
    evenement: ReservationEvent;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedReservations {
    data: ReservationData[];
    links: PaginationLink[];
    total: number;
}

const props = defineProps<{
    reservations?: PaginatedReservations;
    totalReservations?: number;
    filters?: {
        search?: string;
        event?: string;
        status?: string;
    };
}>();

// ── Filters ──
const search = ref(props.filters?.search || '');
const eventFilter = ref(props.filters?.event || '');
const statusFilter = ref(props.filters?.status || '');
const { t, locale } = useI18n();

const translatePagination = (label: string) => {
    if (label.toLowerCase().includes('previous')) return t('common.previous');
    if (label.toLowerCase().includes('next')) return t('common.next');
    return label;
};

let filterTimeout: ReturnType<typeof setTimeout> | null = null;
const applyFilters = () => {
    if (filterTimeout) clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get('/admin/reservations', {
            search: search.value || undefined,
            event: eventFilter.value || undefined,
            status: statusFilter.value || undefined,
        }, { preserveState: true, replace: true });
    }, 400);
};

watch([search, eventFilter, statusFilter], applyFilters);

const resetFilters = () => {
    search.value = '';
    eventFilter.value = '';
    statusFilter.value = '';
    router.get('/admin/reservations', {}, { preserveState: true, replace: true });
};

const hasActiveFilters = computed(() => search.value || eventFilter.value || statusFilter.value);

// ── Status helpers ──
const statusColor = (status: string) => {
    switch (status) {
        case 'confirmed': return 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800';
        case 'pending': return 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800';
        case 'cancelled': return 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800';
        default: return 'bg-gray-100 text-gray-700 border-gray-200';
    }
};

const ticketTypeLabel = (type: string | null) => {
    switch (type) {
        case 'participant': return t('events.ticketParticipant');
        case 'spectator': return t('events.ticketSpectator');
        default: return t('events.ticketStandard');
    }
};

const ticketTypeColor = (type: string | null) => {
    switch (type) {
        case 'participant': return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400';
        case 'spectator': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        default: return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString(locale.value, { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

// ── Create Reservation Modal ──
const showCreateModal = ref(false);
const createProcessing = ref(false);
const createErrors = ref<Record<string, string[]>>({});

// User search
const selectedUser = ref<any>(null);
const userSearchQuery = ref('');
const userSearchResults = ref<any[]>([]);
const isSearchingUsers = ref(false);
let userSearchTimeout: ReturnType<typeof setTimeout> | null = null;

const searchUsersForCreate = () => {
    if (userSearchTimeout) clearTimeout(userSearchTimeout);
    if (!userSearchQuery.value || userSearchQuery.value.length < 2) {
        userSearchResults.value = [];
        return;
    }
    userSearchTimeout = setTimeout(async () => {
        isSearchingUsers.value = true;
        try {
            const res = await axios.get(`/admin/reservations/search-users?q=${userSearchQuery.value}`);
            userSearchResults.value = res.data;
        } catch (e) {
            console.error('Failed to search users', e);
        } finally {
            isSearchingUsers.value = false;
        }
    }, 300);
};

const pickUser = (user: any) => {
    selectedUser.value = user;
    userSearchQuery.value = '';
    userSearchResults.value = [];
};

// Event search
const selectedEvent = ref<any>(null);
const eventSearchQuery = ref('');
const eventSearchResults = ref<any[]>([]);
const isSearchingEvents = ref(false);
let eventSearchTimeout: ReturnType<typeof setTimeout> | null = null;

const searchEventsForCreate = () => {
    if (eventSearchTimeout) clearTimeout(eventSearchTimeout);
    if (!eventSearchQuery.value || eventSearchQuery.value.length < 2) {
        eventSearchResults.value = [];
        return;
    }
    eventSearchTimeout = setTimeout(async () => {
        isSearchingEvents.value = true;
        try {
            const res = await axios.get(`/admin/reservations/search-events?q=${eventSearchQuery.value}`);
            eventSearchResults.value = res.data;
        } catch (e) {
            console.error('Failed to search events', e);
        } finally {
            isSearchingEvents.value = false;
        }
    }, 300);
};

const pickEvent = (event: any) => {
    selectedEvent.value = event;
    eventSearchQuery.value = '';
    eventSearchResults.value = [];
    // Reset ticket type to default
    newReservation.value.ticket_type = event.is_tournoi ? 'spectator' : 'standard';
};

const newReservation = ref({
    nombre_tickets: 1,
    ticket_type: 'standard',
});

const openCreateModal = () => {
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    selectedUser.value = null;
    selectedEvent.value = null;
    userSearchQuery.value = '';
    eventSearchQuery.value = '';
    userSearchResults.value = [];
    eventSearchResults.value = [];
    createErrors.value = {};
    newReservation.value = { nombre_tickets: 1, ticket_type: 'standard' };
};

const submitCreateReservation = () => {
    if (createProcessing.value) return;
    if (!selectedUser.value) {
        toast.error(t('events.selectParticipantError'));
        return;
    }
    if (!selectedEvent.value) {
        toast.error(t('events.selectEventError'));
        return;
    }

    createProcessing.value = true;
    createErrors.value = {};

    router.post('/admin/reservations', {
        user_identifier: String(selectedUser.value.id),
        evenement_id: selectedEvent.value.id,
        nombre_tickets: newReservation.value.nombre_tickets,
        ticket_type: newReservation.value.ticket_type,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(t('events.reservationCreatedSuccess', { username: selectedUser.value.username }));
            closeCreateModal();
        },
        onError: (errors) => {
            createErrors.value = {};
            Object.keys(errors).forEach(key => {
                createErrors.value[key] = [errors[key]];
            });
        },
        onFinish: () => {
            createProcessing.value = false;
        }
    });
};

// ── Cancel Reservation ──
const cancellingId = ref<number | null>(null);

const cancelReservation = (reservation: ReservationData) => {
    if (!confirm(t('events.confirmCancelAdminReservation', { id: reservation.id, username: reservation.user?.username }))) return;

    cancellingId.value = reservation.id;
    router.patch(`/admin/reservations/${reservation.id}/cancel`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(t('events.reservationCancelledSuccess', { id: reservation.id }));
        },
        onError: () => {
            toast.error(t('events.reservationCancelledError'));
        },
        onFinish: () => {
            cancellingId.value = null;
        }
    });
};
</script>

<template>
    <Head :title="t('events.reservationsManagement')" />

    <AppLayout>
        <div class="px-4 py-8 md:px-8 space-y-8 max-w-[1400px] mx-auto w-full min-w-0">

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div class="space-y-1">
                    <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">{{ t('events.reservations') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('events.reservationsDesc') }}</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-2xl shadow-lg shadow-indigo-500/20 transition-all active:scale-95"
                >
                    <Plus class="w-5 h-5" />
                    {{ t('events.addReservation') }}
                </button>
            </div>

            <!-- Filters -->
            <div class="relative z-20 bg-white/70 dark:bg-neutral-900/70 backdrop-blur-sm p-5 md:p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-neutral-800 grid grid-cols-1 md:grid-cols-12 gap-4 items-end w-full">
                <div class="md:col-span-4 relative w-full space-y-1.5 min-w-0">
                    <label class="text-[10px] uppercase font-bold text-gray-400 ps-1">{{ t('events.participantLabel2') }}</label>
                    <div class="relative">
                        <Search class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="t('events.searchParticipant')"
                            class="w-full ps-9 pe-4 py-2.5 bg-gray-50/50 dark:bg-neutral-800/50 border border-gray-200 dark:border-neutral-700 rounded-xl focus:ring-2 focus:ring-indigo-500 dark:text-white text-sm"
                        >
                    </div>
                </div>
                <div class="md:col-span-4 relative w-full space-y-1.5 min-w-0">
                    <label class="text-[10px] uppercase font-bold text-gray-400 ps-1">{{ t('events.event') }}</label>
                    <div class="relative w-full">
                        <Ticket class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input
                            v-model="eventFilter"
                            type="text"
                            :placeholder="t('events.filterByEventTitle')"
                            class="w-full ps-9 pe-4 py-2.5 bg-gray-50/50 dark:bg-neutral-800/50 border border-gray-200 dark:border-neutral-700 rounded-xl focus:ring-2 focus:ring-indigo-500 dark:text-white text-sm"
                        >
                    </div>
                </div>
                <div class="md:col-span-3 space-y-1.5">
                    <label class="text-[10px] uppercase font-bold text-gray-400 ps-1">{{ t('events.statusCol') }}</label>
                    <select
                        v-model="statusFilter"
                        class="w-full px-4 py-2.5 bg-gray-50/50 dark:bg-neutral-800/50 border border-gray-200 dark:border-neutral-700 rounded-xl focus:ring-2 focus:ring-indigo-500 dark:text-white text-sm"
                    >
                        <option value="">{{ t('events.allStatuses') }}</option>
                        <option value="confirmed">{{ t('events.statusConfirmed') }}</option>
                        <option value="pending">{{ t('events.statusPending') }}</option>
                        <option value="cancelled">{{ t('events.statusCancelled') }}</option>
                    </select>
                </div>
                <div class="md:col-span-1 flex items-center justify-start md:justify-end">
                    <button v-if="hasActiveFilters" @click="resetFilters" class="w-full md:w-auto p-2.5 flex items-center justify-center text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl hover:bg-gray-200 dark:hover:bg-neutral-700 transition-colors" :title="t('common.clear')">
                        <FilterX class="w-5 h-5 mr-2 md:mr-0" />
                        <span class="md:hidden font-bold text-sm">{{ t('common.clear') }}</span>
                    </button>
                </div>
            </div>

            <!-- Stats Summary -->
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-neutral-900 border border-gray-100 dark:border-neutral-800 rounded-2xl p-4 md:p-5 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">{{ t('events.totalReservations') }}</p>
                    <p class="text-xl md:text-2xl font-extrabold text-gray-900 dark:text-white">{{ totalReservations || 0 }}</p>
                </div>
                <div class="bg-white dark:bg-neutral-900 border border-gray-100 dark:border-neutral-800 rounded-2xl p-4 md:p-5 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">{{ t('events.onThisPage') }}</p>
                    <p class="text-xl md:text-2xl font-extrabold text-gray-900 dark:text-white">{{ reservations?.data?.length || 0 }}</p>
                </div>
                <div class="hidden lg:block bg-white dark:bg-neutral-900 border border-gray-100 dark:border-neutral-800 rounded-2xl p-5 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">{{ t('events.activeFilters') }}</p>
                    <p class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ [search, eventFilter, statusFilter].filter(Boolean).length }}</p>
                </div>
            </div>

            <!-- Reservations Table -->
            <div class="bg-white dark:bg-neutral-900 border border-gray-100 dark:border-neutral-800 rounded-3xl shadow-sm overflow-hidden w-full">
                <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-neutral-800 w-full">
                    <table class="w-full min-w-[1000px]">
                        <thead>
                            <tr class="bg-gray-50/80 dark:bg-neutral-800/50 border-b border-gray-100 dark:border-neutral-800">
                                <th class="text-start text-[11px] font-bold text-gray-400 uppercase tracking-widest px-6 py-4">{{ t('events.id') }}</th>
                                <th class="text-start text-[11px] font-bold text-gray-400 uppercase tracking-widest px-6 py-4">{{ t('events.participantLabel2') }}</th>
                                <th class="text-start text-[11px] font-bold text-gray-400 uppercase tracking-widest px-6 py-4">{{ t('events.event') }}</th>
                                <th class="text-start text-[11px] font-bold text-gray-400 uppercase tracking-widest px-6 py-4">{{ t('events.type') }}</th>
                                <th class="text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest px-6 py-4">{{ t('events.ticketsLabel') }}</th>
                                <th class="text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest px-6 py-4">{{ t('events.statusCol') }}</th>
                                <th class="text-start text-[11px] font-bold text-gray-400 uppercase tracking-widest px-6 py-4">{{ t('events.date') }}</th>
                                <th class="text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest px-6 py-4">{{ t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-neutral-800">
                            <tr v-for="r in reservations?.data" :key="r.id" class="hover:bg-gray-50/50 dark:hover:bg-neutral-800/30 transition-colors">
                                <td class="px-6 py-4 text-sm font-mono text-gray-400 dark:text-gray-500">#{{ r.id }}</td>
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ r.user?.username || t('events.na') }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ r.user?.email || '' }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <Link :href="`/events/${r.evenement?.id}`" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline truncate block max-w-[200px]">
                                        {{ r.evenement?.titre || t('events.na') }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="[ticketTypeColor(r.ticket_type), 'text-xs font-bold px-2.5 py-1 rounded-lg']">
                                        {{ ticketTypeLabel(r.ticket_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ r.nombre_tickets }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="[statusColor(r.statut), 'text-xs font-bold px-3 py-1 rounded-full border capitalize']">
                                        {{ t(`events.status_${r.statut}`) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ formatDate(r.created_at) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link :href="`/events/${r.evenement?.id}`" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors">
                                            <Eye class="w-3.5 h-3.5" />
                                            {{ t('common.view') }}
                                        </Link>
                                        <button
                                            v-if="r.statut !== 'cancelled' && new Date(r.evenement?.date_debut) > new Date()"
                                            @click="cancelReservation(r)"
                                            :disabled="cancellingId === r.id"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors disabled:opacity-50"
                                        >
                                            <Loader2 v-if="cancellingId === r.id" class="w-3.5 h-3.5 animate-spin" />
                                            <Ban v-else class="w-3.5 h-3.5" />
                                            {{ t('common.cancel') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!reservations?.data?.length">
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-16 h-16 bg-gray-100 dark:bg-neutral-800 rounded-2xl flex items-center justify-center">
                                            <Ticket class="w-8 h-8 text-gray-300 dark:text-gray-600" />
                                        </div>
                                        <p class="font-bold text-gray-500 dark:text-gray-400">{{ t('events.noReservationsFound') }}</p>
                                        <button v-if="hasActiveFilters" @click="resetFilters" class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">{{ t('events.clearAllFilters') }}</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="reservations?.links && reservations.links.length > 3" class="flex justify-center mt-6 pb-20">
                <div class="flex gap-2 flex-wrap justify-center">
                    <template v-for="(link, p) in reservations.links" :key="p">
                        <div v-if="link.url === null" class="px-3 sm:px-4 py-2 border border-gray-200 dark:border-neutral-700 rounded-xl text-xs sm:text-sm font-medium opacity-50 cursor-not-allowed bg-gray-50 dark:bg-neutral-800 text-gray-500 dark:text-gray-400 whitespace-nowrap" v-html="translatePagination(link.label)"></div>
                        <Link v-else :href="link.url" class="px-3 sm:px-4 py-2 border rounded-xl text-xs sm:text-sm font-medium transition whitespace-nowrap" :class="[link.active ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-500/20' : 'bg-white dark:bg-neutral-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-neutral-700 hover:bg-gray-50 dark:hover:bg-neutral-700']" v-html="translatePagination(link.label)"></Link>
                    </template>
                </div>
            </div>
        </div>

        <!-- ── Create Reservation Modal ── -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showCreateModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeCreateModal"></div>

                    <div class="relative bg-white dark:bg-neutral-900 rounded-3xl shadow-2xl border border-gray-100 dark:border-neutral-800 w-full max-w-xl max-h-[90vh] flex flex-col">
                        <!-- Header -->
                        <div class="flex-shrink-0 flex items-center justify-between px-6 sm:px-8 py-4 sm:py-5 bg-white dark:bg-neutral-900 border-b border-gray-100 dark:border-neutral-800 rounded-t-3xl">
                            <div>
                                <h2 class="text-lg sm:text-xl font-extrabold text-gray-900 dark:text-white">{{ t('events.addReservation') }}</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ t('events.addReservationDesc') }}</p>
                            </div>
                            <button @click="closeCreateModal" class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-neutral-800 transition-colors">
                                <X class="w-5 h-5 text-gray-500" />
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="flex-1 overflow-y-auto p-6 sm:p-8 space-y-6">
                            <!-- Select Participant -->
                            <div class="space-y-3">
                                <label class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <Users class="w-4 h-4 text-indigo-500" />
                                    {{ t('events.selectParticipantReq') }}
                                </label>
                                <div v-if="selectedUser" class="flex items-center justify-between p-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-2xl">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold">{{ selectedUser.username?.charAt(0)?.toUpperCase() }}</div>
                                        <div class="overflow-hidden">
                                            <p class="font-bold text-gray-900 dark:text-white truncate">{{ selectedUser.username }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ selectedUser.email }}</p>
                                        </div>
                                    </div>
                                    <button @click="selectedUser = null" class="flex-shrink-0 p-1.5 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/20 text-red-500 transition-colors">
                                        <X class="w-4 h-4" />
                                    </button>
                                </div>
                                <div class="relative">
                                    <Users class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                    <input
                                        v-model="userSearchQuery"
                                        type="text"
                                        :placeholder="t('events.searchByUsernameOrEmail')"
                                        @input="searchUsersForCreate"
                                        class="w-full ps-10 pe-4 py-2.5 bg-gray-50/50 dark:bg-neutral-800/50 border border-gray-200 dark:border-neutral-700 rounded-xl focus:ring-2 focus:ring-indigo-500 dark:text-white text-sm"
                                    >
                                    <div v-if="isSearchingUsers" class="absolute end-3 top-1/2 -translate-y-1/2">
                                        <Loader2 class="w-4 h-4 text-gray-400 animate-spin" />
                                    </div>
                                    <div v-if="userSearchResults.length > 0" class="absolute z-50 w-full mt-1 bg-white dark:bg-neutral-800 border border-gray-100 dark:border-neutral-700 rounded-2xl shadow-xl max-h-48 overflow-y-auto">
                                        <div
                                            v-for="u in userSearchResults"
                                            :key="u.id"
                                            @click="pickUser(u)"
                                            class="px-4 py-3 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer flex flex-col border-b border-gray-50 dark:border-neutral-700 last:border-0"
                                        >
                                            <span class="font-bold text-gray-900 dark:text-white text-sm truncate">{{ u.username }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ u.email }}</span>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="createErrors.user_identifier" class="text-xs text-red-500 font-medium">{{ createErrors.user_identifier[0] }}</p>
                            </div>

                            <hr class="border-gray-100 dark:border-neutral-800" />

                            <!-- Select Event -->
                            <div class="space-y-3">
                                <label class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <Calendar class="w-4 h-4 text-indigo-500" />
                                    {{ t('events.selectEventReq') }}
                                </label>
                                <div v-if="selectedEvent" class="flex items-center justify-between p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-2xl">
                                    <div class="flex items-center gap-3 overflow-hidden">
                                        <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-xs">
                                            {{ selectedEvent.is_tournoi ? '🏆' : '🎪' }}
                                        </div>
                                        <div class="overflow-hidden">
                                            <p class="font-bold text-gray-900 dark:text-white truncate">{{ selectedEvent.titre }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {{ selectedEvent.is_tournoi ? t('events.tournament') : t('events.event') }}
                                                · {{ t('events.capacityLabel') }}: {{ selectedEvent.capacite_spectateur }} {{ t('events.seats') }}
                                            </p>
                                        </div>
                                    </div>
                                    <button @click="selectedEvent = null" class="flex-shrink-0 p-1.5 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/20 text-red-500 transition-colors">
                                        <X class="w-4 h-4" />
                                    </button>
                                </div>
                                <div class="relative">
                                    <Calendar class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                    <input
                                        v-model="eventSearchQuery"
                                        type="text"
                                        :placeholder="t('events.searchByEventTitle')"
                                        @input="searchEventsForCreate"
                                        class="w-full ps-10 pe-4 py-2.5 bg-gray-50/50 dark:bg-neutral-800/50 border border-gray-200 dark:border-neutral-700 rounded-xl focus:ring-2 focus:ring-indigo-500 dark:text-white text-sm"
                                    >
                                    <div v-if="isSearchingEvents" class="absolute end-3 top-1/2 -translate-y-1/2">
                                        <Loader2 class="w-4 h-4 text-gray-400 animate-spin" />
                                    </div>
                                    <div v-if="eventSearchResults.length > 0" class="absolute z-50 w-full mt-1 bg-white dark:bg-neutral-800 border border-gray-100 dark:border-neutral-700 rounded-2xl shadow-xl max-h-48 overflow-y-auto">
                                        <div
                                            v-for="ev in eventSearchResults"
                                            :key="ev.id"
                                            @click="pickEvent(ev)"
                                            class="px-4 py-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 cursor-pointer flex items-center gap-3 border-b border-gray-50 dark:border-neutral-700 last:border-0"
                                        >
                                            <span class="text-lg flex-shrink-0">{{ ev.is_tournoi ? '🏆' : '🎪' }}</span>
                                            <div class="flex flex-col overflow-hidden">
                                                <span class="font-bold text-gray-900 dark:text-white text-sm truncate">{{ ev.titre }}</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                    {{ ev.is_tournoi ? t('events.tournament') : t('events.event') }} · {{ ev.capacite_spectateur }} {{ t('events.seats') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="createErrors.evenement_id" class="text-xs text-red-500 font-medium">{{ createErrors.evenement_id[0] }}</p>
                            </div>

                            <hr class="border-gray-100 dark:border-neutral-800" />

                            <!-- Ticket Type (if tournament) -->
                            <div v-if="selectedEvent?.is_tournoi" class="space-y-2">
                                <label class="text-sm font-bold text-gray-900 dark:text-white">{{ t('events.ticketTypeReq') }}</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <button
                                        type="button"
                                        @click="newReservation.ticket_type = 'spectator'"
                                        :class="[
                                            'p-4 border-2 rounded-2xl text-center transition-all',
                                            newReservation.ticket_type === 'spectator'
                                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                                : 'border-gray-200 dark:border-neutral-700 hover:border-blue-300'
                                        ]"
                                    >
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">👀 {{ t('events.spectator') }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ t('events.watchTournamentTicket') }}</p>
                                    </button>
                                    <button
                                        type="button"
                                        @click="newReservation.ticket_type = 'participant'"
                                        :class="[
                                            'p-4 border-2 rounded-2xl text-center transition-all',
                                            newReservation.ticket_type === 'participant'
                                                ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                                : 'border-gray-200 dark:border-neutral-700 hover:border-purple-300'
                                        ]"
                                    >
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">🎮 {{ t('events.participantLabel') }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ t('events.competeTournament') }}</p>
                                    </button>
                                </div>
                            </div>

                            <!-- Number of Tickets -->
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-gray-900 dark:text-white">{{ t('events.numberOfTicketsReq') }}</label>
                                <input
                                    v-model.number="newReservation.nombre_tickets"
                                    type="number"
                                    min="1"
                                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-neutral-700 rounded-xl bg-white dark:bg-neutral-800 text-sm dark:text-white focus:ring-2 focus:ring-indigo-500"
                                />
                                <p v-if="createErrors.nombre_tickets" class="text-xs text-red-500 font-medium">{{ createErrors.nombre_tickets[0] }}</p>
                            </div>

                            <!-- Info Banner -->
                            <div class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800 rounded-2xl p-4 flex gap-3 items-start">
                                <span class="text-lg">⚡</span>
                                <div>
                                    <p class="text-sm font-bold text-amber-900 dark:text-amber-300">{{ t('events.paymentSkipped') }}</p>
                                    <p class="text-xs text-amber-700 dark:text-amber-400">{{ t('events.paymentSkippedDesc') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex-shrink-0 flex items-center justify-end gap-3 px-6 sm:px-8 py-4 sm:py-5 bg-gray-50 dark:bg-neutral-800/50 border-t border-gray-100 dark:border-neutral-800 rounded-b-3xl">
                            <button @click="closeCreateModal" class="px-5 py-2.5 text-sm font-bold text-gray-700 dark:text-gray-300 bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl hover:bg-gray-50 dark:hover:bg-neutral-700 transition-colors">
                                {{ t('common.cancel') }}
                            </button>
                            <button
                                @click="submitCreateReservation"
                                :disabled="createProcessing"
                                class="px-6 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 rounded-xl shadow-lg shadow-indigo-500/20 transition-all active:scale-95 flex items-center gap-2"
                            >
                                <Loader2 v-if="createProcessing" class="w-4 h-4 animate-spin" />
                                <Plus v-else class="w-4 h-4" />
                                {{ createProcessing ? t('events.creating') : t('events.createReservation') }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active {
    transition: all 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
    opacity: 0;
}
.modal-enter-from > div:last-child, .modal-leave-to > div:last-child {
    transform: scale(0.95) translateY(10px);
}
</style>
