<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import {
    BeakerIcon,
    CheckCircleIcon,
    XCircleIcon,
    ChevronLeftIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline'
import { computed } from 'vue'

const props = defineProps({
    submission: Object, // Includes chapter.questions.options and answers
    is_practice: Boolean,
})

const exercise = computed(() => props.submission.chapter)
const answers = computed(() => props.submission.answers || {})

const getOptionText = (question, optionId) => {
    return question.options.find(o => o.id === Number(optionId))?.texte || optionId
}

// Function to determine if a question was answered correctly
const isCorrect = (question) => {
    if (question.type !== 'qcm') return null
    const submittedId = answers.value[question.id]
    const correctOption = question.options.find(o => o.is_correct)
    return submittedId && correctOption && Number(submittedId) === correctOption.id
}

const getCorrectOption = (question) => {
    return question.options.find(o => o.is_correct)
}
</script>

<template>
    <Head :title="'Résultat : ' + (exercise.exercise_title || exercise.titre)" />
    <AuthenticatedLayout>
        <div class="max-w-3xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

            <!-- Back navigation -->
            <Link :href="route('student.exercises.index')" class="flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-widest mb-8 hover:text-blue-600 transition">
                <ChevronLeftIcon class="h-4 w-4" />
                Retour aux exercices
            </Link>

            <!-- Header -->
            <header class="mb-10">
                <div class="flex flex-col md:flex-row md:items-start justify-between gap-6 mb-4">
                    <div class="flex items-start gap-4">
                        <div class="h-12 w-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center shadow-sm border border-blue-100 shrink-0">
                            <BeakerIcon class="h-6 w-6" />
                        </div>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight leading-snug">{{ exercise.exercise_title || exercise.titre }}</h1>
                            <p class="text-[10px] sm:text-xs font-bold text-gray-400 uppercase tracking-widest mt-1.5">{{ exercise.module?.titre }}</p>
                        </div>
                    </div>
                    <div class="text-left md:text-right flex flex-row md:flex-col items-center md:items-end justify-between md:justify-start bg-gray-50 md:bg-transparent p-4 md:p-0 rounded-2xl border md:border-0 border-gray-100">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest block md:hidden mb-1">Note obtenue</p>
                            <div class="text-3xl font-black text-blue-600 tracking-tighter">{{ submission.grade || 0 }}<span class="text-xs text-gray-300 ml-1">/ {{ exercise.exercise_points }}</span></div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest hidden md:block">Note obtenue</p>
                        </div>
                        <div v-if="is_practice" class="md:mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 text-amber-600 border border-amber-100 rounded-full text-[9px] sm:text-[10px] font-black uppercase tracking-widest shadow-sm">
                            <InformationCircleIcon class="h-3.5 w-3.5 shrink-0" />
                            Mode Entraînement
                        </div>
                    </div>
                </div>

                <div v-if="submission.trainer_feedback" class="p-5 sm:p-6 bg-green-50 rounded-2xl sm:rounded-3xl border border-green-100 text-sm text-green-900 font-medium relative mt-6 shadow-sm">
                    <InformationCircleIcon class="h-6 w-6 text-green-500 absolute -top-3 -left-3 bg-white rounded-full shadow-sm" />
                    <p class="text-[9px] sm:text-[10px] font-black text-green-600 uppercase tracking-widest mb-1.5 opacity-80">Retour du formateur</p>
                    <p class="leading-relaxed">{{ submission.trainer_feedback }}</p>
                </div>
            </header>

            <!-- Questions Breakdown -->
            <div class="space-y-6 sm:space-y-8">
                <div
                    v-for="(question, idx) in exercise.questions"
                    :key="question.id"
                    class="bg-white rounded-[2rem] sm:rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden relative"
                >
                    <!-- Question Header & Badges -->
                    <div class="px-5 sm:px-8 pt-6 sm:pt-8 pb-4">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                            <div class="flex items-start gap-3 sm:gap-4 flex-1">
                                <span class="h-8 w-8 bg-gray-900 text-white rounded-xl flex items-center justify-center font-black text-sm shrink-0 mt-0.5 shadow-md shadow-gray-200">{{ idx + 1 }}</span>
                                <p class="font-black text-gray-900 text-base sm:text-lg tracking-tight leading-snug">{{ question.enonce }}</p>
                            </div>
                            
                            <!-- Correction Badge (Mobile-friendly flow) -->
                            <div v-if="question.type === 'qcm'" class="self-start sm:self-auto shrink-0 mt-2 sm:mt-0">
                                <div v-if="isCorrect(question)" class="inline-flex items-center gap-1.5 text-green-600 font-black text-[10px] uppercase tracking-widest bg-green-50 px-3 py-1.5 rounded-full border border-green-100 shadow-sm">
                                    <CheckCircleIcon class="h-3.5 w-3.5 shrink-0" />
                                    Correct
                                </div>
                                <div v-else class="inline-flex items-center gap-1.5 text-red-500 font-black text-[10px] uppercase tracking-widest bg-red-50 px-3 py-1.5 rounded-full border border-red-100 shadow-sm">
                                    <XCircleIcon class="h-3.5 w-3.5 shrink-0" />
                                    Incorrect
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- QCM Options Display -->
                    <div v-if="question.type === 'qcm'" class="px-5 sm:px-8 pb-6 sm:pb-8 space-y-3 mt-1 sm:mt-2">
                        <div
                            v-for="option in question.options"
                            :key="option.id"
                            class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 p-4 sm:p-5 rounded-2xl border transition-all"
                            :class="[
                                option.is_correct ? 'border-green-500 bg-green-50/80 shadow-sm shadow-green-100/50' : 'border-gray-100',
                                answers[question.id] == option.id && !option.is_correct ? 'border-red-500 bg-red-50/80 shadow-sm shadow-red-100/50' : ''
                            ]"
                        >
                            <div class="flex items-start sm:items-center gap-3 sm:gap-4 w-full">
                                <div class="h-5 w-5 sm:h-6 sm:w-6 rounded-full border-2 flex items-center justify-center shrink-0 mt-0.5 sm:mt-0"
                                    :class="[
                                        option.is_correct ? 'border-green-500 bg-green-500 text-white' : 'border-gray-200 bg-white',
                                        answers[question.id] == option.id && !option.is_correct ? 'border-red-500 bg-red-500 text-white' : ''
                                    ]"
                                >
                                    <CheckCircleIcon v-if="option.is_correct" class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                    <XCircleIcon v-else-if="answers[question.id] == option.id" class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                </div>
                                <span class="font-bold text-sm sm:text-base leading-snug" :class="option.is_correct ? 'text-green-900' : (answers[question.id] == option.id ? 'text-red-900' : 'text-gray-600')">
                                    {{ option.texte }}
                                </span>
                            </div>
                            
                            <div class="flex flex-wrap items-center gap-2 mt-2 sm:mt-0 ml-8 sm:ml-auto shrink-0">
                                <span v-if="option.is_correct && answers[question.id] != option.id" class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-green-700 bg-green-100 px-2.5 py-1 sm:py-1.5 rounded-lg border border-green-200">
                                    Réponse attendue
                                </span>
                                <span v-if="answers[question.id] == option.id" class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest px-2.5 py-1 sm:py-1.5 rounded-lg border" :class="option.is_correct ? 'text-green-700 bg-green-100 border-green-200' : 'text-red-700 bg-red-100 border-red-200'">
                                    Votre réponse
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Open Question Display -->
                    <div v-else class="px-5 sm:px-8 pb-6 sm:pb-8 mt-1 sm:mt-2 space-y-4">
                        <div class="p-5 sm:p-6 bg-gray-50 rounded-2xl border border-gray-100 text-sm sm:text-base text-gray-700 font-medium leading-relaxed">
                            <p class="text-[9px] sm:text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                                <UserCircleIcon class="h-4 w-4 shrink-0" />
                                Votre réponse
                            </p>
                            <div class="whitespace-pre-wrap">{{ answers[question.id] || '(Aucune réponse)' }}</div>
                        </div>
                        <div v-if="question.expected_answer" class="p-5 sm:p-6 bg-green-50 rounded-2xl border border-green-100 text-sm sm:text-base text-green-800 font-medium leading-relaxed shadow-sm">
                            <p class="text-[9px] sm:text-[10px] font-black text-green-600 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                                <CheckCircleIcon class="h-4 w-4 shrink-0" />
                                Corrigé attendu
                            </p>
                            <div class="whitespace-pre-wrap">{{ question.expected_answer }}</div>
                        </div>
                    </div>
                </div>

                <div class="pt-6 sm:pt-10 pb-20 sm:pb-4 flex justify-center">
                    <Link
                        :href="route('student.exercises.index')"
                        class="w-full sm:w-auto px-10 py-5 sm:py-4 bg-gray-900 text-white rounded-2xl font-black text-sm uppercase tracking-widest text-center hover:bg-blue-600 transition shadow-xl shadow-gray-100 transform active:scale-[0.98]"
                    >
                        Terminer la revue
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
