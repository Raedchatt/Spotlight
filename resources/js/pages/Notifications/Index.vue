<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { toast } from 'vue-sonner';
import {
    Bell,
    CalendarPlus,
    CalendarX,
    Pencil,
    Trash2,
    Users,
    UserCheck,
    UserX,
    Check,
    CheckCheck,
    Inbox,
    Ticket,
} from 'lucide-vue-next';
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';

const page = usePage();
const authUser = computed(() => page.props.auth?.user as any);

const { t, locale: currentLocale } = useI18n();

const breadcrumbs = computed(() => [
    { title: t('events.dashboardNav'), href: '/dashboard' },
    { title: t('notifications.title'), href: '/notifications' },
]);

interface Notification {
    id: number;
    message: string;
    type: string;
    date_envoi: string;
    lu: boolean;
    created_at: string;
    updated_at: string;
}

const notifications = ref<Notification[]>([]);
const loading = ref(true);
const activeFilter = ref<'all' | 'unread' | 'read'>('all');

const unreadCount = computed(() => notifications.value.filter(n => !n.lu).length);

const filteredNotifications = computed(() => {
    if (activeFilter.value === 'unread') return notifications.value.filter(n => !n.lu);
    if (activeFilter.value === 'read') return notifications.value.filter(n => n.lu);
    return notifications.value;
});

const typeConfig: Record<string, { icon: any; color: string; bg: string; labelKey: string }> = {
    evenement_cree: { icon: CalendarPlus, color: 'text-emerald-500', bg: 'bg-emerald-500/10', labelKey: 'notifications.type_evenement_cree' },
    evenement_modifie: { icon: Pencil, color: 'text-amber-500', bg: 'bg-amber-500/10', labelKey: 'notifications.type_evenement_modifie' },
    evenement_supprime: { icon: Trash2, color: 'text-red-500', bg: 'bg-red-500/10', labelKey: 'notifications.type_evenement_supprime' },
    reservation_cree: { icon: Ticket, color: 'text-teal-500', bg: 'bg-teal-500/10', labelKey: 'notifications.type_reservation_cree' },
    reservation_annulee: { icon: CalendarX, color: 'text-rose-500', bg: 'bg-rose-500/10', labelKey: 'notifications.type_reservation_annulee' },
    invitation_collaboration: { icon: Users, color: 'text-blue-500', bg: 'bg-blue-500/10', labelKey: 'notifications.type_invitation_collaboration' },
    collaboration_acceptee: { icon: UserCheck, color: 'text-violet-500', bg: 'bg-violet-500/10', labelKey: 'notifications.type_collaboration_acceptee' },
    collaboration_rejected: { icon: UserX, color: 'text-red-500', bg: 'bg-red-500/10', labelKey: 'notifications.type_collaboration_rejected' },
};

const getTypeConfig = (type: string) => {
    return typeConfig[type] || { icon: Bell, color: 'text-neutral-500', bg: 'bg-neutral-500/10', labelKey: 'notifications.type_default' };
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

    if (diffSec < 60) return t('notifications.justNow');
    if (diffMin < 60) return t('notifications.minutesAgo', { n: diffMin });
    if (diffHour < 24) return t('notifications.hoursAgo', { n: diffHour });
    if (diffDay < 7) return t('notifications.daysAgo', { n: diffDay });
    return date.toLocaleDateString(currentLocale.value, { month: 'short', day: 'numeric', year: 'numeric' });
};

const formatDate = (dateStr: string) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString(currentLocale.value, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const fetchNotifications = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/web-api/notifications');
        notifications.value = response.data;
    } catch (error) {
        console.error('Error fetching notifications:', error);
        toast.error(t('notifications.failedToLoad'));
    } finally {
        loading.value = false;
    }
};

const markAsRead = async (notification: Notification) => {
    if (notification.lu) return;
    try {
        await axios.patch(`/web-api/notifications/${notification.id}/read`);
        notification.lu = true;
    } catch (error) {
        console.error('Error marking notification as read:', error);
        toast.error(t('notifications.failedToMarkRead'));
    }
};



const markAllAsRead = async () => {
    try {
        await axios.patch('/web-api/notifications/read-all');
        notifications.value.forEach(n => n.lu = true);
        toast.success(t('notifications.allMarkedRead'));
    } catch (error) {
        console.error('Error marking all as read:', error);
        toast.error(t('notifications.failedToMarkAllRead'));
    }
};

onMounted(() => {
    fetchNotifications();

    if (window.Echo && authUser.value?.id) {
        window.Echo.private(`notifications.${authUser.value.id}`)
            .listen('.notification.sent', (e: { notification: Notification }) => {
                notifications.value.unshift(e.notification);
            });
    }
});
</script>

