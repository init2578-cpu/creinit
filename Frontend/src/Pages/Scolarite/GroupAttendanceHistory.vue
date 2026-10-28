<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { 
    ChevronLeftIcon,
    ChevronRightIcon,
    CalendarIcon,
    ClockIcon,
    UserIcon,
    UserGroupIcon,
    CheckCircleIcon,
    XCircleIcon,
    InformationCircleIcon,
    LockClosedIcon,
    MagnifyingGlassIcon,
    ArrowDownTrayIcon,
    PrinterIcon,
    AcademicCapIcon,
    EyeIcon,
    XMarkIcon,
    MapPinIcon,
    ChatBubbleBottomCenterTextIcon,
    SparklesIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    group: Object,
    stats: Object,
    students: Array,
    sessions: Array,
})

const activeTab = ref('students') // 'students' | 'sessions'
const searchQuery = ref('')
const selectedSession = ref(null)
const isSessionModalOpen = ref(false)

const selectedStudent = ref(null)
const isStudentModalOpen = ref(false)
const studentFilter = ref('anomalies') // 'anomalies' | 'all' | 'absent_non_justifie' | 'justifie' | 'late' | 'present'

const filteredStudents = computed(() => {
    if (!props.students) return []
    if (!searchQuery.value) return props.students
    const q = searchQuery.value.toLowerCase().trim()
    return props.students.filter(s => 
        (s.name && s.name.toLowerCase().includes(q)) ||
        (s.email && s.email.toLowerCase().includes(q)) ||
        (s.telephone && s.telephone.includes(q))
    )
})

const filteredSessions = computed(() => {
    if (!props.sessions) return []
    if (!searchQuery.value) return props.sessions
    const q = searchQuery.value.toLowerCase().trim()
    return props.sessions.filter(sess => 
        formatDate(sess.date).toLowerCase().includes(q) ||
        sess.date.includes(q)
    )
})

function formatDate(dateString) {
    if (!dateString) return ''
    const parts = dateString.split('T')[0].split('-').map(Number)
    if (parts.length === 3 && parts[0] && parts[1] && parts[2]) {
        const date = new Date(parts[0], parts[1] - 1, parts[2], 12, 0, 0)
        return date.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
    }
    return new Date(dateString).toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
}

function formatDateLong(dateString) {
    if (!dateString) return ''
    const parts = dateString.split('T')[0].split('-').map(Number)
    if (parts.length === 3 && parts[0] && parts[1] && parts[2]) {
        const date = new Date(parts[0], parts[1] - 1, parts[2], 12, 0, 0)
        const str = date.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
        return str.charAt(0).toUpperCase() + str.slice(1)
    }
    return dateString
}

function getDayOfWeek(dateString) {
    if (!dateString) return ''
    const parts = dateString.split('T')[0].split('-').map(Number)
    if (parts.length === 3 && parts[0] && parts[1] && parts[2]) {
        const date = new Date(parts[0], parts[1] - 1, parts[2], 12, 0, 0)
        const str = date.toLocaleDateString('fr-FR', { weekday: 'long' })
        return str.charAt(0).toUpperCase() + str.slice(1)
    }
    return ''
}

function formatTime(timeStr) {
    if (!timeStr) return ''
    return timeStr.substring(0, 5)
}

function openSessionModal(session) {
    selectedSession.value = session
    isSessionModalOpen.value = true
}

function closeSessionModal() {
    selectedSession.value = null
    isSessionModalOpen.value = false
}

function openStudentModal(student) {
    selectedStudent.value = student
    const hasAnomalies = ((student.absences_count || 0) > 0 || (student.late_count || 0) > 0)
    studentFilter.value = hasAnomalies ? 'anomalies' : 'all'
    isStudentModalOpen.value = true
}

function closeStudentModal() {
    selectedStudent.value = null
    isStudentModalOpen.value = false
}

const studentFilteredRecords = computed(() => {
    if (!selectedStudent.value || !selectedStudent.value.records) return []
    const records = selectedStudent.value.records

    if (studentFilter.value === 'all') {
        return records
    }
    if (studentFilter.value === 'anomalies') {
        return records.filter(r => r.status !== 'present')
    }
    if (studentFilter.value === 'absent_non_justifie') {
        return records.filter(r => r.status === 'absent_non_justifie')
    }
    if (studentFilter.value === 'justifie') {
        return records.filter(r => r.status === 'justifie')
    }
    if (studentFilter.value === 'late') {
        return records.filter(r => r.status === 'late' || r.status === 'en_retard')
    }
    if (studentFilter.value === 'present') {
        return records.filter(r => r.status === 'present')
    }
    return records
})

function printPage() {
    window.print()
}

const statusBadge = (status) => {
    switch(status) {
        case 'present':
            return { 
                label: 'Présent', 
                bg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                dot: 'bg-emerald-500'
            }
        case 'absent_non_justifie':
            return { 
                label: 'Absence non justifiée', 
                bg: 'bg-rose-50 text-rose-700 border-rose-200',
                dot: 'bg-rose-500'
            }
        case 'justifie':
            return { 
                label: 'Absence justifiée', 
                bg: 'bg-blue-50 text-blue-700 border-blue-200',
                dot: 'bg-blue-500'
            }
        case 'late':
        case 'en_retard':
            return { 
                label: 'Retard', 
                bg: 'bg-amber-50 text-amber-700 border-amber-200',
                dot: 'bg-amber-500'
            }
        default:
            return { 
                label: status, 
                bg: 'bg-gray-50 text-gray-700 border-gray-200',
                dot: 'bg-gray-400'
            }
    }
}
</script>

