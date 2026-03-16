<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { Bell, CalendarPlus, CalendarX, Pencil, Trash2, Users, UserCheck, UserX, Check, CheckCheck, Ticket } from 'lucide-vue-next';
import { ref, onMounted, computed } from 'vue';
import Badge from '@/components/ui/badge/Badge.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface Notification {
    id: number;
    message: string;
    type: string;
    date_envoi: string;
    lu: boolean;
    created_at: string;
    updated_at: string;
}



const props = defineProps<{
    userId?: number;
}>();

const notifications = ref<Notification[]>([]);
const loading = ref(true);
const unreadNotificationsCount = ref(0);

const unreadCount = computed(() => unreadNotificationsCount.value);

const typeConfig: Record<string, { icon: any; color: string; bg: string }> = {
    evenement_cree: { icon: CalendarPlus, color: 'text-emerald-500', bg: 'bg-emerald-500/10' },
    evenement_modifie: { icon: Pencil, color: 'text-amber-500', bg: 'bg-amber-500/10' },
    evenement_supprime: { icon: Trash2, color: 'text-red-500', bg: 'bg-red-500/10' },
    reservation_cree: { icon: Ticket, color: 'text-teal-500', bg: 'bg-teal-500/10' },
    reservation_annulee: { icon: CalendarX, color: 'text-rose-500', bg: 'bg-rose-500/10' },
    invitation_collaboration: { icon: Users, color: 'text-blue-500', bg: 'bg-blue-500/10' },
    collaboration_acceptee: { icon: UserCheck, color: 'text-violet-500', bg: 'bg-violet-500/10' },
    collaboration_rejected: { icon: UserX, color: 'text-red-500', bg: 'bg-red-500/10' },
};

const getTypeConfig = (type: string) => {
    return typeConfig[type] || { icon: Bell, color: 'text-neutral-500', bg: 'bg-neutral-500/10' };
};

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

const fetchNotifications = async () => {
    try {
        const response = await axios.get('/web-api/notifications');
        notifications.value = response.data;
        unreadNotificationsCount.value = notifications.value.filter(n => !n.lu).length;
    } catch (error) {
        console.error('Error fetching notifications:', error);
    } finally {
        loading.value = false;
    }
};

const markAsRead = async (notification: Notification) => {
    if (notification.lu) return;
    try {
        await axios.patch(`/web-api/notifications/${notification.id}/read`);
        notification.lu = true;
        if (unreadNotificationsCount.value > 0) {
            unreadNotificationsCount.value--;
        }
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.patch('/web-api/notifications/read-all');
        notifications.value.forEach(n => n.lu = true);
        unreadNotificationsCount.value = 0;
    } catch (error) {
        console.error('Error marking all as read:', error);
    }
};

