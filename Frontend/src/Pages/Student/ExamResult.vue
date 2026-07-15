<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { 
    CheckCircleIcon, 
    XCircleIcon, 
    HomeIcon,
    AcademicCapIcon,
    DocumentTextIcon,
    ClipboardDocumentCheckIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    exam: Object,
    result: Object
})

const finalScore = (parseFloat(props.result.score) || 0) + (parseFloat(props.result.bonus) || 0)
const percentage = props.exam.total_points > 0 ? Math.round((finalScore / props.exam.total_points) * 100) : 0

const isOptionSelected = (answers, questionId, optionId) => {
    if (!answers || !answers[questionId]) return false;
    const ans = answers[questionId];
    return Array.isArray(ans) ? ans.includes(optionId) : ans == optionId;
};
</script>

<template>
    <Head title="Correction de l'examen" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto py-12 px-4">
            <div class="bg-white rounded-[3.5rem] shadow-xl border border-gray-100 overflow-hidden">
                <!-- Header / Score -->
                <div class="bg-slate-900 p-12 text-center relative overflow-hidden">
                    <div class="relative z-10">
                        <AcademicCapIcon class="h-16 w-16 text-emerald-500 mx-auto mb-6 opacity-80" />
                        <h1 class="text-4xl font-black text-white tracking-tight mb-2">Correction de l'Examen</h1>
                        <p class="text-slate-400 font-bold uppercase tracking-widest text-xs">{{ exam.titre }} - {{ exam.module?.titre }}</p>
                        
                        <div class="mt-10 flex items-center justify-center gap-8">
                            <div class="text-center">
                                <div class="text-6xl font-black text-white leading-none">{{ percentage }}%</div>
                                <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2">Réussite</div>
                            </div>
                            <div class="h-16 w-px bg-white/10"></div>
                            <div class="text-center">
                                <div class="text-6xl font-black text-emerald-500 leading-none">{{ finalScore.toFixed(2) }}<span class="text-3xl text-emerald-700">/{{ exam.total_points }}</span></div>
                                <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-2">Note Finale</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Decorative background -->
                    <div class="absolute inset-0 opacity-20">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-600 rounded-full blur-[100px] translate-x-1/2 -translate-y-1/2"></div>
                    </div>
                </div>

                <!-- Correction Detail -->
                <div class="p-8 md:p-16 space-y-8">
                    <h3 class="text-2xl font-black text-gray-900 tracking-tight mb-10 border-b border-gray-100 pb-6 text-center">Détail des Réponses</h3>
                    
                    <div v-for="(question, qIndex) in exam.questions" :key="question.id" class="p-8 rounded-3xl border border-gray-100 shadow-sm bg-white relative">
                        <div class="flex items-center gap-2 mb-4 border-b border-gray-50 pb-4">
                            <span class="px-3 py-1 bg-gray-100 text-gray-600 text-[10px] font-black uppercase tracking-widest rounded-lg">
                                Question {{ qIndex + 1 }}
                            </span>
                            <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg border" :class="question.type === 'qcm' ? 'bg-indigo-50 text-indigo-600 border-indigo-100' : 'bg-purple-50 text-purple-600 border-purple-100'">
                                {{ question.type === 'qcm' ? 'QCM' : 'Question Ouverte' }}
                            </span>
                            <span class="text-[10px] font-bold text-gray-400 ml-auto">{{ question.points }} pts</span>
                        </div>
                        
                        <p class="text-lg font-bold text-gray-800 leading-tight mb-6">
                            {{ question.enonce }}
                        </p>
                        
                        <!-- QCM -->
                        <div v-if="question.type === 'qcm'" class="space-y-3">
                            <div v-for="opt in question.options" :key="opt.id" class="flex items-center justify-between p-4 rounded-2xl border text-sm transition-all" :class="[
                                isOptionSelected(result.answers, question.id, opt.id) ? (opt.is_correct ? 'bg-green-50 border-green-200 text-green-800 shadow-sm' : 'bg-red-50 border-red-200 text-red-800 shadow-sm') : (opt.is_correct ? 'bg-green-50/50 border-green-100 text-green-700' : 'bg-gray-50 border-gray-100 text-gray-500')
                            ]">
                                <div class="flex items-center gap-4">
                                    <div class="h-5 w-5 rounded-md border-2 flex items-center justify-center shrink-0" :class="isOptionSelected(result.answers, question.id, opt.id) ? (opt.is_correct ? 'border-green-500 bg-green-500' : 'border-red-500 bg-red-500') : 'border-gray-300'">
                                        <div v-if="isOptionSelected(result.answers, question.id, opt.id)" class="h-2 w-2 bg-white rounded-sm"></div>
                                    </div>
                                    <span class="font-bold">{{ opt.texte }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div v-if="opt.is_correct" class="flex items-center gap-1.5 text-green-600 text-[10px] font-black uppercase tracking-widest">
                                        <CheckCircleIcon class="h-5 w-5" />
                                        <span class="hidden sm:inline">Bonne réponse</span>
                                    </div>
                                    <div v-if="isOptionSelected(result.answers, question.id, opt.id) && !opt.is_correct" class="flex items-center gap-1.5 text-red-600 text-[10px] font-black uppercase tracking-widest">
                                        <XCircleIcon class="h-5 w-5" />
                                        <span class="hidden sm:inline">Votre choix</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!result.answers || !result.answers[question.id] || (Array.isArray(result.answers[question.id]) && result.answers[question.id].length === 0)" class="text-sm text-red-500 font-bold italic mt-4 flex items-center gap-2 p-4 bg-red-50 rounded-2xl border border-red-100">
                                <XCircleIcon class="h-5 w-5" />
                                Vous n'avez pas répondu à cette question.
                            </div>
                        </div>

                        <!-- Open Question -->
                        <div v-else class="grid gap-6">
                            <div class="bg-gray-50 p-6 rounded-3xl border border-gray-200 shadow-inner">
                                <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-3 flex items-center gap-2">
                                    <DocumentTextIcon class="h-5 w-5" /> Votre Réponse
                                </p>
                                <p v-if="result.answers && result.answers[question.id]" class="text-base font-medium text-gray-800 whitespace-pre-wrap leading-relaxed">{{ result.answers[question.id] }}</p>
                                <p v-else class="text-sm text-red-500 font-bold italic flex items-center gap-1.5">
                                    <XCircleIcon class="h-5 w-5" />
                                    Aucune réponse fournie.
                                </p>
                            </div>
                            <div v-if="question.expected_answer" class="bg-emerald-50/50 p-6 rounded-3xl border border-emerald-100 shadow-sm relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/5 rounded-full blur-2xl -mr-10 -mt-10 pointer-events-none"></div>
                                <p class="text-[10px] text-emerald-600 font-black uppercase tracking-widest mb-3 flex items-center gap-2 relative z-10">
                                    <ClipboardDocumentCheckIcon class="h-5 w-5" /> Corrigé Attendu
                                </p>
                                <p class="text-base font-medium text-emerald-900 whitespace-pre-wrap leading-relaxed relative z-10">{{ question.expected_answer }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="!exam.questions || exam.questions.length === 0" class="text-center py-12 text-gray-400 font-bold bg-gray-50 rounded-3xl border border-gray-100">
                        Aucune question trouvée.
                    </div>

                    <!-- Actions -->
                    <div class="pt-8">
                        <Link :href="route('student.dashboard')" class="w-full py-5 bg-slate-900 text-white rounded-[2rem] font-black text-xs tracking-widest uppercase transition hover:bg-black flex items-center justify-center gap-3 shadow-xl">
                            <HomeIcon class="h-5 w-5" />
                            Retour au tableau de bord
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
