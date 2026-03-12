<script setup lang="ts">
import { ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { 
    Dialog, 
    DialogContent, 
    DialogDescription, 
    DialogHeader, 
    DialogTitle, 
    DialogFooter 
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { CreditCard, Info } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits(['update:open']);

const page = usePage();
const auth = page.props.auth as any;
const rib = ref('');
const processing = ref(false);
const error = ref('');

const closePopup = async (skip = false) => {
    if (skip && auth.user?.organisateur) {
        router.put(`/web-api/organisateurs/${auth.user.organisateur.id}`, {
            rib_popup_seen: true
        }, {
            preserveScroll: true,
            onSuccess: () => {
                emit('update:open', false);
            }
        });
    } else {
        emit('update:open', false);
    }
};

const submitRib = async () => {
    if (!rib.value) {
        error.value = 'Please enter your RIB.';
        return;
    }

    processing.value = true;
    error.value = '';

    router.put(`/web-api/organisateurs/${auth.user.organisateur.id}`, {
        rib: rib.value,
        rib_popup_seen: true
    }, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:open', false);
        },
        onError: (err) => {
            error.value = Object.values(err)[0] as string || 'Failed to update bank information.';
        },
        onFinish: () => {
            processing.value = false;
        }
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="closePopup(false)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <div class="mx-auto w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mb-4">
                    <CreditCard class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <DialogTitle class="text-center text-xl">Bank Information Required</DialogTitle>
                <DialogDescription class="text-center">
                    To receive payments from event registrations, we need your bank account information (RIB).
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-2">
                <div class="space-y-2">
                    <Label for="rib">RIB (Bank Account Number)</Label>
                    <Input 
                        id="rib" 
                        v-model="rib" 
                        placeholder="Enter your 20-digit RIB" 
                        class="font-mono"
                        maxlength="30"
                    />
                    <p v-if="error" class="text-xs text-red-500 font-medium">{{ error }}</p>
                </div>

                <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 flex gap-3">
                    <Info class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
                    <p class="text-xs text-amber-800 dark:text-amber-300">
                        <strong>Note:</strong> You can skip this for now, but you won't be able to publish events until your bank information is provided.
                    </p>
                </div>
            </div>

            <DialogFooter class="flex flex-col sm:flex-row gap-2">
                <Button variant="ghost" @click="closePopup(true)" class="sm:flex-1">Skip for now</Button>
                <Button @click="submitRib" :disabled="processing" class="bg-blue-600 hover:bg-blue-700 sm:flex-1">
                    {{ processing ? 'Saving...' : 'Save Information' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
