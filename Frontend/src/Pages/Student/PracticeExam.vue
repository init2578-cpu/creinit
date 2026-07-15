<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { 
    QuestionMarkCircleIcon, 
    ArrowRightIcon, 
    CheckBadgeIcon,
    AcademicCapIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    exam: Object
})

const currentQuestionIndex = ref(0)
const answers = ref({})
const form = useForm({
    answers: {}
})

const currentQuestion = computed(() => props.exam.questions[currentQuestionIndex.value])

function selectOption(optionId) {
    answers.value[currentQuestion.value.id] = optionId
}

function nextQuestion() {
    if (currentQuestionIndex.value < props.exam.questions.length - 1) {
        currentQuestionIndex.value++
    } else {
        submitExam()
    }
}

function submitExam() {
    form.answers = answers.value
    form.post(route('student.exams.submit', props.exam.id))
}


</script>

<template>
    <Head :title="'Entraînement : ' + exam.titre" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto py-8 px-4">
            <header class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight">{{ exam.titre }}</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-[10px] font-black uppercase tracking-widest rounded">Mode Entraînement Libre</span>
                        <span class="text-gray-400 text-sm font-bold">• Aucune limite de temps</span>
                    </div>
                </div>
                <div class="h-16 w-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                    <AcademicCapIcon class="h-8 w-8" />
                </div>
            </header>

            <div class="bg-white p-8 sm:p-12 rounded-[3rem] shadow-sm border border-gray-100 min-h-[400px] flex flex-col">
                <div class="mb-10 flex items-center justify-between">
                    <span class="px-4 py-1.5 bg-gray-900 text-white rounded-xl text-xs font-black uppercase tracking-widest">
                        Question {{ currentQuestionIndex + 1 }} sur {{ exam.questions.length }}
                    </span>
                    <div class="flex gap-1">
                        <div v-for="(_, i) in exam.questions" :key="i" class="h-1.5 w-4 rounded-full transition-all" :class="i === currentQuestionIndex ? 'bg-blue-600 w-8' : 'bg-gray-100'"></div>
                    </div>
                </div>

                <div class="flex-1">
                    <h2 class="text-2xl font-black text-gray-900 leading-tight mb-8">
                        {{ currentQuestion.enonce }}
                    </h2>

                    <div v-if="currentQuestion.type === 'qcm'" class="grid grid-cols-1 gap-4">
                        <button 
                            v-for="opt in currentQuestion.options" 
                            :key="opt.id"
                            @click="selectOption(opt.id)"
                            class="p-6 rounded-2xl border-2 text-left transition-all flex items-center gap-4 group"
                            :class="answers[currentQuestion.id] === opt.id ? 'border-blue-600 bg-blue-50/50' : 'border-gray-50 bg-gray-50 hover:border-gray-200'"
                        >
                            <div class="h-6 w-6 rounded-full border-2 flex items-center justify-center shrink-0 transition-colors" :class="answers[currentQuestion.id] === opt.id ? 'bg-blue-600 border-blue-600' : 'bg-white border-gray-200 group-hover:border-blue-400'">
                                <div class="h-2 w-2 bg-white rounded-full" v-if="answers[currentQuestion.id] === opt.id"></div>
                            </div>
                            <span class="font-bold text-gray-700">{{ opt.texte }}</span>
                        </button>
                    </div>

                    <div v-else-if="currentQuestion.type === 'open'" class="mt-4">
                        <textarea 
                            v-model="answers[currentQuestion.id]"
                            class="w-full h-48 bg-gray-50 border border-gray-200 rounded-[2rem] p-8 text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all resize-none placeholder-gray-400 text-lg"
                            placeholder="Saisissez votre réponse ici..."
                        ></textarea>
                    </div>
                </div>

                <div class="mt-12 flex justify-end">
                    <button 
                        @click="nextQuestion"
                        :disabled="!answers[currentQuestion.id]"
                        class="px-8 py-4 bg-gray-900 text-white rounded-2xl font-black flex items-center gap-3 hover:bg-black transition disabled:opacity-50 disabled:grayscale"
                    >
                        {{ currentQuestionIndex === exam.questions.length - 1 ? 'Terminer & Voir la Correction' : 'Question Suivante' }}
                        <ArrowRightIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
