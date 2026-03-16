import { ref, computed, onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const unreadMessagesCount = ref(0);
const unreadNotificationsCount = ref(0);
const isInitialized = ref(false);

const totalUnreadCount = computed(() => unreadMessagesCount.value + unreadNotificationsCount.value);

export function useUnreadCounts() {
    const page = usePage();
    const auth = computed(() => (page.props.auth as any) || {});

    const fetchUnreadCounts = async () => {
        if (!auth.value.user) return;
        try {
            const [messagesRes, notificationsRes] = await Promise.all([
                axios.get('/web-api/messages/recent'),
                axios.get('/web-api/notifications')
            ]);

            if (messagesRes.data.status) {
                unreadMessagesCount.value = messagesRes.data.conversations.reduce(
                    (acc: number, curr: any) => acc + curr.unread_count, 
                    0
                );
            }

            unreadNotificationsCount.value = (notificationsRes.data || []).filter((n: any) => !n.lu).length;
        } catch (error) {
            console.error('Error fetching unread counts:', error);
        }
    };

    const setupEchoListeners = (userId: number) => {
        if (!window.Echo) return;

        // Leave existing channels if any to avoid duplicates if this is called multiple times
        window.Echo.leave(`notifications.${userId}`);
        window.Echo.leave(`chat.${userId}`);

        // Notifications
        window.Echo.private(`notifications.${userId}`)
            .listen('.notification.sent', () => {
                unreadNotificationsCount.value++;
            });

        // Messages
        window.Echo.private(`chat.${userId}`)
            .listen('.message.sent', () => {
                unreadMessagesCount.value++;
            });
    };

    const init = () => {
        if (isInitialized.value) return;
        if (auth.value.user) {
            fetchUnreadCounts();
            setupEchoListeners(auth.value.user.id);
            isInitialized.value = true;
        }
    };

    // Auto-init if user is available
    watch(() => auth.value.user?.id, (newId) => {
        if (newId) {
            fetchUnreadCounts();
            setupEchoListeners(newId);
        } else {
            unreadMessagesCount.value = 0;
            unreadNotificationsCount.value = 0;
            isInitialized.value = false;
        }
    }, { immediate: true });

    return {
        unreadMessagesCount,
        unreadNotificationsCount,
        totalUnreadCount,
        fetchUnreadCounts,
        init
    };
}
