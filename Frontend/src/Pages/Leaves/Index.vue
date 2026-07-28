<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import DateInput from '@/Components/DateInput.vue'
import { formatDate } from '@/utils/format'
import { 
    CalendarDaysIcon, 
    PlusIcon, 
    DocumentTextIcon,
    TrashIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    XMarkIcon,
    PencilSquareIcon,
    DocumentCheckIcon,
    UserGroupIcon,
    MinusCircleIcon,
    ExclamationTriangleIcon,
    UserIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    leaves: {
        type: Array,
        default: () => []
    },
    deductions: {
        type: Array,
        default: () => []
    },
    users: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({})
    },
    my_stats: {
        type: Object,
        default: () => ({})
    }
})

const page = usePage()
const isDirecteur = computed(() => page.props.auth.user?.roles?.includes('Directeur'))

const activeTab = ref('leaves') // 'leaves' or 'deductions'
const showAddModal = ref(false)
const showReviewModal = ref(false)
const showDeductionModal = ref(false)

const selectedLeave = ref(null)
const editingLeave = ref(null)

const form = useForm({
    type: 'Annuel',
    date_debut: '',
    date_fin: '',
    motif: '',
    document: null
})

const reviewForm = useForm({
    status: '',
    admin_commentaire: ''
})

const deductionForm = useForm({
    user_id: '',
    reason_type: 'absence',
    unit: 'heures',
    amount: '',
    date_incident: '',
    motif: ''
})

const numberOfDays = computed(() => {
    if (!form.date_debut || !form.date_fin) return null;
    const start = new Date(form.date_debut);
    const end = new Date(form.date_fin);
    if (isNaN(start) || isNaN(end)) return null;
    
    if (end < start) return 0;
    
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; 
    return diffDays;
})

const calculatedDaysDeducted = computed(() => {
    if (!deductionForm.amount || isNaN(deductionForm.amount) || Number(deductionForm.amount) <= 0) return 0;
    const amt = Number(deductionForm.amount);
    if (deductionForm.unit === 'heures') {
        return (amt / 6).toFixed(2);
    }
    return amt.toFixed(2);
})

function openAddModal() {
    editingLeave.value = null
    form.reset()
    showAddModal.value = true
}

function openEditModal(leave) {
    editingLeave.value = leave
    form.type = leave.type
    form.date_debut = leave.date_debut.substring(0, 10)
    form.date_fin = leave.date_fin.substring(0, 10)
    form.motif = leave.motif
    form.document = null
    showAddModal.value = true
}

function submit() {
    if (editingLeave.value) {
        form.post(route('leaves.update', editingLeave.value.id), {
            forceFormData: true,
            onSuccess: () => {
                showAddModal.value = false
                editingLeave.value = null
                form.reset()
            }
        })
    } else {
        form.post(route('leaves.store'), {
            forceFormData: true,
            onSuccess: () => {
                showAddModal.value = false
                form.reset()
            }
        })
    }
}

function openReviewModal(leave) {
    selectedLeave.value = leave
    reviewForm.status = 'approuve'
    reviewForm.admin_commentaire = leave.admin_commentaire || ''
    showReviewModal.value = true
}

function submitReview() {
    reviewForm.patch(route('leaves.status.update', selectedLeave.value.id), {
        onSuccess: () => {
            showReviewModal.value = false
            selectedLeave.value = null
            reviewForm.reset()
        }
    })
}

function deleteLeave(id) {
    if (confirm('Voulez-vous vraiment annuler cette demande de congé ?')) {
        router.delete(route('leaves.destroy', id))
    }
}

function openDeductionModal() {
    deductionForm.reset()
    showDeductionModal.value = true
}

function submitDeduction() {
    deductionForm.post(route('leaves.deductions.store'), {
        onSuccess: () => {
            showDeductionModal.value = false
            deductionForm.reset()
        }
    })
}

function deleteDeduction(id) {
    if (confirm('Voulez-vous vraiment annuler cette retenue sur congé ?')) {
        router.delete(route('leaves.deductions.destroy', id))
    }
}

function calculateDays(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    if (isNaN(start) || isNaN(end)) return 0;
    const diffTime = Math.abs(end - start);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
}
</script>