onMounted(() => {
    fetchNotifications();

    if (window.Echo && props.userId) {
        window.Echo.private(`notifications.${props.userId}`)
            .listen('.notification.sent', (e: { notification: Notification }) => {
                notifications.value.unshift(e.notification);
                unreadNotificationsCount.value++;
                // Keep only top 20 or similar if needed for the dropdown
                if (notifications.value.length > 20) {
                    notifications.value.pop();
                }
            });
    }
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium border border-neutral-200 bg-white shadow-sm hover:bg-neutral-100 hover:text-neutral-900 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:bg-neutral-800 dark:hover:text-neutral-50 h-10 w-10 relative focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-neutral-950">
            <Bell class="h-5 w-5 text-neutral-700 dark:text-neutral-300 pointer-events-none" />
            <Badge 
                v-if="unreadCount > 0" 
                variant="destructive" 
                class="absolute -top-1 -right-1 h-5 w-5 p-0 flex items-center justify-center text-[10px] rounded-full border-2 border-white dark:border-neutral-900 pointer-events-none"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </Badge>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-96 p-0 overflow-hidden rounded-2xl shadow-2xl border-neutral-200 dark:border-neutral-800">
            <DropdownMenuLabel class="p-4 flex items-center justify-between bg-neutral-50/50 dark:bg-neutral-900/50">
                <span class="text-base font-bold">Notifications</span>
                <div class="flex items-center gap-3">
                    <button 
                        v-if="unreadCount > 0"
                        @click.stop="markAllAsRead"
                        class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium flex items-center gap-1 transition-colors"
                    >
                        <CheckCheck class="h-3.5 w-3.5" />
                        Mark all read
                    </button>
                    <Link 
                        href="/notifications" 
                        class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
                    >
                        See all
                    </Link>
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator class="m-0" />
            
            <div class="max-h-[420px] overflow-y-auto">
                <!-- Loading skeleton -->
                <template v-if="loading">
                    <div v-for="i in 4" :key="i" class="p-4 flex items-start gap-3 animate-pulse">
                        <div class="h-9 w-9 rounded-xl bg-neutral-200 dark:bg-neutral-800 flex-shrink-0"></div>
                        <div class="flex-1 space-y-2 pt-0.5">
                            <div class="h-3 w-3/4 bg-neutral-200 dark:bg-neutral-800 rounded"></div>
                            <div class="h-2 w-1/3 bg-neutral-100 dark:bg-neutral-900 rounded"></div>
                        </div>
                    </div>
                </template>

                <!-- Notification items -->
                <template v-else-if="notifications.length > 0">
                    <DropdownMenuItem 
                        v-for="notif in notifications" 
                        :key="notif.id"
                        as-child
                        class="p-0 focus:bg-neutral-50 dark:focus:bg-neutral-900/80 cursor-pointer border-b border-neutral-100 dark:border-neutral-800/50 last:border-0"
                    >
                        <button 
                            @click="markAsRead(notif)" 
                            class="w-full p-4 flex items-start gap-3 text-left transition-all duration-200 hover:bg-neutral-50 dark:hover:bg-neutral-800/50"
                            :class="{ 'bg-blue-50/40 dark:bg-blue-950/20': !notif.lu }"
                        >
                            <!-- Type icon -->
                            <div 
                                class="flex-shrink-0 h-9 w-9 rounded-xl flex items-center justify-center transition-colors"
                                :class="getTypeConfig(notif.type).bg"
                            >
                                <component 
                                    :is="getTypeConfig(notif.type).icon" 
                                    class="h-4.5 w-4.5"
                                    :class="getTypeConfig(notif.type).color"
                                />
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <p 
                                    class="text-sm leading-snug"
                                    :class="notif.lu 
                                        ? 'text-neutral-600 dark:text-neutral-400' 
                                        : 'text-neutral-900 dark:text-neutral-100 font-semibold'"
                                >
                                    {{ notif.message }}
                                </p>
                                <span class="text-[11px] mt-1 block" :class="notif.lu ? 'text-neutral-400 dark:text-neutral-600' : 'text-blue-500 dark:text-blue-400 font-medium'">
                                    {{ timeAgo(notif.date_envoi) }}
                                </span>
                            </div>

                            <!-- Unread dot -->
                            <div v-if="!notif.lu" class="flex-shrink-0 mt-2">
                                <div class="h-2.5 w-2.5 rounded-full bg-blue-500 shadow-sm shadow-blue-500/50"></div>
                            </div>
                            <div v-else class="flex-shrink-0 mt-2">
                                <Check class="h-3.5 w-3.5 text-neutral-300 dark:text-neutral-700" />
                            </div>
                        </button>
                    </DropdownMenuItem>
                </template>

                <!-- Empty state -->
                <div v-else class="p-10 text-center">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-neutral-100 dark:bg-neutral-800 text-neutral-400 mb-3">
                        <Bell class="w-7 h-7" />
                    </div>
                    <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">No notifications yet</p>
                    <p class="text-xs text-neutral-400 dark:text-neutral-600 mt-1">You're all caught up!</p>
                </div>
            </div>

            <DropdownMenuSeparator class="m-0" />
            <div class="p-3 text-center bg-neutral-50/30 dark:bg-neutral-900/30">
                <Link 
                    href="/notifications" 
                    class="text-xs font-bold text-neutral-900 dark:text-white hover:underline uppercase tracking-wider"
                >
                    View All Notifications
                </Link>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
