<script setup>
import { 
    ExclamationTriangleIcon, 
    CheckCircleIcon, 
    InformationCircleIcon, 
    XCircleIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        required: true
    },
    message: {
        type: String,
        default: ''
    },
    type: {
        type: String, // 'info', 'success', 'warning', 'danger'
        default: 'warning'
    },
    confirmText: {
        type: String,
        default: 'Confirmer'
    },
    cancelText: {
        type: String,
        default: 'Annuler'
    },
    isLoading: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['confirm', 'cancel'])

const icons = {
    info: InformationCircleIcon,
    success: CheckCircleIcon,
    warning: ExclamationTriangleIcon,
    danger: XCircleIcon
}

const colors = {
    info: {
        bg: 'bg-cyan-500/10',
        text: 'text-cyan-400',
        border: 'border-cyan-500/20',
        button: 'bg-gradient-to-r from-cyan-600 to-blue-700 shadow-cyan-900/40 hover:brightness-110 active:scale-95'
    },
    success: {
        bg: 'bg-amber-500/10',
        text: 'text-amber-400',
        border: 'border-amber-500/20',
        button: 'bg-gradient-to-r from-amber-600 to-yellow-700 shadow-amber-900/40 hover:brightness-110 active:scale-95'
    },
    warning: {
        bg: 'bg-orange-500/10',
        text: 'text-orange-400',
        border: 'border-orange-500/20',
        button: 'bg-gradient-to-r from-orange-600 to-amber-700 shadow-orange-900/40 hover:brightness-110 active:scale-95'
    },
    danger: {
        bg: 'bg-rose-500/10',
        text: 'text-rose-400',
        border: 'border-rose-500/20',
        button: 'bg-gradient-to-r from-rose-600 to-red-700 shadow-rose-900/40 hover:brightness-110 active:scale-95'
    }
}
</script>

<template>
    <transition name="modal">
        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-hidden">
            <!-- Backdrop -->
            <transition name="backdrop">
                <div v-if="isOpen" class="fixed inset-0 bg-black/80 backdrop-blur-xl" @click="!isLoading && emit('cancel')"></div>
            </transition>

            <!-- Modal Panel -->
            <transition name="panel">
                <div v-if="isOpen" class="relative w-full max-w-md mx-auto z-50">
                    <div class="relative flex flex-col w-full bg-slate-900/80 backdrop-blur-3xl border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.8)] rounded-[3rem] overflow-hidden">
                        
                        <!-- Decorative scanlines -->
                        <div class="absolute inset-0 pointer-events-none opacity-[0.03]" 
                            style="background-image: linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px); background-size: 20px 20px;">
                        </div>

                        <!-- Top decorative bar -->
                        <div class="h-1.5 w-full bg-gradient-to-r" :class="{
                            'from-cyan-500 to-blue-600': type === 'info',
                            'from-amber-500 to-yellow-600': type === 'success',
                            'from-orange-400 to-amber-600': type === 'warning',
                            'from-rose-500 to-red-600': type === 'danger',
                        }"></div>

                        <!-- Content -->
                        <div class="relative p-10 sm:p-12 flex-auto">
                            <!-- Close button -->
                            <button :disabled="isLoading" @click="emit('cancel')" class="absolute top-10 right-10 p-3 rounded-2xl text-slate-500 hover:text-white hover:bg-white/5 transition-all disabled:opacity-50">
                                <XMarkIcon class="h-6 w-6" />
                            </button>

                            <div class="flex flex-col items-center text-center">
                                <!-- Icon -->
                                <div class="mb-8 inline-flex items-center justify-center w-24 h-24 rounded-[2.5rem] bg-white/5 shadow-inner" :class="colors[type].text">
                                    <component :is="icons[type]" class="h-12 w-12" />
                                </div>
                                
                                <!-- Text -->
                                <h3 class="text-3xl font-black text-white mb-4 tracking-tight uppercase leading-none">
                                    {{ title }}
                                </h3>
                                <p v-if="message" class="text-slate-400 font-bold text-sm tracking-wide leading-relaxed max-w-[280px]">
                                    {{ message }}
                                </p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-center p-10 bg-black/40 gap-4 border-t border-white/5">
                            <button 
                                type="button" 
                                class="flex-1 px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 bg-white/5 border border-white/5 rounded-2xl hover:bg-white/10 hover:text-white transition active:scale-95 disabled:opacity-50 disabled:pointer-events-none"
                                :disabled="isLoading"
                                @click="emit('cancel')"
                            >
                                {{ cancelText }}
                            </button>
                            <button 
                                type="button" 
                                class="flex-1 px-6 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-white shadow-2xl rounded-2xl transition focus:outline-none flex items-center justify-center gap-2 disabled:opacity-50 disabled:pointer-events-none"
                                :class="colors[type].button"
                                :disabled="isLoading"
                                @click="emit('confirm')"
                            >
                                <svg v-if="isLoading" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ isLoading ? 'En cours...' : confirmText }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </transition>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.backdrop-enter-active,
.backdrop-leave-active {
  transition: opacity 0.3s ease;
}
.backdrop-enter-from,
.backdrop-leave-to {
  opacity: 0;
}

.panel-enter-active {
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.panel-leave-active {
  transition: all 0.2s ease-in;
}
.panel-enter-from,
.panel-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(10px);
}
</style>
