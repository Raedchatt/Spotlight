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

        const response = await axios.post("/login", {
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
<a
href="/auth/google"
class="w-full border py-2 rounded-full flex items-center justify-center gap-2 hover:bg-gray-50 transition text-[#222222]"
>
<img src="https://www.svgrepo.com/show/475656/google-color.svg" width="18" />
Login with Google
</a>

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
