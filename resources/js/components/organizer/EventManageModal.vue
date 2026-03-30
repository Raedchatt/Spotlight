<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { 
    Users, 
    Ticket, 
    TrendingUp, 
    Mail, 
    Phone, 
    ExternalLink, 
    Pencil,
    Loader2,
    Calendar,
    MapPin,
    Trophy,
    Shield,
    ShieldCheck,
    ShieldAlert,
    Ban,
    UsersRound,
    Settings2,
    Check
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
    DropdownMenuCheckboxItem
} from '@/components/ui/dropdown-menu';
import { Link, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import CancelEventButton from '@/components/organizer/CancelEventButton.vue';

const props = defineProps<{
    eventId: number | null;
    open: boolean;
}>();

const emit = defineEmits(['update:open']);

const page = usePage();
const auth = computed(() => page.props.auth as any);

const loading = ref(true);
const stats = ref<any>(null);
const reservations = ref<any[]>([]);
const error = ref<string | null>(null);

const isOwner = computed(() => stats.value?.event?.organisateur_id === auth.value?.user?.id);

const filteredCollaborators = computed(() => {
    if (!stats.value?.collaborators) return [];
    if (isOwner.value) return stats.value.collaborators;
    return stats.value.collaborators.filter((c: any) => c.statut === 'accepted');
});

const fetchData = async () => {
    if (!props.eventId) return;
    
    loading.value = true;
    error.value = null;
    try {
        const [statsRes, resRes] = await Promise.all([
            axios.get(`/web-api/events/${props.eventId}/management-stats`),
            axios.get(`/web-api/events/${props.eventId}/reservations`)
        ]);
        
        stats.value = statsRes.data;
        reservations.value = resRes.data.reservations;
    } catch (err: any) {
        console.error('Error fetching event data:', err);
        error.value = err.response?.data?.message || 'Failed to load event data.';
    } finally {
        loading.value = false;
    }
};

const togglePermission = async (collaboratorId: number, permission: string, currentValue: boolean) => {
    if (!props.eventId) return;
    try {
        await axios.patch(`/web-api/events/${props.eventId}/collaborators/${collaboratorId}/toggle-permission`, {
            permission,
            value: !currentValue
        });
        
        // Optimistic UI update
        const collab = stats.value.collaborators.find((c: any) => c.id === collaboratorId);
        if (collab) {
            collab[permission] = !currentValue;
        }
        toast.success('Permission updated.');
    } catch (err: any) {
        toast.error('Failed to update permission.');
    }
};

watch(() => props.open, (newVal) => {
    if (newVal && props.eventId) {
        fetchData();
    }
});

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'confirmed': return 'default';
        case 'pending': return 'outline';
        case 'cancelled': return 'destructive';
        default: return 'secondary';
    }
};