<template>
    <Head title="Gestion des Congés" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-8 px-4">
            <header class="mb-8 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight">Gestion des Congés</h1>
                    <p class="text-gray-500">Demandes d'absences, congés annuels et retenues (absences & retards).</p>
                </div>
                <div class="flex items-center gap-3">
                    <button 
                        v-if="isDirecteur"
                        @click="openDeductionModal"
                        class="px-5 py-3 bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200/80 rounded-2xl font-black flex items-center gap-2 transition shadow-sm"
                    >
                        <MinusCircleIcon class="h-5 w-5 text-rose-600" />
                        Appliquer une Retenue
                    </button>
                    <button 
                        @click="openAddModal"
                        class="px-5 py-3 bg-indigo-600 text-white rounded-2xl font-black flex items-center gap-2 hover:bg-black transition shadow-lg shadow-indigo-100"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Nouvelle Demande
                    </button>
                </div>
            </header>

            <!-- KPI Cards for Directeur -->
            <div v-if="isDirecteur && stats" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center">
                        <ClockIcon class="w-7 h-7" />
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-widest text-gray-400">Demandes en attente</p>
                        <p class="text-3xl font-black text-gray-900">{{ stats.pending_count }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center">
                        <UserGroupIcon class="w-7 h-7" />
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-widest text-gray-400">Agents en congé</p>
                        <p class="text-3xl font-black text-gray-900">{{ stats.active_count }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-500 flex items-center justify-center">
                        <DocumentCheckIcon class="w-7 h-7" />
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-widest text-gray-400">Approuvés (Année)</p>
                        <p class="text-3xl font-black text-gray-900">{{ stats.approved_year_count }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center">
                        <MinusCircleIcon class="w-7 h-7" />
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-widest text-gray-400">Jours Déduits (Total)</p>
                        <p class="text-3xl font-black text-rose-600">{{ stats.total_deducted_days || 0 }}j</p>
                    </div>
                </div>
            </div>

            <!-- KPI Cards for User -->
            <div v-else-if="stats" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center">
                        <CalendarDaysIcon class="w-7 h-7" />
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-widest text-gray-400">Jours Restants</p>
                        <p class="text-3xl font-black text-indigo-600">{{ stats.remaining_days }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-gray-50 text-gray-500 flex items-center justify-center">
                        <DocumentCheckIcon class="w-7 h-7" />
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-widest text-gray-400">Jours Consommés</p>
                        <p class="text-3xl font-black text-gray-900">{{ stats.consumed_days }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center">
                        <MinusCircleIcon class="w-7 h-7" />
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-widest text-gray-400">Retenues (Absence/Retard)</p>
                        <p class="text-3xl font-black text-rose-600">{{ stats.deducted_days || 0 }}j</p>
                    </div>
                </div>
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center">
                        <ClockIcon class="w-7 h-7" />
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-widest text-gray-400">En attente</p>
                        <p class="text-3xl font-black text-gray-900">{{ stats.pending_count }}</p>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="flex items-center gap-8 border-b border-gray-200/80 mb-6 px-2">
                <button 
                    @click="activeTab = 'leaves'"
                    :class="['pb-4 font-black text-sm transition relative flex items-center gap-2', activeTab === 'leaves' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-400 hover:text-gray-600']"
                >
                    <CalendarDaysIcon class="h-4 w-4" />
                    Demandes de Congés ({{ leaves?.length || 0 }})
                </button>
                <button 
                    @click="activeTab = 'deductions'"
                    :class="['pb-4 font-black text-sm transition relative flex items-center gap-2.5', activeTab === 'deductions' ? 'text-rose-600 border-b-2 border-rose-600' : 'text-gray-400 hover:text-gray-600']"
                >
                    <MinusCircleIcon class="h-4 w-4" />
                    <span>Retenues (Absences / Retards)</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-black bg-rose-50 text-rose-700 border border-rose-100">{{ deductions?.length || 0 }}</span>
                </button>
            </div>

            <!-- Tab 1: Leaves Table -->
            <div v-if="activeTab === 'leaves'" class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th v-if="isDirecteur" class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Utilisateur</th>
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Type & Période</th>
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Durée</th>
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Statut</th>
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 text-right uppercase tracking-widest">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="leave in leaves" :key="leave.id" class="group hover:bg-gray-50/50 transition-colors">
                            <td v-if="isDirecteur" class="px-8 py-6">
                                <div class="font-black text-gray-900">{{ leave.user?.name }}</div>
                                <div class="text-xs text-gray-500 font-bold">{{ leave.user?.email }}</div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="font-black text-indigo-600 mb-1">{{ leave.type }}</div>
                                <div class="flex items-center gap-2 text-gray-500 text-xs font-bold">
                                    <CalendarDaysIcon class="h-4 w-4" />
                                    Du {{ formatDate(leave.date_debut) }} au {{ formatDate(leave.date_fin) }}
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span class="inline-flex items-center justify-center px-3 py-1 bg-gray-100 text-gray-700 text-xs font-black rounded-xl">
                                    {{ calculateDays(leave.date_debut, leave.date_fin) }} j
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <span v-if="leave.status === 'en_attente'" class="px-3 py-1.5 bg-amber-50 text-amber-600 text-[10px] font-black uppercase tracking-widest rounded-lg flex items-center gap-1.5 w-max">
                                    <ClockIcon class="h-3.5 w-3.5" /> En attente
                                </span>
                                <span v-else-if="leave.status === 'approuve'" class="px-3 py-1.5 bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-widest rounded-lg flex items-center gap-1.5 w-max">
                                    <CheckCircleIcon class="h-3.5 w-3.5" /> Approuvé
                                </span>
                                <span v-else-if="leave.status === 'rejete'" class="px-3 py-1.5 bg-red-50 text-red-600 text-[10px] font-black uppercase tracking-widest rounded-lg flex items-center gap-1.5 w-max">
                                    <XCircleIcon class="h-3.5 w-3.5" /> Rejeté
                                </span>
                                <div v-if="leave.admin_commentaire" class="mt-2 text-xs text-gray-500 italic truncate max-w-[200px]" :title="leave.admin_commentaire">
                                    "{{ leave.admin_commentaire }}"
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center justify-end gap-2">
                                    <a v-if="leave.document_path" :href="route('leaves.document', leave.id)" target="_blank" class="p-2.5 bg-gray-50 text-gray-400 hover:text-indigo-600 rounded-xl transition" title="Voir le justificatif">
                                        <DocumentTextIcon class="h-5 w-5" />
                                    </a>
                                    <button 
                                        v-if="isDirecteur && leave.status === 'en_attente'"
                                        @click="openReviewModal(leave)"
                                        class="p-2.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-xl transition" title="Traiter la demande"
                                    >
                                        <CheckCircleIcon class="h-5 w-5" />
                                    </button>
                                    <button 
                                        v-if="!isDirecteur && leave.status === 'en_attente'"
                                        @click="openEditModal(leave)"
                                        class="p-2.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition" title="Modifier la demande"
                                    >
                                        <PencilSquareIcon class="h-5 w-5" />
                                    </button>
                                    <button 
                                        v-if="!isDirecteur && leave.status === 'en_attente'"
                                        @click="deleteLeave(leave.id)"
                                        class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition" title="Annuler la demande"
                                    >
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="leaves.length === 0">
                            <td :colspan="isDirecteur ? 5 : 4" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <CalendarDaysIcon class="h-12 w-12 text-gray-200 mb-4" />
                                    <p class="text-gray-400 font-bold">Aucune demande de congé enregistrée.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tab 2: Deductions Table -->
            <div v-else-if="activeTab === 'deductions'" class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th v-if="isDirecteur" class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Agent / Employé</th>
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Motif & Incident</th>
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Quantité Saisie</th>
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Équivalent Congé</th>
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Explication</th>
                            <th v-if="isDirecteur" class="px-8 py-5 text-[10px] font-black text-gray-400 text-right uppercase tracking-widest">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="deduction in deductions" :key="deduction.id" class="group hover:bg-gray-50/50 transition-colors">
                            <td v-if="isDirecteur" class="px-8 py-6">
                                <div class="font-black text-gray-900">{{ deduction.user?.name }}</div>
                                <div class="text-xs text-gray-500 font-bold">{{ deduction.user?.email }}</div>
                            </td>
                            <td class="px-8 py-6">
                                <span :class="[
                                    'px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider inline-block mb-1',
                                    deduction.reason_type === 'absence' ? 'bg-red-50 text-red-600 border border-red-100' :
                                    deduction.reason_type === 'retard' ? 'bg-amber-50 text-amber-600 border border-amber-100' :
                                    'bg-purple-50 text-purple-600 border border-purple-100'
                                ]">
                                    {{ deduction.reason_type === 'absence' ? 'Absence non justifiée' : deduction.reason_type === 'retard' ? 'Retard répété' : 'Autre motif' }}
                                </span>
                                <div v-if="deduction.date_incident" class="text-xs font-bold text-gray-500 flex items-center gap-1 mt-0.5">
                                    <ClockIcon class="h-3.5 w-3.5" />
                                    Incident du {{ formatDate(deduction.date_incident) }}
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span class="font-black text-gray-900 text-sm">
                                    {{ deduction.amount }} {{ deduction.unit }}
                                </span>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-rose-50 text-rose-700 text-xs font-black rounded-xl border border-rose-100">
                                    -{{ deduction.days_deducted }} j
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <p class="text-xs font-medium text-gray-600 max-w-xs">{{ deduction.motif }}</p>
                                <p v-if="deduction.creator" class="text-[10px] text-gray-400 font-bold mt-1">Appliqué par {{ deduction.creator.name }}</p>
                            </td>
                            <td v-if="isDirecteur" class="px-8 py-6 text-right">
                                <button 
                                    @click="deleteDeduction(deduction.id)"
                                    class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition" title="Annuler cette retenue"
                                >
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="deductions.length === 0">
                            <td :colspan="isDirecteur ? 6 : 4" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <MinusCircleIcon class="h-12 w-12 text-gray-200 mb-4" />
                                    <p class="text-gray-400 font-bold">Aucune retenue enregistrée pour le moment.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Modal Add Leave -->
            <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
                <div class="bg-white w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl">
                    <h2 class="text-2xl font-black text-gray-900 mb-6 tracking-tight">{{ editingLeave ? 'Modifier la demande' : 'Nouvelle demande de congé' }}</h2>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Type de congé</label>
                            <select v-model="form.type" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4 focus:ring-2 focus:ring-indigo-600">
                                <option value="Annuel">Annuel</option>
                                <option value="Maladie">Maladie</option>
                                <option value="Maternité">Maternité</option>
                                <option value="Sans solde">Sans solde</option>
                                <option value="Autre">Autre</option>
                            </select>
                            <p v-if="form.errors.type" class="mt-1 text-[10px] text-red-600 font-bold">{{ form.errors.type }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Date de début</label>
                                <DateInput v-model="form.date_debut" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4" />
                                <p v-if="form.errors.date_debut" class="mt-1 text-[10px] text-red-600 font-bold">{{ form.errors.date_debut }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Date de fin</label>
                                <DateInput v-model="form.date_fin" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4" />
                                <p v-if="form.errors.date_fin" class="mt-1 text-[10px] text-red-600 font-bold">{{ form.errors.date_fin }}</p>
                            </div>
                        </div>
                        
                        <!-- Quota Summary Breakdown -->
                        <div v-if="my_stats && form.type !== 'Maternité' && form.type !== 'Maladie'" class="bg-gray-50 border border-gray-100 rounded-2xl p-4 text-xs font-bold text-gray-600 space-y-1.5">
                            <div class="flex justify-between">
                                <span>Solde annuel de base :</span>
                                <span class="font-black text-gray-900">30 jours</span>
                            </div>
                            <div class="flex justify-between text-gray-500">
                                <span>Congés pris / en attente :</span>
                                <span class="font-black text-gray-700">-{{ my_stats.consumed_days || 0 }}j</span>
                            </div>
                            <div class="flex justify-between text-rose-600">
                                <span>Retenues (absences & retards) :</span>
                                <span class="font-black">-{{ my_stats.deducted_days || 0 }}j</span>
                            </div>
                            <div class="border-t border-gray-200 pt-1.5 flex justify-between text-indigo-700 font-black text-sm">
                                <span>Solde disponible :</span>
                                <span>{{ my_stats.remaining_days || 0 }} jour(s)</span>
                            </div>
                        </div>

                        <!-- Highlighted Duration & Excess Warning -->
                        <div v-if="numberOfDays" class="space-y-2">
                            <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-4 flex items-center justify-between shadow-sm shadow-indigo-100/50">
                                <div class="flex items-center gap-2 text-indigo-600">
                                    <ClockIcon class="h-6 w-6" />
                                    <span class="text-sm font-black uppercase tracking-widest">Durée estimée</span>
                                </div>
                                <div class="text-3xl font-black text-indigo-700">
                                    {{ numberOfDays }} <span class="text-sm font-bold opacity-70">jour{{ numberOfDays > 1 ? 's' : '' }}</span>
                                </div>
                            </div>

                            <div v-if="form.type !== 'Maternité' && form.type !== 'Maladie' && my_stats && numberOfDays > (my_stats.remaining_days || 0)" class="bg-rose-50 border border-rose-200/80 rounded-2xl p-4 flex items-start gap-3">
                                <ExclamationTriangleIcon class="h-5 w-5 text-rose-600 shrink-0 mt-0.5" />
                                <div class="text-xs text-rose-700 font-medium">
                                    <p class="font-black uppercase tracking-wider mb-0.5">Dépassement de solde disponible</p>
                                    <p>Vous sollicitez {{ numberOfDays }} jour(s), mais votre solde restant (après déduction des congés passés et retenues) est de <strong>{{ my_stats.remaining_days || 0 }} jour(s)</strong>.</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Motif / Description</label>
                            <textarea v-model="form.motif" rows="3" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4 focus:ring-2 focus:ring-indigo-600"></textarea>
                            <p v-if="form.errors.motif" class="mt-1 text-[10px] text-red-600 font-bold">{{ form.errors.motif }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Justificatif (Optionnel)</label>
                            <input @change="e => form.document = e.target.files[0]" type="file" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-black file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            <p v-if="form.errors.document" class="mt-1 text-[10px] text-red-600 font-bold">{{ form.errors.document }}</p>
                        </div>
                        <div class="flex gap-4 mt-8">
                            <button @click="showAddModal = false" type="button" class="flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black">Annuler</button>
                            <button :disabled="form.processing" type="submit" class="flex-[2] py-4 bg-indigo-600 text-white rounded-2xl font-black shadow-lg shadow-indigo-100">
                                {{ editingLeave ? 'Mettre à jour' : 'Soumettre' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Review Leave Modal (Directeur) -->
            <div v-if="showReviewModal && selectedLeave" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
                <div class="bg-white w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl">
                    <h2 class="text-2xl font-black text-gray-900 mb-2 tracking-tight">Traiter la demande</h2>
                    <p class="text-gray-500 text-sm mb-6">Demande de {{ selectedLeave.user?.name }}</p>
                    
                    <div class="bg-gray-50 p-4 rounded-2xl mb-6">
                        <div class="font-black text-indigo-600 mb-1">{{ selectedLeave.type }}</div>
                        <div class="text-xs text-gray-600 font-bold mb-2">Du {{ formatDate(selectedLeave.date_debut) }} au {{ formatDate(selectedLeave.date_fin) }}</div>
                        <p class="text-sm text-gray-700">{{ selectedLeave.motif }}</p>
                    </div>

                    <form @submit.prevent="submitReview" class="space-y-4">
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Décision</label>
                            <div class="flex gap-4">
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" v-model="reviewForm.status" value="approuve" class="peer sr-only">
                                    <div class="p-4 rounded-2xl border-2 border-gray-100 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 flex flex-col items-center gap-2 transition-all">
                                        <CheckCircleIcon class="h-6 w-6 text-emerald-500" />
                                        <span class="font-black text-sm text-emerald-700">Approuver</span>
                                    </div>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" v-model="reviewForm.status" value="rejete" class="peer sr-only">
                                    <div class="p-4 rounded-2xl border-2 border-gray-100 peer-checked:border-red-500 peer-checked:bg-red-50 flex flex-col items-center gap-2 transition-all">
                                        <XCircleIcon class="h-6 w-6 text-red-500" />
                                        <span class="font-black text-sm text-red-700">Rejeter</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Commentaire (Optionnel)</label>
                            <textarea v-model="reviewForm.admin_commentaire" rows="2" placeholder="Explication du refus, instructions particulières..." class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4 focus:ring-2 focus:ring-indigo-600"></textarea>
                        </div>
                        <div class="flex gap-4 mt-8">
                            <button @click="showReviewModal = false" type="button" class="flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black">Annuler</button>
                            <button :disabled="reviewForm.processing" type="submit" class="flex-[2] py-4 bg-gray-900 hover:bg-black text-white rounded-2xl font-black shadow-lg">
                                Confirmer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Retenue sur Congé (Directeur) -->
            <div v-if="showDeductionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
                <div class="bg-white w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <MinusCircleIcon class="w-6 h-6" />
                        </div>
                        <h2 class="text-2xl font-black text-gray-900 tracking-tight">Appliquer une Retenue</h2>
                    </div>
                    <p class="text-gray-500 text-sm mb-6">Soustraire des heures ou jours de congé suite à des absences ou retards.</p>
                    
                    <form @submit.prevent="submitDeduction" class="space-y-4">
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Sélectionner l'Agent</label>
                            <select v-model="deductionForm.user_id" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4 focus:ring-2 focus:ring-rose-500">
                                <option value="" disabled>Choisir un agent...</option>
                                <option v-for="u in users" :key="u.id" :value="u.id">
                                    {{ u.name }} {{ u.email ? `(${u.email})` : '' }}
                                </option>
                            </select>
                            <p v-if="deductionForm.errors.user_id" class="mt-1 text-[10px] text-red-600 font-bold">{{ deductionForm.errors.user_id }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Motif de la retenue</label>
                                <select v-model="deductionForm.reason_type" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4 focus:ring-2 focus:ring-rose-500">
                                    <option value="absence">Absence non justifiée</option>
                                    <option value="retard">Retard répété</option>
                                    <option value="autre">Autre motif</option>
                                </select>
                                <p v-if="deductionForm.errors.reason_type" class="mt-1 text-[10px] text-red-600 font-bold">{{ deductionForm.errors.reason_type }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Date de l'incident</label>
                                <DateInput v-model="deductionForm.date_incident" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4" />
                                <p v-if="deductionForm.errors.date_incident" class="mt-1 text-[10px] text-red-600 font-bold">{{ deductionForm.errors.date_incident }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Unité</label>
                                <select v-model="deductionForm.unit" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4 focus:ring-2 focus:ring-rose-500">
                                    <option value="heures">Heures</option>
                                    <option value="jours">Jours</option>
                                </select>
                                <p v-if="deductionForm.errors.unit" class="mt-1 text-[10px] text-red-600 font-bold">{{ deductionForm.errors.unit }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Quantité ({{ deductionForm.unit }})</label>
                                <input v-model="deductionForm.amount" type="number" step="0.5" min="0.5" placeholder="ex: 4 ou 2" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4 focus:ring-2 focus:ring-rose-500" />
                                <p v-if="deductionForm.errors.amount" class="mt-1 text-[10px] text-red-600 font-bold">{{ deductionForm.errors.amount }}</p>
                            </div>
                        </div>

                        <!-- Impact Preview Card -->
                        <div class="bg-rose-50/80 border border-rose-100 rounded-2xl p-4 flex items-center justify-between">
                            <div class="flex items-center gap-2 text-rose-700">
                                <ExclamationTriangleIcon class="h-5 w-5 text-rose-600" />
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wider">Impact sur le solde</p>
                                    <p class="text-[11px] text-rose-600 font-medium">6 heures d'absence/retard = 1 jour de congé déduit</p>
                                </div>
                            </div>
                            <div class="text-2xl font-black text-rose-700">
                                -{{ calculatedDaysDeducted }} <span class="text-xs font-bold">jour{{ calculatedDaysDeducted > 1 ? 's' : '' }}</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Explication / Remarque</label>
                            <textarea v-model="deductionForm.motif" rows="3" placeholder="Préciser les heures ou détails du retard/absence..." class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4 focus:ring-2 focus:ring-rose-500"></textarea>
                            <p v-if="deductionForm.errors.motif" class="mt-1 text-[10px] text-red-600 font-bold">{{ deductionForm.errors.motif }}</p>
                        </div>

                        <div class="flex gap-4 mt-8">
                            <button @click="showDeductionModal = false" type="button" class="flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black">Annuler</button>
                            <button :disabled="deductionForm.processing" type="submit" class="flex-[2] py-4 bg-rose-600 hover:bg-rose-700 text-white rounded-2xl font-black shadow-lg shadow-rose-100">
                                Enregistrer la Retenue
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
