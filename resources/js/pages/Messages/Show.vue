<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, onMounted, nextTick, computed } from 'vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import { show } from '@/routes/messages';
import axios from 'axios';

const props = defineProps({
    otherUser: Object,
    initialMessages: Array,
    conversations: Array
});

const page = usePage();
const messages = ref(props.initialMessages || []);
const messagesContainer = ref(null);
const showOriginal = ref({}); // Track which message IDs show original content
const contenu = ref('');
const isSending = ref(false);

const otherUserName = computed(() => {
    return props.otherUser?.username || 'User';
});

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

onMounted(() => {
    scrollToBottom();
});

const sendMessage = async () => {
    if (!contenu.value.trim() || isSending.value) return;

    try {
        isSending.value = true;
        const response = await axios.post('/web-api/messages', {
            contenu: contenu.value,
            receiver_id: props.otherUser.id
        });

        if (response.data.status) {
            messages.value.push(response.data.message);
            contenu.value = '';
            scrollToBottom();
        }
    } catch (error) {
        console.error('Error sending message:', error);
        alert('Failed to send message: ' + (error.response?.data?.message || error.message));
    } finally {
        isSending.value = false;
    }
};

const toggleOriginal = (messageId) => {
    showOriginal.value[messageId] = !showOriginal.value[messageId];
};

