<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Trash2, Loader2 } from 'lucide-vue-next';
import axios from 'axios';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const props = defineProps<{
  eventId: number | string;
  eventTitle: string;
}>();

const emit = defineEmits(['cancelled']);
const isSubmitting = ref(false);

const handleCancel = async () => {
  const confirmed = window.confirm(t('events.confirmCancel', { title: props.eventTitle }));
  
  if (!confirmed) return;

  try {
    isSubmitting.value = true;
    const response = await axios.post(`/web-api/events/${props.eventId}/cancel`);
    
    toast.success(t('events.cancelSuccess', { count: response.data.refunded_count }));
    emit('cancelled', props.eventId);
  } catch (error: any) {
    console.error('Error:', error);
    toast.error(error.response?.data?.message || t('events.cancelError'));
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
    {{ isSubmitting ? t('events.cancelling') : t('events.cancel') }}
  </Button>
</template>
