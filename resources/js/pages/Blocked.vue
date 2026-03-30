<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ShieldBan, Clock, LogOut, ArrowLeft } from 'lucide-vue-next';

const props = defineProps<{
    blockedUntil: string | null;
    auth: any;
}>();

const isPermanent = computed(() => !props.blockedUntil);

const formattedDate = computed(() => {
    if (!props.blockedUntil) return null;
    return new Date(props.blockedUntil).toLocaleString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
});

const daysRemaining = computed(() => {
    if (!props.blockedUntil) return null;
    const diff = new Date(props.blockedUntil).getTime() - Date.now();
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
});

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <Head title="Compte Bloqué" />

    <div class="min-h-screen bg-gradient-to-br from-gray-900 via-red-950 to-gray-900 flex items-center justify-center p-4">
        <!-- Background pattern -->
        <div class="absolute inset-0 opacity-5 pointer-events-none" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 40px 40px;"></div>

        <div class="relative max-w-lg w-full">
            <!-- Card -->
            <div class="bg-white/5 backdrop-blur-xl border border-red-500/20 rounded-3xl p-10 text-center shadow-2xl">
                <!-- Icon -->
                <div class="flex items-center justify-center mb-6">
                    <div class="w-24 h-24 rounded-full bg-red-500/10 border-2 border-red-500/30 flex items-center justify-center">
                        <ShieldBan class="w-12 h-12 text-red-400" />
                    </div>
                </div>

                <!-- Title -->
                <h1 class="text-3xl font-bold text-white mb-2">Compte Suspendu</h1>
                <p class="text-red-300/80 text-base mb-8">
                    Votre accès à la plateforme a été temporairement restreint par un administrateur.
                </p>

                <!-- Block Info Card -->
                <div class="bg-black/30 border border-red-500/20 rounded-2xl p-6 mb-8 text-left space-y-4">
                    <div v-if="isPermanent" class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-red-900/50 flex items-center justify-center flex-shrink-0">
                            <ShieldBan class="w-5 h-5 text-red-400" />
                        </div>
                        <div>
                            <p class="text-xs text-red-400/70 uppercase font-semibold tracking-wider">Durée</p>
                            <p class="text-white font-semibold">Suspension permanente</p>
                        </div>
                    </div>

                    <template v-else>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-orange-900/50 flex items-center justify-center flex-shrink-0">
                                <Clock class="w-5 h-5 text-orange-400" />
                            </div>
                            <div>
                                <p class="text-xs text-orange-400/70 uppercase font-semibold tracking-wider">Bloqué jusqu'au</p>
                                <p class="text-white font-semibold">{{ formattedDate }}</p>
                            </div>
                        </div>
                        <div class="border-t border-white/5 pt-4">
                            <p class="text-orange-300/70 text-sm text-center">
                                Il vous reste encore
                                <span class="font-bold text-orange-300 text-base"> {{ daysRemaining }} jour{{ daysRemaining !== 1 ? 's' : '' }} </span>
                                de suspension.
                            </p>
                        </div>
                    </template>
                </div>

                <!-- Message -->
                <p class="text-gray-400 text-sm mb-8">
                    Si vous pensez que c'est une erreur, veuillez contacter le support de la plateforme.
                </p>

                <!-- Buttons -->
                <div class="space-y-3">
                    <button
                        @click="logout"
                        class="w-full flex items-center justify-center gap-2 bg-white/10 hover:bg-white/15 border border-white/10 text-white font-medium py-3 px-6 rounded-xl transition-all duration-200"
                    >
                        <ArrowLeft class="w-4 h-4" />
                        Retour à la connexion
                    </button>
                    <button
                        @click="logout"
                        class="w-full flex items-center justify-center gap-2 text-gray-500 hover:text-gray-300 font-medium py-2 px-6 rounded-xl transition-all duration-200 text-sm"
                    >
                        <LogOut class="w-4 h-4" />
                        Se déconnecter uniquement
                    </button>
                </div>
            </div>

            <!-- Footer text -->
            <p class="text-center text-gray-600 text-xs mt-6">Spotlight &copy; {{ new Date().getFullYear() }}</p>
        </div>
    </div>
</template>
