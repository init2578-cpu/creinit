<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, computed } from 'vue'
import { useForm, Link, usePage } from '@inertiajs/vue3'
import { 
    ChevronLeftIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    CalendarIcon,
    InformationCircleIcon,
    ExclamationTriangleIcon,
    MegaphoneIcon,
    PencilSquareIcon,
    TrashIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'
import { formatTime } from '@/utils/format'

const props = defineProps({
    schedule: Object,
    date: String,
    students: Array,
    settings: Object,
    readonly: { type: Boolean, default: false },
    can_report_advance: { type: Boolean, default: false },
})

const page = usePage()

const canReportAdvance = computed(() => {
    const roles = page.props.auth.user?.roles || []
    return props.can_report_advance || roles.includes('Directeur') || roles.includes('Secrétaire')
})

const isTrainer = computed(() => page.props.auth.user?.is_trainer ?? false)

const form = useForm({
    schedule_id: props.schedule.id,
    date: props.date,
    latitude: null,
    longitude: null,
    students: props.students.map(s => ({
        id: s.id,
        name: s.name,
        status: s.status || 'present'
    }))
})

// Advance reported absence modal state
const isAdvanceModalOpen = ref(false)
const selectedTargetStudent = ref(null)
const motifPresets = [
    'Raison médicale / Maladie',
    'Empêchement familial / Urgence',
    'Démarche administrative / Concours',
    'Transport / Déplacement',
    'Autre motif personnel'
]

const advanceForm = useForm({
    schedule_id: props.schedule.id,
    group_id: props.schedule.group.id,
    user_id: '',
    date: props.date,
    motif: 'Raison médicale / Maladie',
    action: 'report'
})

const availableStudents = computed(() => {
    return props.students.filter(s => !s.is_trainer)
})

function openAdvanceModal(student = null) {
    if (student) {
        selectedTargetStudent.value = student
        advanceForm.user_id = student.id
        advanceForm.motif = student.motif || 'Raison médicale / Maladie'
    } else {
        selectedTargetStudent.value = availableStudents.value[0] || null
        advanceForm.user_id = availableStudents.value[0]?.id || ''
        advanceForm.motif = 'Raison médicale / Maladie'
    }
    advanceForm.action = 'report'
    isAdvanceModalOpen.value = true
}

function closeAdvanceModal() {
    isAdvanceModalOpen.value = false
    selectedTargetStudent.value = null
}

function submitAdvanceReport() {
    advanceForm.action = 'report'
    advanceForm.post(route('attendance.report-absence'), {
        preserveScroll: true,
        onSuccess: () => {
            closeAdvanceModal()
        }
    })
}

function removeAdvanceReport(student) {
    if (!confirm(`Confirmez-vous le retrait de l'absence signalée pour ${student.name} ?`)) {
        return
    }
    const cancelForm = useForm({
        schedule_id: props.schedule.id,
        group_id: props.schedule.group.id,
        user_id: student.id,
        date: props.date,
        action: 'cancel'
    })
    cancelForm.post(route('attendance.report-absence'), {
        preserveScroll: true,
        onSuccess: () => {
            closeAdvanceModal()
        }
    })
}

// GPS check is done server-side via EnsureWithinPremises middleware
const locationError = ref('')
const isCheckingLocation = ref(false)

const checkLocation = () => {
    return new Promise((resolve) => {
        if (!navigator.geolocation) {
            locationError.value = "Géolocalisation non supportée."
            resolve(false); return
        }
        isCheckingLocation.value = true
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                form.latitude = pos.coords.latitude
                form.longitude = pos.coords.longitude
                isCheckingLocation.value = false
                locationError.value = ''
                resolve(true)
            },
            () => {
                isCheckingLocation.value = false
                locationError.value = "Accès à la position refusé. La géolocalisation est obligatoire pour émarger."
                resolve(false)
            },
            { timeout: 10000, enableHighAccuracy: true }
        )
    })
}

