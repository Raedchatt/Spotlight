<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import AppLogo from '@/components/AppLogo.vue';
import { Mail } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post('/forgot-password');
};
</script>

<template>
    <AuthLayout>
        <Head :title="t('auth.forgotPasswordTitle')" />

        <div class="space-y-6">
            <!-- Header with Logo -->
            <div class="flex flex-col items-center justify-center space-y-4 text-center">
                <AppLogo class="h-20" height="h-14" />
                <div class="space-y-2">
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">
                        {{ t('auth.forgotPasswordTitle') }}?
                    </h1>
                    <p class="text-sm text-muted-foreground max-w-[280px]">
                        {{ t('auth.forgotPasswordDesc') }}
                    </p>
                </div>
            </div>

            <!-- Success message -->
            <div
                v-if="status"
                class="rounded-xl bg-green-50 p-4 text-center text-sm font-medium text-green-700 border border-green-100 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800"
            >
                {{ t(status) }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div class="grid gap-2">
                    <div class="relative group">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground group-focus-within:text-blue-500 transition-colors">
                            <Mail class="size-5" />
                        </div>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            autofocus
                            required
                            :placeholder="t('auth.emailPlaceholder')"
                            class="w-full rounded-full border border-border bg-background pl-12 pr-4 py-3.5 text-foreground outline-none ring-offset-background transition-all focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder:text-muted-foreground/60"
                        />
                    </div>
                    <InputError :message="form.errors.email" class="px-4" />
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-full bg-blue-600 py-3.5 text-white font-bold text-sm uppercase tracking-wide hover:bg-blue-700 focus:ring-4 focus:ring-blue-500/20 active:scale-[0.98] disabled:opacity-60 transition-all shadow-lg shadow-blue-600/20"
                >
                    <span v-if="form.processing">{{ t('common.loading') }}</span>
                    <span v-else>{{ t('auth.emailResetLink') }}</span>
                </button>

                <div class="pt-2 text-center text-sm">
                    <a 
                        href="/" 
                        class="font-semibold text-blue-500 hover:text-blue-600 transition-colors inline-flex items-center gap-2"
                    >
                        {{ t('auth.backToHome') }}
                    </a>
                </div>
            </form>
        </div>
    </AuthLayout>
</template>
