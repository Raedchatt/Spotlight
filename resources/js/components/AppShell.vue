<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppShellVariant } from '@/types';

type Props = {
    variant?: AppShellVariant;
    class?: string;
};

const props = withDefaults(defineProps<Props>(), {
    variant: 'header',
});

const isOpen = usePage().props.sidebarOpen;
const className = computed(() => props.class);
</script>

<template>
    <div v-if="variant === 'header'" :class="className" class="w-full text-zinc-900 dark:text-zinc-50 relative">
        <slot />
    </div>
    <SidebarProvider v-else :default-open="isOpen" class="relative">
        <slot />
    </SidebarProvider>
</template>
