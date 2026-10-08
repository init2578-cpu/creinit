<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, computed, watch } from 'vue'
import { router, Link, useForm, usePage } from '@inertiajs/vue3'
import { 
    CalendarIcon, 
    UserGroupIcon, 
    ClockIcon,
    MapPinIcon,
    ChevronRightIcon,
    CheckCircleIcon,
    XCircleIcon,
    MegaphoneIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'
import { formatTime } from '@/utils/format'

const props = defineProps({
    schedules: Array,
    selectedDate: String,
    can_report_advance: {
        type: Boolean,
        default: false,
    },
    active_groups: {
        type: Array,
        default: () => [],
    },
})

const page = usePage()
const date = ref(props.selectedDate)

const canReportAdvance = computed(() => {
    const roles = page.props.auth.user?.roles || []
    return props.can_report_advance || roles.includes('Directeur') || roles.includes('Secrétaire')
})

watch(date, (newDate) => {
    router.get(route('attendance.index'), { date: newDate }, {
        preserveState: true,
        preserveScroll: true,
    })
})

function formatDate(dateString) {
    if (!dateString) return ''
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('fr-FR', options);
}

// Modal and advance reporting state
const isAdvanceModalOpen = ref(false)
const motifPresets = [
    'Raison médicale / Maladie',
    'Empêchement familial / Urgence',
    'Démarche administrative / Concours',
    'Transport / Déplacement',
    'Autre motif personnel'
]

const advanceForm = useForm({
    group_id: '',
    user_id: '',
    schedule_id: '',
    date: props.selectedDate,
    motif: 'Raison médicale / Maladie',
    action: 'report',
})

const selectedGroup = computed(() => {
    if (!props.active_groups) return null
    return props.active_groups.find(g => g.id === advanceForm.group_id) || null
})

const availableStudents = computed(() => {
    return selectedGroup.value?.students || []
})

const availableSchedules = computed(() => {
    return selectedGroup.value?.schedules || []
})

function openAdvanceModal(schedule = null) {
    if (schedule) {
        advanceForm.group_id = schedule.group?.id || schedule.group_id || ''
        advanceForm.schedule_id = schedule.id
        advanceForm.date = date.value
        advanceForm.user_id = ''
        advanceForm.motif = 'Raison médicale / Maladie'
    } else {
        advanceForm.group_id = props.active_groups?.[0]?.id || ''
        advanceForm.schedule_id = ''
        advanceForm.date = date.value
        advanceForm.user_id = ''
        advanceForm.motif = 'Raison médicale / Maladie'
    }
    advanceForm.action = 'report'
    isAdvanceModalOpen.value = true
}

function closeAdvanceModal() {
    isAdvanceModalOpen.value = false
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

</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-8 px-4">
            <header class="mb-10 flex flex-col sm:flex-row sm:items-end justify-between gap-6">
                <div>
                    <h1 class="text-4xl font-black text-gray-900 tracking-tight">Listes de Présence</h1>
                    <p class="text-gray-500 mt-2 font-medium">Suivi des présences par session de formation.</p>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <button 
                        v-if="canReportAdvance" 
                        @click="openAdvanceModal()"
                        type="button"
                        class="px-5 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-2xl text-xs font-black uppercase tracking-wider shadow-lg shadow-purple-500/20 transition flex items-center gap-2"
                    >
                        <MegaphoneIcon class="h-4 w-4" />
                        <span>Signaler une absence</span>
                    </button>

                    <div class="flex items-center gap-4 bg-white p-2 rounded-2xl border border-gray-100 shadow-sm">
                        <CalendarIcon class="h-6 w-6 text-blue-600 ml-2" />
                        <input 
                            v-model="date" 
                            type="date" 
                            title="jj/mm/aaaa"
                            class="border-0 focus:ring-0 font-black text-gray-900 cursor-pointer"
                        >
                    </div>
                </div>
            </header>

            <div class="mb-8">
                <h2 class="text-lg font-black text-gray-900 uppercase tracking-widest mb-4 flex items-center gap-2">
                    Sessions du {{ formatDate(date) }}
                </h2>

                <div v-if="schedules.length === 0" class="bg-white rounded-[2rem] p-12 text-center border border-gray-100 flex flex-col items-center">
                    <div class="w-20 h-20 bg-gray-50 text-gray-300 rounded-3xl flex items-center justify-center mb-6">
                        <CalendarIcon class="h-10 w-10" />
                    </div>
                    <h3 class="text-xl font-black text-gray-900">Aucune session prévue</h3>
                    <p class="text-gray-500 mt-2 font-medium max-w-xs mx-auto">Il n'y a pas de cours programmés pour cette date dans l'emploi du temps.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="schedule in schedules" :key="schedule.id" 
                        class="bg-white rounded-[2.5rem] border border-gray-100 p-8 hover:shadow-2xl hover:shadow-gray-200/50 transition duration-500 flex flex-col relative overflow-hidden group">
                        
                        <!-- Status Badge & Action -->
                        <div class="absolute top-6 right-6 flex items-center gap-1.5">
                            <span v-if="schedule.group?.status === 'closed'" class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200">
                                Clôturé
                            </span>

                            <span v-if="schedule.advance_reported_count > 0" class="flex items-center gap-1 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider bg-purple-50 text-purple-700 border border-purple-200" title="Absences signalées à l'avance pour cette session">
                                <MegaphoneIcon class="h-3 w-3" />
                                {{ schedule.advance_reported_count }} signalée(s)
                            </span>

                            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest"
                                :class="schedule.attendance_taken ? 'bg-green-50 text-green-600' : 'bg-amber-50 text-amber-600'">
                                <CheckCircleIcon v-if="schedule.attendance_taken" class="h-3 w-3" />
                                <ClockIcon v-else class="h-3 w-3" />
                                {{ schedule.attendance_taken ? 'Saisie effectuée' : 'En attente' }}
                            </div>

                            <button 
                                v-if="canReportAdvance"
                                @click="openAdvanceModal(schedule)"
                                type="button"
                                class="p-1.5 rounded-xl text-purple-600 bg-purple-50 hover:bg-purple-100 border border-purple-200 transition"
                                title="Signaler une absence pour cette session"
                            >
                                <MegaphoneIcon class="h-3.5 w-3.5" />
                            </button>
                        </div>

                        <div class="mb-6 flex items-start gap-4">
                            <div class="p-4 bg-blue-50 text-blue-600 rounded-2xl group-hover:bg-blue-600 group-hover:text-white transition duration-300">
                                <UserGroupIcon class="h-8 w-8" />
                            </div>
                            <div>
                                <span class="text-[10px] font-black text-blue-500 uppercase tracking-widest">{{ schedule.group.nom_groupe }}</span>
                                <h3 class="text-xl font-black text-gray-900 leading-tight mt-1">{{ schedule.group.module.titre }}</h3>
                            </div>
                        </div>

                        <div class="space-y-3 mb-8">
                            <div class="flex items-center gap-3 text-gray-500">
                                <ClockIcon class="h-5 w-5 text-gray-400" />
                                <span class="text-sm font-bold">{{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-gray-500">
                                <MapPinIcon class="h-5 w-5 text-gray-400" />
                                <span class="text-sm font-bold">{{ schedule.room.nom }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-gray-500">
                                <div class="w-5 h-5 bg-gray-100 rounded-full flex items-center justify-center text-[10px] font-black text-gray-400 text-center leading-[20px]">F</div>
                                <span class="text-sm font-bold">{{ schedule.formateur.name }}</span>
                            </div>
                        </div>

                        <div class="mt-auto grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <Link 
                                :href="route('attendance.take', { schedule: schedule.id, date: selectedDate })"
                                class="w-full py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all flex items-center justify-center gap-1.5 border text-center"
                                :class="schedule.group?.status === 'closed' 
                                    ? 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'
                                    : (schedule.attendance_taken ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-blue-600 text-white shadow-lg shadow-blue-100 hover:bg-blue-700')"
                            >
                                {{ schedule.group?.status === 'closed' ? 'Feuille émargée' : (schedule.attendance_taken ? 'Voir / Modifier' : 'Faire l\'appel') }}
                            </Link>

                            <Link 
                                :href="route('groups.attendances.history', schedule.group.id)"
                                class="w-full py-3.5 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all flex items-center justify-center gap-1.5 bg-slate-900 text-white hover:bg-black text-center"
                                title="Bilan complet d'assiduité du groupe"
                            >
                                Bilan groupe
                                <ChevronRightIcon class="h-3.5 w-3.5" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal: Signaler une absence à l'avance (Secrétaire et Directeur) -->
            <div v-if="isAdvanceModalOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-[2.5rem] max-w-lg w-full p-8 shadow-2xl border border-gray-100 relative">
                    <div class="flex items-center justify-between pb-6 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-purple-50 text-purple-600 rounded-2xl border border-purple-100">
                                <MegaphoneIcon class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-gray-900">Signaler une absence avant cours</h3>
                                <p class="text-xs font-bold text-gray-400 mt-0.5">Réservé au Secrétaire et au Directeur</p>
                            </div>
                        </div>
                        <button @click="closeAdvanceModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitAdvanceReport" class="mt-6 space-y-4">
                        <!-- Groupe -->
                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-600 mb-2">Groupe de formation</label>
                            <select 
                                v-model="advanceForm.group_id" 
                                required
                                class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-4 py-3 text-sm font-bold text-gray-800 focus:bg-white focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition"
                            >
                                <option value="" disabled>Sélectionner un groupe</option>
                                <option v-for="grp in active_groups" :key="grp.id" :value="grp.id">
                                    {{ grp.nom_groupe }}
                                </option>
                            </select>
                        </div>

                        <!-- Apprenant -->
                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-600 mb-2">Apprenant concerné</label>
                            <select 
                                v-model="advanceForm.user_id" 
                                required
                                :disabled="!advanceForm.group_id"
                                class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-4 py-3 text-sm font-bold text-gray-800 focus:bg-white focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition disabled:opacity-50"
                            >
                                <option value="" disabled>{{ advanceForm.group_id ? 'Sélectionner un apprenant' : 'Choisissez d\'abord un groupe' }}</option>
                                <option v-for="st in availableStudents" :key="st.id" :value="st.id">
                                    {{ st.name }} ({{ st.email }})
                                </option>
                            </select>
                        </div>

                        <!-- Date et Session -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-gray-600 mb-2">Date du cours</label>
                                <input 
                                    v-model="advanceForm.date" 
                                    type="date"
                                    required
                                    class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-4 py-3 text-sm font-bold text-gray-800 focus:bg-white focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-gray-600 mb-2">Créneau / Cours</label>
                                <select 
                                    v-model="advanceForm.schedule_id"
                                    class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-4 py-3 text-xs font-bold text-gray-800 focus:bg-white focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition"
                                >
                                    <option value="">Tous les cours de la journée</option>
                                    <option v-for="sc in availableSchedules" :key="sc.id" :value="sc.id">
                                        {{ formatTime(sc.start_time) }} - {{ formatTime(sc.end_time) }} ({{ sc.room?.nom || 'Salle' }})
                                    </option>
                                </select>
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

                        <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-2">
                            <button 
                                type="button" 
                                @click="closeAdvanceModal"
                                class="px-5 py-3 rounded-2xl text-xs font-bold text-gray-500 hover:bg-gray-100 transition"
                            >
                                Annuler
                            </button>
                            <button 
                                type="submit"
                                :disabled="advanceForm.processing"
                                class="px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-2xl text-xs font-black uppercase tracking-wider shadow-lg shadow-purple-500/20 transition disabled:opacity-50"
                            >
                                {{ advanceForm.processing ? 'Enregistrement...' : 'Enregistrer' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
