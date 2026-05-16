<script setup lang="ts">
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { 
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Eye, Edit, Trash2, UserPlus, ShieldBan, Search, CheckCircle } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    users: any;
}>();

const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isShowModalOpen = ref(false);
const isBlockModalOpen = ref(false);
const selectedUser = ref<any>(null);

const { t, locale } = useI18n();

// Form for Blocking
const blockForm = useForm({
    days: undefined as number | undefined,
});

// Form for Adding
const form = useForm({
    first_name: '',
    last_name: '',
    username: '',
    email: '',
    role: 'participant',
    password: '',
});

// Form for Editing
const editForm = useForm({
    id: null as number | null,
    first_name: '',
    last_name: '',
    username: '',
    email: '',
    role: 'participant',
    password: '',
});

const submitAdd = () => {
    form.post('/admin/users', {
        onSuccess: () => {
            isAddModalOpen.value = false;
            form.reset();
            toast.success(t('events.userCreatedSuccess'));
        },
        onError: () => {
            toast.error(t('events.userCreatedError'));
        },
    });
};

const openEditModal = (user: any) => {
    selectedUser.value = user;
    editForm.id = user.id;
    editForm.first_name = user.first_name || '';
    editForm.last_name = user.last_name || '';
    editForm.username = user.username || '';
    editForm.email = user.email || '';
    editForm.role = user.role || 'participant';
    editForm.password = ''; // Don't pre-fill password
    isEditModalOpen.value = true;
};

const submitEdit = () => {
    editForm.put(`/admin/users/${editForm.id}`, {
        onSuccess: () => {
            isEditModalOpen.value = false;
            editForm.reset();
            toast.success(t('events.userUpdatedSuccess'));
        },
        onError: () => {
            toast.error(t('events.userUpdatedError'));
        },
    });
};

const openShowModal = (user: any) => {
    selectedUser.value = user;
    isShowModalOpen.value = true;
};

const confirmDelete = (user: any) => {
    if (confirm(t('events.confirmDeleteUser', { name: `${user.first_name || ''} ${user.last_name || ''}`.trim() || t('events.thisUser') }))) {
        router.delete(`/admin/users/${user.id}`, {
            onSuccess: () => toast.success(t('events.userDeletedSuccess')),
            onError: () => toast.error(t('events.userDeletedError')),
        });
    }
};

const openBlockModal = (user: any) => {
    selectedUser.value = user;
    blockForm.reset();
    isBlockModalOpen.value = true;
};

const submitBlock = () => {
    if (!selectedUser.value) return;
    blockForm.post(`/web-api/admin/block/${selectedUser.value.id}`, {
        onSuccess: () => {
            isBlockModalOpen.value = false;
            blockForm.reset();
            toast.success(t('events.userBlockedSuccess'));
        },
        onError: () => {
            toast.error(t('events.userBlockedError'));
        },
    });
};

const getRoleBadgeColor = (role: string) => {
    switch (role) {
        case 'administrateur': return 'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800/50';
        case 'organisateur': return 'bg-indigo-100 text-indigo-700 border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-400 dark:border-indigo-800/50';
        case 'revendeur': return 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800/50';
        default: return 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800/50';
    }
};

const displayRole = (role: string) => {
    switch (role) {
        case 'administrateur': return t('events.roleAdmin');
        case 'organisateur': return t('events.roleOrganizer');
        case 'revendeur': return t('events.roleRevendeur');
        default: return t('events.roleParticipant');
    }
};

const translatePagination = (label: string) => {
    if (label.toLowerCase().includes('previous')) return t('common.previous');
    if (label.toLowerCase().includes('next')) return t('common.next');
    return label;
};
</script>

