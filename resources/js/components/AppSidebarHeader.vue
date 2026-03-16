<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';
import { useUnreadCounts } from '@/composables/useUnreadCounts';
import Badge from '@/components/ui/badge/Badge.vue';

const { totalUnreadCount } = useUnreadCounts();

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="sticky top-0 z-50 flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 bg-white/80 px-6 backdrop-blur-md transition-[width,height] ease-linear dark:bg-neutral-900/80 group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <div class="relative">
                <SidebarTrigger class="-ml-1" />
                <Badge 
                    v-if="totalUnreadCount > 0" 
                    variant="destructive" 
                    class="absolute -top-1 -right-1 h-4 w-4 p-0 flex items-center justify-center text-[8px] rounded-full border border-white dark:border-neutral-900 pointer-events-none"
                >
                    {{ totalUnreadCount > 9 ? '9+' : totalUnreadCount }}
                </Badge>
            </div>
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
    </header>
</template>
