<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import AppLogo from '@/components/AppLogo.vue';
import { LockKeyhole, Mail, CheckCircle2 } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps<{
    token: string;
    email: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/reset-password', {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <AuthLayout>
        <Head :title="t('auth.resetPasswordTitle')" />

        <div class="space-y-6">
            <!-- Header with Logo -->
            <div class="flex flex-col items-center justify-center space-y-4 text-center">
                <AppLogo class="h-20" height="h-14" />
                <div class="space-y-2">
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">
                        {{ t('auth.newPassword') }}
                    </h1>
                    <p class="text-sm text-muted-foreground max-w-[280px]">
                        {{ t('auth.resetPasswordDesc') }}
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Email (readonly) -->
                <div class="grid gap-2">
                    <div class="relative opacity-60">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground">
                            <Mail class="size-5" />
                        </div>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            readonly
                            class="w-full rounded-full border border-border bg-muted/50 pl-12 pr-4 py-3.5 text-muted-foreground outline-none cursor-not-allowed"
                        />
                    </div>
                </div>

                <!-- New Password -->
                <div class="grid gap-2">
                    <div class="relative group">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground group-focus-within:text-blue-500 transition-colors">
                            <LockKeyhole class="size-5" />
                        </div>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            autofocus
                            required
                            :placeholder="t('auth.newPassword')"
                            class="w-full rounded-full border border-border bg-background pl-12 pr-4 py-3.5 text-foreground outline-none ring-offset-background transition-all focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>
                    <InputError :message="form.errors.password" class="px-4" />
                </div>

                <!-- Confirm Password -->
                <div class="grid gap-2">
                    <div class="relative group">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground group-focus-within:text-blue-500 transition-colors">
                            <CheckCircle2 class="size-5" />
                        </div>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                            :placeholder="t('auth.confirmPassword')"
                            class="w-full rounded-full border border-border bg-background pl-12 pr-4 py-3.5 text-foreground outline-none ring-offset-background transition-all focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>
                    <InputError :message="form.errors.password_confirmation" class="px-4" />
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full rounded-full bg-blue-600 py-3.5 text-white font-bold text-sm uppercase tracking-wide hover:bg-blue-700 focus:ring-4 focus:ring-blue-500/20 active:scale-[0.98] disabled:opacity-60 transition-all shadow-lg shadow-blue-600/20"
                    >
                        <span v-if="form.processing">{{ t('common.loading') }}</span>
                        <span v-else>{{ t('auth.updatePasswordBtn') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AuthLayout>
</template>
