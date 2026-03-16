<script setup lang="ts">
import { usePage, Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Search, Calendar, MessageSquare, Bell } from 'lucide-vue-next';
import { computed } from 'vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { useUnreadCounts } from '@/composables/useUnreadCounts';
import Badge from '@/components/ui/badge/Badge.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

const page = usePage();
const auth = computed(() => page.props.auth as any);
const { unreadMessagesCount, unreadNotificationsCount } = useUnreadCounts();

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
                href: '/dashboard/discovery',
                icon: Search,
            },
            {
                title: 'My Reservations',
                href: '/dashboard/reservations',
                icon: Calendar,
            },
        ]
        : [
            {
                title: 'My Hosted Events',
                href: '/dashboard/events',
                icon: Folder,
            },
        ]),
    {
        title: 'Messages',
        href: '/messages',
        icon: MessageSquare,
        count: unreadMessagesCount.value,
    },
    {
        title: 'Notifications',
        href: '/notifications',
        icon: Bell,
        count: unreadNotificationsCount.value,
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
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
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
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
