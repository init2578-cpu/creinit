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
    PencilSquareIcon
} from '@heroicons/vue/24/outline'
import { formatTime } from '@/utils/format'

const props = defineProps({
    nextSchedules: Array,
    absenceCount: Number,
    progress: Number,
    group: Object,
    upcomingExams: Array,
    recentExercises: Array
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

import { computed } from 'vue'

const DAYS = ['', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']
const dayLabel = (d) => DAYS[d] ?? String(d).substring(0, 3)
</script>

<template>
    <Head title="Espace Apprenant" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 -mt-6 sm:-mt-8 lg:-mt-10 font-sans">
            <header class="mb-6 flex flex-col md:flex-row items-center gap-8 bg-white p-6 rounded-[2.5rem] border border-gray-100 shadow-sm">
                <div class="h-32 w-32 rounded-[2.5rem] bg-blue-600 flex items-center justify-center overflow-hidden text-white text-4xl font-black shadow-xl shadow-blue-100 shrink-0">
                    <img v-if="$page.props.auth.user.profile_photo_url" :src="$page.props.auth.user.profile_photo_url" class="h-full w-full object-cover">
                    <template v-else>{{ $page.props.auth.user.name.charAt(0) }}</template>
                </div>
                <div class="text-center md:text-left">
                    <h1 class="text-4xl font-black text-gray-900 tracking-tight mb-3">
                        Bienvenue, <span class="text-blue-600">{{ $page.props.auth.user.name }}</span>
                    </h1>
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-2">
                        <span class="px-3 py-1 bg-gray-900 text-white rounded-lg text-[10px] font-black uppercase tracking-widest">
                            Cohorte 2026
                        </span>
                        <span v-if="group" class="px-3 py-1 bg-blue-50 text-blue-700 rounded-lg text-[10px] font-black uppercase tracking-widest">
                            {{ group.nom_groupe }}
                        </span>
                    </div>
                </div>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Activity (Progress & Planning) -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Progress Section -->
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-black text-gray-900 flex items-center gap-3">
                                <BookOpenIcon class="h-6 w-6 text-blue-600" />
                                Ma Progression
                            </h2>
                            <span class="text-2xl font-black text-blue-600">{{ progress }}%</span>
                        </div>
                        
                        <div class="w-full h-4 bg-gray-100 rounded-full overflow-hidden mb-4">
                            <div 
                                class="h-full bg-blue-600 rounded-full transition-all duration-1000 ease-out"
                                :style="{ width: progress + '%' }"
                            ></div>
                        </div>
                        
                        <p class="text-gray-500 text-sm font-medium">
                            {{ progress >= 100 ? 'Félicitations ! Vous avez complété tous les chapitres.' : 'Continuez vos efforts pour valider le module actuel.' }}
                        </p>
                    </div>

                    <!-- Upcoming Classes -->
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100">
                        <h2 class="text-xl font-black text-gray-900 mb-6 flex items-center gap-3">
                            <CalendarIcon class="h-6 w-6 text-indigo-600" />
                            Prochains Cours
                        </h2>
                        
                        <div class="space-y-4">
                            <div v-for="sch in nextSchedules" :key="sch.id" class="p-4 bg-gray-50/50 rounded-2xl flex items-center justify-between group hover:bg-white hover:shadow-lg hover:shadow-gray-100 transition-all border border-transparent hover:border-gray-100">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 px-2 bg-white rounded-xl shadow-sm flex flex-col items-center justify-center border border-gray-100 min-w-[64px]">
                                        <span class="text-[8px] font-black text-blue-600 uppercase">{{ dayLabel(sch.day_of_week) }}</span>
                                        <span class="text-[10px] font-black text-gray-900 font-mono tracking-tighter">{{ formatTime(sch.start_time) }}</span>
                                    </div>
                                    <div>
                                        <p class="font-black text-gray-900">{{ sch.group.nom_groupe }}</p>
                                        <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">{{ sch.room.nom }} • {{ sch.formateur.name }}</p>
                                    </div>
                                </div>
                                <ArrowRightIcon class="h-5 w-5 text-gray-300 group-hover:text-blue-600 transition" />
                            </div>
                            <div v-if="nextSchedules.length === 0" class="py-8 text-center text-gray-400 font-bold italic">
                                Aucun cours prévu pour le moment.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Side Info (Discipline & Quick Actions) -->
                <div class="space-y-8">
                    <!-- Upcoming Exams -->
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100">
                        <h2 class="text-xl font-black text-gray-900 mb-6 flex items-center gap-3">
                            <PencilSquareIcon class="h-6 w-6 text-red-500" />
                            Examens à venir
                        </h2>
                        <div class="space-y-4">
                            <div v-for="exam in upcomingExams" :key="exam.id" class="p-5 bg-red-50/50 rounded-2xl flex items-center justify-between group hover:bg-white hover:shadow-lg transition-all border border-transparent hover:border-red-100">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-red-500 font-black">
                                        <ClockIcon class="h-6 w-6" />
                                    </div>
                                    <div>
                                        <p class="font-black text-gray-900">{{ exam.titre }}</p>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ exam.is_practice ? 'Entraînement' : 'Examen Final' }}</p>
                                    </div>
                                </div>
                                <Link :href="route('student.exams.show', exam.id)" class="px-4 py-2 bg-red-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-red-700 transition">
                                    Commencer
                                </Link>
                            </div>
                            <div v-if="upcomingExams.length === 0" class="py-6 text-center text-gray-400 font-bold italic text-sm">
                                Aucun examen prévu.
                            </div>
                        </div>
                    </div>

                    <!-- Recent Exercises -->
                    <div class="bg-gray-900 p-8 rounded-[2.5rem] shadow-lg text-white">
                        <h3 class="text-xl font-black mb-8 flex items-center gap-3">
                            <ArrowTrendingUpIcon class="h-6 w-6 text-blue-500" />
                            Notes d'Exercices
                        </h3>
                        <div class="space-y-6">
                            <div v-for="ex in recentExercises" :key="ex.id" class="flex items-center justify-between border-b border-white/5 pb-4 last:border-0 last:pb-0">
                                <div>
                                    <p class="font-bold text-sm text-gray-100 truncate w-40">{{ ex.chapter.titre }}</p>
                                    <p class="text-[8px] font-black uppercase tracking-widest text-gray-500">{{ ex.status === 'graded' ? 'Évalué' : 'En attente' }}</p>
                                </div>
                                <div class="text-right">
                                    <div v-if="ex.status === 'graded'" class="text-xl font-black text-blue-400">
                                        {{ ex.grade }}<span class="text-[10px] text-gray-600">/20</span>
                                    </div>
                                    <div v-else class="text-[10px] font-black uppercase text-gray-600 tracking-widest">
                                        ...
                                    </div>
                                </div>
                            </div>
                            <div v-if="recentExercises.length === 0" class="py-4 text-center text-gray-600 font-bold italic text-xs">
                                Aucune note enregistrée.
                            </div>
                        </div>

                        <Link :href="route('student.courses')" class="mt-10 w-full p-4 bg-white/5 hover:bg-white/10 rounded-2xl flex items-center justify-center gap-3 group transition border border-white/5">
                            <span class="text-[10px] font-black uppercase tracking-widest">Voir tous mes cours</span>
                            <ArrowRightIcon class="h-4 w-4 group-hover:translate-x-1 transition-transform" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
