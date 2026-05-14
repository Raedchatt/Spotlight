<script setup lang="ts">
import { useSidebar } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    height?: string;
}>(), {
    height: 'h-7'
});

// Safer way to get sidebar state since AppLogo might be used without a SidebarProvider
const state = (() => {
    try {
        const sidebar = useSidebar();
        return sidebar.state;
    } catch (e) {
        return computed(() => 'expanded');
    }
})();

const page = usePage();
const isAdmin = computed(() => (page.props.auth as any)?.user?.role === 'administrateur');
</script>

<template>
    <div class="flex items-center gap-2">
        <template v-if="state === 'collapsed' && isAdmin">
            <img src="/images/icon.png" alt="Spotlight Icon" :class="['w-auto object-contain', height]" />
        </template>
        <template v-else>
            <img src="/images/logo_Black.png" alt="Spotlight Logo" :class="['w-auto dark:hidden', height]" />
            <img src="/images/logo_white.png" alt="Spotlight Logo" :class="['w-auto hidden dark:block', height]" />
        </template>
    </div>
</template>