<template>
    <Head :title="`Historique des Présences — ${group.nom_groupe}`" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-6 px-4 font-sans space-y-8">
            <!-- Navigation Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <Link 
                    :href="route('groups.index')" 
                    class="inline-flex items-center gap-2 text-gray-500 hover:text-blue-600 font-bold text-sm transition group"
                >
                    <div class="p-2.5 bg-white rounded-2xl shadow-sm border border-gray-100 group-hover:bg-blue-50 group-hover:border-blue-100 transition">
                        <ChevronLeftIcon class="h-4 w-4" />
                    </div>
                    Retour aux groupes
                </Link>

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('groups.students.index', group.id)"
                        class="px-5 py-3 rounded-2xl bg-white border border-gray-200 text-gray-700 font-black text-xs uppercase tracking-wider hover:bg-gray-50 transition shadow-sm flex items-center gap-2"
                    >
                        <UserGroupIcon class="h-4 w-4 text-gray-400" />
                        Gérer les Apprenants
                    </Link>
                    <button
                        @click="printPage"
                        class="px-5 py-3 rounded-2xl bg-slate-900 text-white font-black text-xs uppercase tracking-wider hover:bg-black transition shadow-sm flex items-center gap-2"
                    >
                        <PrinterIcon class="h-4 w-4" />
                        Imprimer le bilan
                    </button>
                </div>
            </div>

            <!-- Group Hero Banner -->
            <div class="relative overflow-hidden bg-white p-8 sm:p-10 rounded-[2.5rem] border border-gray-100 shadow-sm">
                <div class="absolute top-0 right-0 -mt-16 -mr-16 w-80 h-80 bg-blue-50/60 rounded-full blur-3xl opacity-70"></div>
                <div class="absolute bottom-0 left-0 -mb-16 -ml-16 w-64 h-64 bg-emerald-50/40 rounded-full blur-2xl opacity-50"></div>

                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span 
                                class="px-3.5 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border"
                                :class="group.status === 'closed' 
                                    ? 'bg-rose-50 text-rose-700 border-rose-200' 
                                    : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                            >
                                <span v-if="group.status === 'closed'" class="inline-flex items-center gap-1">
                                    <LockClosedIcon class="h-3 w-3" />
                                    Formation Clôturée
                                </span>
                                <span v-else class="inline-flex items-center gap-1.5">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Groupe En cours
                                </span>
                            </span>

                            <span class="px-3.5 py-1.5 bg-blue-50 text-blue-700 rounded-full text-[10px] font-black uppercase tracking-widest border border-blue-100">
                                Année {{ group.annee_academique }}
                            </span>
                            <span class="px-3.5 py-1.5 bg-gray-50 text-gray-600 rounded-full text-[10px] font-black uppercase tracking-widest border border-gray-200">
                                {{ students.length }} Apprenants inscrits
                            </span>
                        </div>

                        <div>
                            <h1 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight leading-tight">
                                Historique d'Émargement &mdash; {{ group.nom_groupe }}
                            </h1>
                            <p class="text-gray-500 text-sm font-medium mt-1">
                                Module : <span class="font-bold text-gray-800">{{ group.module?.titre }}</span>
                                <span class="mx-2 text-gray-300">•</span>
                                Formateur : <span class="font-bold text-gray-800">{{ group.formateur?.name || 'N/A' }}</span>
                            </p>
                        </div>

                        <!-- Closed group notice -->
                        <div v-if="group.status === 'closed'" class="p-4 bg-amber-50/70 border border-amber-200/80 rounded-2xl flex items-center gap-3 text-amber-800 text-xs font-semibold max-w-2xl">
                            <LockClosedIcon class="h-5 w-5 shrink-0 text-amber-600" />
                            <span>
                                Ce groupe a été clôturé après fin de formation. Les listes de présence, décomptes d'heures et bilans individuels sont conservés et archivés.
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Global KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Total Sessions -->
                <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-5">
                    <div class="h-14 w-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                        <CalendarIcon class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Séances Enregistrées</p>
                        <h3 class="text-2xl font-black text-gray-900 leading-tight mt-0.5">{{ stats.total_sessions }}</h3>
                        <p class="text-[11px] font-medium text-gray-500">Séances d'émargement</p>
                    </div>
                </div>

                <!-- Taux Global -->
                <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-5">
                    <div class="h-14 w-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                        <CheckCircleIcon class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Taux Moyen d'Assiduité</p>
                        <h3 class="text-2xl font-black text-emerald-600 leading-tight mt-0.5">{{ stats.overall_attendance_rate }}%</h3>
                        <div class="w-24 h-1.5 bg-gray-100 rounded-full mt-1.5 overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full" :style="{ width: `${stats.overall_attendance_rate}%` }"></div>
                        </div>
                    </div>
                </div>

                <!-- Total Presences -->
                <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-5">
                    <div class="h-14 w-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100">
                        <UserGroupIcon class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Présences</p>
                        <h3 class="text-2xl font-black text-indigo-600 leading-tight mt-0.5">{{ stats.total_presences }}</h3>
                        <p class="text-[11px] font-medium text-gray-500">Émargements "Présent"</p>
                    </div>
                </div>

                <!-- Total Absences & Retards -->
                <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-5">
                    <div class="h-14 w-14 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-100">
                        <XCircleIcon class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Absences & Retards</p>
                        <h3 class="text-2xl font-black text-rose-600 leading-tight mt-0.5">{{ stats.total_absences }}</h3>
                        <p class="text-[11px] font-medium text-gray-500">{{ stats.total_lates }} retard(s) signalé(s)</p>
                    </div>
                </div>
            </div>

            <!-- Tabs & Search Controls -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-3 rounded-2xl border border-gray-100 shadow-sm">
                <!-- Tab Buttons -->
                <div class="flex items-center gap-2">
                    <button
                        @click="activeTab = 'students'"
                        class="px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition-all flex items-center gap-2"
                        :class="activeTab === 'students' 
                            ? 'bg-slate-900 text-white shadow-sm' 
                            : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100'"
                    >
                        <UserGroupIcon class="h-4 w-4" />
                        Bilan par Apprenant ({{ students.length }})
                    </button>

                    <button
                        @click="activeTab = 'sessions'"
                        class="px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-wider transition-all flex items-center gap-2"
                        :class="activeTab === 'sessions' 
                            ? 'bg-slate-900 text-white shadow-sm' 
                            : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100'"
                    >
                        <CalendarIcon class="h-4 w-4" />
                        Feuilles d'Émargement par Séance ({{ sessions.length }})
                    </button>
                </div>

                <!-- Search Input -->
                <div class="relative w-full sm:w-72">
                    <MagnifyingGlassIcon class="h-4 w-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        :placeholder="activeTab === 'students' ? 'Filtrer par nom ou email...' : 'Filtrer par date...'"
                        class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold text-gray-800 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>
            </div>

            <!-- TAB 1: Bilan nominatif par apprenant -->
            <div v-if="activeTab === 'students'" class="space-y-4">
                <div v-if="filteredStudents.length === 0" class="bg-white rounded-[2rem] p-16 text-center border border-gray-100">
                    <UserGroupIcon class="h-12 w-12 text-gray-300 mx-auto mb-3" />
                    <h3 class="text-base font-black text-gray-800">Aucun apprenant trouvé</h3>
                    <p class="text-xs text-gray-400 mt-1">Aucun résultat ne correspond à votre filtre de recherche.</p>
                </div>

                <div v-else class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm overflow-hidden">
                    <!-- Informative Interactive Tip Banner -->
                    <div class="px-6 py-3 bg-gradient-to-r from-blue-50/70 to-indigo-50/30 border-b border-blue-100/60 flex items-center justify-between text-xs text-blue-900 font-medium">
                        <div class="flex items-center gap-2">
                            <InformationCircleIcon class="h-4 w-4 text-blue-600 shrink-0" />
                            <span><strong class="font-black text-blue-950">Astuce :</strong> Cliquez sur une ligne pour voir le détail de chaque absence, absence non justifiée et retard avec ses jours et dates.</span>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-blue-600 bg-blue-100/60 px-2.5 py-0.5 rounded-full hidden sm:inline-block">
                            Ligne cliquable
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50/70 border-b border-gray-100 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                    <th class="py-4 px-6">Apprenant</th>
                                    <th class="py-4 px-4 text-center">Séances</th>
                                    <th class="py-4 px-4 text-center">Présences</th>
                                    <th class="py-4 px-4 text-center">Absences</th>
                                    <th class="py-4 px-4 text-center">Abs. Justifiées</th>
                                    <th class="py-4 px-4 text-center">Retards</th>
                                    <th class="py-4 px-4 text-center">Taux d'Assiduité</th>
                                    <th class="py-4 px-6 text-right">Détails</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm font-medium">
                                <tr 
                                    v-for="student in filteredStudents" 
                                    :key="student.id"
                                    @click="openStudentModal(student)"
                                    class="hover:bg-blue-50/40 cursor-pointer transition-all duration-150 group"
                                    title="Cliquer pour afficher les détails des absences et retards de cet apprenant"
                                >
                                    <!-- Learner Profile -->
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3.5">
                                            <div class="h-11 w-11 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white font-black text-base flex items-center justify-center overflow-hidden shrink-0 shadow-sm">
                                                <img v-if="student.profile_photo_url" :src="student.profile_photo_url" class="h-full w-full object-cover">
                                                <span v-else>{{ student.name.charAt(0).toUpperCase() }}</span>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-black text-gray-900 leading-snug group-hover:text-blue-600 transition-colors">{{ student.name }}</span>
                                                    <span 
                                                        v-if="student.role !== 'Apprenant'"
                                                        class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full"
                                                        :class="student.role === 'Chef de groupe' ? 'bg-blue-600 text-white' : 'bg-amber-500 text-white'"
                                                    >
                                                        {{ student.role }}
                                                    </span>
                                                </div>
                                                <p class="text-xs text-gray-400 font-medium">{{ student.email || student.telephone || 'Aucun contact' }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Total sessions -->
                                    <td class="py-4 px-4 text-center font-bold text-gray-600">
                                        {{ student.total_sessions }}
                                    </td>

                                    <!-- Presences -->
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-100">
                                            {{ student.presences_count }}
                                        </span>
                                    </td>

                                    <!-- Absences non justifiees -->
                                    <td class="py-4 px-4 text-center">
                                        <span 
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black border"
                                            :class="student.absences_non_justifiees_count > 0 ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-gray-50 text-gray-400 border-gray-100'"
                                        >
                                            {{ student.absences_non_justifiees_count }}
                                        </span>
                                    </td>

                                    <!-- Absences justifiees -->
                                    <td class="py-4 px-4 text-center">
                                        <span 
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black border"
                                            :class="student.absences_justifiees_count > 0 ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-gray-50 text-gray-400 border-gray-100'"
                                        >
                                            {{ student.absences_justifiees_count }}
                                        </span>
                                        <div v-if="student.absences_signalees_count > 0" class="text-[9px] font-black text-purple-600 mt-0.5">
                                            dont {{ student.absences_signalees_count }} signalée(s)
                                        </div>
                                    </td>

                                    <!-- Retards -->
                                    <td class="py-4 px-4 text-center">
                                        <span 
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black border"
                                            :class="student.late_count > 0 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-gray-50 text-gray-400 border-gray-100'"
                                        >
                                            {{ student.late_count }}
                                        </span>
                                    </td>

                                    <!-- Taux de presence -->
                                    <td class="py-4 px-4 text-center">
                                        <div class="inline-flex flex-col items-center">
                                            <span 
                                                class="text-sm font-black"
                                                :class="student.attendance_rate >= 80 ? 'text-emerald-600' : student.attendance_rate >= 50 ? 'text-amber-600' : 'text-rose-600'"
                                            >
                                                {{ student.attendance_rate }}%
                                            </span>
                                            <div class="w-20 h-1.5 bg-gray-100 rounded-full mt-1 overflow-hidden">
                                                <div 
                                                    class="h-full rounded-full"
                                                    :class="student.attendance_rate >= 80 ? 'bg-emerald-500' : student.attendance_rate >= 50 ? 'bg-amber-500' : 'bg-rose-500'"
                                                    :style="{ width: `${student.attendance_rate}%` }"
                                                ></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Details Action Button -->
                                    <td class="py-4 px-6 text-right">
                                        <button
                                            type="button"
                                            @click.stop="openStudentModal(student)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-black text-blue-600 bg-blue-50/80 border border-blue-100 group-hover:bg-blue-600 group-hover:text-white group-hover:border-blue-600 transition shadow-xs"
                                        >
                                            <EyeIcon class="h-3.5 w-3.5" />
                                            <span>Détails</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Feuilles d'Émargement par Séance -->
            <div v-if="activeTab === 'sessions'" class="space-y-4">
                <div v-if="filteredSessions.length === 0" class="bg-white rounded-[2rem] p-16 text-center border border-gray-100">
                    <CalendarIcon class="h-12 w-12 text-gray-300 mx-auto mb-3" />
                    <h3 class="text-base font-black text-gray-800">Aucune feuille d'émargement enregistrée</h3>
                    <p class="text-xs text-gray-400 mt-1">Aucune séance d'émargement n'a encore été enregistrée pour ce groupe.</p>
                </div>

                <div v-else class="grid grid-cols-1 gap-4">
                    <div 
                        v-for="session in filteredSessions" 
                        :key="session.date + '_' + session.schedule_id"
                        class="bg-white rounded-3xl border border-gray-100 p-6 sm:p-7 hover:shadow-xl hover:shadow-gray-100/60 transition duration-300 flex flex-col lg:flex-row lg:items-center justify-between gap-6"
                    >
                        <div class="space-y-2">
                            <div class="flex items-center gap-3">
                                <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl">
                                    <CalendarIcon class="h-5 w-5" />
                                </div>
                                <div>
                                    <h3 class="text-base sm:text-lg font-black text-gray-900 capitalize">
                                        {{ formatDate(session.date) }}
                                    </h3>
                                    <div class="flex flex-wrap items-center gap-3 text-xs font-medium text-gray-500 mt-0.5">
                                        <span v-if="session.schedule" class="flex items-center gap-1">
                                            <ClockIcon class="h-3.5 w-3.5 text-gray-400" />
                                            {{ formatTime(session.schedule.start_time) }} - {{ formatTime(session.schedule.end_time) }}
                                        </span>
                                        <span v-if="session.schedule?.room" class="flex items-center gap-1">
                                            <span class="text-gray-300">•</span>
                                            {{ session.schedule.room }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <span class="text-gray-300">•</span>
                                            Formateur : 
                                            <span 
                                                class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase"
                                                :class="session.trainer_status === 'present' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                            >
                                                {{ session.trainer_status === 'present' ? 'Présent' : session.trainer_status }}
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Badges for the session -->
                        <div class="flex flex-wrap items-center gap-2.5">
                            <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 px-3.5 py-2 rounded-2xl text-center min-w-[70px]">
                                <div class="text-sm font-black">{{ session.present }}</div>
                                <div class="text-[9px] font-black uppercase tracking-wider opacity-75">Présents</div>
                            </div>
                            <div class="bg-rose-50 border border-rose-100 text-rose-800 px-3.5 py-2 rounded-2xl text-center min-w-[70px]">
                                <div class="text-sm font-black">{{ session.absent_non_justifie }}</div>
                                <div class="text-[9px] font-black uppercase tracking-wider opacity-75">Absents</div>
                            </div>
                            <div class="bg-amber-50 border border-amber-100 text-amber-800 px-3.5 py-2 rounded-2xl text-center min-w-[70px]">
                                <div class="text-sm font-black">{{ session.late }}</div>
                                <div class="text-[9px] font-black uppercase tracking-wider opacity-75">Retards</div>
                            </div>
                            <div class="bg-blue-50 border border-blue-100 text-blue-800 px-3.5 py-2 rounded-2xl text-center min-w-[70px]">
                                <div class="text-sm font-black">{{ session.justifie }}</div>
                                <div class="text-[9px] font-black uppercase tracking-wider opacity-75">Justifiés</div>
                            </div>
                            <div class="bg-gray-50 border border-gray-100 text-gray-600 px-3.5 py-2 rounded-2xl text-center min-w-[70px]">
                                <div class="text-sm font-black">{{ session.total_students }}</div>
                                <div class="text-[9px] font-black uppercase tracking-wider opacity-75">Total</div>
                            </div>

                            <!-- Inspect Details Button -->
                            <button
                                @click="openSessionModal(session)"
                                class="px-4 py-3 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-wider hover:bg-black transition shadow-sm flex items-center gap-1.5 ml-2"
                            >
                                <EyeIcon class="h-4 w-4" />
                                Détails de l'appel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal: Session Attendance Details -->
            <div 
                v-if="isSessionModalOpen && selectedSession" 
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
                @click.self="closeSessionModal"
            >
                <div class="bg-white rounded-[2.5rem] max-w-2xl w-full p-8 shadow-2xl border border-gray-100 max-h-[85vh] flex flex-col">
                    <div class="flex items-center justify-between pb-6 border-b border-gray-100">
                        <div>
                            <span class="text-[10px] font-black text-blue-600 uppercase tracking-widest">Feuille d'émargement</span>
                            <h3 class="text-xl font-black text-gray-900 capitalize mt-0.5">
                                Séance du {{ formatDate(selectedSession.date) }}
                            </h3>
                            <p v-if="selectedSession.schedule" class="text-xs text-gray-500 font-medium mt-0.5">
                                Horaire : {{ formatTime(selectedSession.schedule.start_time) }} - {{ formatTime(selectedSession.schedule.end_time) }} &bull; Salle : {{ selectedSession.schedule.room }}
                            </p>
                        </div>
                        <button 
                            @click="closeSessionModal"
                            class="p-2.5 rounded-2xl bg-gray-100 text-gray-500 hover:bg-gray-200 transition"
                        >
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Students List in Session -->
                    <div class="flex-1 overflow-y-auto py-4 space-y-2.5 custom-scrollbar">
                        <div 
                            v-for="record in selectedSession.records" 
                            :key="record.user_id"
                            class="p-4 rounded-2xl border border-gray-100 bg-gray-50/50 flex items-center justify-between"
                        >
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-xl bg-blue-100 text-blue-700 font-black text-sm flex items-center justify-center shrink-0">
                                    {{ record.user_name.charAt(0).toUpperCase() }}
                                </div>
                                <span class="font-black text-gray-900 text-sm">{{ record.user_name }}</span>
                            </div>

                            <div v-if="record.is_advance_reported" class="flex flex-col items-end">
                                <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-purple-50 text-purple-700 border border-purple-200">
                                    📢 Absence signalée
                                </span>
                                <span v-if="record.motif" class="text-[10px] text-gray-500 font-medium mt-0.5 max-w-[200px] truncate" :title="record.motif">
                                    {{ record.motif }}
                                </span>
                            </div>
                            <span 
                                v-else
                                class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider border"
                                :class="statusBadge(record.status).bg"
                            >
                                {{ statusBadge(record.status).label }}
                            </span>
                        </div>
                    </div>

                    <!-- Footer with Summary -->
                    <div class="pt-6 border-t border-gray-100 flex items-center justify-between">
                        <div class="text-xs text-gray-500 font-bold">
                            Total : <span class="text-gray-900 font-black">{{ selectedSession.total_students }} apprenant(s)</span>
                        </div>
                        <button
                            @click="closeSessionModal"
                            class="px-6 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs transition"
                        >
                            Fermer
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal: Student Detailed Attendance History -->
            <div 
                v-if="isStudentModalOpen && selectedStudent" 
                class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-gray-900/60 backdrop-blur-sm print:hidden"
                @click.self="closeStudentModal"
            >
                <div class="bg-white rounded-[2.5rem] max-w-3xl w-full p-6 sm:p-8 shadow-2xl border border-gray-100 max-h-[90vh] flex flex-col">
                    <!-- Modal Header -->
                    <div class="flex items-start justify-between pb-5 border-b border-gray-100">
                        <div class="flex items-center gap-4">
                            <div class="h-14 w-14 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white font-black text-xl flex items-center justify-center overflow-hidden shrink-0 shadow-md shadow-blue-500/20">
                                <img v-if="selectedStudent.profile_photo_url" :src="selectedStudent.profile_photo_url" class="h-full w-full object-cover">
                                <span v-else>{{ selectedStudent.name.charAt(0).toUpperCase() }}</span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2.5">
                                    <h3 class="text-xl sm:text-2xl font-black text-gray-900 leading-tight">
                                        {{ selectedStudent.name }}
                                    </h3>
                                    <span 
                                        v-if="selectedStudent.role !== 'Apprenant'"
                                        class="text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full"
                                        :class="selectedStudent.role === 'Chef de groupe' ? 'bg-blue-600 text-white' : 'bg-amber-500 text-white'"
                                    >
                                        {{ selectedStudent.role }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 font-medium mt-1 flex flex-wrap items-center gap-2">
                                    <span>{{ selectedStudent.email || 'Aucun email' }}</span>
                                    <span v-if="selectedStudent.telephone" class="text-gray-300">•</span>
                                    <span v-if="selectedStudent.telephone">{{ selectedStudent.telephone }}</span>
                                    <span class="text-gray-300">•</span>
                                    <span class="text-blue-600 font-bold">Groupe {{ group.nom_groupe }}</span>
                                </p>
                            </div>
                        </div>
                        <button 
                            @click="closeStudentModal"
                            class="p-2.5 rounded-2xl bg-gray-100 text-gray-500 hover:bg-gray-200 transition"
                            title="Fermer"
                        >
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Student Statistics Summary Cards -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 py-4 border-b border-gray-100">
                        <!-- Taux Assiduité -->
                        <div class="bg-gray-50 p-3.5 rounded-2xl border border-gray-100">
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Assiduité</p>
                            <div class="flex items-baseline justify-between mt-1">
                                <span 
                                    class="text-lg sm:text-xl font-black"
                                    :class="selectedStudent.attendance_rate >= 80 ? 'text-emerald-600' : selectedStudent.attendance_rate >= 50 ? 'text-amber-600' : 'text-rose-600'"
                                >
                                    {{ selectedStudent.attendance_rate }}%
                                </span>
                                <span class="text-[10px] font-bold text-gray-500">{{ selectedStudent.presences_count }}/{{ selectedStudent.total_sessions }}</span>
                            </div>
                            <div class="w-full h-1.5 bg-gray-200 rounded-full mt-1.5 overflow-hidden">
                                <div 
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="selectedStudent.attendance_rate >= 80 ? 'bg-emerald-500' : selectedStudent.attendance_rate >= 50 ? 'bg-amber-500' : 'bg-rose-500'"
                                    :style="{ width: `${selectedStudent.attendance_rate}%` }"
                                ></div>
                            </div>
                        </div>

                        <!-- Absences non justifiées -->
                        <div class="bg-rose-50/70 p-3.5 rounded-2xl border border-rose-100">
                            <p class="text-[9px] font-black text-rose-500 uppercase tracking-widest">Non justifiées</p>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-lg sm:text-xl font-black text-rose-700">
                                    {{ selectedStudent.absences_non_justifiees_count }}
                                </span>
                                <XCircleIcon class="h-5 w-5 text-rose-400" />
                            </div>
                            <p class="text-[10px] font-medium text-rose-600/80 mt-1">Absence(s) directe(s)</p>
                        </div>

                        <!-- Absences justifiées -->
                        <div class="bg-blue-50/70 p-3.5 rounded-2xl border border-blue-100">
                            <p class="text-[9px] font-black text-blue-500 uppercase tracking-widest">Justifiées</p>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-lg sm:text-xl font-black text-blue-700">
                                    {{ selectedStudent.absences_justifiees_count }}
                                </span>
                                <InformationCircleIcon class="h-5 w-5 text-blue-400" />
                            </div>
                            <p class="text-[10px] font-medium text-blue-600/80 mt-1">
                                {{ selectedStudent.absences_signalees_count > 0 ? `dont ${selectedStudent.absences_signalees_count} signalée(s)` : 'Avec justificatif' }}
                            </p>
                        </div>

                        <!-- Retards -->
                        <div class="bg-amber-50/70 p-3.5 rounded-2xl border border-amber-100">
                            <p class="text-[9px] font-black text-amber-500 uppercase tracking-widest">Retards</p>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-lg sm:text-xl font-black text-amber-700">
                                    {{ selectedStudent.late_count }}
                                </span>
                                <ClockIcon class="h-5 w-5 text-amber-400" />
                            </div>
                            <p class="text-[10px] font-medium text-amber-600/80 mt-1">Arrivée(s) tardive(s)</p>
                        </div>
                    </div>

                    <!-- Filter Tabs inside modal -->
                    <div class="pt-4 pb-2 flex flex-wrap items-center gap-2">
                        <button
                            @click="studentFilter = 'anomalies'"
                            class="px-3 py-1.5 rounded-xl font-black text-xs uppercase tracking-wider transition-all flex items-center gap-1.5 border"
                            :class="studentFilter === 'anomalies'
                                ? 'bg-slate-900 text-white border-slate-900 shadow-sm'
                                : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'"
                        >
                            <span>Absences & Retards</span>
                            <span 
                                class="px-1.5 py-0.2 rounded-full text-[10px]"
                                :class="studentFilter === 'anomalies' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"
                            >
                                {{ (selectedStudent.absences_count || 0) + (selectedStudent.late_count || 0) }}
                            </span>
                        </button>

                        <button
                            @click="studentFilter = 'absent_non_justifie'"
                            class="px-3 py-1.5 rounded-xl font-black text-xs uppercase tracking-wider transition-all flex items-center gap-1.5 border"
                            :class="studentFilter === 'absent_non_justifie'
                                ? 'bg-rose-600 text-white border-rose-600 shadow-sm'
                                : 'bg-rose-50/60 text-rose-700 border-rose-200 hover:bg-rose-100'"
                        >
                            <span>Non justifiées</span>
                            <span 
                                class="px-1.5 py-0.2 rounded-full text-[10px]"
                                :class="studentFilter === 'absent_non_justifie' ? 'bg-white/20 text-white' : 'bg-rose-200 text-rose-800'"
                            >
                                {{ selectedStudent.absences_non_justifiees_count }}
                            </span>
                        </button>

                        <button
                            @click="studentFilter = 'justifie'"
                            class="px-3 py-1.5 rounded-xl font-black text-xs uppercase tracking-wider transition-all flex items-center gap-1.5 border"
                            :class="studentFilter === 'justifie'
                                ? 'bg-blue-600 text-white border-blue-600 shadow-sm'
                                : 'bg-blue-50/60 text-blue-700 border-blue-200 hover:bg-blue-100'"
                        >
                            <span>Justifiées</span>
                            <span 
                                class="px-1.5 py-0.2 rounded-full text-[10px]"
                                :class="studentFilter === 'justifie' ? 'bg-white/20 text-white' : 'bg-blue-200 text-blue-800'"
                            >
                                {{ selectedStudent.absences_justifiees_count }}
                            </span>
                        </button>

                        <button
                            @click="studentFilter = 'late'"
                            class="px-3 py-1.5 rounded-xl font-black text-xs uppercase tracking-wider transition-all flex items-center gap-1.5 border"
                            :class="studentFilter === 'late'
                                ? 'bg-amber-600 text-white border-amber-600 shadow-sm'
                                : 'bg-amber-50/60 text-amber-700 border-amber-200 hover:bg-amber-100'"
                        >
                            <span>Retards</span>
                            <span 
                                class="px-1.5 py-0.2 rounded-full text-[10px]"
                                :class="studentFilter === 'late' ? 'bg-white/20 text-white' : 'bg-amber-200 text-amber-800'"
                            >
                                {{ selectedStudent.late_count }}
                            </span>
                        </button>

                        <button
                            @click="studentFilter = 'all'"
                            class="px-3 py-1.5 rounded-xl font-black text-xs uppercase tracking-wider transition-all flex items-center gap-1.5 border ml-auto"
                            :class="studentFilter === 'all'
                                ? 'bg-slate-900 text-white border-slate-900 shadow-sm'
                                : 'bg-gray-50 text-gray-500 border-gray-200 hover:bg-gray-100'"
                        >
                            <span>Tout l'historique</span>
                            <span 
                                class="px-1.5 py-0.2 rounded-full text-[10px]"
                                :class="studentFilter === 'all' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"
                            >
                                {{ selectedStudent.records?.length || selectedStudent.total_sessions }}
                            </span>
                        </button>
                    </div>

                    <!-- Records List Body -->
                    <div class="flex-1 overflow-y-auto py-3 space-y-3 custom-scrollbar pr-1">
                        <!-- Empty State: 100% presence when filter is anomalies -->
                        <div 
                            v-if="studentFilter === 'anomalies' && ((selectedStudent.absences_count || 0) + (selectedStudent.late_count || 0) === 0)"
                            class="p-8 rounded-3xl bg-emerald-50/60 border border-emerald-200 text-center space-y-3 my-4"
                        >
                            <div class="h-14 w-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto">
                                <SparklesIcon class="h-7 w-7" />
                            </div>
                            <h4 class="text-base font-black text-emerald-900">Assiduité exemplaire !</h4>
                            <p class="text-xs text-emerald-700 max-w-md mx-auto">
                                Cet apprenant n'a aucune absence ni retard enregistré. Il a été présent à toutes les {{ selectedStudent.presences_count }} séances enregistrées (100% de présence).
                            </p>
                            <button
                                @click="studentFilter = 'all'"
                                class="mt-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black rounded-xl uppercase tracking-wider transition shadow-sm inline-flex items-center gap-1.5"
                            >
                                <EyeIcon class="h-3.5 w-3.5" />
                                Voir toutes les séances
                            </button>
                        </div>

                        <!-- Generic Empty State -->
                        <div 
                            v-else-if="studentFilteredRecords.length === 0"
                            class="p-10 rounded-3xl bg-gray-50 text-center space-y-2 border border-gray-100 my-4"
                        >
                            <InformationCircleIcon class="h-10 w-10 text-gray-300 mx-auto" />
                            <h4 class="text-sm font-black text-gray-700">Aucun enregistrement</h4>
                            <p class="text-xs text-gray-400">Aucune séance ne correspond au filtre sélectionné pour cet apprenant.</p>
                        </div>

                        <!-- Records Items -->
                        <div 
                            v-for="record in studentFilteredRecords" 
                            :key="record.id || (record.date + '_' + record.schedule?.id)"
                            class="p-4 sm:p-5 rounded-2xl border transition-all duration-200 bg-white hover:shadow-md"
                            :class="record.status === 'absent_non_justifie' 
                                ? 'border-rose-200/80 bg-rose-50/15' 
                                : record.status === 'justifie' 
                                ? 'border-blue-200/80 bg-blue-50/15'
                                : record.status === 'late' || record.status === 'en_retard'
                                ? 'border-amber-200/80 bg-amber-50/15'
                                : 'border-gray-100 bg-gray-50/40'"
                        >
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <!-- Date & Day Block -->
                                <div class="flex items-center gap-3.5">
                                    <div 
                                        class="h-12 w-14 rounded-2xl flex flex-col items-center justify-center shrink-0 border shadow-xs"
                                        :class="record.status === 'absent_non_justifie'
                                            ? 'bg-rose-50 text-rose-700 border-rose-200'
                                            : record.status === 'justifie'
                                            ? 'bg-blue-50 text-blue-700 border-blue-200'
                                            : record.status === 'late' || record.status === 'en_retard'
                                            ? 'bg-amber-50 text-amber-700 border-amber-200'
                                            : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                                    >
                                        <span class="text-[9px] font-black uppercase tracking-wider leading-none">
                                            {{ getDayOfWeek(record.date).substring(0, 3) }}
                                        </span>
                                        <span class="text-base font-black leading-tight mt-0.5">
                                            {{ record.date ? record.date.split('-')[2] : '--' }}
                                        </span>
                                    </div>

                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-black text-gray-900 capitalize">
                                                {{ formatDateLong(record.date) }}
                                            </span>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2.5 text-xs text-gray-500 font-medium mt-0.5">
                                            <span v-if="record.schedule" class="flex items-center gap-1">
                                                <ClockIcon class="h-3.5 w-3.5 text-gray-400" />
                                                {{ formatTime(record.schedule.start_time) }} - {{ formatTime(record.schedule.end_time) }}
                                            </span>
                                            <span v-if="record.schedule?.room" class="flex items-center gap-1">
                                                <span class="text-gray-300">•</span>
                                                <MapPinIcon class="h-3.5 w-3.5 text-gray-400" />
                                                {{ record.schedule.room }}
                                            </span>
                                            <span v-if="record.schedule?.formateur" class="flex items-center gap-1">
                                                <span class="text-gray-300">•</span>
                                                Formateur : {{ record.schedule.formateur }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status Badge & Advance notice -->
                                <div class="flex flex-wrap sm:flex-col items-start sm:items-end gap-1.5 self-start sm:self-center">
                                    <div class="flex items-center gap-1.5">
                                        <span 
                                            class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider border flex items-center gap-1.5"
                                            :class="statusBadge(record.status).bg"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="statusBadge(record.status).dot"></span>
                                            {{ statusBadge(record.status).label }}
                                        </span>
                                    </div>

                                    <span 
                                        v-if="record.is_advance_reported" 
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-purple-50 text-purple-700 border border-purple-200 flex items-center gap-1"
                                    >
                                        📢 Signalée à l'avance
                                    </span>
                                </div>
                            </div>

                            <!-- Motif / Justification Callout if exists -->
                            <div 
                                v-if="record.motif" 
                                class="mt-3 p-3 rounded-xl bg-white border border-gray-100 flex items-start gap-2 text-xs"
                            >
                                <ChatBubbleBottomCenterTextIcon class="h-4 w-4 text-blue-500 shrink-0 mt-0.5" />
                                <div class="space-y-0.5">
                                    <span class="font-bold text-gray-700">Motif renseigné :</span>
                                    <p class="text-gray-600 font-medium italic">« {{ record.motif }} »</p>
                                </div>
                            </div>
                            <div 
                                v-else-if="record.status === 'absent_non_justifie'"
                                class="mt-2 text-[11px] text-gray-400 font-medium italic pl-1"
                            >
                                Aucun motif ni justificatif fourni pour cette séance.
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                        <div class="text-xs text-gray-500 font-bold">
                            Total : <span class="text-gray-900 font-black">{{ studentFilteredRecords.length }}</span> séance(s) affichée(s)
                        </div>
                        <button
                            @click="closeStudentModal"
                            class="px-6 py-2.5 rounded-xl bg-slate-900 hover:bg-black text-white font-black text-xs uppercase tracking-wider transition shadow-sm"
                        >
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
