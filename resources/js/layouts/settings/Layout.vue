<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import { type NavItem } from '@/types';
import { Lock, User } from 'lucide-vue-next';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: editProfile(),
        icon: User,
    },
    {
        title: 'Password',
        href: editPassword(),
        icon: Lock,
    },
];

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6 max-w-7xl mx-auto">
        <Heading
            title="Settings"
            description="Manage your profile and account settings"
            class="mb-8"
        />

        <div class="flex flex-col space-y-8">
            <nav
                class="flex flex-wrap gap-2 pb-4 border-b"
                aria-label="Settings"
            >
                <Button
                    v-for="item in sidebarNavItems"
                    :key="toUrl(item.href)"
                    variant="ghost"
                    :class="[
                        'justify-start',
                        { 'bg-muted': isCurrentUrl(item.href) },
                    ]"
                    as-child
                >
                    <Link :href="item.href">
                        <component :is="item.icon" class="h-4 w-4" />
                        {{ item.title }}
                    </Link>
                </Button>
            </nav>

            <div class="w-full">
                <section class="space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
