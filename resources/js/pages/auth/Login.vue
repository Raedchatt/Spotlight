<<<<<<< HEAD
<script setup>
import { ref } from "vue"
import axios from "axios"
import { router } from "@inertiajs/vue3"
import { UserIcon, LockClosedIcon } from "@heroicons/vue/24/outline"

const email = ref("")
const password = ref("")

const message = ref("")
const isError = ref(false)

const login = async () => {

    message.value = ""
    isError.value = false

    try {

        const response = await axios.post("/api/login", {
            email: email.value,
            password: password.value
        })

        if (response.data.status) {

            message.value = response.data.message

            setTimeout(() => {
                router.visit("/dashboard")
            }, 800)

        }

    } catch (e) {

        isError.value = true
        message.value = e.response?.data?.message || "Invalid credentials"

        console.log(e)
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
class="mx-auto w-36 mb-4"
/>

<p class="text-sm text-gray-500">
Login to manage your events and bookings.
</p>
</div>

<!-- MESSAGE -->
<div
v-if="message"
:class="{
'bg-green-100 text-green-700': !isError,
'bg-red-100 text-red-700': isError
}"
class="p-3 rounded-lg mb-4 text-sm font-medium"
>
{{ message }}
</div>

<div class="space-y-4">

<!-- EMAIL -->
<div class="relative">
<UserIcon class="w-5 h-5 text-gray-400 absolute left-4 top-3.5"/>
<input
v-model="email"
type="email"
placeholder="Email"
class="w-full pl-11 p-3 rounded-full border outline-none text-[#222222]"
/>
</div>

<!-- PASSWORD -->
<div class="relative">
<LockClosedIcon class="w-5 h-5 text-gray-400 absolute left-4 top-3.5"/>
<input
v-model="password"
type="password"
placeholder="Password"
class="w-full pl-11 p-3 rounded-full border outline-none text-[#222222]"
/>
</div>

<div class="text-right text-sm text-blue-500 cursor-pointer">
Forgot password
</div>

<!-- LOGIN BUTTON -->
<button
@click="login"
class="w-full bg-blue-600 text-white py-3 rounded-full hover:bg-blue-700 transition"
>
LOGIN
</button>

<div class="text-center text-gray-400">OR</div>

<!-- GOOGLE LOGIN -->
<button
class="w-full border py-2 rounded-full flex items-center justify-center gap-2 hover:bg-gray-50 transition text-[#222222]"
>
<img src="https://www.svgrepo.com/show/475656/google-color.svg" width="18" />
Login with Google
</button>

<p class="text-center text-sm mt-4 text-[#222222]">
Don't have account ?
<a href="/register">
<span class="text-blue-500">Sign up</span>
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
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <AuthBase
        title="Log in to your account"
        description="Enter your email and password below to log in"
    >
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Password</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-sm"
                            :tabindex="5"
                        >
                            Forgot password?
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span>Remember me</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    Log in
                </Button>
            </div>

            <div
                class="text-center text-sm text-muted-foreground"
                v-if="canRegister"
            >
                Don't have an account?
                <TextLink :href="register()" :tabindex="5">Sign up</TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
>>>>>>> a4d878a35023aeb496e8a22b58cad4a3fa2ae64e
