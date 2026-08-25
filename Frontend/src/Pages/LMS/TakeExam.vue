<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import axios from 'axios'
import Timer from '@/Components/Timer.vue'
import { 
    ExclamationTriangleIcon, 
    ChevronLeftIcon, 
    ChevronRightIcon,
    CheckCircleIcon,
    Bars3Icon,
    XMarkIcon,
    ArrowsPointingOutIcon,
    AcademicCapIcon
} from '@heroicons/vue/24/outline'
import ConfirmModal from '@/Components/ConfirmModal.vue'

const props = defineProps({
    exam: Object
})

const currentQuestionIndex = ref(0)
const showViolationModal = ref(false)
const violationMessage = ref('')
const countdown = ref(15)
const isStarted = ref(false)
const showQuestionMap = ref(false)
const showConfirmSubmit = ref(false)
let countdownInterval = null

const form = useForm({
    answers: {}
})

// Fullscreen & Anti-Cheat
function startExam() {
    if (props.exam.is_practice) {
        isStarted.value = true
        return
    }

    axios.post(route('student.exams.start', props.exam.id))
        .then(() => {
            isStarted.value = true
            requestFullscreen()
        })
        .catch((error) => {
            alert(error.response?.data?.error || "Erreur: Impossible de démarrer l'examen. Il est possible qu'il soit bloqué.");
        })
}

function requestFullscreen() {
    const el = document.documentElement
    if (el.requestFullscreen) {
        el.requestFullscreen().catch(err => {
            console.error("Error attempting to enable full-screen mode:", err.message)
        })
    }
}

function handleVisibilityChange() {
    if (document.hidden && !props.exam.is_practice && isStarted.value) {
        triggerViolation("Sortie d'onglet détectée. Revenez immédiatement !")
    } else if (!document.hidden && countdownInterval) {
        stopViolationCountdown()
    }
}

function handleFullscreenChange() {
    if (!document.fullscreenElement && !props.exam.is_practice && isStarted.value) {
        triggerViolation("Le mode plein écran est obligatoire.")
    } else if (document.fullscreenElement && countdownInterval) {
        stopViolationCountdown()
    }
}

function triggerViolation(message) {
    showViolationModal.value = true
    violationMessage.value = message
    countdown.value = 15
    
    if (countdownInterval) clearInterval(countdownInterval)
    
    countdownInterval = setInterval(() => {
        if (countdown.value > 0) {
            countdown.value--
        } else {
            clearInterval(countdownInterval)
            emergencySubmit()
        }
    }, 1000)
}

function stopViolationCountdown() {
    if (!document.hidden && document.fullscreenElement) {
        if (countdownInterval) {
            clearInterval(countdownInterval)
            countdownInterval = null
        }
        showViolationModal.value = false
    }
}

// Emergency auto-submit
function emergencySubmit() {
    form.post(route('student.exams.submit', props.exam.id), {
        preserveScroll: true,
        onFinish: () => {
            // Force redirect if possible
        }
    })
}

function submitExam() {
    showConfirmSubmit.value = true
}

function handleConfirmSubmit() {
    showConfirmSubmit.value = false
    form.post(route('student.exams.submit', props.exam.id))
}

onMounted(() => {
    if (props.exam && props.exam.questions) {
        props.exam.questions.forEach(q => {
            if (q.type === 'qcm') {
                form.answers[q.id] = []
            } else {
                form.answers[q.id] = ''
            }
        })
    }

    if (!props.exam.is_practice) {
        document.addEventListener('visibilitychange', handleVisibilityChange)
        document.addEventListener('fullscreenchange', handleFullscreenChange)
    }
})

onUnmounted(() => {
    document.removeEventListener('visibilitychange', handleVisibilityChange)
    document.removeEventListener('fullscreenchange', handleFullscreenChange)
    if (countdownInterval) clearInterval(countdownInterval)
})

const currentQuestion = computed(() => props.exam.questions[currentQuestionIndex.value])

function nextQuestion() {
    if (currentQuestionIndex.value < props.exam.questions.length - 1) {
        currentQuestionIndex.value++
    }
}

function prevQuestion() {
    if (currentQuestionIndex.value > 0) {
        currentQuestionIndex.value--
    }
}

function jumpToQuestion(index) {
    currentQuestionIndex.value = index
    showQuestionMap.value = false
}
</script>

