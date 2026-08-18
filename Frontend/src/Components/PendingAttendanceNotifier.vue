<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import api from '@/services/api'
import { 
    BellAlertIcon, 
    XMarkIcon,
    ArrowRightIcon,
    ClockIcon
} from '@heroicons/vue/24/outline'

const page = usePage()
const roles = computed(() => page.props.auth?.user?.roles || [])
const isAuthorized = computed(() => {
    return roles.value.includes('Directeur') || roles.value.includes('Secrétaire') || roles.value.includes('Admin')
})

const alerts = ref([])
const showModal = ref(false)
const dismissedAlertIds = ref(new Set())
let pollInterval = null

// Web Audio API Synthesized Emergency Warning Sound
const playAlertSound = () => {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext
        if (!AudioContext) return
        const ctx = new AudioContext()

        // Beep 1: High tone (880Hz)
        const osc1 = ctx.createOscillator()
        const gain1 = ctx.createGain()
        osc1.type = 'sine'
        osc1.frequency.setValueAtTime(880, ctx.currentTime)
        gain1.gain.setValueAtTime(0.3, ctx.currentTime)
        gain1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3)
        osc1.connect(gain1)
        gain1.connect(ctx.destination)
        osc1.start(ctx.currentTime)
        osc1.stop(ctx.currentTime + 0.3)

        // Beep 2: Higher tone (1174Hz)
        const osc2 = ctx.createOscillator()
        const gain2 = ctx.createGain()
        osc2.type = 'sine'
        osc2.frequency.setValueAtTime(1174.66, ctx.currentTime + 0.35)
        gain2.gain.setValueAtTime(0.4, ctx.currentTime + 0.35)
        gain2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.75)
        osc2.connect(gain2)
        gain2.connect(ctx.destination)
        osc2.start(ctx.currentTime + 0.35)
        osc2.stop(ctx.currentTime + 0.75)
    } catch (e) {
        console.warn("Audio alert blocked or unsupported:", e)
    }
}

const checkPendingAlerts = async () => {
    if (!isAuthorized.value) return

    try {
        const response = await api.get('/attendance/pending-alerts')
        const incomingAlerts = response.data.alerts || []

        // Filter out alerts that user dismissed in current session
        const activeAlerts = incomingAlerts.filter(a => !dismissedAlertIds.value.has(a.schedule_id))

        if (activeAlerts.length > 0) {
            const currentIds = alerts.value.map(a => a.schedule_id)
            const hasNewAlert = activeAlerts.some(a => !currentIds.includes(a.schedule_id))

            alerts.value = activeAlerts
            showModal.value = true

            if (hasNewAlert) {
                playAlertSound()
            }
        } else {
            alerts.value = []
            showModal.value = false
        }
    } catch (error) {
        console.error("Erreur lors de la vérification des alertes d'émargement:", error)
    }
}

const dismissAlert = (scheduleId) => {
    dismissedAlertIds.value.add(scheduleId)
    alerts.value = alerts.value.filter(a => a.schedule_id !== scheduleId)
    if (alerts.value.length === 0) {
        showModal.value = false
    }
}

const dismissAll = () => {
    alerts.value.forEach(a => dismissedAlertIds.value.add(a.schedule_id))
    alerts.value = []
    showModal.value = false
}

const goToAttendance = () => {
    showModal.value = false
    router.visit(route('attendance.index'))
}

onMounted(() => {
    if (isAuthorized.value) {
        checkPendingAlerts()
        pollInterval = setInterval(checkPendingAlerts, 25000)
    }
})

onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval)
    }
})
</script>

<template>
    <div v-if="isAuthorized && showModal && alerts.length > 0" class="fixed top-5 right-5 z-[9999] max-w-md w-full animate-bounce-short">
        <div class="relative bg-slate-900 border-2 border-red-500 rounded-3xl shadow-2xl p-5 text-white overflow-hidden backdrop-blur-xl">
            <!-- Pulsing emergency bg glow -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-red-600/30 rounded-full blur-2xl animate-pulse pointer-events-none"></div>

            <!-- Header -->
            <div class="flex items-center justify-between pb-3 border-b border-white/10 relative z-10">
                <div class="flex items-center gap-2.5">
                    <div class="h-10 w-10 bg-red-600/20 border border-red-500/40 rounded-xl flex items-center justify-center text-red-400 shrink-0">
                        <BellAlertIcon class="h-6 w-6 animate-pulse" />
                    </div>
                    <div>
                        <h4 class="text-sm font-black tracking-tight text-white flex items-center gap-1.5">
                            Retard Émargement 
                            <span class="px-2 py-0.5 bg-red-600 text-white text-[9px] font-black rounded-full uppercase tracking-wider animate-pulse">
                                +5 min
                            </span>
                        </h4>
                        <p class="text-[10px] text-slate-400 font-bold">Alerte Direction & Secrétariat</p>
                    </div>
                </div>

                <button @click="dismissAll" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-white/10 transition-colors">
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </div>

            <!-- Alert List -->
            <div class="py-3 space-y-2.5 max-h-60 overflow-y-auto custom-scrollbar relative z-10">
                <div 
                    v-for="alert in alerts" 
                    :key="alert.schedule_id"
                    class="p-3 bg-white/5 border border-white/10 rounded-2xl flex items-center justify-between hover:bg-white/10 transition-all"
                >
                    <div class="min-w-0 flex-1 pr-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-black text-red-400">{{ alert.formateur }}</span>
                            <span class="text-[9px] font-bold text-slate-400 bg-slate-800 px-2 py-0.5 rounded-full border border-slate-700">
                                {{ alert.room }}
                            </span>
                        </div>
                        <p class="text-xs font-bold text-white mt-0.5 truncate">{{ alert.group_name }}</p>
                        <div class="flex items-center gap-1.5 mt-1 text-[10px] text-amber-400 font-bold">
                            <ClockIcon class="h-3.5 w-3.5" />
                            <span>Début : {{ alert.start_time }} (Retard: {{ alert.minutes_late }} min)</span>
                        </div>
                    </div>

                    <button 
                        @click="dismissAlert(alert.schedule_id)" 
                        class="text-[9px] font-black uppercase tracking-wider text-slate-400 hover:text-white px-2 py-1 bg-white/5 rounded-lg border border-white/10 hover:bg-white/20 transition"
                        title="Masquer cet avertissement"
                    >
                        Ignorer
                    </button>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="pt-3 border-t border-white/10 flex items-center justify-between gap-3 relative z-10">
                <button 
                    @click="dismissAll" 
                    class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition-all"
                >
                    Fermer Tout
                </button>
                <button 
                    @click="goToAttendance" 
                    class="flex-1 py-2 px-4 bg-gradient-to-r from-red-600 to-orange-600 hover:from-red-700 hover:to-orange-700 text-white rounded-xl font-extrabold text-xs uppercase tracking-wider shadow-lg flex items-center justify-center gap-1.5 transition-all transform active:scale-95"
                >
                    <span>Gérer Émargement</span>
                    <ArrowRightIcon class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes bounceShort {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}
.animate-bounce-short {
    animation: bounceShort 0.6s ease-in-out 2;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 3px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
}
</style>
