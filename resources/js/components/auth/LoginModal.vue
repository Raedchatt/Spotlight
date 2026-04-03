<script setup lang="ts">
import { ref } from "vue"
import axios from "axios"
import { router } from "@inertiajs/vue3"
import { UserIcon, LockClosedIcon } from "@heroicons/vue/24/outline"
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
  (e: 'switchToRegister'): void
}>()

const email = ref("")
const password = ref("")
const message = ref("")
const isError = ref(false)
const { closeAll } = useAuthModal()

const login = () => {
    message.value = ""
    isError.value = false
    
    router.post("/login", {
        email: email.value,
        password: password.value
    }, {
        onSuccess: () => {
            closeAll()
        },
        onError: (errors) => {
            isError.value = true
            message.value = Object.values(errors)[0] as string
        }
    })
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[420px] p-8 rounded-2xl border-none">
      <DialogTitle class="sr-only">Login Modal</DialogTitle>
      <DialogDescription class="sr-only">Enter your credentials to access your account.</DialogDescription>
      <!-- LOGO -->
      <div class="text-center mb-8 mt-2 text-foreground">
        <img src="/images/logo_Black.png" alt="Spotlight Logo" class="mx-auto w-36 mb-4 dark:hidden" />
        <img src="/images/logo_white.png" alt="Spotlight Logo" class="mx-auto w-36 mb-4 hidden dark:block" />
        <p class="text-sm text-muted-foreground">
          Login to manage your events and bookings.
        </p>
      </div>

      <!-- MESSAGE -->
      <div v-if="message" :class="{'bg-green-100 text-green-700': !isError, 'bg-red-100 text-red-700': isError}" class="p-3 rounded-lg mb-4 text-sm font-medium">
        {{ message }}
      </div>

      <div class="space-y-4">
        <!-- EMAIL -->
        <div class="relative">
          <UserIcon class="w-5 h-5 text-muted-foreground absolute left-4 top-3.5"/>
          <input v-model="email" type="email" placeholder="Email" class="w-full pl-11 p-3 rounded-full border border-border bg-background outline-none text-foreground" />
        </div>

        <!-- PASSWORD -->
        <div class="relative">
          <LockClosedIcon class="w-5 h-5 text-muted-foreground absolute left-4 top-3.5"/>
          <input v-model="password" type="password" placeholder="Password" class="w-full pl-11 p-3 rounded-full border border-border bg-background outline-none text-foreground" @keyup.enter="login" />
        </div>

        <div class="text-right text-sm text-blue-500 cursor-pointer hover:underline">
          Forgot password
        </div>

        <!-- LOGIN BUTTON -->
        <button @click="login" class="w-full bg-blue-600 text-white py-3 rounded-full hover:bg-blue-700 transition">
          LOGIN
        </button>





        <!-- TEST LOGINS -->
        <div class="grid grid-cols-2 gap-2 mt-2">
          <button @click="email = 'org@org.com'; password = '12345678';" class="bg-orange-600/10 text-orange-600 py-2 px-2 rounded-full hover:bg-orange-600/20 transition text-[10px] font-bold border border-orange-600/20 uppercase tracking-wider">
            Login as Org
          </button>
          <button @click="email = 'part@part.com'; password = '12345678';" class="bg-green-600/10 text-green-600 py-2 px-2 rounded-full hover:bg-green-600/20 transition text-[10px] font-bold border border-green-600/20 uppercase tracking-wider">
            Login as Part
          </button>
        </div>
        <div class="grid grid-cols-2 gap-2 mt-2">
          <button @click="email = 'aff@aff.com'; password = '12345678';" class="bg-orange-600/10 text-orange-600 py-2 px-2 rounded-full hover:bg-orange-600/20 transition text-[10px] font-bold border border-orange-600/20 uppercase tracking-wider">
            Login as affiliate
          </button>
          <button @click="email = 'admin@admin.com'; password = '12345678';" class="bg-green-600/10 text-green-600 py-2 px-2 rounded-full hover:bg-green-600/20 transition text-[10px] font-bold border border-green-600/20 uppercase tracking-wider">
            Login as admin
          </button>
        </div>










        <div class="text-center text-gray-400">OR</div>

        <!-- GOOGLE LOGIN -->
        <a href="/auth/google" class="w-full border border-border py-2 rounded-full flex items-center justify-center gap-2 hover:bg-accent transition text-foreground">
          <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="18" />
          Login with Google
        </a>

        <p class="text-center text-sm mt-4 text-foreground">
          Don't have account ?
          <button @click="emit('switchToRegister')" class="text-blue-500 hover:underline">Sign up</button>
        </p>
      </div>
    </DialogContent>
  </Dialog>
</template>
