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
import { Eye, Edit, Trash2, UserPlus, ShieldBan } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    users: any;
}>();

const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isShowModalOpen = ref(false);
const isBlockModalOpen = ref(false);
const selectedUser = ref<any>(null);

// Form for Blocking
const blockForm = useForm({
    days: null as number | null,
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
        },
    });
};

const openShowModal = (user: any) => {
    selectedUser.value = user;
    isShowModalOpen.value = true;
};

const confirmDelete = (user: any) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer ${user.first_name || 'cet'} ${user.last_name || 'utilisateur'} ?`)) {
        router.delete(`/admin/users/${user.id}`);
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
        },
    });
};

const getRoleBadgeColor = (role: string) => {
    switch (role) {
        case 'administrateur': return 'bg-red-100 text-red-800 border-red-200';
        case 'organisateur': return 'bg-purple-100 text-purple-800 border-purple-200';
        default: return 'bg-blue-100 text-blue-800 border-blue-200';
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Gestion des Utilisateurs" />

        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <div class="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Utilisateurs</h1>
                    <p class="text-sm text-gray-500 mt-1">Gérez tous les membres de la plateforme (Administrateurs, Organisateurs, Participants).</p>
                </div>
                
                <Dialog v-model:open="isAddModalOpen">
                    <DialogTrigger as-child>
                        <Button class="bg-blue-600 hover:bg-blue-700 text-white gap-2">
                            <UserPlus class="w-4 h-4" />
                            Ajouter un utilisateur
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle>Ajouter un utilisateur</DialogTitle>
                            <DialogDescription>
                                Créez un nouveau compte avec les accès correspondants.
                            </DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="submitAdd" class="space-y-4 py-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="first_name">Prénom</Label>
                                    <Input id="first_name" v-model="form.first_name" required />
                                </div>
                                <div class="space-y-2">
                                    <Label for="last_name">Nom</Label>
                                    <Input id="last_name" v-model="form.last_name" required />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <Label for="username">Nom d'utilisateur</Label>
                                <Input id="username" v-model="form.username" required />
                            </div>
                            <div class="space-y-2">
                                <Label for="email">Email</Label>
                                <Input id="email" type="email" v-model="form.email" required />
                            </div>
                            <div class="space-y-2">
                                <Label for="role">Rôle</Label>
                                <select id="role" v-model="form.role" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                                    <option value="participant">Participant</option>
                                    <option value="organisateur">Organisateur</option>
                                    <option value="administrateur">Administrateur</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <Label for="password">Mot de passe</Label>
                                <Input id="password" type="password" v-model="form.password" required />
                            </div>
                            <DialogFooter>
                                <Button type="submit" :disabled="form.processing">Créer</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50/50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4 font-medium">Utilisateur</th>
                                <th class="px-6 py-4 font-medium">Contact</th>
                                <th class="px-6 py-4 font-medium">Rôle</th>
                                <th class="px-6 py-4 font-medium">Création</th>
                                <th class="px-6 py-4 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="user in users?.data" :key="user.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs">
                                            {{ (user.first_name || 'U').charAt(0) }}{{ (user.last_name || '').charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900">{{ user.first_name }} {{ user.last_name }}</div>
                                            <div class="text-gray-500 text-xs">@{{ user.username }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ user.email }}
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['px-2.5 py-1 text-xs font-semibold rounded-full border', getRoleBadgeColor(user.role)]">
                                        {{ user.role.charAt(0).toUpperCase() + user.role.slice(1) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-500 text-xs">
                                    {{ new Date(user.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button variant="ghost" size="icon" @click="openShowModal(user)" class="text-blue-600 hover:text-blue-700 hover:bg-blue-50">
                                            <Eye class="w-4 h-4" />
                                        </Button>
                                        <Button variant="ghost" size="icon" @click="openEditModal(user)" class="text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                                            <Edit class="w-4 h-4" />
                                        </Button>
                                        <Button variant="ghost" size="icon" @click="openBlockModal(user)" class="text-orange-500 hover:text-orange-600 hover:bg-orange-50">
                                            <ShieldBan class="w-4 h-4" />
                                        </Button>
                                        <Button variant="ghost" size="icon" @click="confirmDelete(user)" class="text-red-600 hover:text-red-700 hover:bg-red-50">
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination Controls -->
            <div v-if="users?.links && users?.links.length > 3" class="flex justify-center mt-6 space-x-1">
                <template v-for="(link, p) in users.links" :key="p">
                    <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-500 bg-white border border-gray-200 rounded-md opacity-50 cursor-not-allowed" v-html="link.label"></div>
                    <Link v-else :href="link.url" class="px-4 py-2 text-sm border border-gray-200 rounded-md transition-colors" :class="[link.active ? 'bg-blue-600 outline-blue-600 text-white border-blue-600' : 'bg-white hover:bg-gray-50 text-gray-700']" v-html="link.label"></Link>
                </template>
            </div>
        </div>

        <!-- Edit Modal -->
        <Dialog v-model:open="isEditModalOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Modifier un utilisateur</DialogTitle>
                    <DialogDescription>
                        Ajustez les détails du compte de cet utilisateur.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitEdit" class="space-y-4 py-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="edit_first_name">Prénom</Label>
                            <Input id="edit_first_name" v-model="editForm.first_name" required />
                        </div>
                        <div class="space-y-2">
                            <Label for="edit_last_name">Nom</Label>
                            <Input id="edit_last_name" v-model="editForm.last_name" required />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="edit_username">Nom d'utilisateur</Label>
                        <Input id="edit_username" v-model="editForm.username" required />
                    </div>
                    <div class="space-y-2">
                        <Label for="edit_email">Email</Label>
                        <Input id="edit_email" type="email" v-model="editForm.email" required />
                    </div>
                    <div class="space-y-2">
                        <Label for="edit_role">Rôle</Label>
                        <select id="edit_role" v-model="editForm.role" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" required>
                            <option value="participant">Participant</option>
                            <option value="organisateur">Organisateur</option>
                            <option value="administrateur">Administrateur</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="edit_password">Nouveau mot de passe <span class="text-xs text-gray-400 font-normal">(Optionnel)</span></Label>
                        <Input id="edit_password" type="password" v-model="editForm.password" />
                    </div>
                    <DialogFooter>
                        <Button type="submit" :disabled="editForm.processing">Mettre à jour</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Show Modal -->
        <Dialog v-model:open="isShowModalOpen">
            <DialogContent class="sm:max-w-[425px]" v-if="selectedUser">
                <DialogHeader>
                    <DialogTitle>Profil Utilisateur</DialogTitle>
                </DialogHeader>
                <div class="py-6 space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xl">
                            {{ (selectedUser.first_name || 'U').charAt(0) }}{{ (selectedUser.last_name || '').charAt(0) }}
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-gray-900">{{ selectedUser.first_name }} {{ selectedUser.last_name }}</h3>
                            <p class="text-gray-500">@{{ selectedUser.username }}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-y-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Email</p>
                            <p class="text-sm font-medium">{{ selectedUser.email }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Rôle</p>
                            <span :class="['px-2 py-0.5 text-xs font-semibold rounded-md border', getRoleBadgeColor(selectedUser.role)]">
                                {{ selectedUser.role }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Membre depuis</p>
                            <p class="text-sm font-medium">{{ new Date(selectedUser.created_at).toLocaleDateString() }}</p>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Block Modal -->
        <Dialog v-model:open="isBlockModalOpen">
            <DialogContent class="sm:max-w-[380px]" v-if="selectedUser">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <ShieldBan class="w-5 h-5 text-orange-500" />
                        Bloquer l'utilisateur
                    </DialogTitle>
                    <DialogDescription>
                        Bloquer <strong>{{ selectedUser.username }}</strong>. Laissez vide pour un blocage permanent.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitBlock" class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="block_days">Nombre de jours <span class="text-xs text-gray-400">(Optionnel)</span></Label>
                        <Input id="block_days" type="number" min="1" v-model="blockForm.days" placeholder="Ex: 7 jours" />
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="outline" @click="isBlockModalOpen = false">Annuler</Button>
                        <Button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white" :disabled="blockForm.processing">
                            Confirmer le blocage
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
