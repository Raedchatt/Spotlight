<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import { Folder, LayoutGrid, Menu, Search, Calendar, Bell, MessageCircle, User, LogOut } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import LoginModal from '@/components/auth/LoginModal.vue';
import RegisterModal from '@/components/auth/RegisterModal.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
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
import { dashboard, logout } from '@/routes';
import type { BreadcrumbItem, NavItem } from '@/types';




type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

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
                title: 'Discovery',
                href: '/discovery',
                icon: Search,
            }
        ];
    }
    
    if (auth.value.user.role === 'participant') {
        return [
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
        ];
    }
    
    return [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Events',
            href: '/dashboard/events',
            icon: Folder,
        },
    ];
});

const { isLoginOpen, isRegisterOpen, openLogin, openRegister } = useAuthModal();

defineExpose({
    openLogin,
    openRegister
});

const handleLogout = () => {
    router.post(logout(), {}, {
        onFinish: () => {
            router.flushAll();
        }
    });
};
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80 bg-white dark:bg-neutral-900 shadow-sm">
            <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button variant="ghost" size="icon" class="mr-2 h-9 w-9">
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" class="w-[300px] p-6">
                            <SheetTitle class="sr-only">Navigation Menu</SheetTitle>
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
                                    <Link href="/settings/profile" class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent">
                                        <User class="h-5 w-5" /> Profile
                                    </Link>
                                    <button @click="handleLogout" class="flex w-full items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent">
                                        <LogOut class="h-5 w-5" /> Log out
                                    </button>
                                </div>
                                <div class="flex flex-col space-y-4" v-else>
                                    <button @click="openLogin" class="flex w-full items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent text-left">
                                        Log in
                                    </button>
                                    <button @click="openRegister" class="flex w-full items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent text-left">
                                        Sign up
                                    </button>
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link :href="auth.user ? (auth.user.role === 'participant' ? '/discovery' : dashboard()) : '/'" class="flex items-center gap-x-2 mr-[2%] ">
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
                        <template v-if="auth.user">
                            <Button variant="outline" size="icon" class="h-10 w-10">
                                <Bell class="h-5 w-5 text-neutral-700 dark:text-neutral-300" />
                            </Button>
                            <Button variant="outline" size="icon" class="h-10 w-10">
                                <MessageCircle class="h-5 w-5 text-neutral-700 dark:text-neutral-300" />
                            </Button>
                            <Link href="/settings/profile" class="relative">
                                <Button variant="outline" size="icon" class="h-10 w-10">
                                    <User class="h-5 w-5 text-neutral-700 dark:text-neutral-300" />
                                </Button>
                                <!-- RIB Warning Indicator -->
                                <div v-if="auth.user?.role === 'organisateur' && (!auth.user?.organisateur || !auth.user?.organisateur.rib)" 
                                     class="absolute -top-0.5 -right-0.5 flex h-3 w-3 z-50">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500 border-2 border-white dark:border-neutral-900 shadow-sm"></span>
                                </div>
                            </Link>
                            <Button variant="outline" size="icon" class="h-10 w-10" @click="handleLogout">
                                <LogOut class="h-5 w-5 text-neutral-700 dark:text-neutral-300" />
                            </Button>
                        </template>
                        <template v-else>
                            <button @click="openLogin" class="text-sm font-semibold text-neutral-700 hover:underline dark:text-neutral-300">Log in</button>
                            <button @click="openRegister" class="ml-4 text-sm font-semibold text-neutral-700 hover:underline dark:text-neutral-300">Sign up</button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="props.breadcrumbs.length > 1"
            class="hidden w-full border-b border-sidebar-border/70 bg-white dark:bg-neutral-900"
        >
            <div class="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>

        <LoginModal v-model:open="isLoginOpen" @switchToRegister="isLoginOpen = false; isRegisterOpen = true" />
        <RegisterModal v-model:open="isRegisterOpen" @switchToLogin="isRegisterOpen = false; isLoginOpen = true" />
    </div>
</template>
