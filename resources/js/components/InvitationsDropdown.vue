<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { UserPlus, Calendar, Clock, Check, X } from 'lucide-vue-next';
import { computed } from 'vue';
import Badge from '@/components/ui/badge/Badge.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const page = usePage();
const auth = computed(() => page.props.auth as any);

const pendingInvitations = computed(() => auth.value?.pending_invitations || []);
const pendingCount = computed(() => pendingInvitations.value.length);

const timeAgo = (dateStr: string) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);

    if (diffSec < 60) return 'Just now';
    if (diffMin < 60) return `${diffMin}m ago`;
    if (diffHour < 24) return `${diffHour}h ago`;
    if (diffDay < 7) return `${diffDay}d ago`;
    return date.toLocaleDateString();
};
</script>

<template>
    <DropdownMenu v-if="auth.user && auth.user.role === 'organisateur'">
        <DropdownMenuTrigger class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium border border-neutral-200 bg-white shadow-sm hover:bg-neutral-100 hover:text-neutral-900 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:bg-neutral-800 dark:hover:text-neutral-50 h-10 w-10 relative focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-neutral-950">
            <UserPlus class="h-5 w-5 text-neutral-700 dark:text-neutral-300 pointer-events-none" />
            <Badge 
                v-if="pendingCount > 0" 
                variant="destructive" 
                class="absolute -top-1 -right-1 h-5 w-5 p-0 flex items-center justify-center text-[10px] rounded-full border-2 border-white dark:border-neutral-900 pointer-events-none bg-blue-500 hover:bg-blue-600"
            >
                {{ pendingCount > 9 ? '9+' : pendingCount }}
            </Badge>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-96 p-0 overflow-hidden rounded-2xl shadow-2xl border-neutral-200 dark:border-neutral-800">
            <DropdownMenuLabel class="p-4 flex items-center justify-between bg-neutral-50/50 dark:bg-neutral-900/50">
                <span class="text-base font-bold text-blue-600 dark:text-blue-400">Collaboration Invites</span>
            </DropdownMenuLabel>
            <DropdownMenuSeparator class="m-0" />
            
            <div class="max-h-[420px] overflow-y-auto">
                <!-- Invitation items -->
                <template v-if="pendingCount > 0">
                    <DropdownMenuItem 
                        v-for="invitation in pendingInvitations" 
                        :key="invitation.id"
                        as-child
                        class="p-0 focus:bg-neutral-50 dark:focus:bg-neutral-900/80 cursor-pointer border-b border-neutral-100 dark:border-neutral-800/50 last:border-0"
                    >
                        <Link 
                            :href="`/events/${invitation.evenement_id}`"
                            class="w-full p-4 flex items-start gap-3 text-left transition-all duration-200 hover:bg-blue-50/50 dark:hover:bg-blue-900/20 bg-white dark:bg-neutral-950"
                        >
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center border border-blue-200 dark:border-blue-800 shadow-sm mt-1">
                                <UserPlus class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 leading-tight mb-1">
                                    {{ invitation.organisateur }} invited you
                                </p>
                                <p class="text-xs text-neutral-600 dark:text-neutral-400 mb-2 truncate">
                                    To co-organize: <span class="font-medium text-neutral-800 dark:text-neutral-200">{{ invitation.titre }}</span>
                                </p>
                                
                                <div class="flex items-center gap-4 text-[10px] text-neutral-500 font-medium">
                                    <div class="flex items-center gap-1">
                                        <Calendar class="w-3 h-3" />
                                        <span>{{ new Date(invitation.date_debut).toLocaleDateString() }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <Clock class="w-3 h-3" />
                                        <span>{{ timeAgo(invitation.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex-shrink-0 self-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <div class="h-6 w-6 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                                    <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400">View</span>
                                </div>
                            </div>
                        </Link>
                    </DropdownMenuItem>
                </template>

                <!-- Empty state -->
                <div v-else class="p-10 text-center">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-neutral-100 dark:bg-neutral-800 text-neutral-400 mb-3">
                        <UserPlus class="w-7 h-7" />
                    </div>
                    <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">No pending invitations</p>
                    <p class="text-xs text-neutral-400 dark:text-neutral-600 mt-1">When someone invites you to co-organize an event, it will appear here.</p>
                </div>
            </div>
            <DropdownMenuSeparator class="m-0" />
            <div class="p-3 text-center bg-neutral-50/30 dark:bg-neutral-900/30 flex justify-center">
               <span class="text-[11px] text-neutral-500 dark:text-neutral-400 bg-neutral-100 dark:bg-neutral-800 px-2 py-1 rounded-full flex items-center gap-1">
                   <Check class="w-3 h-3" />
                   Click an invite to review and accept
               </span>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
