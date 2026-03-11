<script setup>
import { ref } from "vue"

const code = ref("")

const verify = () => {
    console.log("2FA code:", code.value)
}
</script>

<template>
<div class="flex items-center justify-center min-h-screen bg-black">

<div class="bg-white w-[420px] rounded-2xl p-8 shadow-xl">

<div class="text-center mb-8 mt-6">
<img src="/images/logo_Black.png" class="mx-auto w-28 mb-4 dark:hidden" />
<img src="/images/logo_white.png" class="mx-auto w-28 mb-4 hidden dark:block" />
<h2 class="text-lg text-[#222222] font-semibold">
Enter verification code
</h2>
</div>

<input
v-model="code"
type="text"
placeholder="6-digit code"
class="w-full p-3 rounded-full border mb-4"
/>

<button
@click="verify"
class="w-full bg-blue-600 text-white py-3 rounded-full"
>
Verify
</button>

</div>
</div>
</template>