</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="w-full max-w-full sm:max-w-[95vw] lg:max-w-[1200px] h-[100dvh] sm:h-[90vh] overflow-y-auto p-0 border-none bg-zinc-50 dark:bg-zinc-950 shadow-2xl transition-all duration-300">
            <div v-if="loading" class="flex flex-col items-center justify-center py-24 space-y-4">
                <Loader2 class="w-10 h-10 animate-spin text-blue-600" />
                <p class="text-muted-foreground font-medium">Loading event intelligence...</p>
            </div>

            <div v-else-if="error" class="p-12 text-center space-y-4">
                <div class="mx-auto w-12 h-12 bg-red-100 rounded-full flex items-center justify-center text-red-600">
                    <TrendingUp class="w-6 h-6 rotate-180" />
                </div>
                <h3 class="text-xl font-bold">Something went wrong</h3>
                <p class="text-muted-foreground">{{ error }}</p>
                <Button @click="fetchData" variant="outline">Try Again</Button>
            </div>

            <div v-else-if="stats" class="animate-in fade-in slide-in-from-bottom-4 duration-500">
                <!-- Header Section with Pro Glassmorphism -->
                <div class="bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md p-4 sm:p-8 border-b border-zinc-200 dark:border-zinc-800 sticky top-0 z-10">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <Badge variant="outline" class="bg-blue-50 text-blue-600 border-blue-100 uppercase tracking-widest text-[10px] font-bold">
                                    {{ stats.event.is_tournoi ? 'Tournament' : 'Standard Event' }}
                                </Badge>
                                <span class="text-muted-foreground text-xs font-medium">ID: #{{ stats.event.id }}</span>
                            </div>
                            <h2 class="text-xl sm:text-3xl font-black text-foreground tracking-tight leading-none truncate max-w-[250px] sm:max-w-none">{{ stats.event.titre }}</h2>
                        </div>
                        <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
                            <Link :href="`/events/${stats.event.id}`">
                                <Button variant="outline" size="sm" class="font-bold border-zinc-200">
                                    <ExternalLink class="w-4 h-4 mr-2" />
                                    Public View
                                </Button>
                            </Link>
                            <Link v-if="stats.user_permissions.can_edit" :href="`/dashboard/events/${stats.event.id}/edit`">
                                <Button size="sm" class="bg-blue-600 hover:bg-blue-700 font-bold">
                                    <Pencil class="w-4 h-4 mr-2" />
                                    Edit Event
                                </Button>
                            </Link>

                            <!-- Cancel Event (Permissions-based) -->
                            <CancelEventButton 
                                v-if="stats.user_permissions.can_cancel && stats.event.statut !== 'annule'"
                                :event-id="stats.event.id"
                                :event-title="stats.event.titre"
                                @cancelled="fetchData"
                            />
                        </div>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="p-4 sm:p-8 space-y-8">
                    <!-- Stats Grid - 4 columns on desktop, 1 on mobile -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                        <!-- Revenue Card -->
                        <Card class="border-none shadow-sm bg-gradient-to-br from-indigo-600 to-blue-700 text-white overflow-hidden relative group">
                            <div class="absolute -right-4 -top-4 opacity-10 group-hover:scale-125 transition-transform duration-500">
                                <TrendingUp class="w-32 h-32" />
                            </div>
                            <CardHeader class="pb-2">
                                <CardTitle class="text-xs uppercase tracking-widest opacity-80 font-black">Total Revenue</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="text-4xl font-black">{{ stats.stats.total_revenue.toLocaleString() }} <span class="text-xl font-bold opacity-80">TND</span></div>
                                <p class="text-xs mt-2 opacity-70 font-medium">Based on confirmed tickets</p>
                            </CardContent>
                        </Card>

                        <!-- Standard Stats (Non-Tournament) -->
                        <template v-if="!stats.event.is_tournoi">
                            <Card class="border-none shadow-sm overflow-hidden bg-white dark:bg-zinc-900">
                                <CardHeader class="pb-2 border-b">
                                    <CardTitle class="text-[10px] uppercase tracking-widest font-black text-muted-foreground flex items-center gap-2">
                                        <Users class="w-4 h-4 text-blue-600" />
                                        Reservations
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="pt-4">
                                    <div class="text-3xl font-black text-foreground">{{ stats.stats.total_reserved }} / {{ stats.stats.capacity }}</div>
                                    <div class="w-full bg-zinc-100 dark:bg-zinc-800 h-2 rounded-full mt-3 overflow-hidden">
                                        <div 
                                            class="bg-blue-600 h-full rounded-full transition-all duration-1000" 
                                            :style="{ width: (stats.stats.total_reserved / stats.stats.capacity * 100) + '%' }"
                                        ></div>
                                    </div>
                                    <p class="text-[10px] text-muted-foreground mt-2 font-bold uppercase tracking-wide">{{ stats.stats.remaining }} spots remaining</p>
                                </CardContent>
                            </Card>
                        </template>

                        <!-- Tournament Stats -->
                        <template v-else>
                            <Card class="border-none shadow-sm overflow-hidden bg-white dark:bg-zinc-900">
                                <CardHeader class="pb-2 border-b">
                                    <CardTitle class="text-[10px] uppercase tracking-widest font-black text-muted-foreground flex items-center gap-2">
                                        <Trophy class="w-4 h-4 text-amber-500" />
                                        Participants
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="pt-4">
                                    <div class="text-3xl font-black text-foreground">{{ stats.stats.participant_reserved }} / {{ stats.stats.participant_capacity }}</div>
                                    <div class="w-full bg-zinc-100 dark:bg-zinc-800 h-2 rounded-full mt-3 overflow-hidden">
                                        <div 
                                            class="bg-amber-500 h-full rounded-full transition-all duration-1000" 
                                            :style="{ width: (stats.stats.participant_reserved / stats.stats.participant_capacity * 100) + '%' }"
                                        ></div>
                                    </div>
                                    <p class="text-[10px] text-muted-foreground mt-2 font-bold uppercase tracking-wide">{{ stats.stats.participant_remaining }} players remaining</p>
                                </CardContent>
                            </Card>

                            <Card class="border-none shadow-sm overflow-hidden bg-white dark:bg-zinc-900">
                                <CardHeader class="pb-2 border-b">
                                    <CardTitle class="text-[10px] uppercase tracking-widest font-black text-muted-foreground flex items-center gap-2">
                                        <Users class="w-4 h-4 text-blue-600" />
                                        Spectators
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="pt-4">
                                    <div class="text-3xl font-black text-foreground">{{ stats.stats.spectator_reserved }} / {{ stats.stats.spectator_capacity }}</div>
                                    <div class="w-full bg-zinc-100 dark:bg-zinc-800 h-2 rounded-full mt-3 overflow-hidden">
                                        <div 
                                            class="bg-blue-600 h-full rounded-full transition-all duration-1000" 
                                            :style="{ width: (stats.stats.spectator_reserved / stats.stats.spectator_capacity * 100) + '%' }"
                                        ></div>
                                    </div>
                                    <p class="text-[10px] text-muted-foreground mt-2 font-bold uppercase tracking-wide">{{ stats.stats.spectator_remaining }} spots remaining</p>
                                </CardContent>
                            </Card>
                        </template>
                    </div>

                    <!-- Participants Table -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2 space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-bold flex items-center gap-2">
                                    <Users class="w-5 h-5 text-indigo-600" />
                                    Participants List
                                </h3>
                                <Badge variant="secondary" class="font-bold border-none">{{ reservations.length }} entries</Badge>
                            </div>
                            
                            <div class="rounded-xl border border-zinc-100 dark:border-zinc-800 overflow-hidden bg-white dark:bg-zinc-900 shadow-sm relative overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-zinc-50 dark:bg-zinc-800/50 text-[10px] font-black uppercase tracking-widest text-muted-foreground">
                                            <th class="px-6 py-4 border-b whitespace-nowrap text-xs">User</th>
                                            <th class="px-6 py-4 border-b whitespace-nowrap text-xs">Contact</th>
                                            <th class="px-6 py-4 border-b whitespace-nowrap text-xs">Tickets</th>
                                            <th class="px-6 py-4 border-b whitespace-nowrap text-xs">Date</th>
                                            <th class="px-6 py-4 border-b whitespace-nowrap text-xs text-right">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border">
                                        <tr v-for="res in reservations" :key="res.id" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-600 flex items-center justify-center font-bold text-sm">
                                                        {{ res.user.username.charAt(0).toUpperCase() }}
                                                    </div>
                                                    <div class="space-y-0.5">
                                                        <div class="font-bold text-sm text-foreground">{{ res.user.username }}</div>
                                                        <Badge v-if="res.ticket_type" variant="outline" class="text-[9px] uppercase tracking-wide font-black border-zinc-200">
                                                            {{ res.ticket_type }}
                                                        </Badge>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="space-y-1">
                                                    <div class="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                                                        <Mail class="w-3.5 h-3.5" />
                                                        {{ res.user.email }}
                                                    </div>
                                                    <div class="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                                                        <Phone class="w-3.5 h-3.5" />
                                                        {{ res.user.telephone || 'N/A' }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-1.5 font-bold text-sm">
                                                    <Ticket class="w-4 h-4 text-blue-600" />
                                                    {{ res.nombre_tickets }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-xs font-semibold text-muted-foreground">{{ formatDate(res.created_at) }}</div>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <Badge :variant="getStatusBadge(res.statut)" class="capitalize font-bold text-[10px]">
                                                    {{ res.statut }}
                                                </Badge>
                                            </td>
                                        </tr>
                                        <tr v-if="reservations.length === 0">
                                            <td colspan="5" class="px-6 py-12 text-center text-muted-foreground font-medium italic">
                                                No reservations found for this event.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Management Team (Collaborators) -->
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl overflow-hidden shadow-sm border border-border">
                        <div class="p-6 border-b flex items-center justify-between">
                            <h3 class="text-lg font-black text-foreground flex items-center gap-2">
                                <Users class="w-5 h-5 text-indigo-600" />
                                {{ isOwner ? 'Management Team' : 'Team' }}
                            </h3>
                        </div>
                        
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Owner -->
                            <div class="flex items-center gap-4 p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-800">
                                <div class="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center font-bold text-lg">
                                    {{ isOwner ? 'Me' : 'O' }}
                                </div>
                                <div class="flex-1 space-y-0.5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-foreground">Event Owner</span>
                                        <Badge class="bg-indigo-600 text-[9px] uppercase font-black tracking-tighter">Owner</Badge>
                                    </div>
                                    <p class="text-xs text-muted-foreground">Main event creator and administrator.</p>
                                </div>
                            </div>

                            <!-- Collaborators -->
                            <div v-for="collab in filteredCollaborators" :key="collab.id" 
                                class="flex items-center gap-4 p-4 rounded-xl border border-zinc-100 dark:border-zinc-800 hover:border-indigo-200 dark:hover:border-indigo-900 transition-all relative group">
                                <div class="w-12 h-12 rounded-full bg-zinc-100 dark:bg-zinc-800 text-muted-foreground flex items-center justify-center font-bold text-lg relative overflow-hidden">
                                     {{ collab.user.username.charAt(0).toUpperCase() }}
                                     <div v-if="collab.can_edit || collab.can_cancel" class="absolute bottom-0 right-0 p-0.5 bg-indigo-600 text-white">
                                         <ShieldCheck class="w-2.5 h-2.5" />
                                     </div>
                                </div>
                                <div class="flex-1 space-y-1">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-foreground">{{ collab.user.username }}</span>
                                            <Badge :variant="collab.statut === 'accepted' ? 'default' : 'outline'" class="text-[9px] uppercase font-black tracking-tighter">
                                                {{ collab.statut }}
                                            </Badge>
                                        </div>
                                        
                                        <!-- Permissions Manager (Owner Only) -->
                                        <DropdownMenu v-if="isOwner && collab.statut === 'accepted'">
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon" class="h-8 w-8 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <Settings2 class="w-4 h-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" class="w-56">
                                                <DropdownMenuLabel>Collaborator Permissions</DropdownMenuLabel>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuCheckboxItem 
                                                    :checked="collab.can_edit"
                                                    @update:checked="togglePermission(collab.id, 'can_edit', collab.can_edit)"
                                                >
                                                    <Pencil class="w-4 h-4 mr-2" /> Can Edit Event
                                                </DropdownMenuCheckboxItem>
                                                <DropdownMenuCheckboxItem 
                                                    :checked="collab.can_cancel"
                                                    @update:checked="togglePermission(collab.id, 'can_cancel', collab.can_cancel)"
                                                >
                                                    <Ban class="w-4 h-4 mr-2" /> Can Cancel Event
                                                </DropdownMenuCheckboxItem>
                                                <DropdownMenuCheckboxItem 
                                                    :checked="collab.can_manage_team"
                                                    @update:checked="togglePermission(collab.id, 'can_manage_team', collab.can_manage_team)"
                                                >
                                                    <UsersRound class="w-4 h-4 mr-2" /> Can Manage Team
                                                </DropdownMenuCheckboxItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                    
                                    <div class="flex flex-wrap gap-1.5 mt-1">
                                        <div v-if="collab.can_edit" class="flex items-center gap-1 text-[8px] font-black uppercase text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 px-1.5 py-0.5 rounded">
                                            <Pencil class="w-2 h-2" /> Editor
                                        </div>
                                        <div v-if="collab.can_cancel" class="flex items-center gap-1 text-[8px] font-black uppercase text-red-600 bg-red-50 dark:bg-red-900/20 px-1.5 py-0.5 rounded">
                                            <Ban class="w-2 h-2" /> Cancellable
                                        </div>
                                         <div v-if="collab.can_manage_team" class="flex items-center gap-1 text-[8px] font-black uppercase text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 px-1.5 py-0.5 rounded">
                                            <UsersRound class="w-2 h-2" /> HR Manager
                                        </div>
                                    </div>
                                    <p class="text-[10px] text-muted-foreground">{{ collab.user.email }}</p>
                                </div>
                            </div>

                            <div v-if="filteredCollaborators.length === 0 && isOwner" class="md:col-span-2 py-4 text-center text-muted-foreground text-sm italic">
                                No co-organizers have been invited to this event yet.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