async function handleSave() {
    if (props.schedule.group.gps_check_required !== 0 && props.schedule.group.gps_check_required !== false && props.schedule.group.gps_check_required) {
        const locOk = await checkLocation()
        if (!locOk) return
    } else {
        form.latitude = 0
        form.longitude = 0
    }

    form.post(route('attendance.store'), {
        preserveScroll: true
    })
}

function setStatusAll(status) {
    form.students.forEach(s => s.status = status)
}

function formatDate(dateString) {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('fr-FR', options);
}

const statusConfig = {
    present: { label: 'Présent', color: 'text-green-600', bg: 'bg-green-50', border: 'border-green-100', icon: CheckCircleIcon },
    absent_non_justifie: { label: 'Absent', color: 'text-red-600', bg: 'bg-red-50', border: 'border-red-100', icon: XCircleIcon },
    late: { label: 'Retard', color: 'text-amber-600', bg: 'bg-amber-50', border: 'border-amber-100', icon: ClockIcon },
    justifie: { label: 'Justifié', color: 'text-blue-600', bg: 'bg-blue-50', border: 'border-blue-100', icon: InformationCircleIcon },
}

const filteredStatusConfig = computed(() => {
    if (isTrainer.value) {
        return {
            present: statusConfig.present,
            absent_non_justifie: statusConfig.absent_non_justifie,
        }
    }
    return statusConfig
})

