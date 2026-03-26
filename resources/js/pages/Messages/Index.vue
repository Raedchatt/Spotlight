<script setup>
import { Link, Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { discovery } from '@/routes';
import { show } from '@/routes/messages';

const props = defineProps({
    conversations: Array
});

const search = ref('');

const filteredConversations = computed(() => {
    return (props.conversations || []).filter(c => 
        (c.name?.toLowerCase().includes((search.value || '').toLowerCase())) ||
        (c.last_message?.toLowerCase().includes((search.value || '').toLowerCase()))
    );
});
</script>

<template>
    <Head title="Messages" />

    <AppLayout>
        <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-extrabold text-foreground tracking-tight">Messages</h1>
            </div>

            <!-- Search Area -->
            <div class="relative mb-6">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input 
                    v-model="search"
                    type="text" 
                    placeholder="Search conversations..." 
                    class="block w-full pl-10 pr-3 py-3 border border-border bg-muted/30 text-foreground placeholder-muted-foreground rounded-2xl focus:outline-none focus:ring-2 focus:ring-[#1a56db] focus:border-[#1a56db] focus:bg-background sm:text-sm transition-all duration-200"
                >
            </div>

            <!-- Conversations List -->
            <div v-if="filteredConversations.length > 0" class="space-y-3">
                <Link 
                    v-for="convo in filteredConversations" 
                    :key="convo.id"
                    :href="show.url(convo.id)"
                    class="group block bg-card hover:bg-muted/50 border border-border hover:border-[#1a56db]/30 rounded-2xl transition-all duration-300 transform hover:-translate-y-1 overflow-hidden shadow-sm"
                >
                    <div class="p-4 flex items-center gap-4">
                        <!-- Avatar -->
                        <div class="flex-shrink-0 relative">
                            <div v-if="convo.avatar" class="h-14 w-14 rounded-2xl overflow-hidden ring-2 ring-border group-hover:ring-[#1a56db]/50 transition-all">
                                <img :src="convo.avatar" :alt="convo.name" class="h-full w-full object-cover">
                            </div>
                            <div v-else class="h-14 w-14 rounded-2xl bg-gradient-to-br from-[#1a56db] to-blue-600 flex items-center justify-center text-white text-xl font-bold ring-2 ring-border group-hover:ring-[#1a56db]/50 transition-all">
                                {{ convo.name?.charAt(0) || '?' }}
                            </div>
                            <!-- Unread Badge -->
                            <div v-if="convo.unread_count > 0" class="absolute -top-1 -right-1 bg-destructive text-destructive-foreground text-[10px] font-bold h-5 w-5 rounded-full flex items-center justify-center ring-2 ring-background animate-pulse">
                                {{ convo.unread_count }}
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-grow min-w-0">
                            <div class="flex justify-between items-start mb-1">
                                <h3 class="text-base font-semibold text-foreground group-hover:text-[#1a56db] transition-colors truncate">
                                    {{ convo.name }}
                                </h3>
                                <span class="text-xs text-muted-foreground whitespace-nowrap">
                                    {{ convo.last_message_time }}
                                </span>
                            </div>
                            <p class="text-sm text-muted-foreground truncate line-clamp-1">
                                {{ convo.last_message }}
                            </p>
                        </div>

                        <!-- Arrow -->
                        <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                            <svg class="h-5 w-5 text-[#1a56db]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-20">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-muted text-muted-foreground mb-4">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                </div>
                <h3 class="text-xl font-medium text-foreground">No messages yet</h3>
                <p class="mt-2 text-muted-foreground max-w-sm mx-auto">Start a conversation to see them here.</p>
                <Link 
                    v-if="$page.props.auth.user.role === 'participant'"
                    :href="discovery().url" 
                    class="mt-6 inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-2xl text-white bg-[#1a56db] hover:bg-[#1a56db]/90 transition-colors"
                >
                    Explore Events
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
