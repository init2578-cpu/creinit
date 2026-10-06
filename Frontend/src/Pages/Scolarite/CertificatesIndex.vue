<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { 
    AcademicCapIcon, 
    CheckBadgeIcon, 
    ArrowDownTrayIcon,
    TrashIcon,
    FunnelIcon,
    MagnifyingGlassIcon,
    ClockIcon,
    PrinterIcon,
    ArrowPathIcon,
    EyeIcon,
    UsersIcon,
    ArchiveBoxIcon,
    SparklesIcon,
    DocumentTextIcon,
    XMarkIcon,
    ShieldCheckIcon,
    CalendarDaysIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    students: {
        type: Array,
        default: () => []
    },
    modules: {
        type: Array,
        default: () => []
    },
    closedGroups: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({
            total_students: 0,
            total_certificates: 0,
            total_reussite: 0,
            total_participation: 0,
            closed_groups_count: 0,
            closed_groups_pending_count: 0,
        })
    }
})

// Active tab: default to 'closed-groups' if any closed group exists, else 'all'
const activeTab = ref(props.closedGroups && props.closedGroups.length > 0 ? 'closed-groups' : 'all')

const searchQuery = ref('')
const selectedModule = ref(null)
const selectedGroupFilter = ref(null)

// Filtering for all students view
const filteredStudents = computed(() => {
    if (!props.students) return []
    return props.students.filter(student => {
        const name = String(student.name || '').toLowerCase()
        const email = String(student.email || '').toLowerCase()
        const query = searchQuery.value.toLowerCase()
        const matchesSearch = name.includes(query) || email.includes(query)
        
        if (!selectedModule.value) return matchesSearch
        
        return matchesSearch && student.progress.some(p => p.module_id === selectedModule.value)
    })
})

// Modal for single generation with customize option
const showGenerateModal = ref(false)
const modalStudent = ref(null)
const modalModule = ref(null)
const modalGroup = ref(null)
const modalScore = ref(null)
const modalType = ref('reussite')
const modalStartDate = ref('')
const modalEndDate = ref('')
const defaultAutoStartDate = ref('')
const defaultAutoEndDate = ref('')
const isSubmitting = ref(false)

function openGenerateModal(student, module, group, currentScore = null, currentType = null, startDate = null, endDate = null) {
    modalStudent.value = student
    modalModule.value = module
    modalGroup.value = group
    modalScore.value = currentScore !== null && currentScore !== undefined ? currentScore : null
    
    if (currentType) {
        modalType.value = currentType
    } else if (modalScore.value !== null) {
        modalType.value = Number(modalScore.value) >= 10 ? 'reussite' : 'participation'
    } else {
        modalType.value = 'reussite'
    }

    const start = startDate || student?.certificate?.start_date || student?.start_date || group?.default_start_date || ''
    const end = endDate || student?.certificate?.end_date || student?.end_date || group?.default_end_date || ''

    modalStartDate.value = start
    modalEndDate.value = end
    defaultAutoStartDate.value = student?.start_date || group?.default_start_date || ''
    defaultAutoEndDate.value = student?.end_date || group?.default_end_date || ''
    
    showGenerateModal.value = true
}

function resetModalDatesToAuto() {
    modalStartDate.value = defaultAutoStartDate.value
    modalEndDate.value = defaultAutoEndDate.value
}

function submitGenerateModal() {
    if (!modalStudent.value || !modalModule.value) return
    isSubmitting.value = true
    
    router.post(
        route('certificates.generate', { student: modalStudent.value.id, module: modalModule.value.id }),
        {
            group_id: modalGroup.value ? modalGroup.value.id : null,
            type: modalType.value,
            score: modalScore.value !== null && modalScore.value !== '' ? Number(modalScore.value) : null,
            start_date: modalStartDate.value || null,
            end_date: modalEndDate.value || null,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSubmitting.value = false
                showGenerateModal.value = false
            }
        }
    )
}

// Quick 1-click generation using defaults
function quickGenerateCertificate(studentId, moduleId, groupId = null, suggestedType = null, score = null) {
    if (isSubmitting.value) return
    isSubmitting.value = true
    
    router.post(
        route('certificates.generate', { student: studentId, module: moduleId }),
        {
            group_id: groupId,
            type: suggestedType,
            score: score,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSubmitting.value = false
            }
        }
    )
}

