<<<<<<< HEAD
<script setup>
import { ref } from "vue"
import axios from "axios"

import {
UserIcon,
EnvelopeIcon,
PhoneIcon,
LockClosedIcon
} from "@heroicons/vue/24/outline"

const username = ref("")
const role = ref("participant")
const email = ref("")
const phone = ref("")
const password = ref("")
const confirmPassword = ref("")
const errors = ref({})
const message = ref("")

const register = async () => {
    errors.value = {}
    message.value = ""
    try {
        const response = await axios.post("/api/register", {
            username: username.value,
            role: role.value,
            email: email.value,
            telephone: phone.value,
            password: password.value,
            password_confirmation: confirmPassword.value
        })
        if (response.data.status) {
            message.value = response.data.message
            // Optional: redirect to login after a delay
            setTimeout(() => {
                window.location.href = "/login"
            }, 2000)
        }
    } catch (e) {
        if (e.response && e.response.data.errors) {
            errors.value = e.response.data.errors
        } else {
            message.value = e.response?.data?.message || "An error occurred during registration"
        }
    }
}
</script>

<template>
<div class="flex items-center justify-center min-h-screen bg-black">

<div class="bg-white w-[420px] rounded-2xl p-8 shadow-xl">

<!-- LOGO -->
<div class="text-center mb-8 mt-6">
<img
src="/images/logo_Black.png"
alt="Spotlight Logo"
class="mx-auto w-42 mb-4"
/>
</div>

<div v-if="message" :class="{'bg-green-100 text-green-700': !Object.keys(errors).length, 'bg-red-100 text-red-700': Object.keys(errors).length}" class="p-3 rounded-lg mb-4 text-sm font-medium">
    {{ message }}
</div>

<div class="space-y-4">


<!-- USERNAME + ROLE -->
<div class="flex gap-2">
    <div class="relative w-1/2">
        <UserIcon class="w-5 h-5 absolute left-3 top-3 text-gray-400"/>
        <input
            v-model="username"
            type="text"
            placeholder="Username"
            class="w-full pl-10 p-3 rounded-full border outline-none text-[#222222]"
            :class="{'border-red-500': errors.username}"
        />
        <p v-if="errors.username" class="text-red-500 text-xs mt-1 ml-4">{{ errors.username[0] }}</p>
    </div>
    <div class="relative w-1/2">
        <select
            v-model="role"
            class="w-full p-3 rounded-full border outline-none text-[#222222]"
            :class="{'border-red-500': errors.role}"
        >
            <option value="participant">Participant</option>
            <option value="organisateur">Organisateur</option>
            <option value="revendeur">Revendeur</option>
        </select>
        <p v-if="errors.role" class="text-red-500 text-xs mt-1 ml-4">{{ errors.role[0] }}</p>
    </div>
</div>

<!-- EMAIL -->
<div class="relative">
    <EnvelopeIcon class="w-5 h-5 absolute left-3 top-3 text-gray-400"/>
    <input
        v-model="email"
        type="email"
        placeholder="Email"
        class="w-full pl-10 p-3 rounded-full border outline-none text-[#222222]"
        :class="{'border-red-500': errors.email}"
    />
    <p v-if="errors.email" class="text-red-500 text-xs mt-1 ml-4">{{ errors.email[0] }}</p>
</div>

<!-- PHONE -->
<div class="relative">
    <PhoneIcon class="w-5 h-5 absolute left-3 top-3 text-gray-400"/>
    <input
        v-model="phone"
        type="text"
        placeholder="Phone number"
        class="w-full pl-10 p-3 rounded-full border outline-none text-[#222222]"
        :class="{'border-red-500': errors.telephone}"
    />
    <p v-if="errors.telephone" class="text-red-500 text-xs mt-1 ml-4">{{ errors.telephone[0] }}</p>
</div>

<!-- PASSWORDS -->
<div class="flex gap-2">
    <div class="relative w-1/2">
        <LockClosedIcon class="w-5 h-5 absolute left-3 top-3 text-gray-400"/>
        <input
            v-model="password"
            type="password"
            placeholder="Password"
            class="w-full pl-10 p-3 rounded-full border outline-none text-[#222222]"
            :class="{'border-red-500': errors.password}"
        />
        <p v-if="errors.password" class="text-red-500 text-xs mt-1 ml-4">{{ errors.password[0] }}</p>
    </div>
    <div class="relative w-1/2">
        <LockClosedIcon class="w-5 h-5 absolute left-3 top-3 text-gray-400"/>
        <input
            v-model="confirmPassword"
            type="password"
            placeholder="Confirm Password"
            class="w-full pl-10 p-3 rounded-full border outline-none text-[#222222]"
        />
    </div>
</div>

<!-- SIGNUP BUTTON -->
<button
@click="register"
class="w-full bg-blue-600 text-white py-3 rounded-full hover:bg-blue-700 transition"
>
Signup
</button>

<div class="text-center text-gray-400">OR</div>

<!-- GOOGLE -->
<button
class="w-full border py-2 rounded-full flex items-center justify-center gap-2 hover:bg-gray-50 transition text-[#222222]"
>
<img src="https://www.svgrepo.com/show/475656/google-color.svg" width="18" />
Signup with Google
</button>

<p class="text-center text-sm mt-4 text-[#222222]">
Already have account ?
<a href="/login">
<span class="text-blue-500 cursor-pointer">Login</span>
</a>
</p>

</div>
</div>
</div>
</template>
=======
<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
</script>

<template>
    <AuthBase
        title="Create an account"
        description="Enter your details below to create your account"
    >
        <Head title="Register" />

        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        placeholder="Full name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        name="password"
                        placeholder="Password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Confirm password"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    tabindex="5"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" />
                    Create account
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink
                    :href="login()"
                    class="underline underline-offset-4"
                    :tabindex="6"
                    >Log in</TextLink
                >
            </div>
        </Form>
    </AuthBase>
</template>
>>>>>>> a4d878a35023aeb496e8a22b58cad4a3fa2ae64e
