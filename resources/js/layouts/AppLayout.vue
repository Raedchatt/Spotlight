<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth as any);
const isAdmin = computed(() => auth.value?.user?.role === 'administrateur');

const ActiveLayout = computed(() => isAdmin.value ? AppSidebarLayout : AppHeaderLayout);
</script>

<template>
    <component :is="ActiveLayout" :breadcrumbs="breadcrumbs">
        <slot />
    </component>
</template>
