<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Badge from '@/components/ui/badge/Badge.vue';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { type NavItem } from '@/types';


defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup class="px-2 py-0 mt-4">
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                    :class="[
                        $page.props.auth?.user?.role === 'administrateur' 
                            ? 'text-slate-600 dark:text-white/70 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5 data-[active=true]:bg-indigo-600 data-[active=true]:text-white data-[active=true]:shadow-lg data-[active=true]:shadow-indigo-500/20 transition-all duration-300' 
                            : ''
                    ]"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                        <Badge 
                            v-if="item.count !== undefined" 
                            variant="destructive" 
                            class="ml-auto h-5 min-w-5 flex items-center justify-center rounded-full text-[10px] px-1"
                        >
                            {{ item.count }}
                        </Badge>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
