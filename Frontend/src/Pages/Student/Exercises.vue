<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import {
    BeakerIcon,
    CheckCircleIcon,
    ClockIcon,
    DocumentArrowUpIcon,
    XCircleIcon
} from '@heroicons/vue/24/outline'
import { ref } from 'vue'

const props = defineProps({
    exercises: Array,
})

const selectedExercise = ref(null)
const uploadForm = useForm({ file: null, student_comment: '' })

const openSubmit = (exercise) => {
    selectedExercise.value = exercise
    uploadForm.reset()
}

const submitExercise = () => {
    uploadForm.post(route('student.exercises.submit', selectedExercise.value.id), {
        onSuccess: () => { selectedExercise.value = null }
    })
}

const statusLabel = (sub) => {
    if (!sub) return 'Non rendu';
    if (sub.status === 'pending') return 'En attente de correction';
    if (sub.status === 'rejected') return 'À retravailler';
    
    const labels = {
        'excellent': 'Excellent',
        'very_good': 'Très Bien',
        'good': 'Bien',
        'satisfactory': 'Passable',
        'graded': 'Validé',
        'weak': 'Faible'
    };
    
    if (labels[sub.status]) {
        return `${labels[sub.status]} : ${sub.grade} pts`;
    }
    
    return 'En attente de correction';
}

const statusClass = (sub) => {
    if (!sub) return 'bg-gray-50 text-gray-400 border-gray-100';
    if (sub.status === 'pending') return 'bg-amber-50 text-amber-600 border-amber-100';
    if (sub.status === 'rejected') return 'bg-orange-50 text-orange-600 border-orange-100';
    if (sub.status === 'weak') return 'bg-red-50 text-red-600 border-red-100';
    if (sub.status === 'excellent') return 'bg-emerald-50 text-emerald-600 border-emerald-100';
    if (sub.status === 'very_good' || sub.status === 'graded') return 'bg-green-50 text-green-600 border-green-100';
    if (sub.status === 'good') return 'bg-lime-50 text-lime-600 border-lime-100';
    if (sub.status === 'satisfactory') return 'bg-blue-50 text-blue-600 border-blue-100';
    return 'bg-amber-50 text-amber-600 border-amber-100';
}

const isSuccess = (status) => {
    return ['excellent', 'very_good', 'good', 'satisfactory', 'graded'].includes(status);
}

const accentClass = (sub) => {
    if (!sub) return 'bg-gray-200';
    if (sub.status === 'pending') return 'bg-amber-400';
    if (sub.status === 'rejected') return 'bg-orange-400';
    if (sub.status === 'weak') return 'bg-red-400';
    if (sub.status === 'excellent') return 'bg-emerald-400';
    if (sub.status === 'very_good' || sub.status === 'graded') return 'bg-green-400';
    if (sub.status === 'good') return 'bg-lime-400';
    if (sub.status === 'satisfactory') return 'bg-blue-400';
    return 'bg-amber-400';
}
</script>

