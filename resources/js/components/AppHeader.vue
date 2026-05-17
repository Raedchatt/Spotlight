<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import { Folder, LayoutGrid, Menu, Search, Calendar, User, LogOut, Users } from 'lucide-vue-next';
import { MessageSquare, Bell } from 'lucide-vue-next';
import { computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';
import AppLogo from '@/components/AppLogo.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';

import LoginModal from '@/components/auth/LoginModal.vue';
import RegisterModal from '@/components/auth/RegisterModal.vue';
import InvitationsDropdown from '@/components/InvitationsDropdown.vue';
import MessagesDropdown from '@/components/MessagesDropdown.vue';
import NotificationsDropdown from '@/components/NotificationsDropdown.vue';

import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useAuthModal } from '@/composables/useAuthModal';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useUnreadCounts } from '@/composables/useUnreadCounts';
import { dashboard, logout } from '@/routes';
import type { BreadcrumbItem, NavItem } from '@/types';

const { t } = useI18n();









type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth as any);
const { isCurrentUrl, whenCurrentUrl } = useCurrentUrl();

const activeItemStyles = 'text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100 font-semibold';

const mainNavItems = computed<NavItem[]>(() => {
    if (!auth.value.user) {
        return [
            {
                title: t('nav.discovery'),
                href: '/discovery',
                icon: Search,
            }
        ];
    }
    
    if (auth.value.user.role === 'participant') {
        return [
            {
                title: t('nav.discovery'),
                href: '/discovery',
                icon: Search,
            },
            {
                title: t('nav.myReservations'),
                href: '/dashboard/reservations',
                icon: Calendar,
            },
        ];
    }
    
    if (auth.value.user.role === 'revendeur') {
        return [
            {
                title: t('nav.dashboard'),
                href: '/affiliate/dashboard',
                icon: LayoutGrid,
            },
            {
                title: t('nav.discovery'),
                href: '/discovery',
                icon: Search,
            },
        ];
    }
    
    return [
        {
            title: t('nav.dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: t('nav.myHostedEvents'),
            href: '/dashboard/events',
            icon: Folder,
        },
        ...(auth.value.has_collaborations 
            ? [{
                title: t('nav.myCollaborations'),
                href: '/dashboard/collaborations',
                icon: Users,
            }] 
            : []
        ),
    ];
});

const { isLoginOpen, isRegisterOpen, openLogin, openRegister } = useAuthModal();

const { unreadMessagesCount, unreadNotificationsCount, totalUnreadCount } = useUnreadCounts();

onMounted(() => {
    // Initialization is handled by watch in useUnreadCounts
});

// Watch for flash messages
watch(
    () => page.props.flash as any,
    (flash) => {
        if (flash?.success) {
            toast.success(t(flash.success));
        }
        if (flash?.error) {
            toast.error(t(flash.error));
        }
        if (flash?.status) {
            toast.info(t(flash.status));
        }
    },
    { deep: true, immediate: true }
);

defineExpose({
    openLogin,
    openRegister
});

const handleLogout = () => {
    router.post(logout());
};
</script>

<template>
    <div>
        <!-- Fixed Header Container -->
        <header class="fixed top-0 left-0 right-0 z-50 border-b border-sidebar-border/80 bg-white/80 backdrop-blur-md dark:bg-neutral-900/80 shadow-sm">
            <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button variant="ghost" size="icon" class="mr-2 h-9 w-9 relative">
                                <Menu class="h-5 w-5" />
                                <Badge 
                                    v-if="totalUnreadCount > 0" 
                                    variant="destructive" 
                                    class="absolute -top-1 -right-1 h-4 w-4 p-0 flex items-center justify-center text-[8px] rounded-full border border-white dark:border-neutral-900 pointer-events-none"
                                >
                                    {{ totalUnreadCount > 9 ? '9+' : totalUnreadCount }}
                                </Badge>
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" class="w-[300px] p-6">
                            <SheetTitle class="sr-only">{{ t('nav.navigationMenu') }}</SheetTitle>
                            <SheetHeader class="flex justify-start text-left">
                                <AppLogo />
                            </SheetHeader>
                            <div class="flex h-full flex-1 flex-col justify-between space-y-4 py-6">
                                <nav class="-mx-3 space-y-1">
                                    <Link
                                        v-for="item in mainNavItems"
                                        :key="item.title"
                                        :href="item.href"
                                        class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                        :class="whenCurrentUrl(item.href, activeItemStyles)"
                                    >
                                        <component v-if="item.icon" :is="item.icon" class="h-5 w-5" />
                                        {{ item.title }}
                                    </Link>
                                </nav>
                                <div class="flex flex-col space-y-4" v-if="auth.user">
                                    <div class="space-y-1 mb-2 border-b border-sidebar-border/50 pb-4">
                                        <Link href="/messages" class="flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent" :class="whenCurrentUrl('/messages', activeItemStyles)">
                                            <div class="flex items-center gap-x-3">
                                                <MessageSquare class="h-5 w-5" /> {{ t('nav.messages') }}
                                            </div>
                                            <Badge v-if="unreadMessagesCount > 0" variant="destructive" class="h-5 min-w-5 flex items-center justify-center rounded-full text-[10px] px-1">
                                                {{ unreadMessagesCount }}
                                            </Badge>
                                        </Link>
                                        
                                        <Link href="/notifications" class="flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent" :class="whenCurrentUrl('/notifications', activeItemStyles)">
                                            <div class="flex items-center gap-x-3">
                                                <Bell class="h-5 w-5" /> {{ t('nav.notifications') }}
                                            </div>
                                            <Badge v-if="unreadNotificationsCount > 0" variant="destructive" class="h-5 min-w-5 flex items-center justify-center rounded-full text-[10px] px-1">
                                                {{ unreadNotificationsCount }}
                                            </Badge>
                                        </Link>
                                    </div>

                                    <Link href="/settings/profile" class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent" :class="whenCurrentUrl('/settings/profile', activeItemStyles)">
                                        <User class="h-5 w-5" /> {{ t('nav.profile') }}
                                    </Link>
                                    <button @click="handleLogout" class="flex w-full items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent">
                                        <LogOut class="h-5 w-5" /> {{ t('nav.logout') }}
                                    </button>
                                </div>
                                <div class="flex flex-col space-y-4" v-else>
                                    <button @click="openLogin" class="flex w-full items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent text-left">
                                        {{ t('nav.login') }}
                                    </button>
                                    <button @click="openRegister" class="flex w-full items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent text-left">
                                        {{ t('nav.signup') }}
                                    </button>
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link :href="auth.user ? (auth.user.role === 'participant' ? '/discovery' : auth.user.role === 'revendeur' ? '/affiliate/dashboard' : dashboard()) : '/'" class="flex items-center gap-x-2 mr-[2%] ">
                    <AppLogo />
                </Link>

                <!-- Desktop Menu -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <NavigationMenu class="flex h-full items-stretch">
                        <NavigationMenuList class="flex h-full items-stretch space-x-4 mt-[2%]">
                            <NavigationMenuItem
                                v-for="(item, index) in mainNavItems"
                                :key="index"
                                class="relative flex h-full items-center"
                            >
                                <Link
                                    :class="[
                                        'h-9 cursor-pointer px-3 text-base font-semibold hover:text-black dark:hover:text-white',
                                        isCurrentUrl(item.href) ? 'text-black dark:text-white' : 'text-neutral-500 dark:text-neutral-400'
                                    ]"
                                    :href="item.href"
                                >
                                    {{ item.title }}
                                </Link>
                                <div
                                    v-if="isCurrentUrl(item.href)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-blue-600 dark:bg-blue-500"
                                ></div>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <!-- Right Side Actions -->
                <div class="ml-auto flex items-center space-x-2">
                    <div class="hidden lg:flex items-center gap-2">
                        <LanguageSwitcher />
                        <template v-if="auth.user">
                            <InvitationsDropdown v-if="auth.user.role === 'organisateur'" />
                            <NotificationsDropdown :user-id="auth.user.id" />
                            <MessagesDropdown />
                            <Link href="/settings/profile" class="relative">
                                <Button variant="outline" size="icon" class="h-10 w-10">
                                    <User class="h-5 w-5 text-neutral-700 dark:text-neutral-300" />
                                </Button>
                            </Link>
                            <Button variant="outline" size="icon" class="h-10 w-10" @click="handleLogout">
                                <LogOut class="h-5 w-5 text-neutral-700 dark:text-neutral-300" />
                            </Button>
                        </template>
                        <template v-else>
                            <button @click="openLogin" class="text-sm font-semibold text-neutral-700 hover:underline dark:text-neutral-300">{{ t('nav.login') }}</button>
                            <button @click="openRegister" class="ml-4 text-sm font-semibold text-neutral-700 hover:underline dark:text-neutral-300">{{ t('nav.signup') }}</button>
                        </template>
                    </div>
                </div>
            </div>

        </header>

        <!-- Spacer for fixed header -->
        <div class="h-16"></div>

        <LoginModal v-model:open="isLoginOpen" @switchToRegister="isLoginOpen = false; isRegisterOpen = true" />
        <RegisterModal v-model:open="isRegisterOpen" @switchToLogin="isRegisterOpen = false; isLoginOpen = true" />
    </div>
</template>
