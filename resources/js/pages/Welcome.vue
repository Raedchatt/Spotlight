<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { 
    Calendar, 
    ChevronLeft, 
    ChevronRight,
    Ticket,
    ShieldCheck,
    LayoutDashboard
} from 'lucide-vue-next';
import { ref, onMounted } from 'vue';
import EventCard from '@/components/EventCard.vue';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';
import type { Evenement } from '@/types/event';

// Event data management

defineProps<{
    canRegister: boolean;
}>();

const events = ref<Evenement[]>([]);
const scrollContainer = ref<HTMLElement | null>(null);

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
        <!-- Navbar -->
        <header class="sticky top-0 z-50 border-b border-gray-100 bg-white/80 backdrop-blur-md">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#1a56db] text-white">
                        <Ticket class="size-6" />
                    </div>
                    <span class="text-xl font-bold tracking-tight text-[#111827]">Spotlight</span>
                </div>

                <nav class="flex items-center gap-4">
                    <template v-if="$page.props.auth.user">
                        <Link :href="dashboard()">
                            <Button variant="default" class="bg-[#1a56db] hover:bg-[#1a56db]/90">
                                <LayoutDashboard class="mr-2 size-4" />
                                Dashboard
                            </Button>
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="login()">
                            <Button variant="ghost" class="text-[#111827] hover:bg-gray-50">Log in</Button>
                        </Link>
                        <Link v-if="canRegister" :href="register()">
                            <Button class="bg-[#1a56db] hover:bg-[#1a56db]/90">Get Started</Button>
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main>
            <!-- Hero / Section 1: Fixtures Events -->
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
                        class="no-scrollbar -mx-4 flex gap-6 overflow-x-auto px-4 pb-8"
                    >
                        <div 
                            v-for="event in events" 
                            :key="event.id"
                            class="w-[350px] flex-shrink-0"
                        >
                            <EventCard :event="event" :show-action="false" />
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
            <section class="bg-[#f5f5f5] py-20">
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
                                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                            </p>
                        </div>

                        <!-- Card 2 -->
                        <div class="flex flex-col items-center rounded-2xl border border-gray-100 bg-white p-8 text-center transition-all hover:shadow-lg">
                            <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-2xl border-2 border-[#1a56db] bg-blue-50/50 text-[#1a56db]">
                                <Ticket class="size-8" />
                            </div>
                            <h3 class="text-xl font-bold text-[#111827]">Booking</h3>
                            <p class="mt-4 text-gray-500">
                                Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
                            </p>
                        </div>

                        <!-- Card 3 -->
                        <div class="flex flex-col items-center rounded-2xl border border-gray-100 bg-white p-8 text-center transition-all hover:shadow-lg">
                            <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-2xl border-2 border-[#1a56db] bg-blue-50/50 text-[#1a56db]">
                                <ShieldCheck class="size-8" />
                            </div>
                            <h3 class="text-xl font-bold text-[#111827]">Payment</h3>
                            <p class="mt-4 text-gray-500">
                                Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-100 py-12">
            <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <p class="text-sm text-gray-500">© 2026 Spotlight. All rights reserved.</p>
            </div>
        </footer>
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
