<script setup lang="ts">
import { Globe } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import Button from '@/components/ui/button/Button.vue';
import { useLocale } from '@/composables/useLocale';
import type { SupportedLocale } from '@/i18n';

const { t } = useI18n();
const { locale, changeLocale } = useLocale();

const languages: { code: SupportedLocale; flag: string }[] = [
    { code: 'en', flag: 'EN' },
    { code: 'fr', flag: 'FR' },
    { code: 'ar', flag: 'ar' },
];

const currentFlag = computed(() => {
    return languages.find(l => l.code === locale.value)?.flag || '🌍';
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="outline" size="icon" class="h-10 w-10 relative">
                <span class="text-lg leading-none">{{ currentFlag }}</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-40">
            <DropdownMenuItem
                v-for="lang in languages"
                :key="lang.code"
                @click="changeLocale(lang.code)"
                class="flex items-center gap-2 cursor-pointer"
                :class="{ 'bg-accent': locale === lang.code }"
            >
                <span class="text-base">{{ lang.flag }}</span>
                <span class="text-sm font-medium">{{ t(`language.${lang.code}`) }}</span>
                <span v-if="locale === lang.code" class="ml-auto text-xs text-green-600">✓</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
