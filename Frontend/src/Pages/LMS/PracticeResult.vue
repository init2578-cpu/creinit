<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { 
    CheckCircleIcon, 
    XCircleIcon, 
    ArrowPathIcon,
    HomeIcon,
    AcademicCapIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    score: Number,
    total: Number,
    feedback: Array,
    exam: Object
})

const percentage = Math.round((props.score / props.total) * 100)
</script>

<template>
    <Head title="Résultat de l'entraînement" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto py-12 px-4">
            <div class="bg-white rounded-[3.5rem] shadow-xl border border-gray-100 overflow-hidden">
                <!-- Header / Score -->
                <div class="bg-gray-900 p-12 text-center relative overflow-hidden">
                    <div class="relative z-10">
                        <AcademicCapIcon class="h-16 w-16 text-blue-500 mx-auto mb-6 opacity-80" />
                        <h1 class="text-4xl font-black text-white tracking-tight mb-2">Entraînement Terminé !</h1>
                        <p class="text-gray-400 font-bold uppercase tracking-widest text-xs">{{ exam.titre }}</p>
                        
                        <div class="mt-10 flex items-center justify-center gap-8">
                            <div class="text-center">
                                <div class="text-6xl font-black text-white leading-none">{{ percentage }}%</div>
                                <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest mt-2">Score Global</div>
                            </div>
                            <div class="h-16 w-px bg-white/10"></div>
                            <div class="text-center">
                                <div class="text-6xl font-black text-blue-500 leading-none">{{ score }}/{{ total }}</div>
                                <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest mt-2">Points Obtenus</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Decorative background -->
                    <div class="absolute inset-0 opacity-20">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-blue-600 rounded-full blur-[100px] translate-x-1/2 -translate-y-1/2"></div>
                    </div>
                </div>

                <!-- Correction Detail -->
                <div class="p-8 md:p-16 space-y-8">
                    <h3 class="text-2xl font-black text-gray-900 tracking-tight mb-10 border-b border-gray-100 pb-6 text-center">Correction Détaillée</h3>
                    
                    <div v-for="(item, index) in feedback" :key="index" class="p-8 rounded-3xl border-2 transition-all"
                        :class="item.is_correct ? 'border-green-100 bg-green-50/30' : 'border-red-100 bg-red-50/30'"
                    >
                        <div class="flex items-start gap-4">
                            <div class="mt-1">
                                <CheckCircleIcon v-if="item.is_correct" class="h-8 w-8 text-green-500" />
                                <XCircleIcon v-else class="h-8 w-8 text-red-500" />
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-black uppercase tracking-widest" :class="item.is_correct ? 'text-green-600' : 'text-red-600'">
                                        Question {{ index + 1 }}
                                    </span>
                                </div>
                                <p class="text-lg font-bold text-gray-800 leading-tight mb-4">
                                    {{ exam.questions.find(q => q.id === item.question_id).enonce }}
                                </p>
                                
                                <div v-if="!item.is_correct && item.correct_options" class="mt-4 p-4 bg-white rounded-2xl border border-red-100">
                                    <div class="text-[10px] font-black text-red-400 uppercase tracking-widest mb-2">Les bonnes réponses étaient :</div>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li v-for="opt in item.correct_options" :key="opt.id" class="font-bold text-gray-900 text-sm">
                                            {{ opt.texte }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-12 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <Link :href="route('student.courses')" class="flex-1 py-5 bg-gray-900 text-white rounded-[1.5rem] font-black transition text-center hover:bg-black flex items-center justify-center gap-3">
                            <HomeIcon class="h-5 w-5" />
                            Retour aux cours
                        </Link>
                        <Link :href="route('student.exams.show', exam.id)" class="flex-1 py-5 bg-blue-600 text-white rounded-[1.5rem] font-black transition text-center hover:bg-blue-700 shadow-xl shadow-blue-100 flex items-center justify-center gap-3">
                            <ArrowPathIcon class="h-5 w-5" />
                            Recommencer
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
