<script setup lang="ts">
import {
  UserIcon,
  EnvelopeIcon,
  PhoneIcon,
  LockClosedIcon
} from "@heroicons/vue/24/outline"
import { useForm } from "@inertiajs/vue3"
import { ref } from "vue"
import { toast } from 'vue-sonner'
import { useI18n } from 'vue-i18n'
import {
  Dialog,
  DialogContent,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog'
import { useAuthModal } from "@/composables/useAuthModal"

const props = defineProps<{ open: boolean }>()
const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'switchToLogin'): void
}>()

const { closeAll } = useAuthModal()
const { t } = useI18n()

const form = useForm({
    username: '',
    role: 'participant',
    email: '',
    telephone: '',
    password: '',
    password_confirmation: '',
});

const register = () => {
    form.post("/register", {
        onSuccess: () => {
            closeAll()
            toast.success(t('auth.registerSuccess'))
        },
        onError: () => {
            toast.error(t('auth.registerFailed'))
        }
    })
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[420px] p-8 rounded-2xl border-none">
      <DialogTitle class="sr-only">{{ t('auth.registerTitle') }}</DialogTitle>
      <DialogDescription class="sr-only">{{ t('auth.registerDesc') }}</DialogDescription>
      <!-- LOGO -->
      <div class="text-center mb-8 mt-2">
        <img src="/images/logo_Black.png" alt="Spotlight Logo" class="mx-auto w-42 mb-4 dark:hidden" />
        <img src="/images/logo_white.png" alt="Spotlight Logo" class="mx-auto w-42 mb-4 hidden dark:block" />
      </div>

      <div class="space-y-4">
        <!-- USERNAME + ROLE -->
        <div class="flex gap-2">
            <div class="relative w-1/2">
                <UserIcon class="w-5 h-5 absolute left-3 top-3 text-muted-foreground"/>
                <input v-model="form.username" type="text" :placeholder="t('auth.usernamePlaceholder')" class="w-full pl-10 p-3 rounded-full border border-border bg-background outline-none text-foreground" :class="{'border-red-500': form.errors.username}" />
                <p v-if="form.errors.username" class="text-red-500 text-xs mt-1 ml-4">{{ form.errors.username }}</p>
            </div>
            <div class="relative w-1/2">
                <select v-model="form.role" class="w-full p-3 rounded-full border border-border bg-background outline-none text-foreground" :class="{'border-red-500': form.errors.role}">
                    <option value="participant" class="bg-background">{{ t('auth.roleParticipant') }}</option>
                    <option value="organisateur" class="bg-background">{{ t('auth.roleOrganizer') }}</option>
                    <option value="revendeur" class="bg-background">{{ t('auth.roleReseller') }}</option>
                </select>
                <p v-if="form.errors.role" class="text-red-500 text-xs mt-1 ml-4">{{ form.errors.role }}</p>
            </div>
        </div>

        <!-- EMAIL -->
        <div class="relative">
            <EnvelopeIcon class="w-5 h-5 absolute left-3 top-3 text-muted-foreground"/>
            <input v-model="form.email" type="email" :placeholder="t('auth.emailPlaceholder')" class="w-full pl-10 p-3 rounded-full border border-border bg-background outline-none text-foreground" :class="{'border-red-500': form.errors.email}" />
            <p v-if="form.errors.email" class="text-red-500 text-xs mt-1 ml-4">{{ form.errors.email }}</p>
        </div>

        <!-- PHONE -->
        <div class="relative">
            <PhoneIcon class="w-5 h-5 absolute left-3 top-3 text-muted-foreground"/>
            <input v-model="form.telephone" type="text" :placeholder="t('auth.phonePlaceholder')" class="w-full pl-10 p-3 rounded-full border border-border bg-background outline-none text-foreground" :class="{'border-red-500': form.errors.telephone}" />
            <p v-if="form.errors.telephone" class="text-red-500 text-xs mt-1 ml-4">{{ form.errors.telephone }}</p>
        </div>

        <!-- PASSWORDS -->
        <div class="flex gap-2">
            <div class="relative w-1/2">
                <LockClosedIcon class="w-5 h-5 absolute left-3 top-3 text-muted-foreground"/>
                <input v-model="form.password" type="password" :placeholder="t('auth.passwordPlaceholder')" class="w-full pl-10 p-3 rounded-full border border-border bg-background outline-none text-foreground" :class="{'border-red-500': form.errors.password}" />
                <p v-if="form.errors.password" class="text-red-500 text-xs mt-1 ml-4">{{ form.errors.password }}</p>
            </div>
            <div class="relative w-1/2">
                <LockClosedIcon class="w-5 h-5 absolute left-3 top-3 text-muted-foreground"/>
                <input v-model="form.password_confirmation" type="password" :placeholder="t('auth.confirmPasswordPlaceholder')" class="w-full pl-10 p-3 rounded-full border border-border bg-background outline-none text-foreground" @keyup.enter="register" />
            </div>
        </div>

        <!-- SIGNUP BUTTON -->
        <button 
            @click="register" 
            :disabled="form.processing"
            class="w-full bg-blue-600 text-white py-3 rounded-full hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="form.processing" class="flex items-center justify-center">
             <svg class="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
             </svg>
             {{ t('common.loading', 'Loading...') }}
          </span>
          <span v-else>{{ t('auth.signupBtn') }}</span>
        </button>

        <div class="text-center text-gray-400">{{ t('common.or') }}</div>

        <!-- GOOGLE -->
        <a href="/auth/google" class="w-full border border-border py-2 rounded-full flex items-center justify-center gap-2 hover:bg-accent transition text-foreground">
          <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="18" />
          {{ t('auth.signupWithGoogle') }}
        </a>

        <p class="text-center text-sm mt-4 text-foreground">
          {{ t('auth.hasAccount') }}
          <button @click="emit('switchToLogin')" class="text-blue-500 hover:underline">{{ t('auth.loginLink') }}</button>
        </p>
      </div>

    </DialogContent>
  </Dialog>
</template>
