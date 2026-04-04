<script setup lang="ts">
import { usePage, Link, router } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Search, Calendar, MessageSquare, Bell, Users, Wallet, LogOut, User, TrendingUp } from 'lucide-vue-next';
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
    useSidebar,
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
            : (auth.value.user?.role === 'revendeur'
                ? [
                    {
                        title: 'Affiliate Dashboard',
                        href: '/affiliate/dashboard',
                        icon: TrendingUp,
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
            )
        )
    ),
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

const { state } = useSidebar();

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
                        :class="[auth.user?.role === 'administrateur' ? 'bg-indigo-500/10 text-slate-900 shadow-sm dark:text-white hover:bg-indigo-500/20 border border-slate-200 dark:border-white/5' : 'bg-sidebar-accent/50 group-hover:bg-sidebar-accent/80']"
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
                    <SidebarMenuItem class="mt-auto mb-2">
                        <SidebarMenuButton 
                            @click="handleLogout" 
                            :tooltip="state === 'collapsed' ? 'Logout' : undefined"
                            class="w-full h-12 flex items-center text-slate-600 dark:text-white/80 hover:bg-slate-100 dark:hover:bg-white/10 hover:text-slate-900 dark:hover:text-white transition-all rounded-xl px-3 group border border-transparent hover:border-slate-200 dark:hover:border-white/5 active:scale-[0.98]"
                            :class="[state === 'collapsed' ? 'justify-center !p-0' : 'justify-between']"
                        >
                            <div v-if="state !== 'collapsed'" class="flex items-center gap-3 overflow-hidden">
                                <div class="bg-indigo-500/10 dark:bg-indigo-500/20 rounded-full p-1.5 flex-shrink-0 group-hover:bg-indigo-500/20 dark:group-hover:bg-indigo-500/30 transition-colors">
                                    <User class="w-5 h-5 text-indigo-600 dark:text-indigo-300" />
                                </div>
                                <span class="font-bold truncate text-sm tracking-tight capitalize">{{ auth.user.username }}</span>
                            </div>
                            <LogOut 
                                :class="[
                                    'transition-all duration-300 flex-shrink-0',
                                    state === 'collapsed' 
                                        ? 'w-5 h-5 text-red-500 opacity-100' 
                                        : 'w-4 h-4 opacity-40 dark:opacity-50 group-hover:opacity-100 group-hover:text-red-500 dark:group-hover:text-red-400 ml-2'
                                ]" 
                            />
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
