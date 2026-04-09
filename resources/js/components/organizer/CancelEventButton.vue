<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Trash2, Loader2 } from 'lucide-vue-next';
import axios from 'axios';
import { toast } from 'vue-sonner';

const props = defineProps<{
  eventId: number | string;
  eventTitle: string;
}>();

const emit = defineEmits(['cancelled']);
const isSubmitting = ref(false);

const handleCancel = async () => {
  const confirmed = window.confirm(`Êtes-vous sûr de vouloir annuler l'événement "${props.eventTitle}" ?\n\nCette action est irréversible et tous les participants seront remboursés.`);
  
  if (!confirmed) return;

  try {
    isSubmitting.value = true;
    const response = await axios.post(`/web-api/events/${props.eventId}/cancel`);
    
    toast.success(`Success: ${response.data.refunded_count} refunds processed.`);
    emit('cancelled', props.eventId);
  } catch (error: any) {
    console.error('Erreur:', error);
    toast.error(error.response?.data?.message || 'Error cancelling the event.');
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <Button 
    variant="destructive" 
    size="sm" 
    @click="handleCancel" 
    :disabled="isSubmitting"
    class="gap-2 rounded-xl shadow-sm hover:shadow-md transition-all"
  >
    <Loader2 v-if="isSubmitting" class="h-4 w-4 animate-spin" />
    <Trash2 v-else class="h-4 w-4" />
    {{ isSubmitting ? 'Annulation...' : 'Annuler' }}
  </Button>
</template>
