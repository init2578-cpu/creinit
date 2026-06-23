<script setup>
import { computed, watch } from 'vue'
import { 
    CheckCircleIcon, 
    XCircleIcon, 
    ExclamationTriangleIcon, 
    InformationCircleIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    isOpen: Boolean,
    type: {
        type: String,
        default: 'success' // success, error, warning, info
    },
    title: String,
    message: String,
    buttonText: {
        type: String,
        default: 'Fermer'
    }
})

const emit = defineEmits(['close'])

const icon = computed(() => {
    switch (props.type) {
        case 'success': return CheckCircleIcon
        case 'error': return XCircleIcon
        case 'warning': return ExclamationTriangleIcon
        default: return InformationCircleIcon
    }
})

const colors = computed(() => {
    switch (props.type) {
        case 'success': return 'text-amber-400 bg-amber-400/10'
        case 'error': return 'text-rose-500 bg-rose-500/10'
        case 'warning': return 'text-orange-400 bg-orange-400/10'
        default: return 'text-cyan-400 bg-cyan-400/10'
    }
})

const buttonColor = computed(() => {
    switch (props.type) {
        case 'success': return 'bg-gradient-to-r from-amber-500 to-yellow-600 shadow-amber-900/40'
        case 'error': return 'bg-gradient-to-r from-rose-500 to-red-600 shadow-rose-900/40'
        case 'warning': return 'bg-gradient-to-r from-orange-400 to-amber-600 shadow-orange-900/40'
        default: return 'bg-gradient-to-r from-cyan-500 to-blue-600 shadow-cyan-900/40'
    }
})

// Auto-close success messages after 5 seconds
watch(() => props.isOpen, (newVal) => {
    if (newVal && props.type === 'success') {
        setTimeout(() => {
            emit('close')
        }, 5000)
    }
})
</script>

<template>
    <transition
        enter-active-class="transition duration-500 cubic-bezier(0.34, 1.56, 0.64, 1)"
        enter-from-class="opacity-0 scale-90 translate-y-4"
        enter-to-class="opacity-100 scale-100 translate-y-0"
        leave-active-class="transition duration-300 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div v-if="isOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 overflow-hidden">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/80 backdrop-blur-xl" @click="emit('close')"></div>

            <!-- Modal -->
            <div class="relative bg-slate-900/80 backdrop-blur-3xl rounded-[3rem] shadow-[0_0_80px_rgba(0,0,0,0.6)] border border-white/10 w-full max-w-md overflow-hidden transform transition-all">
                
                <!-- Decorative scanlines / grid -->
                <div class="absolute inset-0 pointer-events-none opacity-[0.05]" 
                    style="background-image: linear-gradient(0deg, transparent 24%, rgba(255, 255, 255, .05) 25%, rgba(255, 255, 255, .05) 26%, transparent 27%, transparent 74%, rgba(255, 255, 255, .05) 75%, rgba(255, 255, 255, .05) 76%, transparent 77%, transparent), linear-gradient(90deg, transparent 24%, rgba(255, 255, 255, .05) 25%, rgba(255, 255, 255, .05) 26%, transparent 27%, transparent 74%, rgba(255, 255, 255, .05) 75%, rgba(255, 255, 255, .05) 76%, transparent 77%, transparent); background-size: 30px 30px;">
                </div>

                <!-- Top decorative bar -->
                <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r" :class="[
                    type === 'success' ? 'from-amber-400 via-yellow-300 to-amber-600' :
                    type === 'error' ? 'from-rose-500 via-pink-400 to-red-600' :
                    type === 'warning' ? 'from-orange-400 via-amber-300 to-orange-600' :
                    'from-cyan-400 via-blue-300 to-indigo-600'
                ]"></div>

                <div class="p-10 sm:p-12 relative z-10">
                    <div class="flex flex-col items-center text-center">
                        <!-- Icon with glow -->
                        <div :class="['p-6 rounded-[2.5rem] mb-8 relative group', colors]">
                            <div class="absolute inset-0 bg-current opacity-20 blur-2xl rounded-full group-hover:opacity-40 transition-opacity"></div>
                            <component :is="icon" class="h-12 w-12 relative z-10" />
                        </div>

                        <h3 class="text-3xl font-black text-white tracking-tight mb-4 uppercase leading-none">
                            {{ title || (type === 'error' ? 'ALERTE SYSTÈME' : 'OPÉRATION RÉUSSIE') }}
                        </h3>
                        
                        <p class="text-slate-400 font-bold text-sm tracking-wide leading-relaxed max-w-[280px]">
                            {{ message }}
                        </p>
                    </div>

                    <div class="mt-10">
                        <button
                            @click="emit('close')"
                            :class="['w-full py-5 rounded-2xl text-white font-black text-xs uppercase tracking-[0.2em] transition-all active:scale-[0.95] shadow-2xl hover:brightness-110', buttonColor]"
                        >
                            {{ buttonText }}
                        </button>
                    </div>
                </div>

                <button 
                    @click="emit('close')"
                    class="absolute top-8 right-8 p-3 text-slate-500 hover:text-white hover:bg-white/5 rounded-2xl transition-all"
                >
                    <XMarkIcon class="h-6 w-6" />
                </button>
            </div>
        </div>
    </transition>
</template>
