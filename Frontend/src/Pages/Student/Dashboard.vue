<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { 
    AcademicCapIcon, 
    CalendarIcon, 
    ExclamationTriangleIcon,
    ShieldExclamationIcon,
    BookOpenIcon,
    ArrowRightIcon,
    ArrowTrendingUpIcon,
    ClockIcon,
    PencilSquareIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline'
import { formatTime } from '@/utils/format'

const props = defineProps({
    nextSchedules: Array,
    absenceCount: Number,
    progress: Number,
    individualProgress: Number,
    group: Object,
    upcomingExams: Array,
    recentExercises: Array,
    recentExams: Array,
    stats: Object
})

const getAbsenceStatus = computed(() => {
    if (props.absenceCount >= 3) return 'bg-red-600'
    if (props.absenceCount >= 2) return 'bg-orange-500'
    return 'bg-blue-600'
})

const getAbsenceLabel = computed(() => {
    if (props.absenceCount >= 3) return 'Statut : BLOQUÉ'
    if (props.absenceCount >= 2) return 'Alerte : Risque d\'exclusion'
    return 'Situation Régulière'
})

import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'

const DAYS = ['', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']
const dayLabel = (d) => DAYS[d] ?? String(d).substring(0, 3)

const isLocating = ref(false)
const loadingExamId = ref(null)

const handleExamAction = (exam) => {
    if (exam.is_practice) {
        router.get(route('student.exams.show', exam.id))
        return
    }

    isLocating.value = true
    loadingExamId.value = exam.id

    if (!navigator.geolocation) {
        window.platformAlert("La géolocalisation n'est pas supportée par votre navigateur.", 'error')
        isLocating.value = false
        loadingExamId.value = null
        return
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            isLocating.value = false
            loadingExamId.value = null
            router.get(route('student.exams.show', exam.id), { 
                latitude: position.coords.latitude, 
                longitude: position.coords.longitude 
            })
        },
        (error) => {
            window.platformAlert("Impossible de récupérer votre position. Veuillez autoriser l'accès au GPS pour passer l'examen.", 'error')
            isLocating.value = false
            loadingExamId.value = null
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 }
    )
}
</script>

<template>
    <Head title="Espace Apprenant" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 -mt-6 sm:-mt-8 lg:-mt-10 font-sans">
            <!-- Hero Header with Premium Gradient & Glassmorphism -->
            <header class="relative overflow-hidden bg-white p-8 sm:p-10 rounded-[2.5rem] border border-gray-100 shadow-sm flex flex-col md:flex-row items-center gap-8 mb-8">
                <div class="absolute top-0 right-0 -mt-16 -mr-16 w-80 h-80 bg-blue-50/50 rounded-full blur-3xl opacity-60"></div>
                <div class="absolute bottom-0 left-0 -mb-16 -ml-16 w-64 h-64 bg-emerald-50/30 rounded-full blur-2xl opacity-40"></div>
                
                <div class="relative z-10 h-32 w-32 rounded-[2.5rem] bg-blue-600 flex items-center justify-center overflow-hidden text-white text-4xl font-black shadow-xl shadow-blue-100 shrink-0 border-4 border-white/80 transition-transform hover:scale-105">
                    <img v-if="$page.props.auth.user.profile_photo_url" :src="$page.props.auth.user.profile_photo_url" class="h-full w-full object-cover">
                    <template v-else>{{ $page.props.auth.user.name.charAt(0) }}</template>
                </div>
                <div class="relative z-10 text-center md:text-left flex-1">
                    <div class="flex items-center justify-center md:justify-start gap-2 mb-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-100/50 text-blue-700 text-[10px] font-black uppercase tracking-[0.2em] rounded-full border border-blue-200/50">
                            <span class="relative flex h-2 w-2">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                            </span>
                            Espace Apprenant
                        </span>
                        <span class="px-3 py-1 bg-slate-100 text-slate-500 text-[10px] font-black uppercase tracking-[0.2em] rounded-full border border-slate-200/50">Cohorte 2026</span>
                    </div>
                    <h1 class="text-4xl sm:text-5xl font-black text-gray-900 tracking-tight leading-none mb-4">
                        Bienvenue, <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 via-indigo-600 to-emerald-600">{{ $page.props.auth.user.name }}</span>
                    </h1>
                    <div v-if="group" class="flex flex-wrap items-center justify-center md:justify-start gap-2">
                        <span class="px-3 py-1.5 bg-indigo-50/80 text-indigo-700 border border-indigo-100 rounded-xl text-[10px] font-black uppercase tracking-widest">
                            Groupe : {{ group.nom_groupe }}
                        </span>
                        <span class="px-3 py-1.5 bg-emerald-50/80 text-emerald-700 border border-emerald-100 rounded-xl text-[10px] font-black uppercase tracking-widest">
                            Module : {{ group.module?.titre }}
                        </span>
                    </div>
                </div>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Activity (Progress & Planning) -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Stats / KPIs -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-indigo-600 text-white p-6 rounded-[2rem] shadow-sm relative overflow-hidden group hover:scale-[1.02] transition-transform">
                            <div class="absolute -right-4 -top-4 opacity-10 group-hover:opacity-20 transition-opacity"><BookOpenIcon class="h-32 w-32" /></div>
                            <h3 class="text-xs font-black uppercase tracking-widest text-indigo-200 mb-2">Moyenne Exercices</h3>
                            <div class="text-4xl font-black">{{ stats?.exerciseAvg !== null ? stats.exerciseAvg : '--' }}<span class="text-xl text-indigo-300">/20</span></div>
                            <p class="text-[10px] text-indigo-200 mt-2">{{ stats?.exercisesDone || 0 }} devoirs notés</p>
                        </div>
                        <div class="bg-emerald-600 text-white p-6 rounded-[2rem] shadow-sm relative overflow-hidden group hover:scale-[1.02] transition-transform">
                            <div class="absolute -right-4 -top-4 opacity-10 group-hover:opacity-20 transition-opacity"><AcademicCapIcon class="h-32 w-32" /></div>
                            <h3 class="text-xs font-black uppercase tracking-widest text-emerald-200 mb-2">Moyenne Examens</h3>
                            <div class="text-4xl font-black">{{ stats?.examAvg !== null ? stats.examAvg : '--' }}<span class="text-xl text-emerald-300">/20</span></div>
                            <p class="text-[10px] text-emerald-200 mt-2">{{ stats?.examsDone || 0 }} examens validés</p>
                        </div>
                    </div>

                    <!-- Progress Section -->
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 transition-all hover:shadow-xl space-y-8">
                        
                        <!-- Progression du Groupe -->
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center border border-blue-100">
                                        <BookOpenIcon class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-black text-gray-900 tracking-tight">Progression de la Classe</h2>
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Programme validé par le formateur</p>
                                    </div>
                                </div>
                                <span class="text-2xl font-black text-blue-600">{{ progress }}%</span>
                            </div>
                            <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-blue-400 to-blue-600 rounded-full transition-all duration-1000 ease-out" :style="{ width: progress + '%' }"></div>
                            </div>
                        </div>

                        <!-- Progression Individuelle -->
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center border border-indigo-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-black text-gray-900 tracking-tight">Ma Progression Individuelle</h2>
                                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Exercices et devoirs soumis</p>
                                    </div>
                                </div>
                                <span class="text-2xl font-black text-indigo-600">{{ individualProgress }}%</span>
                            </div>
                            <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden mb-2">
                                <div class="h-full bg-gradient-to-r from-indigo-400 to-indigo-600 rounded-full transition-all duration-1000 ease-out" :style="{ width: individualProgress + '%' }"></div>
                            </div>
                            <p class="text-gray-500 text-xs font-medium mt-3">
                                {{ individualProgress >= 100 ? 'Félicitations ! Vous avez terminé tous vos exercices.' : 'Continuez à soumettre vos exercices pour progresser individuellement.' }}
                            </p>
                        </div>
                        
                    </div>

                    <!-- Upcoming Classes -->
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 transition-all hover:shadow-xl">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-xl font-black text-gray-900 tracking-tight">Prochains Cours</h2>
                                <p class="text-xs text-gray-500 font-medium">Votre emploi du temps à venir</p>
                            </div>
                            <div class="h-10 w-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center border border-indigo-100">
                                <CalendarIcon class="h-5 w-5" />
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div v-for="sch in nextSchedules" :key="sch.id" class="p-4 bg-slate-50/50 border border-slate-100/50 rounded-2xl flex items-center justify-between group hover:bg-white hover:border-slate-200 hover:shadow-md transition-all">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 px-3 bg-white rounded-xl border border-slate-100 shadow-sm flex flex-col items-center justify-center min-w-[70px]">
                                        <span class="text-[8px] font-black text-blue-600 uppercase tracking-wider">{{ dayLabel(sch.day_of_week) }}</span>
                                        <span class="text-xs font-black text-gray-900 font-mono tracking-tighter">{{ formatTime(sch.start_time) }}</span>
                                    </div>
                                    <div>
                                        <p class="font-black text-gray-900 text-sm">{{ sch.group.nom_groupe }}</p>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mt-0.5">{{ sch.room.nom }} • {{ sch.formateur.name }}</p>
                                    </div>
                                </div>
                                <div class="h-8 w-8 rounded-full bg-slate-50 flex items-center justify-center border border-slate-100/50 group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors">
                                    <ArrowRightIcon class="h-4 w-4" />
                                </div>
                            </div>
                            <div v-if="nextSchedules.length === 0" class="py-8 text-center text-gray-400 font-bold italic text-xs">
                                Aucun cours prévu pour le moment.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Side Info (Discipline & Quick Actions) -->
                <div class="space-y-8">
                    <!-- Upcoming Exams -->
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 transition-all hover:shadow-xl">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-xl font-black text-gray-900 tracking-tight">Examens à venir</h2>
                                <p class="text-xs text-gray-500 font-medium">Évaluations programmées</p>
                            </div>
                            <div class="h-10 w-10 bg-rose-50 text-rose-500 rounded-xl flex items-center justify-center border border-rose-100">
                                <PencilSquareIcon class="h-5 w-5" />
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div v-for="exam in upcomingExams" :key="exam.id" class="p-4 bg-rose-50/30 border border-rose-100/50 rounded-2xl flex items-center justify-between group hover:bg-white hover:border-rose-200 hover:shadow-md transition-all">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 bg-white border border-rose-100 text-rose-500 rounded-xl flex items-center justify-center shadow-sm">
                                        <ClockIcon class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-gray-900">{{ exam.titre }}</p>
                                        <p class="text-[8px] text-rose-500 font-black uppercase tracking-widest mt-0.5">{{ exam.my_result?.status === 'started' ? 'Examen débloqué - À terminer' : (exam.is_practice ? 'Entraînement' : 'Examen Final') }}</p>
                                    </div>
                                </div>
                                <button 
                                    @click="handleExamAction(exam)"
                                    :disabled="isLocating && loadingExamId === exam.id"
                                    class="px-4 py-2 bg-rose-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-rose-700 transition active:scale-95 shadow-sm hover:shadow flex items-center justify-center gap-2 disabled:opacity-50"
                                >
                                    <ArrowPathIcon v-if="isLocating && loadingExamId === exam.id" class="h-3.5 w-3.5 animate-spin" />
                                    {{ isLocating && loadingExamId === exam.id ? 'GPS...' : (exam.my_result?.status === 'started' ? 'Reprendre' : 'Commencer') }}
                                </button>
                            </div>
                            <div v-if="upcomingExams.length === 0" class="py-8 text-center text-gray-400 font-bold italic text-xs">
                                Aucun examen prévu.
                            </div>
                        </div>
                    </div>

                    <!-- Recent Exercises -->
                    <div class="bg-slate-900 p-8 rounded-[2.5rem] shadow-xl text-white relative overflow-hidden transition-all hover:shadow-2xl">
                        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-48 h-48 bg-blue-500/10 rounded-full blur-2xl pointer-events-none"></div>
                        
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h3 class="text-xl font-black tracking-tight">Notes d'Exercices</h3>
                                <p class="text-xs text-slate-400 font-medium">Dernières évaluations</p>
                            </div>
                            <div class="h-10 w-10 bg-white/10 text-blue-400 rounded-xl flex items-center justify-center border border-white/5">
                                <ArrowTrendingUpIcon class="h-5 w-5" />
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div v-for="ex in recentExercises" :key="ex.id" class="flex items-center justify-between border-b border-white/5 pb-3.5 last:border-0 last:pb-0">
                                <div class="min-w-0 flex-1 pr-4">
                                    <p class="font-bold text-sm text-slate-100 truncate">{{ ex.chapter.titre }}</p>
                                    <p class="text-[8px] font-black uppercase tracking-widest text-slate-500 mt-0.5">{{ !['pending', 'rejected'].includes(ex.status) ? 'Évalué' : 'En attente' }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <div v-if="!['pending', 'rejected'].includes(ex.status)" class="text-lg font-black text-blue-400">
                                        {{ ex.grade }}<span class="text-[10px] text-slate-600">/{{ ex.chapter?.exercise_points || 20 }}</span>
                                    </div>
                                    <div v-else class="text-[10px] font-black uppercase text-slate-600 tracking-widest">
                                        ...
                                    </div>
                                </div>
                            </div>
                            <div v-if="recentExercises.length === 0" class="py-8 text-center text-slate-500 font-bold italic text-xs">
                                Aucune note enregistrée.
                            </div>
                        </div>

                        <Link :href="route('student.courses')" class="mt-8 w-full p-4 bg-white/5 hover:bg-white/10 rounded-2xl flex items-center justify-center gap-3 group transition border border-white/5 active:scale-98">
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-200">Voir tous mes cours</span>
                            <ArrowRightIcon class="h-4 w-4 text-slate-400 group-hover:translate-x-1 transition-transform" />
                        </Link>
                    </div>

                    <!-- Recent Exams -->
                    <div class="bg-slate-900 p-8 rounded-[2.5rem] shadow-xl text-white relative overflow-hidden transition-all hover:shadow-2xl">
                        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-48 h-48 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
                        
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h3 class="text-xl font-black tracking-tight">Résultats d'Examens</h3>
                                <p class="text-xs text-slate-400 font-medium">Dernières notes officielles</p>
                            </div>
                            <div class="h-10 w-10 bg-white/10 text-emerald-400 rounded-xl flex items-center justify-center border border-white/5">
                                <AcademicCapIcon class="h-5 w-5" />
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div v-for="res in recentExams" :key="res.id">
                                <Link v-if="res.exam.are_grades_published" :href="route('student.exams.result', res.exam.id)" class="flex items-center justify-between border-b border-white/5 pb-3.5 pt-3.5 first:pt-0 last:border-0 last:pb-0 hover:bg-white/5 transition-colors -mx-4 px-4 rounded-xl group">
                                    <div class="min-w-0 flex-1 pr-4">
                                        <p class="font-bold text-sm text-slate-100 truncate group-hover:text-emerald-400 transition-colors">{{ res.exam.titre }}</p>
                                        <p class="text-[8px] font-black uppercase tracking-widest text-emerald-500 mt-0.5">Voir la correction &rarr;</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-lg font-black text-emerald-400">
                                            {{ res.score }}<span class="text-[10px] text-slate-600">/{{ res.exam.total_points }}</span>
                                        </div>
                                    </div>
                                </Link>
                                <div v-else class="flex items-center justify-between border-b border-white/5 pb-3.5 pt-3.5 first:pt-0 last:border-0 last:pb-0">
                                    <div class="min-w-0 flex-1 pr-4">
                                        <p class="font-bold text-sm text-slate-100 truncate">{{ res.exam.titre }}</p>
                                        <p class="text-[8px] font-black uppercase tracking-widest text-slate-500 mt-0.5">En attente</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-[10px] font-black uppercase text-slate-600 tracking-widest">
                                            ...
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="recentExams.length === 0" class="py-8 text-center text-slate-500 font-bold italic text-xs">
                                Aucun examen passé.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