<template>
    <Head :title="t('events.manageUsers')" />

    <AppLayout>
        <div class="px-4 py-8 md:px-8 space-y-8 max-w-[1400px] mx-auto w-full min-w-0">
            
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">{{ t('events.manageUsers') }}</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">{{ t('events.manageUsersDesc') }}</p>
                </div>
                
                <Dialog v-model:open="isAddModalOpen">
                    <DialogTrigger as-child>
                        <button class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 text-white rounded-xl font-bold flex items-center justify-center gap-2 hover:from-indigo-700 hover:to-violet-700 transition-all duration-300 shadow-lg shadow-indigo-500/25 active:scale-[0.98]">
                            <UserPlus class="w-5 h-5" />
                            {{ t('events.addUser') }}
                        </button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-106.25 dark:bg-neutral-900 dark:border-neutral-800">
                        <DialogHeader>
                            <DialogTitle class="dark:text-white">{{ t('events.addNewUser') }}</DialogTitle>
                            <DialogDescription class="dark:text-gray-400">
                                {{ t('events.addNewUserDesc') }}
                            </DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submitAdd" class="space-y-4 py-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="first_name" class="dark:text-gray-300">{{ t('events.firstName') }}</Label>
                                    <Input id="first_name" v-model="form.first_name" required class="dark:bg-neutral-800 dark:border-neutral-700 dark:text-white" />
                                </div>
                                <div class="space-y-2">
                                    <Label for="last_name" class="dark:text-gray-300">{{ t('events.lastName') }}</Label>
                                    <Input id="last_name" v-model="form.last_name" required class="dark:bg-neutral-800 dark:border-neutral-700 dark:text-white" />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <Label for="username" class="dark:text-gray-300">{{ t('events.username') }}</Label>
                                <Input id="username" v-model="form.username" required class="dark:bg-neutral-800 dark:border-neutral-700 dark:text-white" />
                            </div>
                            <div class="space-y-2">
                                <Label for="email" class="dark:text-gray-300">{{ t('events.emailAddress') }}</Label>
                                <Input id="email" type="email" v-model="form.email" required class="dark:bg-neutral-800 dark:border-neutral-700 dark:text-white" />
                            </div>
                            <div class="space-y-2">
                                <Label for="role" class="dark:text-gray-300">{{ t('events.roleLabel') }}</Label>
                                <select id="role" v-model="form.role" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:cursor-not-allowed disabled:opacity-50 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white" required>
                                    <option value="participant">{{ t('events.roleParticipant') }}</option>
                                    <option value="organisateur">{{ t('events.roleOrganizer') }}</option>
                                    <option value="revendeur">{{ t('events.roleRevendeur') }}</option>
                                    <option value="administrateur">{{ t('events.roleAdmin') }}</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <Label for="password" class="dark:text-gray-300">{{ t('events.password') }}</Label>
                                <Input id="password" type="password" v-model="form.password" required class="dark:bg-neutral-800 dark:border-neutral-700 dark:text-white" />
                            </div>
                            <DialogFooter>
                                <Button type="submit" :disabled="form.processing" class="w-full bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white font-bold rounded-xl h-11 shadow-md shadow-indigo-500/20">{{ t('events.createAccount') }}</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Users List Table -->
            <div class="bg-white/70 dark:bg-neutral-900/70 backdrop-blur-sm rounded-3xl border border-gray-100 dark:border-neutral-800 shadow-sm overflow-hidden transition-all duration-300 hover:shadow-md hover:border-gray-200 dark:hover:border-neutral-700 w-full">
                <div class="overflow-x-auto w-full">
                    <table class="w-full text-sm text-start">
                        <thead class="text-[11px] text-gray-400 dark:text-gray-500 uppercase tracking-[0.1em] bg-gray-50/50 dark:bg-neutral-800/30 border-b border-gray-100 dark:border-neutral-800">
                            <tr>
                                <th class="px-6 py-4 font-bold text-start">{{ t('events.user') }}</th>
                                <th class="px-6 py-4 font-bold text-start">{{ t('events.contactInfo') }}</th>
                                <th class="px-6 py-4 font-bold text-start">{{ t('events.roleLabel') }}</th>
                                <th class="px-6 py-4 font-bold text-start">{{ t('events.joined') }}</th>
                                <th class="px-6 py-4 font-bold text-end">{{ t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-neutral-800/50">
                            <tr v-for="user in users?.data" :key="user.id" class="hover:bg-gray-50/80 dark:hover:bg-neutral-800/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm shadow-sm">
                                            {{ (user.first_name || 'U').charAt(0) }}{{ (user.last_name || '').charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900 dark:text-white text-base">{{ user.first_name }} {{ user.last_name }}</div>
                                            <div class="text-gray-500 dark:text-gray-400 text-xs mt-0.5">@{{ user.username }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300 font-medium tracking-wide">
                                    {{ user.email }}
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['px-3 py-1 text-xs font-bold rounded-full border shadow-sm', getRoleBadgeColor(user.role)]">
                                        {{ displayRole(user.role) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400 text-sm">
                                    {{ new Date(user.created_at).toLocaleDateString(locale, { year: 'numeric', month: 'short', day: 'numeric' }) }}
                                </td>
                                <td class="px-6 py-4 text-end">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openShowModal(user)" class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20 rounded-lg transition-colors" :title="t('common.view')">
                                            <Eye class="w-4 h-4" />
                                        </button>
                                        <button @click="openEditModal(user)" class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-neutral-700/50 rounded-lg transition-colors" :title="t('common.edit')">
                                            <Edit class="w-4 h-4" />
                                        </button>
                                        <button @click="openBlockModal(user)" class="p-2 text-amber-500 hover:text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/20 rounded-lg transition-colors" :title="t('events.blockUser')">
                                            <ShieldBan class="w-4 h-4" />
                                        </button>
                                        <button @click="confirmDelete(user)" class="p-2 text-rose-500 hover:text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-900/20 rounded-lg transition-colors" :title="t('events.deleteUser')">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination Controls -->
            <div v-if="users?.links && users?.links.length > 3" class="flex justify-center mt-6 w-full">
                <div class="flex gap-2 flex-wrap justify-center">
                    <template v-for="(link, p) in users.links" :key="p">
                        <div v-if="link.url === null" class="px-3 sm:px-4 py-2 border border-gray-200 dark:border-neutral-700 rounded-xl text-xs sm:text-sm font-medium opacity-50 cursor-not-allowed bg-gray-50 dark:bg-neutral-800 text-gray-500 dark:text-gray-400 whitespace-nowrap" v-html="translatePagination(link.label)"></div>
                        <Link v-else :href="link.url" class="px-3 sm:px-4 py-2 border rounded-xl text-xs sm:text-sm font-medium transition whitespace-nowrap" :class="[link.active ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-500/20' : 'bg-white dark:bg-neutral-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-neutral-700 hover:bg-gray-50 dark:hover:bg-neutral-700']" v-html="translatePagination(link.label)"></Link>
                    </template>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <Dialog v-model:open="isEditModalOpen">
            <DialogContent class="sm:max-w-106.25 dark:bg-neutral-900 dark:border-neutral-800">
                <DialogHeader>
                    <DialogTitle class="dark:text-white">{{ t('events.editUser') }}</DialogTitle>
                    <DialogDescription class="dark:text-gray-400">
                        {{ t('events.editUserDesc') }}
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitEdit" class="space-y-4 py-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="edit_first_name" class="dark:text-gray-300">{{ t('events.firstName') }}</Label>
                            <Input id="edit_first_name" v-model="editForm.first_name" required class="dark:bg-neutral-800 dark:border-neutral-700 dark:text-white" />
                        </div>
                        <div class="space-y-2">
                            <Label for="edit_last_name" class="dark:text-gray-300">{{ t('events.lastName') }}</Label>
                            <Input id="edit_last_name" v-model="editForm.last_name" required class="dark:bg-neutral-800 dark:border-neutral-700 dark:text-white" />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="edit_username" class="dark:text-gray-300">{{ t('events.username') }}</Label>
                        <Input id="edit_username" v-model="editForm.username" required class="dark:bg-neutral-800 dark:border-neutral-700 dark:text-white" />
                    </div>
                    <div class="space-y-2">
                        <Label for="edit_email" class="dark:text-gray-300">{{ t('events.emailAddress') }}</Label>
                        <Input id="edit_email" type="email" v-model="editForm.email" required class="dark:bg-neutral-800 dark:border-neutral-700 dark:text-white" />
                    </div>
                    <div class="space-y-2">
                        <Label for="edit_role" class="dark:text-gray-300">{{ t('events.roleLabel') }}</Label>
                        <select id="edit_role" v-model="editForm.role" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:cursor-not-allowed disabled:opacity-50 dark:bg-neutral-800 dark:border-neutral-700 dark:text-white" required>
                            <option value="participant">{{ t('events.roleParticipant') }}</option>
                            <option value="organisateur">{{ t('events.roleOrganizer') }}</option>
                            <option value="revendeur">{{ t('events.roleRevendeur') }}</option>
                            <option value="administrateur">{{ t('events.roleAdmin') }}</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="edit_password" class="dark:text-gray-300">{{ t('events.newPassword') }} <span class="text-xs text-gray-500 font-normal">({{ t('events.optional') }})</span></Label>
                        <Input id="edit_password" type="password" v-model="editForm.password" class="dark:bg-neutral-800 dark:border-neutral-700 dark:text-white" />
                    </div>
                    <DialogFooter>
                        <Button type="submit" :disabled="editForm.processing" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl">{{ t('common.save') }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Show Modal -->
        <Dialog v-model:open="isShowModalOpen">
            <DialogContent class="sm:max-w-106.25 dark:bg-neutral-900 dark:border-neutral-800" v-if="selectedUser">
                <DialogHeader>
                    <DialogTitle class="dark:text-white">{{ t('events.userProfile') }}</DialogTitle>
                </DialogHeader>
                <div class="py-6 space-y-8">
                    <div class="flex items-center gap-5 bg-gray-50 dark:bg-neutral-800/50 p-4 rounded-2xl border border-gray-100 dark:border-neutral-800">
                        <div class="w-16 h-16 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-black text-2xl shadow-sm">
                            {{ (selectedUser.first_name || 'U').charAt(0) }}{{ (selectedUser.last_name || '').charAt(0) }}
                        </div>
                        <div>
                            <h3 class="font-black text-xl text-gray-900 dark:text-white">{{ selectedUser.first_name }} {{ selectedUser.last_name }}</h3>
                            <p class="text-indigo-600 dark:text-indigo-400 font-medium">@{{ selectedUser.username }}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-y-6 gap-x-4">
                        <div class="bg-gray-50 dark:bg-neutral-800/30 p-3 rounded-xl">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold mb-1">{{ t('events.emailAddress') }}</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-gray-100 break-all">{{ selectedUser.email }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-neutral-800/30 p-3 rounded-xl">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold mb-1">{{ t('events.roleLabel') }}</p>
                            <span :class="['px-2.5 py-1 text-xs font-bold rounded-md border', getRoleBadgeColor(selectedUser.role)]">
                                {{ displayRole(selectedUser.role) }}
                            </span>
                        </div>
                        <div class="bg-gray-50 dark:bg-neutral-800/30 p-3 rounded-xl col-span-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-bold mb-1">{{ t('events.memberSince') }}</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <CheckCircle class="w-4 h-4 text-emerald-500" />
                                {{ new Date(selectedUser.created_at).toLocaleDateString(locale, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                            </p>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Block Modal -->
        <Dialog v-model:open="isBlockModalOpen">
            <DialogContent class="sm:max-w-[400px] dark:bg-neutral-900 dark:border-neutral-800" v-if="selectedUser">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-amber-600 dark:text-amber-500 text-xl font-bold">
                        <ShieldBan class="w-6 h-6" />
                        {{ t('events.blockUser') }}
                    </DialogTitle>
                    <DialogDescription class="dark:text-gray-400 text-base mt-2">
                        {{ t('events.suspendAccessFor') }} <strong class="text-gray-900 dark:text-white">{{ selectedUser.username }}</strong>. {{ t('events.leaveEmptyForPermanent') }}
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitBlock" class="space-y-6 py-4">
                    <div class="space-y-2">
                        <Label for="block_days" class="dark:text-gray-300 font-bold">{{ t('events.durationDays') }} <span class="text-xs text-gray-500 font-normal">({{ t('events.optional') }})</span></Label>
                        <Input id="block_days" type="number" min="1" v-model="blockForm.days" :placeholder="t('events.eg7')" class="dark:bg-neutral-800 dark:border-neutral-700 dark:text-white h-12 text-lg" />
                    </div>
                    <DialogFooter class="gap-3 sm:gap-2">
                        <Button type="button" variant="outline" @click="isBlockModalOpen = false" class="w-full sm:w-auto dark:border-neutral-700 dark:text-gray-300 dark:hover:bg-neutral-800 rounded-xl">{{ t('common.cancel') }}</Button>
                        <Button type="submit" class="w-full sm:w-auto bg-amber-500 hover:bg-amber-600 focus:ring-amber-500 text-white font-bold rounded-xl" :disabled="blockForm.processing">
                            {{ t('events.confirmSuspension') }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