<template>
    <Head :title="t('notifications.title') + ' - Spotlight'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 max-w-4xl mx-auto w-full">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ t('notifications.title') }}</h1>
                    <p class="text-muted-foreground mt-1">{{ t('notifications.subtitle') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Badge variant="secondary" class="h-8 px-3 flex items-center gap-2">
                        <Bell class="w-4 h-4" />
                        <span>{{ unreadCount }} {{ t('notifications.unread') }}</span>
                    </Badge>
                    <Button
                        v-if="unreadCount > 0"
                        variant="outline"
                        size="sm"
                        @click="markAllAsRead"
                        class="flex items-center gap-1.5"
                    >
                        <CheckCheck class="w-4 h-4" />
                        {{ t('notifications.markAllRead') }}
                    </Button>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="flex items-center gap-1 p-1 bg-muted/50 rounded-xl w-fit border">
                <button
                    v-for="filter in (['all', 'unread', 'read'] as const)"
                    :key="filter"
                    @click="activeFilter = filter"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200 capitalize"
                    :class="activeFilter === filter
                        ? 'bg-white dark:bg-neutral-800 text-neutral-900 dark:text-white shadow-sm'
                        : 'text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300'"
                >
                    {{ t(`notifications.${filter === 'all' ? 'all' : filter === 'unread' ? 'unreadFilter' : 'readFilter'}`) }}
                    <span
                        v-if="filter === 'unread' && unreadCount > 0"
                        class="ml-1.5 inline-flex items-center justify-center h-5 min-w-[20px] px-1.5 text-[10px] font-bold bg-blue-600 text-white rounded-full"
                    >
                        {{ unreadCount }}
                    </span>
                </button>
            </div>

            <!-- Loading Skeleton -->
            <div v-if="loading" class="space-y-3">
                <div
                    v-for="i in 6"
                    :key="i"
                    class="flex items-start gap-4 p-5 rounded-xl border bg-card animate-pulse"
                >
                    <div class="h-11 w-11 rounded-xl bg-muted flex-shrink-0"></div>
                    <div class="flex-1 space-y-2.5 pt-0.5">
                        <div class="h-3 w-20 bg-muted rounded"></div>
                        <div class="h-4 w-3/4 bg-muted rounded"></div>
                        <div class="h-3 w-1/4 bg-muted rounded"></div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else-if="filteredNotifications.length === 0"
                class="text-center py-16 bg-muted/20 rounded-xl border border-dashed"
            >
                <div class="mx-auto w-16 h-16 rounded-2xl bg-muted flex items-center justify-center mb-4">
                    <Inbox class="w-8 h-8 text-muted-foreground" />
                </div>
                <h3 class="text-lg font-semibold">
                    {{ activeFilter === 'all' ? t('notifications.noNotifications') : activeFilter === 'unread' ? t('notifications.allCaughtUp') : t('notifications.noReadNotifications') }}
                </h3>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ activeFilter === 'all' ? t('notifications.noNotificationsDesc') : activeFilter === 'unread' ? t('notifications.allCaughtUpDesc') : t('notifications.noReadNotificationsDesc') }}
                </p>
            </div>

            <!-- Notification List -->
            <div v-else class="space-y-2">
                <div
                    v-for="notif in filteredNotifications"
                    :key="notif.id"
                    @click="markAsRead(notif)"
                    class="w-full relative flex flex-col sm:flex-row items-start gap-4 p-5 rounded-xl border text-left transition-all duration-200 group cursor-pointer"
                    :class="notif.lu
                        ? 'bg-card hover:bg-muted/30 border-border/60'
                        : 'bg-blue-50/50 dark:bg-blue-950/20 hover:bg-blue-50/80 dark:hover:bg-blue-950/30 border-blue-200/50 dark:border-blue-800/30 shadow-sm'"
                >
                    <!-- Type Icon -->
                    <div
                        class="flex-shrink-0 h-11 w-11 rounded-xl flex items-center justify-center transition-transform group-hover:scale-105"
                        :class="getTypeConfig(notif.type).bg"
                    >
                        <component
                            :is="getTypeConfig(notif.type).icon"
                            class="h-5 w-5"
                            :class="getTypeConfig(notif.type).color"
                        />
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <Badge variant="outline" class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5">
                                {{ t(getTypeConfig(notif.type).labelKey) }}
                            </Badge>
                        </div>
                        <p
                            class="text-sm leading-relaxed"
                            :class="notif.lu
                                ? 'text-neutral-600 dark:text-neutral-400'
                                : 'text-neutral-900 dark:text-neutral-100 font-semibold'"
                        >
                            {{ notif.message }}
                        </p>
                        <span
                            class="text-xs mt-1.5 block"
                            :class="notif.lu
                                ? 'text-neutral-400 dark:text-neutral-600'
                                : 'text-blue-500 dark:text-blue-400 font-medium'"
                        >
                            {{ formatDate(notif.date_envoi) }} · {{ timeAgo(notif.date_envoi) }}
                        </span>
                    </div>

                    <!-- Removed Action Buttons for Collaboration Invites -->

                    <!-- Status indicator -->
                    <div class="flex-shrink-0 mt-3 absolute top-5 right-5">
                        <div v-if="!notif.lu" class="h-3 w-3 rounded-full bg-blue-500 shadow-sm shadow-blue-500/50 animate-pulse"></div>
                        <Check v-else class="h-4 w-4 text-neutral-300 dark:text-neutral-700" />
                    </div>
                </div>

            </div>

        </div>
    </AppLayout>
</template>
