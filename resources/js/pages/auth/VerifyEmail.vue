<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';
import { useI18n } from 'vue-i18n';
import LoginModal from '@/components/auth/LoginModal.vue';
import RegisterModal from '@/components/auth/RegisterModal.vue';
import { useAuthModal } from '@/composables/useAuthModal';

const { t } = useI18n();
const { isLoginOpen, isRegisterOpen, openRegister } = useAuthModal();

const form = useForm({
    code: '',
});

const submit = () => {
    form.post('/verify-email', {
        onFinish: () => form.reset('code'),
    });
};
</script>

<template>
    <AuthLayout
        :title="t('auth.verify_email.title', 'Verify your email')"
        :description="t('auth.verify_email.description', 'Enter the 6-digit code we sent to your email.')"
    >
        <Head title="Email verification" />

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <input
                    v-model="form.code"
                    type="text"
                    maxlength="6"
                    placeholder="000000"
                    class="block w-full text-center text-3xl tracking-[1em] font-bold py-4 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-indigo-500 transition-all dark:bg-zinc-900 dark:border-zinc-800"
                    required
                    autofocus
                />
                <p v-if="form.errors.code" class="mt-2 text-sm text-red-600 text-center">
                    {{ form.errors.code }}
                </p>
            </div>

            <Button :disabled="form.processing" class="w-full py-6 text-lg font-semibold">
                <Spinner v-if="form.processing" class="mr-2" />
                {{ t('auth.verify_email.verify_button', 'Verify Code') }}
            </Button>
        </form>

        <div class="mt-8 text-center text-sm">
            <p class="text-gray-500 dark:text-zinc-400">
                {{ t('auth.verify_email.no_code', "Didn't receive a code?") }}
                <button 
                    type="button" 
                    @click="openRegister" 
                    class="text-indigo-600 hover:text-indigo-500 font-medium ml-1"
                >
                    {{ t('auth.signupLink', 'Sign up') }}
                </button>
            </p>
        </div>

        <!-- Auth Modals -->
        <LoginModal v-model:open="isLoginOpen" @switchToRegister="isLoginOpen = false; isRegisterOpen = true" />
        <RegisterModal v-model:open="isRegisterOpen" @switchToLogin="isRegisterOpen = false; isLoginOpen = true" />
    </AuthLayout>
</template>

<style scoped>
input::placeholder {
    letter-spacing: normal;
    color: #e5e7eb;
}
</style>
