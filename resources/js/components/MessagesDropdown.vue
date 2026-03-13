<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { MessageCircle } from 'lucide-vue-next';
import { ref, onMounted } from 'vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';



interface Conversation {
    id: number;
    name: string;
    avatar: string | null;
    last_message: string;
    last_message_time: string;
    unread_count: number;
}

const conversations = ref<Conversation[]>([]);
const loading = ref(true);
const totalUnread = ref(0);

const fetchRecentMessages = async () => {
    try {
        const response = await axios.get('/web-api/messages/recent');
        if (response.data.status) {
            conversations.value = response.data.conversations;
            totalUnread.value = conversations.value.reduce((acc, curr) => acc + curr.unread_count, 0);
        }
    } catch (error) {
        console.error('Error fetching recent messages:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchRecentMessages();
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="outline" size="icon" class="h-10 w-10 relative">
                <MessageCircle class="h-5 w-5 text-neutral-700 dark:text-neutral-300" />
                <Badge 
                    v-if="totalUnread > 0" 
                    variant="destructive" 
                    class="absolute -top-1 -right-1 h-5 w-5 p-0 flex items-center justify-center text-[10px] rounded-full border-2 border-white dark:border-neutral-900"
                >
                    {{ totalUnread > 9 ? '9+' : totalUnread }}
                </Badge>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80 p-0 overflow-hidden rounded-2xl shadow-2xl border-neutral-200 dark:border-neutral-800">
            <DropdownMenuLabel class="p-4 flex items-center justify-between bg-neutral-50/50 dark:bg-neutral-900/50">
                <span class="text-base font-bold">Messages</span>
                <Link 
                    href="/messages" 
                    class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
                >
                    See all
                </Link>
            </DropdownMenuLabel>
            <DropdownMenuSeparator class="m-0" />
            
            <div class="max-h-[400px] overflow-y-auto">
                <template v-if="loading">
                    <div v-for="i in 3" :key="i" class="p-4 flex items-center gap-3 animate-pulse">
                        <div class="h-10 w-10 rounded-full bg-neutral-200 dark:bg-neutral-800"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3 w-24 bg-neutral-200 dark:bg-neutral-800 rounded"></div>
                            <div class="h-2 w-full bg-neutral-100 dark:bg-neutral-900 rounded"></div>
                        </div>
                    </div>
                </template>

                <template v-else-if="conversations.length > 0">
                    <DropdownMenuItem 
                        v-for="convo in conversations" 
                        :key="convo.id"
                        as-child
                        class="p-0 focus:bg-neutral-50 dark:focus:bg-neutral-900 cursor-pointer border-b border-neutral-100 dark:border-neutral-800/50 last:border-0"
                    >
                        <Link :href="'/messages/' + convo.id" class="w-full p-4 flex items-center gap-3 transition-colors">
                            <div class="flex-shrink-0 relative">
                                <template v-if="convo.avatar">
                                    <img :src="convo.avatar" :alt="convo.name" class="h-10 w-10 rounded-full object-cover border border-neutral-200 dark:border-neutral-800">
                                </template>
                                <div v-else class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-sm font-bold">
                                    {{ convo.name?.charAt(0) || '?' }}
                                </div>
                                <div v-if="convo.unread_count > 0" class="absolute -bottom-0.5 -right-0.5 h-3 w-3 bg-blue-600 rounded-full border-2 border-white dark:border-neutral-900"></div>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start mb-0.5">
                                    <span class="text-sm font-semibold truncate" :class="{ 'text-blue-600 dark:text-blue-400': convo.unread_count > 0 }">
                                        {{ convo.name }}
                                    </span>
                                    <span class="text-[10px] text-neutral-500 whitespace-nowrap ml-2">
                                        {{ convo.last_message_time }}
                                    </span>
                                </div>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400 truncate line-clamp-1" :class="{ 'font-medium text-neutral-900 dark:text-neutral-100': convo.unread_count > 0 }">
                                    {{ convo.last_message }}
                                </p>
                            </div>
                        </Link>
                    </DropdownMenuItem>
                </template>

                <div v-else class="p-8 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-neutral-100 dark:bg-neutral-800 text-neutral-400 mb-3">
                        <MessageCircle class="w-6 h-6" />
                    </div>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">No messages yet</p>
                </div>
            </div>

            <DropdownMenuSeparator class="m-0" />
            <div class="p-3 text-center bg-neutral-50/30 dark:bg-neutral-900/30">
                <Link 
                    href="/messages" 
                    class="text-xs font-bold text-neutral-900 dark:text-white hover:underline uppercase tracking-wider"
                >
                    View All Messages
                </Link>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