<template>
    <Head title="Mes Exercices" />
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
            <header class="mb-10">
                <h1 class="text-4xl font-black text-gray-900 tracking-tight flex items-center gap-3">
                    <BeakerIcon class="h-10 w-10 text-blue-600" />
                    Mes Exercices
                </h1>
                <p class="mt-2 text-gray-500 font-medium">Consultez et soumettez vos exercices pratiques.</p>
            </header>

            <div class="space-y-5">
                <div
                    v-for="ex in exercises"
                    :key="ex.id"
                    class="bg-white p-5 sm:p-6 rounded-[2rem] sm:rounded-[2.5rem] shadow-sm hover:shadow-xl hover:shadow-blue-900/5 border border-gray-100 hover:border-blue-200 transition-all duration-300 hover:-translate-y-1 relative overflow-hidden group"
                >
                    <!-- Gradient accent -->
                    <div class="absolute left-0 top-0 bottom-0 w-1.5 transition-colors duration-300" :class="accentClass(ex.my_submission)"></div>

                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pl-2 sm:pl-4">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-5">
                            <div class="flex items-center gap-4">
                                <div class="h-14 w-14 rounded-2xl flex items-center justify-center shadow-sm border border-gray-100 shrink-0 transition-transform group-hover:scale-105"
                                    :class="isSuccess(ex.my_submission?.status) ? 'bg-green-50 text-green-600' : 'bg-blue-50 text-blue-600'"
                                >
                                    <CheckCircleIcon v-if="isSuccess(ex.my_submission?.status)" class="h-7 w-7" />
                                    <ClockIcon v-else class="h-7 w-7" />
                                </div>
                                <!-- Mobile status badge -->
                                <div class="sm:hidden">
                                    <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border" :class="statusClass(ex.my_submission)">
                                        {{ statusLabel(ex.my_submission) }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-black text-gray-900 text-lg tracking-tight leading-snug group-hover:text-blue-600 transition-colors">{{ ex.exercise_title || ex.titre }}</h3>
                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mt-1.5">
                                    {{ ex.module?.titre }} •
                                    {{ ex.exercise_type === 'online' ? 'En ligne' : 'Dépôt fichier' }} •
                                    {{ ex.exercise_points }} pts
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mt-2 md:mt-0 pt-4 md:pt-0 border-t border-gray-50 md:border-t-0">
                            <!-- Desktop status badge -->
                            <span class="hidden sm:inline-block px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border text-center whitespace-nowrap" :class="statusClass(ex.my_submission)">
                                {{ statusLabel(ex.my_submission) }}
                            </span>
                            
                            <!-- Online exercise -->
                            <template v-if="ex.exercise_type === 'online'">
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2" v-if="isSuccess(ex.my_submission?.status) || ex.my_submission?.status === 'weak'">
                                    <Link
                                        :href="route('student.exercises.result', ex.my_submission.id)"
                                        class="w-full sm:w-auto px-5 py-3 sm:py-2.5 bg-green-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest text-center hover:bg-green-700 hover:shadow-lg hover:shadow-green-100 transition-all"
                                    >
                                        Voir résultats
                                    </Link>
                                    <Link
                                        :href="route('student.exercises.start', ex.id) + '?practice=1'"
                                        class="w-full sm:w-auto px-5 py-3 sm:py-2.5 bg-blue-100 text-blue-600 rounded-2xl text-[10px] font-black uppercase tracking-widest text-center hover:bg-blue-200 transition-all"
                                    >
                                        S'entraîner
                                    </Link>
                                </div>
                                <Link
                                    v-else
                                    :href="route('student.exercises.start', ex.id)"
                                    class="w-full sm:w-auto px-5 py-3 sm:py-2.5 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest text-center hover:bg-blue-700 hover:shadow-lg hover:shadow-blue-100 transition-all"
                                >
                                    {{ ex.my_submission ? 'Refaire' : 'Démarrer' }}
                                </Link>
                            </template>

                            <!-- File exercise: open upload modal -->
                            <button
                                v-if="ex.exercise_type === 'file'"
                                @click="openSubmit(ex)"
                                class="w-full sm:w-auto px-5 py-3 sm:py-2.5 bg-gray-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest text-center hover:bg-blue-600 hover:shadow-lg hover:shadow-gray-200 transition-all"
                            >
                                {{ ex.my_submission ? 'Re-soumettre' : 'Soumettre' }}
                            </button>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div v-if="ex.exercise_instructions" class="mt-4 p-4 bg-blue-50/30 rounded-2xl border border-blue-100/50 text-sm text-blue-900 font-medium italic">
                        {{ ex.exercise_instructions }}
                    </div>

                    <!-- Trainer Feedback -->
                    <div v-if="ex.my_submission?.trainer_feedback" class="mt-4 p-4 bg-green-50/30 rounded-2xl border border-green-100 text-sm text-green-900 font-medium">
                        <p class="text-[9px] font-black text-green-500 uppercase tracking-widest mb-1">Retour du formateur</p>
                        {{ ex.my_submission.trainer_feedback }}
                    </div>
                </div>

                <div v-if="exercises.length === 0" class="py-20 text-center text-gray-400 font-bold italic bg-white rounded-[2.5rem] border border-dashed border-gray-200">
                    <BeakerIcon class="h-12 w-12 mx-auto mb-4 text-gray-200" />
                    Aucun exercice disponible pour le moment.
                </div>
            </div>
        </div>

        <!-- File Upload Modal -->
        <div v-if="selectedExercise" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm">
            <div class="bg-white w-full max-w-lg rounded-[2.5rem] overflow-hidden shadow-2xl">
                <div class="p-8 border-b border-gray-100">
                    <h3 class="text-2xl font-black text-gray-900 tracking-tight italic">Soumettre un fichier</h3>
                    <p class="text-xs text-gray-400 font-bold mt-1 uppercase tracking-widest truncate">{{ selectedExercise.exercise_title || selectedExercise.titre }}</p>
                </div>
                <form @submit.prevent="submitExercise" class="p-8 space-y-5">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Fichier (PDF, ZIP, DOC, IMG)</label>
                        <input
                            type="file"
                            @change="uploadForm.file = $event.target.files[0]"
                            required
                            accept=".pdf,.zip,.rar,.doc,.docx,.jpg,.png"
                            class="w-full bg-gray-50 border-0 rounded-2xl font-bold px-5 py-3.5 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:font-black file:bg-blue-50 file:text-blue-600"
                        />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Commentaire (optionnel)</label>
                        <textarea v-model="uploadForm.student_comment" rows="2" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5" placeholder="Message pour le formateur..."></textarea>
                    </div>
                    <div class="pt-4 flex gap-3">
                        <button type="button" @click="selectedExercise = null" class="flex-1 py-4 bg-gray-100 text-gray-500 rounded-2xl font-black text-xs uppercase tracking-widest">Annuler</button>
                        <button type="submit" :disabled="uploadForm.processing" class="flex-1 py-4 bg-blue-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition">
                            Envoyer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
