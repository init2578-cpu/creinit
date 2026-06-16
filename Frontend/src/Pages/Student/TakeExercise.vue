<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { BeakerIcon, CheckCircleIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline'
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    exercise: Object, // Chapter with questions.options
    is_practice: Boolean,
})

// Build reactive answers map: { [question_id]: answer }
const answers = ref({})
props.exercise.questions?.forEach(q => {
    answers.value[q.id] = q.type === 'qcm' ? null : ''
})

const allAnswered = computed(() => {
    return props.exercise.questions?.every(q => {
        const a = answers.value[q.id]
        return a !== null && a !== ''
    })
})

const form = useForm({})
const submitted = ref(false)

const submit = () => {
    form.transform(() => ({
        answers: answers.value,
        type: 'online',
        is_practice: props.is_practice,
    })).post(route('student.exercises.submit', props.exercise.id), {
        onSuccess: () => { 
            if (!props.is_practice) {
                submitted.value = true 
            }
        }
    })
}
</script>

<template>
    <Head :title="exercise.exercise_title || exercise.titre" />
    <AuthenticatedLayout>
        <div class="max-w-3xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

            <!-- Header -->
            <header class="mb-10">
                <Link
                    :href="route('student.exercises.index')"
                    class="inline-flex items-center gap-2 px-4 py-2 mb-6 bg-white rounded-xl shadow-sm border border-gray-100 text-sm font-bold text-gray-600 hover:text-blue-600 hover:bg-blue-50 transition"
                >
                    <ArrowLeftIcon class="h-4 w-4" />
                    Retour aux exercices
                </Link>
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-12 w-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center shadow-sm border border-blue-100">
                        <BeakerIcon class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-gray-900 tracking-tight">{{ exercise.exercise_title || exercise.titre }}</h1>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">{{ exercise.module?.titre }} • {{ exercise.exercise_points }} pts</p>
                    </div>
                </div>
                <div v-if="exercise.exercise_instructions" class="p-5 bg-blue-50/40 rounded-3xl border border-blue-100 text-sm text-blue-900 font-medium italic">
                    {{ exercise.exercise_instructions }}
                </div>
            </header>

            <!-- Success State -->
            <div v-if="submitted" class="py-20 text-center bg-green-50 rounded-[3rem] border border-green-100">
                <CheckCircleIcon class="h-16 w-16 text-green-500 mx-auto mb-4" />
                <h2 class="text-2xl font-black text-green-800 tracking-tight">Exercice soumis !</h2>
                <p class="text-sm text-green-600 font-medium mt-2 mb-8">Votre réponse a été enregistrée. Le formateur la corrigera prochainement.</p>
                
                <Link
                    :href="route('student.exercises.index')"
                    class="inline-block px-8 py-4 bg-green-600 text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-green-700 transition shadow-xl shadow-green-200"
                >
                    Retour aux exercices
                </Link>
            </div>

            <!-- Quiz Form -->
            <div v-else class="space-y-8">
                <div
                    v-for="(question, idx) in exercise.questions"
                    :key="question.id"
                    class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden"
                >
                    <!-- Question Header -->
                    <div class="px-5 sm:px-8 pt-6 sm:pt-8 pb-4 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                        <div class="flex items-start gap-3 sm:gap-4 flex-1">
                            <span class="h-8 w-8 bg-blue-600 text-white rounded-xl flex items-center justify-center font-black text-sm shrink-0 mt-0.5 shadow-md shadow-blue-200">{{ idx + 1 }}</span>
                            <p class="font-black text-gray-900 text-base sm:text-lg tracking-tight leading-snug">{{ question.enonce }}</p>
                        </div>
                        <span class="self-start sm:self-auto text-[10px] font-black text-blue-600 bg-blue-50 px-3 py-1.5 rounded-full shrink-0 border border-blue-100 shadow-sm">{{ question.points }} pts</span>
                    </div>

                    <!-- QCM Options -->
                    <div v-if="question.type === 'qcm'" class="px-5 sm:px-8 pb-6 sm:pb-8 space-y-3 sm:space-y-4">
                        <label
                            v-for="option in question.options"
                            :key="option.id"
                            class="group relative flex items-center gap-4 p-4 sm:p-5 rounded-2xl border cursor-pointer transition-all duration-300 transform active:scale-[0.98]"
                            :class="answers[question.id] === option.id
                                ? 'border-blue-500 bg-blue-50 shadow-md shadow-blue-100/50'
                                : 'border-gray-100 hover:border-blue-200 hover:bg-blue-50/30 hover:shadow-sm'"
                        >
                            <div class="relative flex items-center justify-center w-6 h-6 shrink-0 rounded-full border transition-colors duration-300"
                                :class="answers[question.id] === option.id ? 'border-blue-500 bg-blue-500' : 'border-gray-300 bg-white group-hover:border-blue-400'">
                                <div class="w-2.5 h-2.5 rounded-full bg-white transition-transform duration-300" :class="answers[question.id] === option.id ? 'scale-100' : 'scale-0'"></div>
                            </div>
                            <input
                                type="radio"
                                :name="'q_' + question.id"
                                :value="option.id"
                                v-model="answers[question.id]"
                                class="sr-only"
                            />
                            <span class="font-bold text-gray-800 text-sm sm:text-base leading-snug">{{ option.texte }}</span>
                        </label>
                    </div>

                    <!-- Open Question -->
                    <div v-else class="px-5 sm:px-8 pb-6 sm:pb-8">
                        <textarea
                            v-model="answers[question.id]"
                            rows="4"
                            placeholder="Rédigez votre réponse ici..."
                            class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-medium px-5 py-4 text-sm resize-none transition-shadow hover:bg-gray-100 focus:bg-white"
                        ></textarea>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="!exercise.questions?.length" class="py-20 text-center text-gray-300 font-bold italic bg-white rounded-[2.5rem] border border-dashed border-gray-200">
                    Aucune question n'a encore été ajoutée à cet exercice.
                </div>

                <!-- Submit -->
                <div v-if="exercise.questions?.length" class="flex justify-end pt-4 sm:pt-6 pb-20 sm:pb-4">
                    <button
                        @click="submit"
                        :disabled="form.processing || !allAnswered"
                        class="w-full sm:w-auto px-10 py-5 sm:py-4 bg-blue-600 text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-blue-700 transition shadow-2xl shadow-blue-200 disabled:opacity-50 disabled:cursor-not-allowed transform active:scale-[0.98]"
                    >
                        {{ form.processing ? 'Envoi…' : 'Soumettre l\'exercice' }}
                    </button>
                </div>

                <p v-if="!allAnswered && exercise.questions?.length" class="text-center text-xs text-gray-400 font-bold italic">
                    Répondez à toutes les questions pour pouvoir soumettre.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
