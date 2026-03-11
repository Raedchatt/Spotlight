<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { 
    Calendar, 
    ChevronLeft, 
    ChevronRight,
    Ticket,
    ShieldCheck,
    MoreHorizontal
} from 'lucide-vue-next';
import { ref, onMounted } from 'vue';
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import EventCard from '@/components/EventCard.vue';
import { Button } from '@/components/ui/button';
import { useAuthModal } from '@/composables/useAuthModal';


import type { Evenement } from '@/types/event';





// Event data management

defineProps<{
    canRegister: boolean;
}>();

const scrollContainer = ref<HTMLElement | null>(null);

const events = ref<Evenement[]>([]);
const { openRegister } = useAuthModal();

const fetchEvents = async () => {
    try {
        const response = await axios.get('/api/events/search?statut=ouvert,valide,encours,en_attente&limit=6');
        events.value = response.data;
    } catch (error) {
        console.error('Error fetching events:', error);
    }
};

const scroll = (direction: 'left' | 'right') => {
    if (!scrollContainer.value) return;
    const scrollAmount = 400;
    scrollContainer.value.scrollBy({
        left: direction === 'left' ? -scrollAmount : scrollAmount,
        behavior: 'smooth'
    });
};

onMounted(() => {
    fetchEvents();
});
</script>

<template>
    <Head title="Landing Page" />

    <div class="min-h-screen bg-white font-sans text-[#111827]">
        <AppHeader />

        <main>
            <!-- Hero Banner Section -->
            <section class="relative h-[500px] w-full bg-black overflow-hidden">
                <!-- Fallback gradient if image fails, and dimming overlay -->
                <div class="absolute inset-0 bg-gradient-to-r from-black/80 to-black/40 z-10"></div>
                
                <!-- Background Image (Using a generic concert/crowd placeholder image) -->
                <img 
                    src="/images/hero-banner.webp" 
                    alt="Crowd at event" 
                    class="absolute inset-0 h-full w-full object-cover opacity-60"
                />

                <!-- Content container -->
                <div class="relative z-20 mx-auto flex h-full max-w-7xl flex-col justify-center px-4 sm:px-6 lg:px-8">
                    <div class="max-w-2xl">
                        <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-5xl leading-tight mb-8">
                            Manage Events. Sell Tickets.<br />
                            Create Moments.
                        </h1>
                        <Button 
                            v-if="!$page.props.auth.user"
                            class="bg-[#1a56db] hover:bg-[#1a56db]/90 text-white rounded-full px-8 py-6 text-lg font-semibold flex items-center gap-2"
                            @click="openRegister()"
                        >
                            Signup
                        </Button>
                    </div>
                </div>
            </section>

            <!-- Section 1: Fixtures Events -->
            <section class="py-16 sm:py-24">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-12 flex items-end justify-between">
                        <div>
                            <h2 class="text-3xl font-bold tracking-tight text-[#111827] sm:text-4xl">Fixtures Events</h2>
                            <p class="mt-4 text-lg text-gray-500 text-muted-foreground">Discover and join the most exciting events near you.</p>
                        </div>
                        <div class="flex gap-2">
                            <Button 
                                variant="outline" 
                                size="icon" 
                                class="rounded-full border-gray-200"
                                @click="scroll('left')"
                            >
                                <ChevronLeft class="size-5" />
                            </Button>
                            <Button 
                                variant="outline" 
                                size="icon" 
                                class="rounded-full border-gray-200"
                                @click="scroll('right')"
                            >
                                <ChevronRight class="size-5" />
                            </Button>
                        </div>
                    </div>

                    <div 
                        ref="scrollContainer"
                        class="no-scrollbar -mx-4 flex items-stretch gap-6 overflow-x-auto px-4 py-8"
                    >
                        <div 
                            v-for="event in events" 
                            :key="event.id"
                            class="w-[350px] flex-shrink-0"
                        >
                            <EventCard :event="event" :show-action="false" />
                        </div>
                        
                        <!-- Explore More Card -->
                        <div v-if="events.length > 0" class="w-[350px] flex-shrink-0">
                            <Link 
                                href="/discovery" 
                                class="group flex h-full min-h-[400px] flex-col items-center justify-center rounded-xl border border-slate-300 bg-slate-50 transition-all hover:border-slate-400 hover:bg-slate-100 hover:shadow-md"
                            >
                                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-400 text-white transition-colors group-hover:bg-slate-500">
                                    <MoreHorizontal class="h-8 w-8" stroke-width="3" />
                                </div>
                                <span class="text-lg font-bold text-slate-400 transition-colors group-hover:text-slate-500">Explore more</span>
                            </Link>
                        </div>
                        
                        <template v-if="events.length === 0">
                            <div v-for="idx in 3" :key="idx" class="w-[350px] flex-shrink-0 rounded-2xl bg-gray-50/50 p-6 animate-pulse">
                                <div class="aspect-video w-full rounded-xl bg-gray-200"></div>
                                <div class="mt-4 h-6 w-3/4 rounded bg-gray-200"></div>
                                <div class="mt-2 h-4 w-1/2 rounded bg-gray-200"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </section>

            <!-- Section 2: Our Services -->
            <section class="py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-16 text-center">
                        <h2 class="inline-block text-3xl font-bold tracking-tight text-[#111827] sm:text-4xl">
                            Our Services
                            <div class="mx-auto mt-2 h-1.5 w-12 rounded-full bg-[#111827]"></div>
                        </h2>
                    </div>

                    <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        <!-- Card 1 -->
                        <div class="flex flex-col items-center rounded-2xl border border-gray-100 bg-white p-8 text-center transition-all hover:shadow-lg">
                            <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-2xl border-2 border-[#1a56db] bg-blue-50/50 text-[#1a56db]">
                                <Calendar class="size-8" />
                            </div>
                            <h3 class="text-xl font-bold text-[#111827]">Organize</h3>
                            <p class="mt-4 text-gray-500">
                                Spotlight's organize service empowers event creators to build and manage their events effortlessly. Organizers can create events, set ticket types, define capacity, and track reservations — all from a dedicated dashboard giving them full control over their event from creation to completion.
                            </p>
                        </div>

                        <!-- Card 2 -->
                        <div class="flex flex-col items-center rounded-2xl border border-gray-100 bg-white p-8 text-center transition-all hover:shadow-lg">
                            <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-2xl border-2 border-[#1a56db] bg-blue-50/50 text-[#1a56db]">
                                <Ticket class="size-8" />
                            </div>
                            <h3 class="text-xl font-bold text-[#111827]">Booking</h3>
                            <p class="mt-4 text-gray-500">
                                Spotlight's booking service lets users reserve their spot at any event in just a few clicks. Simply choose an event, select a ticket type, and confirm — your reservation is instantly saved to your account, with real-time availability updates to ensure no overbooking.
                            </p>
                        </div>

                        <!-- Card 3 -->
                        <div class="flex flex-col items-center rounded-2xl border border-gray-100 bg-white p-8 text-center transition-all hover:shadow-lg">
                            <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-2xl border-2 border-[#1a56db] bg-blue-50/50 text-[#1a56db]">
                                <ShieldCheck class="size-8" />
                            </div>
                            <h3 class="text-xl font-bold text-[#111827]">Payment</h3>
                            <p class="mt-4 text-gray-500">
                                Spotlight's payment service provides a secure and seamless checkout experience. Users can complete their booking by paying online directly through the platform, with instant confirmation once the transaction is processed.
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <AppFooter />
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