<template>
    <Head :title="'Examen - ' + exam.titre" />

    <div class="min-h-screen bg-[#0f172a] text-slate-200 flex flex-col font-sans select-none overflow-hidden relative">
        
        <!-- Start Overlay -->
        <div v-if="!isStarted" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900 bg-opacity-95 backdrop-blur-xl">
            <div class="max-w-md w-full p-10 text-center bg-white/5 border border-white/10 rounded-[3rem] shadow-2xl">
                <div class="w-20 h-20 bg-blue-600 rounded-3xl flex items-center justify-center mx-auto mb-8 shadow-lg shadow-blue-500/20">
                    <AcademicCapIcon class="h-10 w-10 text-white" />
                </div>
                <h2 class="text-3xl font-black text-white mb-4 tracking-tight">{{ exam.titre }}</h2>
                <p class="text-slate-400 mb-10 leading-relaxed text-sm">
                    Cet examen est surveillé. Le mode plein écran sera activé automatiquement. 
                    Toute sortie de l'onglet ou du plein écran sera sanctionnée.
                </p>
                <button 
                    @click="startExam"
                    class="w-full py-5 bg-blue-600 hover:bg-blue-500 text-white rounded-2xl font-black text-xs uppercase tracking-widest transition-all shadow-xl shadow-blue-900/20"
                >
                    Commencer l'épreuve
                </button>
            </div>
        </div>

        <!-- Focused Header -->
        <header class="min-h-[4.5rem] sm:h-24 bg-slate-900/50 backdrop-blur-md border-b border-white/5 px-3 sm:px-8 py-3 sm:py-0 flex items-center justify-between sticky top-0 z-40">
            <div class="flex items-center gap-2 sm:gap-6 min-w-0">
                <button @click="showQuestionMap = true" class="p-2.5 sm:p-4 bg-white/5 hover:bg-white/10 rounded-xl sm:rounded-2xl transition border border-white/5 shrink-0">
                    <Bars3Icon class="h-5 w-5 sm:h-6 sm:w-6 text-white" />
                </button>
                <div class="flex flex-col min-w-0">
                    <h1 class="text-sm sm:text-xl font-bold text-white tracking-tight leading-tight truncate max-w-[140px] sm:max-w-none">{{ exam.titre }}</h1>
                    <p class="text-[9px] sm:text-[10px] text-blue-400 font-black uppercase tracking-wider sm:tracking-[0.2em] mt-0.5 whitespace-nowrap">
                        Question {{ currentQuestionIndex + 1 }} sur {{ exam.questions.length }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-6 shrink-0">
                <Timer v-if="isStarted" :duration-minutes="exam.duree_minutes" :absolute-end-time="exam.end_at" @expired="emergencySubmit" />
                <button v-if="!exam.is_practice" @click="requestFullscreen" class="p-2.5 sm:p-3 bg-white/5 rounded-xl border border-white/5 hover:bg-white/10 transition text-slate-400 hidden sm:flex" title="Rétablir le plein écran">
                    <ArrowsPointingOutIcon class="h-4 w-4 sm:h-5 sm:w-5" />
                </button>
            </div>
        </header>

        <!-- Question Area -->
        <main class="flex-1 flex flex-col items-center justify-center p-3 sm:p-8 py-4 sm:py-8 relative overflow-y-auto custom-scrollbar">
            <!-- Glassmorphic Card -->
            <div v-if="currentQuestion" class="w-full max-w-4xl bg-white/5 backdrop-blur-xl rounded-2xl sm:rounded-[3.5rem] shadow-2xl border border-white/10 p-4 sm:p-12 transition-all duration-500 hover:border-white/20">
                <div class="mb-6 sm:mb-10">
                    <div class="flex items-center gap-2 sm:gap-3 mb-4 sm:mb-6">
                        <span class="px-3 sm:px-4 py-1 bg-blue-500/20 text-blue-400 text-[9px] sm:text-[10px] font-black uppercase tracking-widest rounded-full border border-blue-500/30">
                            {{ currentQuestion.points }} Points
                        </span>
                        <span class="px-3 sm:px-4 py-1 bg-white/5 text-slate-400 text-[9px] sm:text-[10px] font-black uppercase tracking-widest rounded-full border border-white/5">
                            {{ currentQuestion.type === 'qcm' ? 'Choix Multiple' : 'Question Ouverte' }}
                        </span>
                    </div>
                    <p class="text-base sm:text-2xl md:text-3xl font-bold text-white leading-relaxed tracking-tight break-words">
                        {{ currentQuestion.enonce }}
                    </p>
                </div>

                <!-- Options / Inputs -->
                <div v-if="currentQuestion.type === 'qcm'" class="grid grid-cols-1 gap-3 sm:gap-4">
                    <label 
                        v-for="(option, idx) in currentQuestion.options" 
                        :key="option.id"
                        class="relative group flex items-start p-3.5 sm:p-6 cursor-pointer rounded-xl sm:rounded-3xl border-2 transition-all gap-2 sm:gap-4"
                        :class="form.answers[currentQuestion.id]?.includes(option.id) 
                            ? 'border-blue-600 bg-blue-600/10' 
                            : 'border-white/5 bg-white/5 hover:bg-white/10 hover:border-white/10'"
                    >
                        <input 
                            type="checkbox" 
                            :name="'q-' + currentQuestion.id" 
                            v-model="form.answers[currentQuestion.id]" 
                            :value="option.id"
                            class="hidden"
                        />
                        <div 
                            class="h-7 w-7 sm:h-8 sm:w-8 rounded-lg sm:rounded-xl border-2 flex items-center justify-center shrink-0 font-black text-xs mt-0.5 transition-all"
                            :class="form.answers[currentQuestion.id]?.includes(option.id) 
                                ? 'border-blue-500 bg-blue-500 text-white' 
                                : 'border-white/20 bg-transparent text-slate-500 group-hover:border-white/40'"
                        >
                            {{ String.fromCharCode(65 + idx) }}
                        </div>
                        <span class="text-sm sm:text-base md:text-lg text-slate-200 font-medium group-hover:text-white transition-colors leading-relaxed break-words flex-1 min-w-0">
                            {{ option.texte }}
                        </span>
                        
                        <div v-if="form.answers[currentQuestion.id]?.includes(option.id)" class="shrink-0 self-center ml-1 sm:ml-auto">
                            <CheckCircleIcon class="h-5 w-5 sm:h-6 sm:w-6 text-blue-500" />
                        </div>
                    </label>
                </div>
                <div v-else-if="currentQuestion.type === 'open'" class="mt-2 sm:mt-4">
                    <textarea 
                        v-model="form.answers[currentQuestion.id]"
                        class="w-full h-36 sm:h-48 bg-white/5 border border-white/10 rounded-xl sm:rounded-[2rem] p-4 sm:p-8 text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all resize-none placeholder-slate-500 text-sm sm:text-lg"
                        placeholder="Saisissez votre réponse ici..."
                    ></textarea>
                </div>
            </div>
            
            <div v-else-if="exam.questions.length === 0" class="text-center p-8 sm:p-12 bg-white/5 rounded-3xl border border-dashed border-white/10 max-w-lg">
                <ExclamationTriangleIcon class="h-10 w-10 sm:h-12 sm:w-12 text-blue-400 mx-auto mb-4" />
                <h3 class="text-lg sm:text-xl font-bold text-white mb-2">Aucune question</h3>
                <p class="text-slate-400 text-xs sm:text-sm">Cet examen ne contient aucune question pour le moment. Veuillez contacter votre formateur.</p>
            </div>
        </main>

        <!-- Navigation Controls -->
        <footer class="h-20 sm:h-28 bg-slate-900/50 backdrop-blur-md border-t border-white/5 px-3 sm:px-12 flex items-center justify-between sticky bottom-0 z-40 shrink-0">
            <button 
                @click="prevQuestion" 
                :disabled="currentQuestionIndex === 0"
                class="flex items-center gap-1.5 sm:gap-2 px-3 sm:px-8 py-2.5 sm:py-4 rounded-xl sm:rounded-2xl font-black text-[11px] sm:text-xs uppercase tracking-wider sm:tracking-widest text-slate-400 hover:bg-white/5 disabled:opacity-10 disabled:cursor-not-allowed transition"
            >
                <ChevronLeftIcon class="h-4 w-4 sm:h-5 sm:w-5" />
                Précédent
            </button>

            <!-- Progress Pips -->
            <div class="hidden lg:flex items-center gap-3">
                <div 
                    v-for="(_, i) in exam.questions" 
                    :key="i"
                    @click="jumpToQuestion(i)"
                    class="h-1.5 rounded-full transition-all cursor-pointer"
                    :class="[
                        i === currentQuestionIndex ? 'bg-blue-500 w-12' : (form.answers[exam.questions[i].id] ? 'bg-emerald-500/50 w-6' : 'bg-white/10 w-4 hover:bg-white/20')
                    ]"
                ></div>
            </div>

            <div class="flex items-center gap-2 sm:gap-4">
                <button 
                    v-if="currentQuestionIndex < exam.questions.length - 1"
                    @click="nextQuestion"
                    class="flex items-center gap-2 sm:gap-3 px-5 sm:px-10 py-3 sm:py-5 bg-blue-600 text-white rounded-xl sm:rounded-[1.5rem] font-black text-xs uppercase tracking-wider sm:tracking-widest hover:bg-blue-500 transition shadow-xl shadow-blue-900/20"
                >
                    Suivant
                    <ChevronRightIcon class="h-4 w-4 sm:h-5 sm:w-5" />
                </button>

                <button 
                    v-else
                    @click="submitExam"
                    :disabled="form.processing"
                    class="flex items-center gap-2 sm:gap-3 px-5 sm:px-10 py-3 sm:py-5 bg-emerald-600 text-white rounded-xl sm:rounded-[1.5rem] font-black text-xs uppercase tracking-wider sm:tracking-widest hover:bg-emerald-500 transition shadow-xl shadow-emerald-900/20"
                >
                    <CheckCircleIcon class="h-5 w-5 sm:h-6 sm:w-6" />
                    Terminer
                </button>
            </div>
        </footer>

        <!-- Side Navigation Map -->
        <div v-if="showQuestionMap" class="fixed inset-0 z-50 flex items-center justify-end bg-slate-950/80 backdrop-blur-sm p-6" @click="showQuestionMap = false">
            <div class="w-full max-w-sm h-full bg-slate-900 border border-white/10 rounded-[3rem] p-10 shadow-2xl flex flex-col" @click.stop>
                <div class="flex items-center justify-between mb-10">
                    <h3 class="text-2xl font-black text-white italic tracking-tight">Navigation</h3>
                    <button @click="showQuestionMap = false" class="p-3 bg-white/5 rounded-2xl text-slate-400 hover:text-white transition">
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </div>
                
                <div class="grid grid-cols-4 gap-4 flex-1 overflow-y-auto pr-2 custom-scrollbar">
                    <button 
                        v-for="(_, i) in exam.questions" 
                        :key="i"
                        @click="jumpToQuestion(i)"
                        class="aspect-square rounded-2xl flex items-center justify-center font-black text-sm border-2 transition-all transition-all shadow-sm"
                        :class="[
                            i === currentQuestionIndex 
                                ? 'bg-blue-600 border-blue-400 text-white shadow-lg shadow-blue-900/20' 
                                : (form.answers[exam.questions[i].id] ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-white/5 border-white/5 text-slate-500 hover:border-white/20')
                        ]"
                    >
                        {{ i + 1 }}
                    </button>
                </div>

                <div class="mt-8 pt-8 border-t border-white/5 space-y-3">
                    <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-widest text-slate-400">
                        <span>Progression</span>
                        <span class="text-blue-400">{{ Object.keys(form.answers).length }} / {{ exam.questions.length }}</span>
                    </div>
                    <div class="w-full h-2 bg-white/5 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-600 transition-all duration-500" :style="{ width: (Object.keys(form.answers).length / exam.questions.length * 100) + '%' }"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Anti-Cheat Modal (Violation Countdown) -->
        <div v-if="showViolationModal" class="fixed inset-0 z-[200] flex items-center justify-center bg-red-950/95 p-6 backdrop-blur-xl">
            <div class="bg-white rounded-[3rem] p-12 max-w-lg w-full text-center shadow-2xl scale-110">
                <div class="w-24 h-24 bg-red-50 text-red-600 rounded-[2rem] flex items-center justify-center mx-auto mb-8 animate-bounce transition-all">
                    <ExclamationTriangleIcon class="h-12 w-12" />
                </div>
                <h2 class="text-3xl font-black text-gray-900 mb-4 tracking-tighter">ALERTE SÉCURITÉ</h2>
                <p class="text-gray-500 mb-8 leading-relaxed font-medium">
                    {{ violationMessage }}
                </p>
                
                <div class="relative w-32 h-32 mx-auto mb-10 flex items-center justify-center">
                    <svg class="absolute inset-0 w-full h-full -rotate-90">
                        <circle cx="64" cy="64" r="60" fill="none" stroke="#f1f5f9" stroke-width="8" />
                        <circle cx="64" cy="64" r="60" fill="none" stroke="#dc2626" stroke-width="8" stroke-dasharray="377" :stroke-dashoffset="377 - (377 * countdown / 15)" class="transition-all duration-1000 linear" />
                    </svg>
                    <span class="text-4xl font-black text-red-600">{{ countdown }}s</span>
                </div>

                <div class="py-4 px-6 mb-6 bg-red-50 text-red-700 rounded-2xl font-black text-xs uppercase tracking-widest">
                    L'EXAMEN SERA SOUMIS AUTOMATIQUEMENT À L'EXPIRATION DU DÉLAI.
                </div>
                
                <button 
                    @click="requestFullscreen"
                    class="w-full py-4 bg-red-600 hover:bg-red-700 text-white rounded-xl font-black text-xs uppercase tracking-widest transition-colors shadow-lg shadow-red-500/30"
                >
                    Reprendre l'examen en plein écran
                </button>
            </div>
        </div>

        <!-- Submit Confirmation Modal -->
        <ConfirmModal 
            :is-open="showConfirmSubmit"
            title="Terminer l'épreuve ?"
            message="Êtes-vous sûr de vouloir terminer l'examen ? Vos réponses seront enregistrées et vous ne pourrez plus revenir en arrière."
            type="warning"
            confirm-text="Oui, Terminer"
            cancel-text="Continuer"
            @confirm="handleConfirmSubmit"
            @cancel="showConfirmSubmit = false"
        />
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}

.select-none {
    user-select: none;
    -webkit-user-select: none;
}

/* Animations labels */
label {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
label:active {
    scale: 0.98;
}
</style>
