<script setup lang="ts">
import { usePage, Link, router } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Search, Calendar, MessageSquare, Bell, Users, Wallet, LogOut, User } from 'lucide-vue-next';
import { computed } from 'vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useUnreadCounts } from '@/composables/useUnreadCounts';
import { dashboard, logout } from '@/routes';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

const page = usePage();
const auth = computed(() => page.props.auth as any);
const counts = computed(() => page.props.sidebar_counts as Record<string, number> || {});
const { unreadMessagesCount, unreadNotificationsCount } = useUnreadCounts();

// ... existing code for mainNavItems and footerNavItems ...
const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    ...(auth.value.user?.role === 'participant'
        ? [
            {
                title: 'Discovery',
                href: '/discovery',
                icon: Search,
            },
            {
                title: 'My Reservations',
                href: '/dashboard/reservations',
                icon: Calendar,
            },
        ]
        : (auth.value.user?.role === 'administrateur'
            ? [
                {
                    title: 'User Management',
                    href: '/admin/users',
                    icon: Users,
                },
                {
                    title: 'Event Validation',
                    href: '/admin/events',
                    icon: Calendar,
                    count: counts.value.event_validation,
                },
                {
                    title: 'Financials',
                    href: '/admin/financials',
                    icon: Wallet,
                    count: counts.value.financials,
                },
            ]
            : [
                {
                    title: 'My Hosted Events',
                    href: '/dashboard/events',
                    icon: Folder,
                },
                ...(auth.value.has_collaborations 
                    ? [{
                        title: 'My Collaborations',
                        href: '/dashboard/collaborations',
                        icon: Users,
                    }] 
                    : []
                ),
            ]
        )),
    {
        title: 'Messages',
        href: '/messages',
        icon: MessageSquare,
        count: unreadMessagesCount.value > 0 ? unreadMessagesCount.value : undefined,
    },
    {
        title: 'Notifications',
        href: '/notifications',
        icon: Bell,
        count: unreadNotificationsCount.value > 0 ? unreadNotificationsCount.value : undefined,
    },
]);

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];

const handleLogout = () => {
    router.post(logout());
};
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton 
                        size="lg" 
                        as-child 
                        :is-active="true" 
                        :class="[auth.user?.role === 'administrateur' ? 'bg-white/20 text-white hover:bg-white/30' : 'bg-sidebar-accent/50 group-hover:bg-sidebar-accent/80']"
                    >
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <template v-if="auth.user?.role === 'administrateur'">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton @click="handleLogout" class="w-full h-12 flex justify-between items-center text-white hover:bg-blue-700/50 hover:text-white transition-colors rounded-xl px-3 group">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <div class="bg-blue-500/50 rounded-full p-1.5 flex-shrink-0">
                                    <User class="w-5 h-5 text-blue-100" />
                                </div>
                                <span class="font-bold truncate text-base">{{ auth.user.username }}</span>
                            </div>
                            <LogOut class="w-5 h-5 opacity-70 group-hover:opacity-100 transition-opacity flex-shrink-0 ml-2" />
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </template>
            <template v-else>
                <NavFooter :items="footerNavItems" />
                <NavUser />
            </template>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