// Batch generation for all students of a closed group
const showBatchModal = ref(false)
const batchTargetGroup = ref(null)
const batchStartDate = ref('')
const batchEndDate = ref('')
const defaultBatchAutoStartDate = ref('')
const defaultBatchAutoEndDate = ref('')

function confirmGenerateForGroup(group) {
    batchTargetGroup.value = group
    batchStartDate.value = group.default_start_date || ''
    batchEndDate.value = group.default_end_date || ''
    defaultBatchAutoStartDate.value = group.default_start_date || ''
    defaultBatchAutoEndDate.value = group.default_end_date || ''
    showBatchModal.value = true
}

function resetBatchDatesToAuto() {
    batchStartDate.value = defaultBatchAutoStartDate.value
    batchEndDate.value = defaultBatchAutoEndDate.value
}

function submitBatchGeneration() {
    if (!batchTargetGroup.value) return
    isSubmitting.value = true
    
    router.post(
        route('certificates.generate-group', { group: batchTargetGroup.value.id }),
        {
            start_date: batchStartDate.value || null,
            end_date: batchEndDate.value || null,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSubmitting.value = false
                showBatchModal.value = false
            }
        }
    )
}

function deleteCertificate(certificateId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette attestation ? Le document PDF associé sera supprimé.')) {
        router.delete(route('certificates.destroy', certificateId), {
            preserveScroll: true
        })
    }
}
</script>

