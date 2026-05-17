<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, onMounted, nextTick, computed } from 'vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { show } from '@/routes/messages';
import axios from 'axios';
import { toast } from 'vue-sonner';

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

const { t } = useI18n();

const otherUserName = computed(() => {
    return props.otherUser?.username || t('common.user');
});

const isAdmin = computed(() => page.props.auth?.user?.role === 'administrateur');

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

onMounted(() => {
    scrollToBottom();
    
    // Listen for real-time messages only if not admin
    if (!isAdmin.value) {
        window.Echo.private(`chat.${page.props.auth.user.id}`)
            .listen('.message.sent', (e) => {
                if (e.message.sender_id === props.otherUser.id) {
                    messages.value.push(e.message);
                    scrollToBottom();
                }
            });
    }
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
        toast.error(t('messages.failedToSend') + ': ' + (error.response?.data?.message || error.message));
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
    <Head :title="t('messages.chatWith', { name: otherUserName })" />

    <AppLayout v-if="otherUser">
        <div class="max-w-[1800px] mx-auto h-[calc(100vh-4rem)] py-4 px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row gap-4 lg:gap-6 overflow-hidden">
            
            <!-- Left Column: Conversations List (30% Width) -->
            <div class="w-full md:w-[30%] h-full flex flex-col bg-card border border-border shadow-sm rounded-3xl overflow-hidden min-h-0">
                <div class="p-5 border-b border-border bg-background/50 backdrop-blur-sm z-10 flex-shrink-0">
                    <h3 class="text-xl font-extrabold text-foreground flex items-center justify-between">
                        {{ t('messages.conversations') }}
                        <span class="bg-blue-100 dark:bg-blue-900/40 text-[#1a56db] dark:text-blue-400 text-xs py-0.5 px-3 rounded-full font-bold">
                            {{ conversations.length }}
                        </span>
                    </h3>
                </div>
                
                <div class="flex-grow overflow-y-auto py-3 px-3 space-y-2 scrollbar-hide min-h-0">
                    <div v-if="conversations && conversations.length > 0" class="space-y-1.5">
                        <Link 
                            v-for="convo in conversations" 
                            :key="convo.id"
                            :href="show.url(convo.id)"
                            :class="[
                                'group flex items-center gap-4 p-4 rounded-2xl transition-all duration-200 w-full text-left',
                                convo.id === otherUser.id 
                                    ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900/50 shadow-sm' 
                                    : 'hover:bg-muted/50 border border-transparent hover:border-border'
                            ]"
                        >
                            <!-- Avatar -->
                            <div class="flex-shrink-0 relative">
                                <div v-if="convo.avatar" class="h-12 w-12 rounded-2xl overflow-hidden ring-2 ring-border group-hover:ring-[#1a56db]/30 transition-all">
                                    <img :src="convo.avatar" :alt="convo.name" class="h-full w-full object-cover">
                                </div>
                                <div v-else class="h-12 w-12 rounded-2xl bg-gradient-to-br from-[#1a56db] to-blue-600 flex items-center justify-center text-white text-lg font-bold ring-2 ring-border group-hover:ring-[#1a56db]/30 transition-all">
                                    {{ convo.name?.charAt(0) || '?' }}
                                </div>
                                <!-- Unread Badge -->
                                <div v-if="convo.unread_count > 0 && convo.id !== otherUser.id" class="absolute -top-1.5 -right-1.5 bg-destructive text-destructive-foreground text-[10px] font-bold h-5 w-5 rounded-full flex items-center justify-center ring-2 ring-background shadow-md">
                                    {{ convo.unread_count }}
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="flex-grow min-w-0">
                                <div class="flex justify-between items-center mb-0.5">
                                    <h4 :class="[
                                        'text-sm font-bold truncate transition-colors',
                                        convo.id === otherUser.id ? 'text-[#1a56db]' : 'text-foreground group-hover:text-[#1a56db]'
                                    ]">
                                        {{ convo.name }}
                                    </h4>
                                </div>
                                <p :class="[
                                    'text-xs truncate',
                                    (convo.unread_count > 0 && convo.id !== otherUser.id) ? 'text-foreground font-semibold' : 'text-muted-foreground'
                                ]">
                                    {{ convo.last_message }}
                                </p>
                            </div>
                        </Link>
                    </div>
                    <div v-else class="text-center py-12 px-4">
                        <div class="w-12 h-12 bg-muted rounded-2xl flex items-center justify-center mx-auto mb-4 text-muted-foreground/50">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-muted-foreground">{{ t('messages.noConversations') }}</p>
                    </div>
                </div>
            </div>

            <!-- Right Column: Active Chat Area (70% Width) -->
            <div class="w-full md:w-[70%] h-full flex flex-col bg-card border border-border shadow-md rounded-3xl overflow-hidden min-h-0">
                <!-- Header -->
                <div class="flex flex-shrink-0 items-center justify-between p-3.5 bg-background/50 backdrop-blur-sm border-b border-border z-10">
                    <div class="flex items-center gap-4">
                        <div v-if="otherUser.avatar_url" class="h-12 w-12 rounded-2xl overflow-hidden ring-2 ring-border shadow-sm">
                            <img :src="otherUser.avatar_url" :alt="otherUserName" class="h-full w-full object-cover">
                        </div>
                        <div v-else class="h-12 w-12 rounded-2xl bg-gradient-to-br from-[#1a56db] to-blue-600 flex items-center justify-center text-white font-bold ring-2 ring-border shadow-sm">
                            {{ otherUserName.charAt(0) || '?' }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-foreground">{{ otherUserName }}</h2>
                            </div>
                            <span class="text-[11px] text-green-500 font-medium flex items-center gap-1.5 mt-0.5">
                                <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span> {{ t('messages.online') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Messages Area -->
                <div 
                    ref="messagesContainer"
                    class="flex-grow overflow-y-auto p-6 bg-muted/5 space-y-6 scroll-smooth scrollbar-hide border-none min-h-0"
                    style="background-image: radial-gradient(circle at 2px 2px, rgba(0,0,0,0.02) 1px, transparent 0); background-size: 24px 24px;"
                >
                    <div v-for="message in messages" :key="message.id" 
                        :class="['flex w-full message-item transition-all duration-300', message.sender_id !== otherUser.id ? 'justify-end' : 'justify-start']"
                    >
                        <div :class="[
                            'max-w-[85%] sm:max-w-[75%] rounded-3xl p-4 shadow-sm transition-all duration-300 group relative',
                            message.sender_id !== otherUser.id 
                                ? 'bg-[#1a56db] text-primary-foreground rounded-br-none' 
                                : 'bg-background text-foreground border border-border rounded-bl-none'
                        ]">
                            <!-- Message Content -->
                            <div class="relative">
                                <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ showOriginal[message.id] ? message.contenu_original : message.contenu }}</p>
                                
                                <!-- AI Reformulation Badge -->
                                <div v-if="message.contenu !== message.contenu_original" class="mt-2.5 flex items-center gap-2">
                                    <button 
                                        @click="toggleOriginal(message.id)"
                                        class="text-[9px] uppercase font-bold tracking-widest px-2.5 py-1 rounded-xl transition-all flex items-center gap-1.5 shadow-sm active:scale-95"
                                        :class="message.sender_id !== otherUser.id 
                                            ? 'bg-white/10 hover:bg-white/20 text-white border border-white/10' 
                                            : 'bg-muted/80 hover:bg-muted text-muted-foreground border border-border'"
                                    >
                                        <svg v-if="!showOriginal[message.id]" class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        {{ showOriginal[message.id] ? t('messages.showAiFinal') : t('messages.aiReformulated') }}
                                    </button>
                                </div>
                            </div>

                            <div :class="['mt-2 text-[10px] font-medium flex items-center gap-1.5', 
                                message.sender_id !== otherUser.id ? 'justify-end text-blue-100/70' : 'justify-start text-muted-foreground/70']">
                                {{ formatTime(message.created_at) }}
                                <svg v-if="message.sender_id !== otherUser.id" class="w-3.5 h-3.5" :class="message.lu ? 'text-blue-100' : 'opacity-30'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Area (hidden for admins observing messages) -->
                <div v-if="!isAdmin" class="flex-shrink-0 p-5 bg-background/50 backdrop-blur-md border-t border-border">
                    <form @submit.prevent="sendMessage" class="flex gap-3 relative max-w-5xl mx-auto items-end">
                        <div class="relative flex-grow group">
                            <textarea 
                                v-model="contenu"
                                :placeholder="t('messages.typeProfessional')" 
                                class="w-full bg-muted/30 border border-border text-foreground rounded-2xl px-5 py-3.5 pr-28 focus:ring-2 focus:ring-[#1a56db]/50 focus:bg-background focus:border-[#1a56db] transition-all placeholder-muted-foreground/60 outline-none resize-none min-h-[56px] max-h-32 scrollbar-hide"
                                :disabled="isSending"
                                rows="1"
                                @keydown.enter.prevent="sendMessage"
                            ></textarea>
                            
                            <!-- AI Status Indicator -->
                            <div class="absolute right-4 bottom-3.5 flex items-center gap-2 pointer-events-none transition-opacity group-focus-within:opacity-100 bg-background/80 px-2 py-1 rounded-lg backdrop-blur-sm border border-border/50">
                                <div class="flex h-1.5 w-1.5 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-[#1a56db]"></span>
                                </div>
                                <span class="text-[10px] text-[#1a56db] font-black tracking-widest uppercase">Gemini AI</span>
                            </div>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="!contenu.trim() || isSending"
                            class="bg-[#1a56db] hover:bg-[#1a56db]/90 text-white h-14 w-14 rounded-2xl transition-all active:scale-90 disabled:opacity-40 flex-shrink-0 flex items-center justify-center shadow-lg shadow-blue-500/20"
                        >
                            <svg v-if="!isSending" class="h-6 w-6 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            <span v-else class="animate-spin border-2 border-white/20 border-t-white rounded-full h-6 w-6 block"></span>
                        </button>
                    </form>
                    <p class="text-[10px] text-center text-muted-foreground mt-3 font-medium uppercase tracking-tighter">
                        {{ t('messages.aiReformulationNotice') }}
                    </p>
                </div>
                <div v-else class="flex-shrink-0 p-5 bg-background/50 backdrop-blur-md border-t border-border flex justify-center items-center">
                    <p class="text-sm text-yellow-600 dark:text-yellow-500 font-bold bg-yellow-50 dark:bg-yellow-900/20 px-6 py-3 rounded-full border border-yellow-200 dark:border-yellow-800">
                        <svg class="h-5 w-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        {{ t('messages.adminObservationMode') }}
                    </p>
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
