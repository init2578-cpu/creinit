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
    UserGroupIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    leaves: Array,
    stats: Object
})

const page = usePage()
const isDirecteur = computed(() => page.props.auth.user?.roles?.includes('Directeur'))

const showAddModal = ref(false)
const showReviewModal = ref(false)
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
            <header class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight">Gestion des Congés</h1>
                    <p class="text-gray-500">Demandes d'absences, congés annuels et permissions.</p>
                </div>
                <button 
                    @click="openAddModal"
                    class="px-5 py-3 bg-indigo-600 text-white rounded-2xl font-black flex items-center gap-2 hover:bg-black transition shadow-lg shadow-indigo-100"
                >
                    <PlusIcon class="h-5 w-5" />
                    Nouvelle Demande
                </button>
            </header>

            <!-- KPI Cards for Directeur -->
            <div v-if="isDirecteur && stats" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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
            </div>

            <!-- KPI Cards for User -->
            <div v-else-if="stats" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center">
                        <CalendarDaysIcon class="w-7 h-7" />
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-widest text-gray-400">Jours Restants</p>
                        <p class="text-3xl font-black text-gray-900">{{ stats.remaining_days }}</p>
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
                    <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center">
                        <ClockIcon class="w-7 h-7" />
                    </div>
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-widest text-gray-400">En attente</p>
                        <p class="text-3xl font-black text-gray-900">{{ stats.pending_count }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm overflow-hidden">
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
                                <div class="font-black text-gray-900">{{ leave.user.name }}</div>
                                <div class="text-xs text-gray-500 font-bold">{{ leave.user.email }}</div>
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
                            <td :colspan="isDirecteur ? 4 : 3" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <CalendarDaysIcon class="h-12 w-12 text-gray-200 mb-4" />
                                    <p class="text-gray-400 font-bold">Aucune demande de congé.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Add/Edit Leave Modal -->
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
                        
                        <!-- Highlighted Duration -->
                        <div v-if="numberOfDays" class="bg-indigo-50 border border-indigo-100 rounded-2xl p-4 flex items-center justify-between shadow-sm shadow-indigo-100/50">
                            <div class="flex items-center gap-2 text-indigo-600">
                                <ClockIcon class="h-6 w-6" />
                                <span class="text-sm font-black uppercase tracking-widest">Durée estimée</span>
                            </div>
                            <div class="text-3xl font-black text-indigo-700">
                                {{ numberOfDays }} <span class="text-sm font-bold opacity-70">jour{{ numberOfDays > 1 ? 's' : '' }}</span>
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
                    <p class="text-gray-500 text-sm mb-6">Demande de {{ selectedLeave.user.name }}</p>
                    
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

        </div>
    </AuthenticatedLayout>
</template>
