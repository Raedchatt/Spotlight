<template>
  <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden max-w-sm w-full border border-gray-100 dark:border-gray-700 transition-all duration-300">
    <div class="p-6">
      <!-- Title -->
      <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-1 tracking-tight">
        {{ event.titre }}
      </h3>
      
      <!-- Price per ticket -->
      <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">
        <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price per ticket</span>
        <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
          {{ formatPrice(unitPrice) }}
        </span>
      </div>

      <!-- Ticket Type Selection (Tournaments Only) -->
      <div v-if="event.is_tournoi" class="mb-6">
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
          Reserve as
        </label>
        <div class="grid grid-cols-2 gap-3">
          <button 
            type="button"
            @click="ticketType = 'spectator'"
            :class="[
              'py-2 px-4 rounded-xl border-2 transition-all font-medium text-sm',
              ticketType === 'spectator' 
                ? 'border-blue-600 bg-blue-50 text-blue-600 dark:bg-blue-900/20' 
                : 'border-gray-100 bg-gray-50 text-gray-500 dark:bg-gray-700 dark:border-gray-600'
            ]"
          >
            Spectator
          </button>
          <button 
            type="button"
            @click="ticketType = 'participant'"
            :class="[
              'py-2 px-4 rounded-xl border-2 transition-all font-medium text-sm',
              ticketType === 'participant' 
                ? 'border-blue-600 bg-blue-50 text-blue-600 dark:bg-blue-900/20' 
                : 'border-gray-100 bg-gray-50 text-gray-500 dark:bg-gray-700 dark:border-gray-600'
            ]"
          >
            Participant
          </button>
        </div>
      </div>

      <!-- Quantity Selector -->
      <div class="mb-6">
        <label for="quantity" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
          Ticket Quantity
        </label>
        
        <div class="flex items-center space-x-4">
          <button 
            type="button" 
            @click="decrement" 
            :disabled="quantity <= 1 || isSubmitting"
            class="w-12 h-12 rounded-full flex items-center justify-center bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 shadow-sm border border-gray-200 dark:border-gray-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed group"
          >
            <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
          </button>
          
          <input 
            type="number" 
            id="quantity" 
            v-model.number="quantity" 
            min="1" 
            :max="maxCapacity"
            :disabled="isSubmitting"
            class="block w-full text-center text-xl font-bold border-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors shadow-sm py-2 px-3"
          >
          
          <button 
            type="button" 
            @click="increment" 
            :disabled="quantity >= maxCapacity || isSubmitting"
            class="w-12 h-12 rounded-full flex items-center justify-center bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 shadow-sm border border-gray-200 dark:border-gray-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed group"
          >
            <svg class="w-5 h-5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
          </button>
        </div>
        
        <!-- Max Capacity Warning -->
        <p v-if="quantity >= maxCapacity" class="text-xs text-amber-500 dark:text-amber-400 mt-2 font-medium flex items-center">
          <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
          Max capacity reached
        </p>
      </div>

      <!-- Total Calculation Card -->
      <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-800/50 rounded-2xl p-5 mb-6 border border-gray-200/50 dark:border-gray-600/50">
        <div class="flex justify-between items-center">
          <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">Total Price</span>
          <span class="text-3xl font-black text-blue-600 dark:text-blue-400">
            {{ formatPrice(totalPrice) }}
          </span>
        </div>
      </div>

      <!-- Live Messaging (Success/Error) -->
      <div class="min-h-[3rem] mb-4">
        <transition 
          enter-active-class="transition ease-out duration-300"
          enter-from-class="opacity-0 translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition ease-in duration-200"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-2"
          mode="out-in"
        >
          <div v-if="successMessage" class="p-3 bg-green-50/80 dark:bg-green-900/40 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 rounded-xl text-sm font-medium flex items-start shadow-sm">
            <svg class="w-5 h-5 mr-2 shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ successMessage }}
          </div>
          <div v-else-if="errorMessage" class="p-3 bg-red-50/80 dark:bg-red-900/40 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 rounded-xl text-sm font-medium flex items-start shadow-sm">
             <svg class="w-5 h-5 mr-2 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ errorMessage }}
          </div>
        </transition>
      </div>

      <!-- Action Button -->
      <button 
        @click="submitReservation" 
        :disabled="!isValid || isSubmitting"
        class="w-full relative flex items-center justify-center py-4 px-6 border border-transparent rounded-2xl shadow-blue-200 dark:shadow-none shadow-lg text-base font-bold text-white bg-blue-600 hover:bg-blue-700 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-70 disabled:cursor-not-allowed disabled:shadow-none transition-all duration-200 ease-in-out"
      >
        <span v-if="isSubmitting" class="absolute left-6">
          <svg class="animate-spin h-5 w-5 text-white/80" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </span>
        <span :class="{'opacity-90 pl-6': isSubmitting}">
          {{ isSubmitting ? 'Processing...' : 'Confirm Reservation' }}
        </span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  event: {
    type: Object,
    required: true,
  }
})

const emit = defineEmits(['reservation-success'])

const quantity = ref(1)
const ticketType = ref(props.event.is_tournoi ? 'spectator' : 'standard')
const isSubmitting = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const unitPrice = computed(() => {
  if (props.event.is_tournoi && ticketType.value === 'participant') {
    return props.event?.prix_participant || 0
  }
  return props.event?.prix_spectateur || 0
})

const maxCapacity = computed(() => {
  if (props.event.is_tournoi && ticketType.value === 'participant') {
    return props.event?.capacite_participant || 0
  }
  return props.event?.capacite_spectateur || 0
})

const totalPrice = computed(() => {
  return quantity.value * unitPrice.value
})

const isValid = computed(() => {
  const q = quantity.value
  return q >= 1 && q <= maxCapacity.value
})

const formatPrice = (price) => {
  return new Intl.NumberFormat('fr-TN', {
    style: 'currency',
    currency: 'TND',
    minimumFractionDigits: 3
  }).format(price)
}

const increment = () => {
  if (quantity.value < maxCapacity.value) {
    quantity.value++
  }
}

const decrement = () => {
  if (quantity.value > 1) {
    quantity.value--
  }
}

watch(quantity, (newVal) => {
  if (successMessage.value || errorMessage.value) {
    successMessage.value = ''
    errorMessage.value = ''
  }
  
  if (newVal < 1) {
    quantity.value = 1
  } else if (newVal > maxCapacity.value) {
    quantity.value = maxCapacity.value
  }
})

watch(ticketType, () => {
  if (quantity.value > maxCapacity.value) {
    quantity.value = maxCapacity.value
  }
})

const submitReservation = async () => {
  if (!isValid.value) return

  try {
    isSubmitting.value = true
    successMessage.value = ''
    errorMessage.value = ''

    const response = await axios.post('/api/reservations', {
      evenement_id: props.event.id,
      nombre_tickets: quantity.value,
      ticket_type: ticketType.value
    })

    successMessage.value = 'Reservation confirmed successfully!'
    quantity.value = 1 
    
    emit('reservation-success', response.data)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'An error occurred during reservation. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}
</script>