<template>
    <Head title="Gestion des Attestations" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-black text-gray-900 tracking-tight flex items-center gap-3">
                        <span>Gestion des Attestations</span>
                        <span v-if="stats.closed_groups_pending_count > 0" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 border border-amber-200 animate-pulse">
                            {{ stats.closed_groups_pending_count }} groupe(s) en attente
                        </span>
                    </h2>
                    <p class="text-sm text-gray-500 mt-1 font-medium">
                        Validation et production des attestations de réussite et de participation
                    </p>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Stats / Quick Actions -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 flex items-center gap-5">
                        <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 shrink-0">
                            <AcademicCapIcon class="h-7 w-7" />
                        </div>
                        <div>
                            <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Total Apprenants</p>
                            <p class="text-2xl font-black text-gray-900">{{ stats.total_students }}</p>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 flex items-center gap-5">
                        <div class="w-14 h-14 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 shrink-0">
                            <CheckBadgeIcon class="h-7 w-7" />
                        </div>
                        <div>
                            <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Attestations Réussite</p>
                            <div class="flex items-baseline gap-2">
                                <p class="text-2xl font-black text-emerald-600">{{ stats.total_reussite }}</p>
                                <span class="text-xs text-gray-400 font-bold">Moyenne ≥ 10</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 flex items-center gap-5">
                        <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600 shrink-0">
                            <DocumentTextIcon class="h-7 w-7" />
                        </div>
                        <div>
                            <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Attestations Participation</p>
                            <div class="flex items-baseline gap-2">
                                <p class="text-2xl font-black text-amber-600">{{ stats.total_participation }}</p>
                                <span class="text-xs text-gray-400 font-bold">Moyenne &lt; 10</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 flex items-center gap-5">
                        <div class="w-14 h-14 bg-purple-50 rounded-2xl flex items-center justify-center text-purple-600 shrink-0">
                            <ArchiveBoxIcon class="h-7 w-7" />
                        </div>
                        <div>
                            <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Groupes Clôturés</p>
                            <div class="flex items-baseline gap-2">
                                <p class="text-2xl font-black text-gray-900">{{ stats.closed_groups_count }}</p>
                                <span v-if="stats.closed_groups_pending_count > 0" class="text-xs text-amber-600 font-bold">
                                    ({{ stats.closed_groups_pending_count }} à valider)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs Selection -->
                <div class="flex border-b border-gray-200 gap-4">
                    <button
                        @click="activeTab = 'closed-groups'"
                        :class="[
                            'py-4 px-6 font-black text-sm uppercase tracking-wider border-b-2 transition flex items-center gap-3',
                            activeTab === 'closed-groups'
                                ? 'border-blue-600 text-blue-600'
                                : 'border-transparent text-gray-400 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        <ArchiveBoxIcon class="w-5 h-5" />
                        <span>Groupes Clôturés (Validation des attestations)</span>
                        <span 
                            v-if="closedGroups.length > 0" 
                            class="px-2.5 py-0.5 rounded-full text-xs font-black"
                            :class="stats.closed_groups_pending_count > 0 ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600'"
                        >
                            {{ closedGroups.length }}
                        </span>
                    </button>

                    <button
                        @click="activeTab = 'all'"
                        :class="[
                            'py-4 px-6 font-black text-sm uppercase tracking-wider border-b-2 transition flex items-center gap-3',
                            activeTab === 'all'
                                ? 'border-blue-600 text-blue-600'
                                : 'border-transparent text-gray-400 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        <UsersIcon class="w-5 h-5" />
                        <span>Vue Globale Apprenants</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-gray-100 text-gray-600">
                            {{ students.length }}
                        </span>
                    </button>
                </div>

                <!-- TAB 1: GROUPES CLÔTURÉS -->
                <div v-if="activeTab === 'closed-groups'" class="space-y-8">
                    
                    <div v-if="closedGroups.length === 0" class="py-20 text-center bg-white rounded-[3rem] border-2 border-dashed border-gray-200">
                        <ArchiveBoxIcon class="h-16 w-16 text-gray-300 mx-auto mb-4" />
                        <h3 class="text-xl font-black text-gray-900">Aucun groupe clôturé pour le moment</h3>
                        <p class="text-gray-500 mt-2 max-w-md mx-auto text-sm">
                            Dès qu'un groupe de formation termine son cursus et est clôturé, tous ses apprenants apparaîtront ici automatiquement avec leurs moyennes pour valider la délivrance des attestations.
                        </p>
                    </div>

                    <div v-for="group in closedGroups" :key="group.id" class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition">
                        <!-- Group Header -->
                        <div class="p-8 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                            <div class="space-y-2">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span class="px-3.5 py-1 bg-amber-500/20 border border-amber-400/30 text-amber-300 rounded-full text-[10px] font-black uppercase tracking-widest">
                                        Groupe Clôturé
                                    </span>
                                    <span class="px-3.5 py-1 bg-white/10 text-slate-300 rounded-full text-[10px] font-black uppercase tracking-widest">
                                        Année {{ group.annee_academique }}
                                    </span>
                                    <span v-if="group.default_start_date_fr && group.default_end_date_fr" class="px-3.5 py-1 bg-white/10 text-slate-300 rounded-full text-[10px] font-black uppercase tracking-widest flex items-center gap-1.5">
                                        <CalendarDaysIcon class="w-3.5 h-3.5 text-blue-400" />
                                        <span>Période : {{ group.default_start_date_fr }} au {{ group.default_end_date_fr }}</span>
                                    </span>
                                    <span class="text-xs text-slate-400 font-medium">
                                        Formateur : {{ group.formateur_name }}
                                    </span>
                                </div>
                                <h3 class="text-2xl font-black tracking-tight text-white flex items-center gap-3">
                                    <span>{{ group.nom_groupe }}</span>
                                    <span class="text-slate-400 font-light">|</span>
                                    <span class="text-blue-400 font-bold">{{ group.module_title }}</span>
                                </h3>
                                <p class="text-xs text-slate-300">
                                    {{ group.students_count }} apprenant(s) inscrit(s) &bull; {{ group.certified_count }} attestation(s) déjà délivrée(s)
                                </p>
                            </div>

                            <!-- Group Actions -->
                            <div class="flex items-center gap-3 flex-wrap">
                                <div class="px-4 py-2 bg-white/10 rounded-2xl text-xs font-bold text-slate-200">
                                    Progression : <span class="font-black text-white">{{ group.certified_count }}/{{ group.students_count }}</span>
                                </div>

                                <button
                                    v-if="group.pending_count > 0"
                                    @click="confirmGenerateForGroup(group)"
                                    :disabled="isSubmitting"
                                    class="px-5 py-3.5 bg-blue-600 hover:bg-blue-500 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-blue-900/40 transition flex items-center gap-2 disabled:opacity-50"
                                >
                                    <SparklesIcon class="h-4 w-4" />
                                    <span>Valider tout le groupe ({{ group.pending_count }})</span>
                                </button>
                                <button
                                    v-else
                                    @click="confirmGenerateForGroup(group)"
                                    :disabled="isSubmitting"
                                    class="px-4 py-3 bg-white/10 hover:bg-white/20 text-slate-200 rounded-2xl font-black text-xs uppercase tracking-widest transition flex items-center gap-2"
                                    title="Régénérer toutes les attestations du groupe"
                                >
                                    <ArrowPathIcon class="h-4 w-4" />
                                    <span>Régénérer le groupe</span>
                                </button>
                            </div>
                        </div>

                        <!-- Students in Closed Group Table -->
                        <div class="divide-y divide-gray-100">
                            <div 
                                v-for="student in group.students" 
                                :key="student.id"
                                class="p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 hover:bg-slate-50/60 transition"
                            >
                                <!-- Student Info -->
                                <div class="flex items-center gap-5 min-w-[280px]">
                                    <div class="w-14 h-14 rounded-2xl bg-blue-600 text-white font-black text-lg flex items-center justify-center overflow-hidden shrink-0 shadow-sm">
                                        <img v-if="student.profile_photo_url" :src="student.profile_photo_url" class="w-full h-full object-cover">
                                        <template v-else>{{ student.name.charAt(0) }}</template>
                                    </div>
                                    <div>
                                        <h4 class="font-black text-gray-900 text-base">{{ student.name }}</h4>
                                        <p class="text-xs text-gray-500 font-medium">{{ student.email || 'Pas d\'email' }}</p>
                                        <p v-if="student.telephone" class="text-[11px] text-gray-400">{{ student.telephone }}</p>
                                    </div>
                                </div>

                                <!-- Attendance and Score -->
                                <div class="flex items-center gap-8 flex-wrap">
                                    <!-- Attendance Rate -->
                                    <div class="text-center sm:text-left">
                                        <span class="text-[10px] font-black uppercase tracking-wider text-gray-400">Présence</span>
                                        <p class="text-sm font-black text-gray-800">{{ student.attendance_rate }}%</p>
                                    </div>

                                    <!-- Score / Moyenne -->
                                    <div class="text-center sm:text-left">
                                        <span class="text-[10px] font-black uppercase tracking-wider text-gray-400">Moyenne</span>
                                        <p 
                                            class="text-sm font-black"
                                            :class="[
                                                student.score === null ? 'text-gray-400 italic' : 
                                                student.score >= 10 ? 'text-emerald-600' : 'text-red-500'
                                            ]"
                                        >
                                            {{ student.score !== null ? `${student.score} / 20` : 'Non renseignée' }}
                                        </p>
                                    </div>

                                    <!-- Training Period -->
                                    <div class="text-center sm:text-left hidden lg:block">
                                        <span class="text-[10px] font-black uppercase tracking-wider text-gray-400">Période</span>
                                        <p class="text-xs font-bold text-gray-700">
                                            <span v-if="student.start_date_fr && student.end_date_fr">
                                                {{ student.start_date_fr }} au {{ student.end_date_fr }}
                                            </span>
                                            <span v-else class="text-gray-400 italic text-[11px]">
                                                Automatique
                                            </span>
                                        </p>
                                    </div>

                                    <!-- Proposed Certificate Type Badge -->
                                    <div>
                                        <span class="text-[10px] font-black uppercase tracking-wider text-gray-400 block mb-1">Attestation</span>
                                        <span 
                                            v-if="student.suggested_type === 'reussite'"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-tight bg-emerald-50 text-emerald-700 border border-emerald-200"
                                        >
                                            <CheckBadgeIcon class="w-4 h-4 text-emerald-600" />
                                            <span>Réussite</span>
                                        </span>
                                        <span 
                                            v-else
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-tight bg-amber-50 text-amber-700 border border-amber-200"
                                        >
                                            <DocumentTextIcon class="w-4 h-4 text-amber-600" />
                                            <span>Participation</span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-3 self-end md:self-center">
                                    <template v-if="student.has_certificate">
                                        <div class="text-right mr-2 hidden sm:block">
                                            <span 
                                                class="text-[10px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-md"
                                                :class="student.certificate.type === 'participation' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'"
                                            >
                                                {{ student.certificate.type === 'participation' ? 'Participation' : 'Réussite' }}
                                            </span>
                                            <p class="text-[10px] text-gray-400 font-medium mt-0.5">{{ student.certificate.issued_at }}</p>
                                        </div>

                                        <a 
                                            :href="route('certificates.download', student.certificate.id)"
                                            class="p-3 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-xl transition shadow-sm"
                                            title="Télécharger l'attestation PDF"
                                        >
                                            <ArrowDownTrayIcon class="h-4 w-4" />
                                        </a>

                                        <Link 
                                            :href="route('certificates.view', student.certificate.uuid)"
                                            target="_blank"
                                            class="p-3 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-xl transition shadow-sm"
                                            title="Consulter le diplôme digital"
                                        >
                                            <EyeIcon class="h-4 w-4" />
                                        </Link>

                                        <button 
                                            @click="openGenerateModal(student, { id: group.module_id, titre: group.module_title }, group, student.certificate.score ?? student.score, student.certificate.type, student.certificate.start_date ?? student.start_date, student.certificate.end_date ?? student.end_date)"
                                            class="p-3 bg-slate-50 text-slate-600 hover:bg-slate-100 rounded-xl transition shadow-sm"
                                            title="Régénérer / Modifier le type, la période ou la note"
                                        >
                                            <ArrowPathIcon class="h-4 w-4" />
                                        </button>

                                        <button 
                                            @click="deleteCertificate(student.certificate.id)"
                                            class="p-3 bg-red-50 text-red-600 hover:bg-red-100 rounded-xl transition shadow-sm"
                                            title="Supprimer l'attestation"
                                        >
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </template>

                                    <template v-else>
                                        <button 
                                            @click="openGenerateModal(student, { id: group.module_id, titre: group.module_title }, group, student.score, student.suggested_type, student.start_date, student.end_date)"
                                            :disabled="isSubmitting"
                                            class="flex items-center gap-2 px-4 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-black text-[11px] uppercase tracking-wider transition shadow-md shadow-blue-200 disabled:opacity-50"
                                        >
                                            <PrinterIcon class="h-4 w-4" />
                                            <span>Valider & Générer</span>
                                        </button>

                                        <button 
                                            @click="openGenerateModal(student, { id: group.module_id, titre: group.module_title }, group, student.score, student.suggested_type, student.start_date, student.end_date)"
                                            class="p-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition shadow-sm"
                                            title="Ajuster la période, la note ou le type manuellement avant génération"
                                        >
                                            <FunnelIcon class="h-4 w-4" />
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- TAB 2: VUE GLOBALE APPRENANTS -->
                <div v-if="activeTab === 'all'" class="space-y-6">
                    <!-- Filters -->
                    <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-gray-100 flex flex-col md:flex-row gap-4 items-center">
                        <div class="relative flex-1 w-full">
                            <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <input 
                                v-model="searchQuery"
                                type="text" 
                                placeholder="Rechercher un apprenant par nom ou email..."
                                class="w-full pl-12 pr-4 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-blue-500/20 transition-all font-medium text-sm"
                            >
                        </div>
                        
                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <FunnelIcon class="h-5 w-5 text-gray-400 hidden md:block" />
                            <select 
                                v-model="selectedModule"
                                class="flex-1 md:w-64 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-blue-500/20 transition-all font-bold text-xs uppercase tracking-widest"
                            >
                                <option :value="null">Tous les Modules</option>
                                <option v-for="module in modules" :key="module.id" :value="module.id">
                                    {{ module.titre }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Content Grid -->
                    <div class="grid grid-cols-1 gap-6">
                        <div v-for="student in filteredStudents" :key="student.id" class="group bg-white rounded-[2.5rem] border border-gray-100 overflow-hidden hover:shadow-xl hover:shadow-blue-900/5 transition-all duration-500">
                            <div class="p-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-8">
                                <div class="flex items-center gap-6">
                                    <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center overflow-hidden text-white font-black text-xl shadow-lg shadow-blue-200">
                                        <img v-if="student.profile_photo_url" :src="student.profile_photo_url" class="h-full w-full object-cover">
                                        <template v-else>{{ student.name.charAt(0) }}</template>
                                    </div>
                                    <div>
                                        <h4 class="text-xl font-black text-gray-900">{{ student.name }}</h4>
                                        <p class="text-sm text-gray-500 font-medium">{{ student.email }}</p>
                                    </div>
                                </div>

                                <div class="flex-1 w-full grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div 
                                        v-for="prog in (selectedModule ? student.progress.filter(p => p.module_id === selectedModule) : student.progress)" 
                                        :key="prog.module_id"
                                        class="p-5 rounded-3xl bg-gray-50/50 border border-gray-100 flex flex-col gap-3"
                                    >
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 line-clamp-1 flex-1">{{ prog.module_title }}</span>
                                            
                                            <span v-if="prog.is_group_closed" class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-tight bg-amber-100 text-amber-800">
                                                Groupe Clôturé
                                            </span>
                                            <span v-else-if="prog.completed" class="flex items-center gap-1 text-[10px] font-black text-emerald-600 uppercase tracking-tighter">
                                                <CheckBadgeIcon class="h-3 w-3" />
                                                Complété
                                            </span>
                                        </div>
                                        
                                        <div class="flex items-center justify-between group/row">
                                            <div class="flex flex-col">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-bold text-gray-900">{{ prog.progress_pct }}% Progression</span>
                                                    <span v-if="prog.score !== null" class="text-[10px] font-bold" :class="prog.score >= 10 ? 'text-emerald-600' : 'text-amber-600'">
                                                        ({{ prog.score }}/20)
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <div class="w-16 h-1 bg-gray-200 rounded-full overflow-hidden">
                                                        <div class="h-full bg-blue-500 transition-all duration-500" :style="{ width: prog.progress_pct + '%' }"></div>
                                                    </div>
                                                    <span class="text-[9px] font-black text-gray-400">{{ prog.completed_count }}/{{ prog.total_chapters }}</span>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <template v-if="prog.has_certificate">
                                                    <a 
                                                        :href="route('certificates.download', prog.certificate.id)"
                                                        class="p-3 bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-100 transition shadow-sm"
                                                        title="Télécharger l'attestation"
                                                    >
                                                        <ArrowDownTrayIcon class="h-4 w-4" />
                                                    </a>
                                                    <button 
                                                        @click="deleteCertificate(prog.certificate.id)"
                                                        class="p-3 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition shadow-sm"
                                                        title="Supprimer"
                                                    >
                                                        <TrashIcon class="h-4 w-4" />
                                                    </button>
                                                </template>
                                                <button 
                                                    v-else-if="prog.completed || prog.is_group_closed"
                                                    @click="openGenerateModal(student, { id: prog.module_id, titre: prog.module_title }, { id: prog.group_id, nom_groupe: prog.group_name }, prog.score, prog.suggested_type, prog.start_date, prog.end_date)"
                                                    class="flex items-center gap-2 px-4 py-3 bg-blue-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-500 transition shadow-lg shadow-blue-200"
                                                >
                                                    <PrinterIcon class="h-4 w-4" />
                                                    Générer
                                                </button>
                                                <span v-else class="text-[10px] font-black text-gray-400 uppercase tracking-widest">En cours</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="filteredStudents.length === 0" class="py-20 text-center bg-white rounded-[3rem] border-2 border-dashed border-gray-100">
                            <AcademicCapIcon class="h-16 w-16 text-gray-200 mx-auto mb-4" />
                            <h3 class="text-xl font-black text-gray-900">Aucun apprenant trouvé</h3>
                            <p class="text-gray-500 mt-2">Ajustez vos filtres ou effectuez une nouvelle recherche.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL: VALIDER / PERSONNALISER L'ATTESTATION INDIVIDUELLE -->
        <div v-if="showGenerateModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-[2.5rem] max-w-lg w-full p-8 shadow-2xl border border-gray-100 space-y-6 animate-in fade-in zoom-in duration-200">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <PrinterIcon class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-gray-900">Production d'Attestation</h3>
                            <p class="text-xs text-gray-500 font-medium">Validation officielle pour {{ modalStudent?.name }}</p>
                        </div>
                    </div>
                    <button @click="showGenerateModal = false" class="p-2 text-gray-400 hover:text-gray-600 rounded-xl">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- Module & Group preview -->
                    <div class="p-4 bg-slate-50 rounded-2xl space-y-1 text-xs">
                        <div class="flex justify-between">
                            <span class="text-gray-400 font-bold uppercase tracking-wider">Module :</span>
                            <span class="font-black text-gray-900">{{ modalModule?.titre }}</span>
                        </div>
                        <div v-if="modalGroup" class="flex justify-between">
                            <span class="text-gray-400 font-bold uppercase tracking-wider">Groupe :</span>
                            <span class="font-bold text-gray-700">{{ modalGroup.nom_groupe }}</span>
                        </div>
                    </div>

                    <!-- Période de formation (Début & Fin) avec saisie / modification -->
                    <div class="p-4 bg-blue-50/60 border border-blue-100 rounded-2xl space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-xs font-black uppercase tracking-wider text-blue-900">
                                <CalendarDaysIcon class="w-4 h-4 text-blue-600" />
                                <span>Période de la formation</span>
                            </div>
                            <button 
                                v-if="defaultAutoStartDate && (modalStartDate !== defaultAutoStartDate || modalEndDate !== defaultAutoEndDate)"
                                type="button" 
                                @click="resetModalDatesToAuto"
                                class="text-[11px] font-bold text-blue-600 hover:text-blue-800 underline transition"
                            >
                                Rétablir auto
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">
                                    Date de début
                                </label>
                                <input 
                                    v-model="modalStartDate"
                                    type="date"
                                    class="w-full px-3 py-2.5 bg-white border border-blue-200 rounded-xl font-bold text-xs text-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">
                                    Date de fin
                                </label>
                                <input 
                                    v-model="modalEndDate"
                                    type="date"
                                    class="w-full px-3 py-2.5 bg-white border border-blue-200 rounded-xl font-bold text-xs text-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                />
                            </div>
                        </div>

                        <div class="flex items-start gap-2 pt-1 text-[11px] text-blue-800/80 leading-snug">
                            <ShieldCheckIcon class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" />
                            <span>
                                Période déduite de l'émargement. Renseignez ou modifiez manuellement avant validation (sous approbation du Directeur).
                            </span>
                        </div>
                    </div>

                    <!-- Note / Moyenne input -->
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-gray-600 mb-2">
                            Moyenne de l'apprenant (/20)
                        </label>
                        <input 
                            v-model="modalScore"
                            type="number"
                            step="0.25"
                            min="0"
                            max="20"
                            placeholder="Ex : 14.50"
                            @input="modalType = (modalScore !== null && modalScore !== '' && Number(modalScore) >= 10) ? 'reussite' : 'participation'"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl font-bold text-sm focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                        />
                        <p class="text-[11px] text-gray-400 mt-1 font-medium">
                            Note calculée à partir des examens et exercices du module.
                        </p>
                    </div>

                    <!-- Certification Type Selection -->
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-gray-600 mb-2">
                            Type d'attestation à délivrer
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <button
                                type="button"
                                @click="modalType = 'reussite'"
                                :class="[
                                    'p-4 rounded-2xl border-2 text-left transition flex flex-col gap-1',
                                    modalType === 'reussite'
                                        ? 'border-emerald-500 bg-emerald-50/70 text-emerald-900'
                                        : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'
                                ]"
                            >
                                <span class="font-black text-xs uppercase tracking-wider flex items-center gap-1.5 text-emerald-700">
                                    <CheckBadgeIcon class="w-4 h-4" />
                                    Réussite
                                </span>
                                <span class="text-[11px] font-medium opacity-80">Moyenne requise acquise (≥ 10/20)</span>
                            </button>

                            <button
                                type="button"
                                @click="modalType = 'participation'"
                                :class="[
                                    'p-4 rounded-2xl border-2 text-left transition flex flex-col gap-1',
                                    modalType === 'participation'
                                        ? 'border-amber-500 bg-amber-50/70 text-amber-900'
                                        : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'
                                ]"
                            >
                                <span class="font-black text-xs uppercase tracking-wider flex items-center gap-1.5 text-amber-700">
                                    <DocumentTextIcon class="w-4 h-4" />
                                    Participation
                                </span>
                                <span class="text-[11px] font-medium opacity-80">N'ayant pas la moyenne (&lt; 10/20)</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button
                        type="button"
                        @click="showGenerateModal = false"
                        class="px-5 py-3 rounded-xl text-xs font-black text-gray-500 hover:bg-gray-100 uppercase tracking-wider transition"
                    >
                        Annuler
                    </button>
                    <button
                        type="button"
                        @click="submitGenerateModal"
                        :disabled="isSubmitting"
                        class="px-6 py-3.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-lg shadow-blue-200 transition flex items-center gap-2 disabled:opacity-50"
                    >
                        <SparklesIcon class="w-4 h-4" />
                        <span>Confirmer & Générer</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: BATCH GENERATION CONFIRMATION -->
        <div v-if="showBatchModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-[2.5rem] max-w-lg w-full p-8 shadow-2xl border border-gray-100 space-y-6 animate-in fade-in zoom-in duration-200">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <SparklesIcon class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-lg font-black text-gray-900">Validation Groupée des Attestations</h3>
                            <p class="text-xs text-gray-500 font-medium">Groupe « {{ batchTargetGroup?.nom_groupe }} »</p>
                        </div>
                    </div>
                    <button @click="showBatchModal = false" class="p-2 text-gray-400 hover:text-gray-600 rounded-xl">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-4 text-sm text-gray-600">
                    <p class="leading-relaxed font-medium">
                        Vous êtes sur le point de générer automatiquement les attestations pour les <strong class="text-gray-900">{{ batchTargetGroup?.students_count }} apprenants</strong> du groupe <strong class="text-gray-900">{{ batchTargetGroup?.nom_groupe }}</strong> (Module : {{ batchTargetGroup?.module_title }}).
                    </p>

                    <div class="p-4 bg-slate-50 rounded-2xl space-y-2 text-xs">
                        <div class="flex items-start gap-2 text-emerald-800">
                            <CheckBadgeIcon class="w-4 h-4 shrink-0 text-emerald-600 mt-0.5" />
                            <span><strong>Attestation de Réussite :</strong> attribuée à tout apprenant ayant obtenu la moyenne requise (≥ 10/20).</span>
                        </div>
                        <div class="flex items-start gap-2 text-amber-800">
                            <DocumentTextIcon class="w-4 h-4 shrink-0 text-amber-600 mt-0.5" />
                            <span><strong>Attestation de Participation :</strong> attribuée à tout étudiant n'ayant pas atteint la moyenne requise (&lt; 10/20).</span>
                        </div>
                    </div>

                    <!-- Période de formation pour le groupe -->
                    <div class="p-4 bg-blue-50/60 border border-blue-100 rounded-2xl space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-xs font-black uppercase tracking-wider text-blue-900">
                                <CalendarDaysIcon class="w-4 h-4 text-blue-600" />
                                <span>Période de la formation du groupe</span>
                            </div>
                            <button 
                                v-if="defaultBatchAutoStartDate && (batchStartDate !== defaultBatchAutoStartDate || batchEndDate !== defaultBatchAutoEndDate)"
                                type="button" 
                                @click="resetBatchDatesToAuto"
                                class="text-[11px] font-bold text-blue-600 hover:text-blue-800 underline transition"
                            >
                                Rétablir auto
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">
                                    Date de début
                                </label>
                                <input 
                                    v-model="batchStartDate"
                                    type="date"
                                    class="w-full px-3 py-2.5 bg-white border border-blue-200 rounded-xl font-bold text-xs text-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">
                                    Date de fin
                                </label>
                                <input 
                                    v-model="batchEndDate"
                                    type="date"
                                    class="w-full px-3 py-2.5 bg-white border border-blue-200 rounded-xl font-bold text-xs text-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                />
                            </div>
                        </div>

                        <div class="flex items-start gap-2 pt-1 text-[11px] text-blue-800/80 leading-snug">
                            <ShieldCheckIcon class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" />
                            <span>
                                Ces dates figureront sur l'ensemble des attestations produites pour ce groupe. Pré-remplies selon les présences (modifiable sous approbation du Directeur).
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button
                        type="button"
                        @click="showBatchModal = false"
                        class="px-5 py-3 rounded-xl text-xs font-black text-gray-500 hover:bg-gray-100 uppercase tracking-wider transition"
                    >
                        Annuler
                    </button>
                    <button
                        type="button"
                        @click="submitBatchGeneration"
                        :disabled="isSubmitting"
                        class="px-6 py-3.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-lg shadow-blue-200 transition flex items-center gap-2 disabled:opacity-50"
                    >
                        <SparklesIcon class="w-4 h-4" />
                        <span>Confirmer la Production Groupée</span>
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
