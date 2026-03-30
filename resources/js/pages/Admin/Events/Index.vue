<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { defineProps, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Calendar, Check, X, Building, MapPin, Tag } from 'lucide-vue-next';

// Define expected props
const props = defineProps({
    events: Object,
});

const processingEventId = ref<number | null>(null);

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('fr-TN', { style: 'currency', currency: 'TND' }).format(amount);
};

// Handle approve functionality
const handleApprove = (id: number) => {
    if (confirm("Are you sure you want to approve this event? It will become public.")) {
        processingEventId.value = id;
        router.patch(`/admin/events/${id}/approve`, {}, {
            preserveScroll: true,
            onFinish: () => {
                processingEventId.value = null;
            }
        });
    }
};

// Handle reject functionality
const handleReject = (id: number) => {
    if (confirm("Are you sure you want to reject this event? It will be marked as rejected and not visible to the public.")) {
        processingEventId.value = id;
        router.patch(`/admin/events/${id}/reject`, {}, {
            preserveScroll: true,
            onFinish: () => {
                processingEventId.value = null;
            }
        });
    }
};
</script>

<template>
    <Head title="Event Validation" />

    <AppLayout>
        <div class="px-4 py-8 md:px-8 space-y-8 max-w-[1200px] mx-auto">
            
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">Event Validation</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Review pending events submitted by organizers.</p>
                </div>
            </div>

            <!-- List of Pending Events -->
            <div v-if="events && events.data.length > 0" class="space-y-4">
                <div v-for="event in events.data" :key="event.id" class="bg-white dark:bg-neutral-900 shadow-sm border border-gray-100 dark:border-neutral-800 rounded-3xl p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 transition hover:shadow-md">
                    
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center p-2 bg-amber-100 dark:bg-amber-900/30 text-amber-600 rounded-xl">
                                <Calendar class="w-5 h-5" />
                            </span>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ event.titre }}</h3>
                        </div>
                        
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-center gap-2">
                                <Building class="w-4 h-4 text-indigo-500" />
                                <span class="font-medium">Organizer: {{ event.organisateur?.username || 'Unknown' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <MapPin class="w-4 h-4 text-indigo-500" />
                                <span>{{ event.lieu }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Tag class="w-4 h-4 text-indigo-500" />
                                <span class="capitalize">{{ event.categorie }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400 font-bold">
                                <span>Price: {{ formatCurrency(event.prix_spectateur) }}</span>
                                <span>• Capacity: {{ event.capacite_spectateur }}</span>
                            </div>
                        </div>
                        
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">{{ event.description }}</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="w-full md:w-auto flex flex-row md:flex-col gap-3">
                        <button
                            @click="handleApprove(event.id)"
                            :disabled="processingEventId === event.id"
                            class="flex-1 md:flex-none px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold rounded-xl flex items-center justify-center gap-2 transition"
                        >
                            <Check class="w-5 h-5" />
                            Approve
                        </button>
                        <button
                            @click="handleReject(event.id)"
                            :disabled="processingEventId === event.id"
                            class="flex-1 md:flex-none px-6 py-2.5 bg-white dark:bg-neutral-800 border-2 border-red-100 dark:border-red-900 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 disabled:opacity-50 font-bold rounded-xl flex items-center justify-center gap-2 transition"
                        >
                            <X class="w-5 h-5" />
                            Reject
                        </button>
                    </div>
                </div>

                <!-- Pagination (if applicable) -->
                <div v-if="events.links && events.links.length > 3" class="flex justify-center mt-6">
                    <div class="flex gap-2">
                        <template v-for="(link, prevKey) in events.links" :key="prevKey">
                            <component
                                :is="link.url ? 'a' : 'span'"
                                :href="link.url"
                                class="px-4 py-2 border rounded-xl text-sm font-medium transition"
                                :class="[
                                    link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-neutral-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-neutral-700 hover:bg-gray-50 dark:hover:bg-neutral-700',
                                    !link.url ? 'opacity-50 cursor-not-allowed hidden md:inline-block' : ''
                                ]"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-gray-50 dark:bg-neutral-800/50 rounded-3xl p-12 text-center border-2 border-dashed border-gray-200 dark:border-neutral-700 flex flex-col items-center justify-center gap-4">
                <div class="w-16 h-16 bg-white dark:bg-neutral-800 rounded-full flex items-center justify-center shadow-sm">
                    <Check class="w-8 h-8 text-emerald-500" />
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">All caught up!</h3>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">There are no pending events waiting for validation at this time.</p>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