const formatTime = (dateString) => {
    return new Date(dateString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head :title="'Chat with ' + otherUserName" />

    <AppLayout v-if="otherUser">
        <div class="max-w-6xl mx-auto h-[calc(100vh-12rem)] py-8 px-4 sm:px-6 lg:px-8 flex gap-6">
            
            <!-- Chat Area (Left Column) -->
            <div class="flex-grow flex flex-col min-w-0">
                <!-- Header -->
                <div class="flex flex-shrink-0 items-center justify-between p-4 bg-gray-900 border border-gray-800 rounded-t-3xl shadow-xl">
                    <div class="flex items-center gap-4">
                        <div v-if="otherUser.avatar_url" class="h-12 w-12 rounded-2xl overflow-hidden ring-2 ring-gray-800">
                            <img :src="otherUser.avatar_url" :alt="otherUserName" class="h-full w-full object-cover">
                        </div>
                        <div v-else class="h-12 w-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold ring-2 ring-gray-800">
                            {{ otherUserName.charAt(0) || '?' }}
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">{{ otherUserName }}</h2>
                            <span class="text-xs text-green-400 flex items-center gap-1">
                                <span class="h-1.5 w-1.5 rounded-full bg-green-400"></span> Online
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Messages Area -->
                <div 
                    ref="messagesContainer"
                    class="flex-grow overflow-y-auto p-6 bg-gray-900/50 border-x border-gray-800 space-y-6 scrollbar-hide"
                >
                    <div v-for="message in messages" :key="message.id" 
                        :class="['flex w-full', message.sender_id === $page.props.auth.user.id ? 'justify-end' : 'justify-start']"
                    >
                        <div :class="[
                            'max-w-[80%] rounded-2xl p-4 shadow-lg transition-all duration-300 transform',
                            message.sender_id === $page.props.auth.user.id 
                                ? 'bg-indigo-600 text-white rounded-br-none hover:scale-[1.02]' 
                                : 'bg-gray-800 text-gray-100 rounded-bl-none hover:scale-[1.02]'
                        ]">
                            <!-- Message Content -->
                            <div class="relative group">
                                <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ showOriginal[message.id] ? message.contenu_original : message.contenu }}</p>
                                
                                <!-- AI Reformulation Badge/Toggle -->
                                <div v-if="message.contenu !== message.contenu_original" class="mt-2 flex items-center gap-2">
                                    <button 
                                        @click="toggleOriginal(message.id)"
                                        class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-black/20 hover:bg-black/40 transition-colors flex items-center gap-1"
                                    >
                                        <svg v-if="!showOriginal[message.id]" class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        {{ showOriginal[message.id] ? 'Show AI Version' : 'AI Reformulated' }}
                                    </button>
                                </div>
                            </div>

                            <div :class="['mt-2 text-[10px] opacity-60 flex items-center gap-1', message.sender_id === $page.props.auth.user.id ? 'justify-end' : 'justify-start']">
                                {{ formatTime(message.created_at) }}
                                <svg v-if="message.sender_id === $page.props.auth.user.id" class="w-3 h-3" :class="message.lu ? 'text-blue-300' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="flex-shrink-0 p-4 bg-gray-900 border border-gray-800 rounded-b-3xl shadow-2xl">
                    <form @submit.prevent="sendMessage" class="flex gap-2 relative">
                        <input 
                            v-model="contenu"
                            type="text" 
                            placeholder="Type professional messages..." 
                            class="flex-grow bg-gray-800 border-none text-gray-100 rounded-2xl px-4 py-3 pr-24 focus:ring-2 focus:ring-indigo-500 transition-all placeholder-gray-500"
                            :disabled="isSending"
                        >
                        
                        <!-- AI Status Indicator -->
                        <div class="absolute right-16 inset-y-0 flex items-center pr-3 pointer-events-none">
                            <span class="flex h-2 w-2 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                            </span>
                            <span class="ml-2 text-[10px] text-indigo-400 font-bold tracking-tighter uppercase hidden sm:inline-block">Gemini AI</span>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="!contenu.trim() || isSending"
                            class="bg-indigo-600 hover:bg-indigo-500 text-white p-3 rounded-xl transition-all active:scale-95 disabled:opacity-50 flex-shrink-0"
                        >
                            <svg v-if="!isSending" class="h-5 w-5 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            <span v-else class="animate-spin border-2 border-white/20 border-t-white rounded-full h-5 w-5 block"></span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Conversations Sidebar (Right Column) -->
            <div class="w-80 flex-shrink-0 relative hidden lg:flex flex-col bg-gray-900 border border-gray-800 rounded-3xl shadow-xl overflow-hidden">
                <div class="p-5 border-b border-gray-800 bg-gray-900 z-10">
                    <h3 class="text-lg font-bold text-white flex items-center justify-between">
                        Conversations
                        <span class="bg-indigo-500/20 text-indigo-400 text-xs py-0.5 px-2 rounded-full">{{ conversations.length }}</span>
                    </h3>
                </div>
                
                <div class="flex-grow overflow-y-auto w-full scrollbar-hide py-2 px-3">
                    <div v-if="conversations && conversations.length > 0" class="space-y-1">
                        <Link 
                            v-for="convo in conversations" 
                            :key="convo.id"
                            :href="show.url(convo.id)"
                            :class="[
                                'group flex items-center gap-3 p-3 rounded-2xl transition-all duration-200 w-full text-left',
                                convo.id === otherUser.id 
                                    ? 'bg-indigo-500/10 border border-indigo-500/30' 
                                    : 'hover:bg-gray-800/80 border border-transparent'
                            ]"
                        >
                            <!-- Avatar -->
                            <div class="flex-shrink-0 relative">
                                <div v-if="convo.avatar" class="h-10 w-10 rounded-xl overflow-hidden ring-2 ring-gray-800">
                                    <img :src="convo.avatar" :alt="convo.name" class="h-full w-full object-cover">
                                </div>
                                <div v-else class="h-10 w-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-sm font-bold ring-2 ring-gray-800">
                                    {{ convo.name?.charAt(0) || '?' }}
                                </div>
                                <!-- Unread Badge -->
                                <div v-if="convo.unread_count > 0 && convo.id !== otherUser.id" class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] font-bold h-4 w-4 rounded-full flex items-center justify-center ring-2 ring-gray-900">
                                    {{ convo.unread_count }}
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="flex-grow min-w-0">
                                <div class="flex justify-between items-start mb-0.5">
                                    <h4 :class="[
                                        'text-sm font-medium truncate',
                                        convo.id === otherUser.id ? 'text-indigo-400' : 'text-gray-200 group-hover:text-white'
                                    ]">
                                        {{ convo.name }}
                                    </h4>
                                </div>
                                <p :class="[
                                    'text-xs truncate',
                                    (convo.unread_count > 0 && convo.id !== otherUser.id) ? 'text-white font-medium' : 'text-gray-500'
                                ]">
                                    {{ convo.last_message }}
                                </p>
                            </div>
                        </Link>
                    </div>
                    <div v-else class="text-center py-10 px-4">
                        <p class="text-sm text-gray-500">No other conversations.</p>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