</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-5xl mx-auto py-8 px-4">
            <!-- Back Navigation -->
            <Link :href="route('attendance.history', { schedule: schedule.id })" class="inline-flex items-center gap-2 text-gray-500 hover:text-blue-600 font-bold text-sm mb-8 transition group">
                <div class="p-2 bg-white rounded-xl shadow-sm border border-gray-100 group-hover:bg-blue-50 group-hover:border-blue-100 transition">
                    <ChevronLeftIcon class="h-4 w-4" />
                </div>
                Retour à l'historique
            </Link>

            <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="p-10 border-b border-gray-50 flex flex-col md:flex-row md:items-center justify-between gap-6 bg-gradient-to-br from-white to-gray-50/50">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[10px] font-black uppercase tracking-widest border border-blue-100">
                                {{ schedule.group.nom_groupe }}
                            </span>
                            <span class="px-3 py-1 bg-gray-50 text-gray-500 rounded-full text-[10px] font-black uppercase tracking-widest border border-gray-100">
                                {{ schedule.room.nom }}
                            </span>
                        </div>
                        <h1 class="text-3xl font-black text-gray-900 tracking-tight">{{ schedule.group.module.titre }}</h1>
                        <p class="text-gray-500 mt-1 font-bold flex items-center gap-2">
                            <CalendarIcon class="h-4 w-4 text-blue-500" />
                            Session du {{ formatDate(date) }}
                            <span class="mx-2 text-gray-300">•</span>
                            <ClockIcon class="h-4 w-4 text-blue-500" />
                            {{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button 
                            v-if="canReportAdvance" 
                            @click="openAdvanceModal()"
                            type="button"
                            class="px-5 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-2xl text-xs font-black uppercase tracking-wider shadow-lg shadow-purple-500/20 transition flex items-center gap-2"
                        >
                            <MegaphoneIcon class="h-4 w-4" />
                            <span>Signaler une absence</span>
                        </button>

                        <div v-if="!readonly" class="flex gap-2">
                            <button @click="setStatusAll('present')" class="px-4 py-2 bg-green-50 text-green-700 rounded-xl text-xs font-black uppercase tracking-widest border border-green-100 hover:bg-green-100 transition">
                                Tous Présents
                            </button>
                            <button @click="setStatusAll('absent_non_justifie')" class="px-4 py-2 bg-red-50 text-red-700 rounded-xl text-xs font-black uppercase tracking-widest border border-red-100 hover:bg-red-100 transition">
                                Tous Absents
                            </button>
                        </div>
                        <div v-else class="flex items-center gap-2 px-4 py-2 bg-amber-50 text-amber-700 rounded-xl text-xs font-black uppercase tracking-widest border border-amber-100">
                            <InformationCircleIcon class="h-4 w-4" />
                            Consultation uniquement
                        </div>
                    </div>
                </div>

                <div class="p-0 overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100">
                                <th class="px-10 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Apprenant</th>
                                <th class="px-10 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Status de présence</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="student in form.students" :key="student.id" 
                                class="group transition"
                                :class="student.is_trainer ? 'bg-indigo-50/50 hover:bg-indigo-50' : 'hover:bg-gray-50/30'"
                            >
                                <td class="px-10 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center font-black text-sm"
                                            :class="student.is_trainer ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-400'"
                                        >
                                            {{ student.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <div class="font-black text-gray-900 leading-none">{{ student.name }}</div>
                                                <span v-if="student.is_trainer" class="px-2 py-0.5 bg-indigo-600 text-white text-[8px] font-black rounded uppercase tracking-widest">Formateur</span>
                                            </div>
                                            <div class="text-[10px] font-bold text-gray-400 mt-1 uppercase tracking-tighter">{{ props.students.find(s => s.id === student.id).email }}</div>

                                            <!-- Advance Reported Absence Badge -->
                                            <div v-if="props.students.find(s => s.id === student.id)?.is_advance_reported" class="mt-2 flex flex-wrap items-center gap-2">
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-[10px] font-black bg-purple-50 text-purple-700 border border-purple-200">
                                                    <MegaphoneIcon class="h-3.5 w-3.5 text-purple-600" />
                                                    Absence signalée avant le cours
                                                    <span v-if="props.students.find(s => s.id === student.id)?.motif" class="font-bold text-purple-900 border-l border-purple-200 pl-1.5">
                                                        {{ props.students.find(s => s.id === student.id)?.motif }}
                                                    </span>
                                                </span>
                                                <span v-if="props.students.find(s => s.id === student.id)?.reported_by_name" class="text-[9px] font-bold text-gray-400">
                                                    Par {{ props.students.find(s => s.id === student.id)?.reported_by_name }} ({{ props.students.find(s => s.id === student.id)?.reported_at }})
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-10 py-6">
                                    <div class="flex flex-col sm:flex-row justify-center items-center gap-3">
                                        <!-- Locked view for trainers when student has an advance reported absence -->
                                        <div v-if="props.students.find(s => s.id === student.id)?.is_advance_reported && isTrainer" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-purple-50 text-purple-700 border border-purple-200 shadow-sm">
                                            <MegaphoneIcon class="h-3.5 w-3.5" />
                                            Absence signalée (Administration)
                                        </div>

                                        <!-- Regular attendance buttons -->
                                        <div v-else class="flex justify-center items-center gap-2 p-1.5 bg-gray-50 rounded-2xl w-fit mx-auto border border-gray-100 shadow-inner">
                                            <template v-if="!readonly">
                                                <button 
                                                    v-for="(config, key) in filteredStatusConfig" 
                                                    :key="key"
                                                    type="button"
                                                    @click="student.status = key"
                                                    class="px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2 whitespace-nowrap border border-transparent shadow-none"
                                                    :class="student.status === key 
                                                        ? `${filteredStatusConfig[student.status]?.bg} ${filteredStatusConfig[student.status]?.color} ${filteredStatusConfig[student.status]?.border} shadow-sm scale-105 ring-4 ring-white` 
                                                        : 'text-gray-400 hover:bg-white hover:text-gray-600'"
                                                >
                                                    <component :is="config.icon" class="h-3.5 w-3.5" />
                                                    {{ config.label }}
                                                </button>
                                            </template>
                                            <template v-else>
                                                <span 
                                                    class="px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2"
                                                    :class="statusConfig[student.status] ? `${statusConfig[student.status].bg} ${statusConfig[student.status].color} ${statusConfig[student.status].border}` : 'bg-gray-100 text-gray-400'"
                                                >
                                                    <component :is="statusConfig[student.status]?.icon || InformationCircleIcon" class="h-3.5 w-3.5" />
                                                    {{ statusConfig[student.status]?.label || student.status }}
                                                </span>
                                            </template>
                                        </div>

                                        <!-- Staff Advance Actions (Directeur/Secrétaire) -->
                                        <div v-if="canReportAdvance && !student.is_trainer" class="flex items-center gap-2">
                                            <button 
                                                v-if="props.students.find(s => s.id === student.id)?.is_advance_reported"
                                                type="button" 
                                                @click="openAdvanceModal(props.students.find(s => s.id === student.id))"
                                                class="p-2 text-purple-600 hover:text-purple-800 hover:bg-purple-50 rounded-xl border border-purple-200 transition"
                                                title="Modifier ou retirer l'absence signalée"
                                            >
                                                <PencilSquareIcon class="h-4 w-4" />
                                            </button>
                                            <button 
                                                v-else
                                                type="button" 
                                                @click="openAdvanceModal(props.students.find(s => s.id === student.id))"
                                                class="px-3 py-1.5 text-[10px] font-black text-purple-600 hover:text-purple-800 hover:bg-purple-50 rounded-xl border border-dashed border-purple-300 transition flex items-center gap-1"
                                                title="Signaler l'absence avant le cours"
                                            >
                                                <MegaphoneIcon class="h-3.5 w-3.5" />
                                                <span>Signaler</span>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-10 bg-gray-50/50 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-6">
                    <template v-if="!readonly">
                        <p class="text-xs font-bold text-gray-400 italic text-center sm:text-left">
                            <InformationCircleIcon class="h-4 w-4 inline mr-1" />
                            L'enregistrement écrasera les données précédentes pour cette session.
                        </p>
                        <div v-if="locationError || form.hasErrors" class="w-full sm:w-auto p-4 bg-red-50 border border-red-100 rounded-[1.5rem] mb-4 sm:mb-0">
                            <div v-if="locationError" class="flex items-center gap-2 text-red-600 text-[10px] font-black uppercase tracking-widest">
                                <ExclamationTriangleIcon class="h-4 w-4" />
                                {{ locationError }}
                            </div>
                            <div v-for="(error, key) in form.errors" :key="key" class="flex items-center gap-2 text-red-600 text-[10px] font-black uppercase tracking-widest">
                                <ExclamationTriangleIcon class="h-4 w-4" />
                                {{ error }}
                            </div>
                        </div>
                        <button 
                            @click="handleSave" 
                            :disabled="form.processing || isCheckingLocation"
                            class="w-full sm:w-auto px-10 py-5 bg-blue-600 text-white rounded-[1.5rem] font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition shadow-xl shadow-blue-100 disabled:opacity-50 flex items-center justify-center gap-3"
                        >
                            <CheckCircleIcon v-if="!form.processing && !isCheckingLocation" class="h-5 w-5" />
                            {{ form.processing ? 'Enregistrement...' : (isCheckingLocation ? 'Vérification position...' : 'Valider la liste de présence') }}
                        </button>
                    </template>
                    <template v-else>
                        <p class="text-xs font-bold text-gray-400 italic text-center sm:text-left">
                            <InformationCircleIcon class="h-4 w-4 inline mr-1" />
                            Cette feuille d'émargement a déjà été validée et est en lecture seule.
                        </p>
                    </template>
                </div>
            </div>

            <!-- Modal: Mentionner l'absence signalée avant le cours (Directeur et Secrétaire uniquement) -->
            <div v-if="isAdvanceModalOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-[2.5rem] max-w-lg w-full p-8 shadow-2xl border border-gray-100 relative">
                    <div class="flex items-center justify-between pb-6 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-purple-50 text-purple-600 rounded-2xl border border-purple-100">
                                <MegaphoneIcon class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-gray-900">Absence signalée avant cours</h3>
                                <p class="text-xs font-bold text-gray-400 mt-0.5">Réservé au Secrétaire et au Directeur</p>
                            </div>
                        </div>
                        <button @click="closeAdvanceModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitAdvanceReport" class="mt-6 space-y-5">
                        <!-- Apprenant -->
                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-600 mb-2">Apprenant concerné</label>
                            <select 
                                v-model="advanceForm.user_id" 
                                required
                                class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-4 py-3 text-sm font-bold text-gray-800 focus:bg-white focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition"
                            >
                                <option value="" disabled>Sélectionner un apprenant</option>
                                <option v-for="st in availableStudents" :key="st.id" :value="st.id">
                                    {{ st.name }} ({{ st.email }})
                                </option>
                            </select>
                        </div>

                        <!-- Date et Session -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-gray-600 mb-2">Date du cours</label>
                                <input 
                                    :value="formatDate(date)" 
                                    disabled 
                                    class="w-full bg-gray-100 border border-gray-200 rounded-2xl px-4 py-3 text-xs font-bold text-gray-500 cursor-not-allowed"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-gray-600 mb-2">Créneau horaire</label>
                                <input 
                                    :value="`${formatTime(schedule.start_time)} - ${formatTime(schedule.end_time)}`" 
                                    disabled 
                                    class="w-full bg-gray-100 border border-gray-200 rounded-2xl px-4 py-3 text-xs font-bold text-gray-500 cursor-not-allowed"
                                />
                            </div>
                        </div>

                        <!-- Motif présélectionné -->
                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-600 mb-2">Motif signalé</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-3">
                                <button 
                                    v-for="preset in motifPresets" 
                                    :key="preset"
                                    type="button"
                                    @click="advanceForm.motif = preset"
                                    class="text-left px-3 py-2 rounded-xl text-xs font-bold border transition text-gray-600 hover:border-purple-300"
                                    :class="advanceForm.motif === preset ? 'bg-purple-50 border-purple-300 text-purple-700' : 'bg-gray-50 border-gray-200'"
                                >
                                    {{ preset }}
                                </button>
                            </div>
                            <input 
                                v-model="advanceForm.motif" 
                                type="text"
                                placeholder="Précisez le motif ou commentaire libre..."
                                required
                                class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-4 py-3 text-sm font-bold text-gray-800 focus:bg-white focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition"
                            />
                        </div>

                        <div class="pt-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <button 
                                v-if="props.students.find(s => s.id === advanceForm.user_id)?.is_advance_reported"
                                type="button"
                                @click="removeAdvanceReport(props.students.find(s => s.id === advanceForm.user_id))"
                                class="w-full sm:w-auto px-5 py-3 rounded-2xl text-xs font-black text-rose-600 hover:bg-rose-50 border border-rose-200 transition uppercase tracking-wider flex items-center justify-center gap-1.5"
                            >
                                <TrashIcon class="h-4 w-4" />
                                Retirer l'absence
                            </button>
                            <div v-else></div>

                            <div class="w-full sm:w-auto flex items-center gap-2">
                                <button 
                                    type="button" 
                                    @click="closeAdvanceModal"
                                    class="w-full sm:w-auto px-5 py-3 rounded-2xl text-xs font-bold text-gray-500 hover:bg-gray-100 transition"
                                >
                                    Annuler
                                </button>
                                <button 
                                    type="submit"
                                    :disabled="advanceForm.processing"
                                    class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-2xl text-xs font-black uppercase tracking-wider shadow-lg shadow-purple-500/20 transition disabled:opacity-50"
                                >
                                    {{ advanceForm.processing ? 'Enregistrement...' : 'Enregistrer' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
