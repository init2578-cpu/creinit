<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { 
    EyeIcon, 
    CheckCircleIcon, 
    XCircleIcon,
    DocumentIcon,
    XMarkIcon,
    UserPlusIcon,
    LinkIcon,
    ClipboardIcon,
    IdentificationIcon,
    MapPinIcon,
    AcademicCapIcon,
    BriefcaseIcon,
    CalendarIcon,
    PencilIcon,
    MagnifyingGlassIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    applications: Array,
    modules: Array
})

const searchQuery = ref('')
const statusFilter = ref('all')
const moduleFilter = ref('all')

const filteredApplications = computed(() => {
    if (!props.applications) return []
    return props.applications.filter(app => {
        const name = String(app.nom_complet || (app.user ? app.user.name : '')).toLowerCase()
        const email = String(app.user ? app.user.email : '').toLowerCase()
        const phone = String(app.telephone || (app.user ? app.user.telephone : '')).toLowerCase()
        const query = searchQuery.value.toLowerCase()
        const matchesSearch = name.includes(query) || email.includes(query) || phone.includes(query)

        const matchesStatus = statusFilter.value === 'all' || app.status === statusFilter.value
        const matchesModule = moduleFilter.value === 'all' || Number(app.module_id) === Number(moduleFilter.value)

        return matchesSearch && matchesStatus && matchesModule
    })
})

const selectedApplication = ref(null)
const previewType = ref(null) // 'cni' or 'diploma'
const isPreviewOpen = ref(false)
const isDetailsOpen = ref(false)
const applicationForDetails = ref(null)

// Editing
const isEditOpen = ref(false)
const editForm = useForm({
    id: null,
    nom_complet: '',
    email: '',
    telephone: '',
    module_id: '',
    adresse_reelle: '',
    date_naissance: '',
    lieu_naissance: '',
    niveau_etude: '',
    dernier_diplome_libelle: '',
    fonction: '',
    etablissement: '',
    commentaires: '',
    sexe: '',
})

// Manual Enrollment
const isManualEnrollOpen = ref(false)
const enrollForm = useForm({
    nom_complet: '',
    email: '',
    telephone: '',
    module_id: '',
    adresse_reelle: '',
    date_naissance: '',
    lieu_naissance: '',
    niveau_etude: '',
    dernier_diplome_libelle: '',
    fonction: '',
    etablissement: '',
    sexe: '',
    cni: null,
    diploma: null,
})

// Link Generation
const isLinkGenOpen = ref(false)
const selectedModuleForLink = ref('')
const generatedLink = ref('')

function openPreview(app, type) {
    selectedApplication.value = app
    previewType.value = type
    isPreviewOpen.value = true
}

function closePreview() {
    isPreviewOpen.value = false
    selectedApplication.value = null
}

function openDetails(app) {
    applicationForDetails.value = app
    isDetailsOpen.value = true
}

function closeDetails() {
    isDetailsOpen.value = false
    applicationForDetails.value = null
}

function updateStatus(id, status) {
    let confirmMsg = '';
    if (status === 'admitted') {
        confirmMsg = 'Confirmer la décision : ADMIS ?';
    } else if (status === 'rejected') {
        confirmMsg = 'Confirmer la décision : REJETÉ ?';
    } else if (status === 'pending') {
        confirmMsg = 'Confirmer la décision : REMETTRE EN ATTENTE ?';
    }

    if (confirm(confirmMsg)) {
        router.patch(route('applications.status.update', id), {
            status: status
        }, {
            preserveScroll: true
        })
    }
}

function submitManualEnroll() {
    enrollForm.post(route('applications.enroll.manual'), {
        onSuccess: () => {
            isManualEnrollOpen.value = false
            enrollForm.reset()
        }
    })
}

function generateRegistrationLink() {
    if (!selectedModuleForLink.value) return
    const baseUrl = window.location.origin
    generatedLink.value = `${baseUrl}/apply?module=${selectedModuleForLink.value}`
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text)
    window.platformAlert('Lien copié dans le presse-papiers !', 'success')
}

function openEdit(app) {
    editForm.id = app.id
    editForm.nom_complet = app.nom_complet || (app.user ? app.user.name : '')
    editForm.email = app.user ? app.user.email : ''
    editForm.telephone = app.telephone || (app.user ? app.user.telephone : '')
    editForm.module_id = app.module_id
    editForm.adresse_reelle = app.adresse_reelle || ''
    editForm.date_naissance = app.date_naissance || ''
    editForm.lieu_naissance = app.lieu_naissance || ''
    editForm.niveau_etude = app.niveau_etude || ''
    editForm.dernier_diplome_libelle = app.dernier_diplome_libelle || ''
    editForm.fonction = app.fonction || ''
    editForm.etablissement = app.etablissement || ''
    editForm.commentaires = app.commentaires || ''
    editForm.sexe = app.sexe || ''
    isEditOpen.value = true
}

function submitEdit() {
    editForm.put(route('applications.update', editForm.id), {
        onSuccess: () => {
            isEditOpen.value = false
            editForm.reset()
        }
    })
}

const getStatusClass = (status) => {
    switch (status) {
        case 'pending': return 'bg-yellow-50 text-yellow-700 border-yellow-100'
        case 'admitted': return 'bg-green-50 text-green-700 border-green-100'
        case 'rejected': return 'bg-red-50 text-red-700 border-red-100'
        default: return 'bg-gray-50 text-gray-700 border-gray-100'
    }
}
</script>

<template>
    <Head title="Gestion des Admissions" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-8 px-4">
            <header class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight">Admissions</h1>
                    <p class="text-gray-500 font-medium">Gérer et valider les dossiers d'inscription des candidats.</p>
                </div>
                <div class="flex gap-3">
                    <button @click="isLinkGenOpen = true" class="flex items-center gap-2 px-5 py-2.5 bg-white text-blue-600 border border-blue-100 rounded-2xl font-black text-sm hover:bg-blue-50 transition shadow-sm">
                        <LinkIcon class="h-5 w-5" />
                        Générer un lien
                    </button>
                    <button @click="isManualEnrollOpen = true" class="flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-2xl font-black text-sm hover:bg-blue-700 transition shadow-lg shadow-blue-100">
                        <UserPlusIcon class="h-5 w-5" />
                        Inscrire un candidat
                    </button>
                </div>
            </header>

            <!-- Filter Bar -->
            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="relative w-full md:w-96">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                        <MagnifyingGlassIcon class="h-5 w-5" />
                    </span>
                    <input 
                        v-model="searchQuery" 
                        type="text" 
                        placeholder="Rechercher un candidat..." 
                        class="w-full bg-gray-50 border-0 rounded-2xl pl-11 pr-5 py-3 font-bold text-sm focus:ring-2 focus:ring-blue-500 placeholder-gray-400 transition"
                    />
                </div>
                
                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <!-- Status Filter -->
                    <div class="flex-1 md:flex-initial min-w-[140px]">
                        <select 
                            v-model="statusFilter" 
                            class="w-full bg-gray-50 border-0 rounded-2xl px-5 py-3 font-bold text-sm focus:ring-2 focus:ring-blue-500 appearance-none cursor-pointer"
                        >
                            <option value="all">Tous les statuts</option>
                            <option value="pending">En attente</option>
                            <option value="admitted">Admis</option>
                            <option value="rejected">Rejeté</option>
                        </select>
                    </div>

                    <!-- Module Filter -->
                    <div class="flex-1 md:flex-initial min-w-[200px]">
                        <select 
                            v-model="moduleFilter" 
                            class="w-full bg-gray-50 border-0 rounded-2xl px-5 py-3 font-bold text-sm focus:ring-2 focus:ring-blue-500 appearance-none cursor-pointer"
                        >
                            <option value="all">Tous les modules</option>
                            <option v-for="m in modules" :key="m.id" :value="m.id">{{ m.titre || m.nom_module }}</option>
                        </select>
                    </div>

                    <!-- Reset Filters Button -->
                    <button 
                        v-if="searchQuery || statusFilter !== 'all' || moduleFilter !== 'all'"
                        @click="searchQuery = ''; statusFilter = 'all'; moduleFilter = 'all'"
                        class="px-4 py-3 bg-red-50 text-red-600 rounded-2xl font-bold text-sm hover:bg-red-100 transition flex items-center gap-1.5"
                    >
                        Réinitialiser
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-black tracking-widest border-b border-gray-100">
                                <th class="px-6 py-4">Candidat</th>
                                <th class="px-6 py-4">Module</th>
                                <th class="px-6 py-4">Documents</th>
                                <th class="px-6 py-4 text-center">Statut</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <!-- Empty State -->
                            <tr v-if="filteredApplications.length === 0">
                                <td colspan="5" class="text-center py-12 text-gray-400 font-bold text-sm">
                                    Aucun candidat ne correspond aux critères de recherche.
                                </td>
                            </tr>
                            <tr v-for="app in filteredApplications" :key="app.id" class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center overflow-hidden font-black">
                                            <img v-if="app.user?.profile_photo_url" :src="app.user.profile_photo_url" class="h-full w-full object-cover">
                                            <template v-else>{{ (app.nom_complet || (app.user ? app.user.name : 'N/A')).charAt(0) }}</template>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900">{{ app.nom_complet || (app.user ? app.user.name : 'N/A') }}</p>
                                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-wider">{{ app.user ? app.user.email : 'Candidat public' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span v-if="app.module" class="px-3 py-1 bg-blue-50 text-blue-700 text-xs font-bold rounded-lg border border-blue-100 italic truncate max-w-[150px] inline-block">
                                        {{ app.module?.titre || app.module?.nom_module }}
                                    </span>
                                    <span v-else class="text-[10px] text-gray-400 font-bold italic">N/A (Module non défini)</span>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-400">
                                    {{ app.cni_path === 'manual_enrollment' ? 'Inscription Manuelle' : '2 Documents fournis' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border" :class="getStatusClass(app.status)">
                                        {{ app.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button @click="openEdit(app)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-xl transition" title="Modifier">
                                            <PencilIcon class="h-6 w-6" />
                                        </button>
                                        <button @click="openDetails(app)" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl font-bold text-xs hover:bg-gray-900 hover:text-white transition flex items-center gap-2">
                                            <EyeIcon class="h-4 w-4" />
                                            Détails
                                        </button>
                                        <template v-if="app.status === 'pending'">
                                            <button @click="updateStatus(app.id, 'admitted')" class="p-2 text-green-600 hover:bg-green-50 rounded-xl transition" title="Admettre">
                                                <CheckCircleIcon class="h-6 w-6" />
                                            </button>
                                            <button @click="updateStatus(app.id, 'rejected')" class="p-2 text-red-600 hover:bg-red-50 rounded-xl transition" title="Rejeter">
                                                <XCircleIcon class="h-6 w-6" />
                                            </button>
                                        </template>
                                        <template v-else-if="$page.props.auth.user.roles.includes('Directeur')">
                                            <button @click="updateStatus(app.id, 'pending')" class="p-2 text-amber-600 hover:bg-amber-50 rounded-xl transition" title="Remettre en attente">
                                                <ArrowPathIcon class="h-6 w-6" />
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Details Modal -->
        <div v-if="isDetailsOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm">
            <div class="bg-white w-full max-w-2xl rounded-[3rem] overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
                <div class="p-8 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <div class="flex items-center gap-4">
                        <div class="h-16 w-16 bg-blue-600 text-white rounded-2xl flex items-center justify-center overflow-hidden text-2xl font-black">
                            <img v-if="applicationForDetails.user?.profile_photo_url" :src="applicationForDetails.user.profile_photo_url" class="h-full w-full object-cover">
                            <template v-else>{{ (applicationForDetails.nom_complet || (applicationForDetails.user ? applicationForDetails.user.name : 'N/A')).charAt(0) }}</template>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black text-gray-900 tracking-tight">{{ applicationForDetails.nom_complet || (applicationForDetails.user ? applicationForDetails.user.name : 'N/A') }}</h3>
                            <div class="flex gap-4 mt-1">
                                <span class="px-3 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-black rounded-full uppercase tracking-widest">{{ applicationForDetails.status }}</span>
                                <span class="text-xs text-gray-400 font-bold italic">
                                    Soumis le {{ new Date(applicationForDetails.created_at).toLocaleDateString() }} 
                                    à {{ new Date(applicationForDetails.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false }) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <button @click="closeDetails" class="p-2 hover:bg-gray-100 rounded-xl transition">
                        <XMarkIcon class="h-6 w-6 text-gray-400" />
                    </button>
                </div>

                <div class="p-8 overflow-y-auto space-y-8 custom-scrollbar">
                    <!-- SECTION: Identity -->
                    <div>
                        <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                             <IdentificationIcon class="h-4 w-4" /> 
                             État Civil & Contact
                        </h4>
                        <div class="grid grid-cols-2 gap-6 p-6 bg-gray-50 rounded-[2rem] border border-gray-100">
                            <div>
                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Naissance & Genre</p>
                                <p class="font-bold text-gray-900 text-sm">
                                    {{ applicationForDetails.date_naissance ? new Date(applicationForDetails.date_naissance).toLocaleDateString() : 'N/A' }} 
                                    à {{ applicationForDetails.lieu_naissance || 'N/A' }}
                                    ({{ applicationForDetails.sexe === 'M' ? 'Masculin' : 'Féminin' }})
                                </p>
                            </div>
                            <div>
                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Téléphone</p>
                                <p class="font-bold text-gray-900 text-sm">{{ applicationForDetails.telephone || (applicationForDetails.user ? applicationForDetails.user.telephone : 'N/A') }}</p>
                            </div>
                            <div>
                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Email</p>
                                <p class="font-bold text-gray-900 text-sm">{{ applicationForDetails.user ? applicationForDetails.user.email : 'N/A' }}</p>
                            </div>
                            <div class="col-span-2 border-t border-gray-200 pt-4">
                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Adresse Réelle</p>
                                <p class="font-bold text-gray-900 text-sm">{{ applicationForDetails.adresse_reelle || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION: Background -->
                    <div>
                        <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                             <AcademicCapIcon class="h-4 w-4" /> 
                             Parcours Académique & Professionnel
                        </h4>
                        <div class="grid grid-cols-2 gap-6 p-6 bg-blue-50/50 rounded-[2rem] border border-blue-100/50">
                            <div>
                                <p class="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-1">Niveau d'étude</p>
                                <p class="font-black text-blue-900 text-sm">{{ applicationForDetails.niveau_etude || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-1">Dernier Diplôme</p>
                                <p class="font-black text-blue-900 text-sm">{{ applicationForDetails.dernier_diplome_libelle || 'N/A' }}</p>
                            </div>
                            <div class="col-span-2 border-t border-blue-100 pt-4">
                                <p class="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-1">Fonction Actuelle</p>
                                <p class="font-black text-blue-900 text-sm">
                                    {{ applicationForDetails.fonction || 'N/A' }}
                                    <span v-if="applicationForDetails.etablissement" class="text-blue-500 font-bold ml-1 italic">@ {{ applicationForDetails.etablissement }}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION: Documents -->
                    <div>
                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4">Documents Attachés</h4>
                        <div class="flex gap-4">
                            <button 
                                v-if="applicationForDetails.cni_path !== 'manual_enrollment'" 
                                @click="openPreview(applicationForDetails, 'cni')"
                                class="flex-1 p-4 bg-white border border-gray-200 rounded-2xl flex items-center justify-between hover:border-blue-600 transition group"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 bg-gray-100 text-gray-500 rounded-lg flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition">
                                        <DocumentIcon class="h-5 w-5" />
                                    </div>
                                    <span class="text-xs font-black text-gray-900 uppercase">Copie CNI</span>
                                </div>
                                <EyeIcon class="h-4 w-4 text-gray-300 group-hover:text-blue-600" />
                            </button>
                            <button 
                                v-if="applicationForDetails.diploma_path !== 'manual_enrollment'" 
                                @click="openPreview(applicationForDetails, 'diploma')"
                                class="flex-1 p-4 bg-white border border-gray-200 rounded-2xl flex items-center justify-between hover:border-blue-600 transition group"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 bg-gray-100 text-gray-500 rounded-lg flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition">
                                        <DocumentIcon class="h-5 w-5" />
                                    </div>
                                    <span class="text-xs font-black text-gray-900 uppercase">Scan Diplôme</span>
                                </div>
                                <EyeIcon class="h-4 w-4 text-gray-300 group-hover:text-blue-600" />
                            </button>
                            <div v-if="applicationForDetails.cni_path === 'manual_enrollment'" class="w-full text-center py-4 bg-gray-50 rounded-2xl border border-dashed border-gray-200 text-gray-400 text-xs font-bold italic">
                                Aucun document (Inscription Manuelle)
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-8 bg-gray-50 flex justify-end gap-3 mt-auto">
                    <template v-if="applicationForDetails.status === 'pending'">
                        <button @click="updateStatus(applicationForDetails.id, 'rejected'); closeDetails()" class="px-6 py-3 bg-white text-red-600 border border-red-100 rounded-2xl font-black text-sm hover:bg-red-50 transition">
                            Rejeter le dossier
                        </button>
                        <button @click="updateStatus(applicationForDetails.id, 'admitted'); closeDetails()" class="px-6 py-3 bg-blue-600 text-white rounded-2xl font-black text-sm hover:bg-blue-700 transition shadow-lg shadow-blue-100">
                            Admettre le candidat
                        </button>
                    </template>
                    <template v-else-if="$page.props.auth.user.roles.includes('Directeur')">
                        <button @click="updateStatus(applicationForDetails.id, 'pending'); closeDetails()" class="px-6 py-3 bg-amber-600 text-white rounded-2xl font-black text-sm hover:bg-amber-700 transition shadow-lg shadow-amber-100">
                            Remettre en attente
                        </button>
                    </template>
                    <button @click="closeDetails" class="px-8 py-3 bg-gray-900 text-white rounded-2xl font-black text-sm">Fermer</button>
                </div>
            </div>
        </div>

        <!-- Manual Enrollment Modal -->
        <div v-if="isManualEnrollOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm">
            <div class="bg-white w-full max-w-3xl rounded-[2.5rem] overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
                <!-- Header -->
                <div class="px-10 py-7 border-b border-gray-100 flex items-center justify-between shrink-0 bg-gray-50/50">
                    <div>
                        <h3 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-3">
                            <UserPlusIcon class="h-7 w-7 text-blue-600" />
                            Inscription Manuelle
                        </h3>
                        <p class="text-sm text-gray-400 font-medium mt-0.5">Remplissez les informations du candidat. Les documents sont optionnels.</p>
                    </div>
                    <button @click="isManualEnrollOpen = false" class="p-2 hover:bg-gray-200 rounded-xl transition">
                        <XMarkIcon class="h-6 w-6 text-gray-400" />
                    </button>
                </div>

                <form @submit.prevent="submitManualEnroll" class="overflow-y-auto custom-scrollbar">
                    <div class="px-10 py-8 space-y-8">

                        <!-- Section: Identité -->
                        <div>
                            <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                                <IdentificationIcon class="h-4 w-4" /> État Civil &amp; Contact
                            </h4>
                            <div class="grid grid-cols-3 gap-4">
                                <div class="col-span-3">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Nom Complet <span class="text-red-500">*</span></label>
                                    <input v-model="enrollForm.nom_complet" type="text" required placeholder="Ex: Moussa Diallo" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5 placeholder-gray-300">
                                    <p v-if="enrollForm.errors.nom_complet" class="text-red-500 text-[10px] mt-1 font-bold">{{ enrollForm.errors.nom_complet }}</p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Sexe <span class="text-red-500">*</span></label>
                                    <select v-model="enrollForm.sexe" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5 appearance-none cursor-pointer">
                                        <option value="">— Choisir —</option>
                                        <option value="M">Masculin</option>
                                        <option value="F">Féminin</option>
                                    </select>
                                    <p v-if="enrollForm.errors.sexe" class="text-red-500 text-[10px] mt-1 font-bold">{{ enrollForm.errors.sexe }}</p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Date de Naissance (jj/mm/aaaa) <span class="text-red-500">*</span></label>
                                    <input v-model="enrollForm.date_naissance" type="date" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5">
                                    <p v-if="enrollForm.errors.date_naissance" class="text-red-500 text-[10px] mt-1 font-bold">{{ enrollForm.errors.date_naissance }}</p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Lieu de Naissance <span class="text-red-500">*</span></label>
                                    <input v-model="enrollForm.lieu_naissance" type="text" required placeholder="Ex: Kolda" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5 placeholder-gray-300">
                                    <p v-if="enrollForm.errors.lieu_naissance" class="text-red-500 text-[10px] mt-1 font-bold">{{ enrollForm.errors.lieu_naissance }}</p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Téléphone <span class="text-red-500">*</span></label>
                                    <input v-model="enrollForm.telephone" type="tel" required placeholder="Ex: 77 000 00 00" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5 placeholder-gray-300">
                                    <p v-if="enrollForm.errors.telephone" class="text-red-500 text-[10px] mt-1 font-bold">{{ enrollForm.errors.telephone }}</p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Email <span class="text-gray-300 text-[9px]">(Optionnel)</span></label>
                                    <input v-model="enrollForm.email" type="email" placeholder="ex@email.com" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5 placeholder-gray-300">
                                    <p v-if="enrollForm.errors.email" class="text-red-500 text-[10px] mt-1 font-bold">{{ enrollForm.errors.email }}</p>
                                </div>
                                <div class="col-span-3">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Adresse Réelle <span class="text-red-500">*</span></label>
                                    <input v-model="enrollForm.adresse_reelle" type="text" required placeholder="Quartier, Ville" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5 placeholder-gray-300">
                                    <p v-if="enrollForm.errors.adresse_reelle" class="text-red-500 text-[10px] mt-1 font-bold">{{ enrollForm.errors.adresse_reelle }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Parcours -->
                        <div class="border-t border-gray-100 pt-8">
                            <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                                <AcademicCapIcon class="h-4 w-4" /> Parcours Académique &amp; Professionnel
                            </h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Niveau d'étude <span class="text-red-500">*</span></label>
                                    <input v-model="enrollForm.niveau_etude" type="text" required placeholder="Ex: Bac+2, Licence..." class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5 placeholder-gray-300">
                                    <p v-if="enrollForm.errors.niveau_etude" class="text-red-500 text-[10px] mt-1 font-bold">{{ enrollForm.errors.niveau_etude }}</p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Dernier Diplôme <span class="text-red-500">*</span></label>
                                    <input v-model="enrollForm.dernier_diplome_libelle" type="text" required placeholder="Ex: BTS Commerce" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5 placeholder-gray-300">
                                    <p v-if="enrollForm.errors.dernier_diplome_libelle" class="text-red-500 text-[10px] mt-1 font-bold">{{ enrollForm.errors.dernier_diplome_libelle }}</p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Fonction Actuelle <span class="text-red-500">*</span></label>
                                    <input v-model="enrollForm.fonction" type="text" required placeholder="Ex: Étudiant, Commerçant..." class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5 placeholder-gray-300">
                                    <p v-if="enrollForm.errors.fonction" class="text-red-500 text-[10px] mt-1 font-bold">{{ enrollForm.errors.fonction }}</p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Établissement <span class="text-gray-300 text-[9px]">(Optionnel)</span></label>
                                    <input v-model="enrollForm.etablissement" type="text" placeholder="Ex: Université Assane Seck" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5 placeholder-gray-300">
                                    <p v-if="enrollForm.errors.etablissement" class="text-red-500 text-[10px] mt-1 font-bold">{{ enrollForm.errors.etablissement }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Formation & Documents -->
                        <div class="border-t border-gray-100 pt-8">
                            <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                                <BriefcaseIcon class="h-4 w-4" /> Formation &amp; Documents
                            </h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Module de Formation <span class="text-red-500">*</span></label>
                                    <select v-model="enrollForm.module_id" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5 appearance-none cursor-pointer">
                                        <option value="">— Sélectionner un module —</option>
                                        <option v-for="m in modules" :key="m.id" :value="m.id">{{ m.titre || m.nom_module }}</option>
                                    </select>
                                    <p v-if="enrollForm.errors.module_id" class="text-red-500 text-[10px] mt-1 font-bold">{{ enrollForm.errors.module_id }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="p-4 bg-gray-50 rounded-2xl border border-dashed border-gray-200 hover:border-blue-300 transition">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Document CNI <span class="text-gray-300 text-[9px]">(Optionnel)</span></label>
                                        <input @input="enrollForm.cni = $event.target.files[0]" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs font-bold text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-blue-600 file:text-white hover:file:bg-blue-700 file:cursor-pointer file:transition cursor-pointer">
                                        <p class="text-[9px] text-gray-400 mt-1.5">PDF, JPG ou PNG · max 5 Mo</p>
                                        <p v-if="enrollForm.errors.cni" class="text-red-500 text-[10px] mt-1 font-bold">{{ enrollForm.errors.cni }}</p>
                                    </div>
                                    <div class="p-4 bg-gray-50 rounded-2xl border border-dashed border-gray-200 hover:border-blue-300 transition">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Scan Diplôme <span class="text-gray-300 text-[9px]">(Optionnel)</span></label>
                                        <input @input="enrollForm.diploma = $event.target.files[0]" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs font-bold text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-blue-600 file:text-white hover:file:bg-blue-700 file:cursor-pointer file:transition cursor-pointer">
                                        <p class="text-[9px] text-gray-400 mt-1.5">PDF, JPG ou PNG · max 5 Mo</p>
                                        <p v-if="enrollForm.errors.diploma" class="text-red-500 text-[10px] mt-1 font-bold">{{ enrollForm.errors.diploma }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Footer Actions -->
                    <div class="px-10 py-6 bg-gray-50/80 border-t border-gray-100 flex gap-4 shrink-0">
                        <button type="button" @click="isManualEnrollOpen = false" class="flex-1 py-3.5 bg-white text-gray-600 border border-gray-200 rounded-2xl font-black text-sm hover:bg-gray-100 transition">Annuler</button>
                        <button type="submit" :disabled="enrollForm.processing" class="flex-1 py-3.5 bg-blue-600 text-white rounded-2xl font-black text-sm shadow-lg shadow-blue-100 hover:bg-blue-700 transition disabled:opacity-50 flex items-center justify-center gap-2">
                            <UserPlusIcon class="h-5 w-5" />
                            Inscrire le candidat
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Link Generation Modal -->
        <div v-if="isLinkGenOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm">
            <div class="bg-white w-full max-w-md rounded-[2.5rem] overflow-hidden shadow-2xl">
                <div class="p-8 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-2xl font-black text-gray-900 tracking-tight">Lien d'Inscription</h3>
                    <button @click="isLinkGenOpen = false" class="p-2 hover:bg-gray-100 rounded-xl transition">
                        <XMarkIcon class="h-6 w-6 text-gray-400" />
                    </button>
                </div>
                <div class="p-8 space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Choisir une Formation</label>
                        <select v-model="selectedModuleForLink" @change="generateRegistrationLink" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5">
                            <option value="">Lien général</option>
                            <option v-for="m in modules" :key="m.id" :value="m.id">{{ m.titre || m.nom_module }}</option>
                        </select>
                    </div>

                    <div v-if="generatedLink" class="bg-blue-50 p-6 rounded-[2rem] border border-blue-100 relative group overflow-hidden">
                        <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">Lien généré</p>
                        <p class="text-xs font-mono font-bold text-blue-800 break-all">{{ generatedLink }}</p>
                        <button 
                            @click="copyToClipboard(generatedLink)"
                            class="mt-4 w-full py-2.5 bg-white text-blue-600 rounded-xl font-black text-xs flex items-center justify-center gap-2 border border-blue-100 hover:bg-blue-600 hover:text-white transition shadow-sm"
                        >
                            <ClipboardIcon class="h-4 w-4" />
                            Copier le lien
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Application Modal -->
        <div v-if="isEditOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm">
            <div class="bg-white w-full max-w-2xl rounded-[3rem] overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
                <div class="p-8 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <h3 class="text-2xl font-black text-gray-900 tracking-tight">Modifier la Candidature</h3>
                    <button @click="isEditOpen = false" class="p-2 hover:bg-gray-100 rounded-xl transition">
                        <XMarkIcon class="h-6 w-6 text-gray-400" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="overflow-y-auto custom-scrollbar p-8 space-y-8">
                    <!-- SECTION: Identity -->
                    <div class="space-y-4">
                        <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] flex items-center gap-2">
                             <IdentificationIcon class="h-4 w-4" /> 
                             Informations Personnelles
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Nom Complet</label>
                                <input v-model="editForm.nom_complet" type="text" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5">
                                <p v-if="editForm.errors.nom_complet" class="text-red-500 text-[10px] mt-1 font-bold">{{ editForm.errors.nom_complet }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Email (Optionnel)</label>
                                <input v-model="editForm.email" type="email" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5">
                                <p v-if="editForm.errors.email" class="text-red-500 text-[10px] mt-1 font-bold">{{ editForm.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Téléphone</label>
                                <input v-model="editForm.telephone" type="tel" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5">
                                <p v-if="editForm.errors.telephone" class="text-red-500 text-[10px] mt-1 font-bold">{{ editForm.errors.telephone }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Module de Formation</label>
                                <select v-model="editForm.module_id" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5">
                                    <option v-for="m in modules" :key="m.id" :value="m.id">{{ m.titre || m.nom_module }}</option>
                                </select>
                                <p v-if="editForm.errors.module_id" class="text-red-500 text-[10px] mt-1 font-bold">{{ editForm.errors.module_id }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION: Birth -->
                    <div class="space-y-4 pt-4 border-t border-gray-100">
                        <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] flex items-center gap-2">
                             <CalendarIcon class="h-4 w-4" /> 
                             Naissance & Adresse
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Sexe <span class="text-red-500">*</span></label>
                                <select v-model="editForm.sexe" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5 appearance-none">
                                    <option value="">Choisir...</option>
                                    <option value="M">Masculin</option>
                                    <option value="F">Féminin</option>
                                </select>
                                <p v-if="editForm.errors.sexe" class="text-red-500 text-[10px] mt-1 font-bold">{{ editForm.errors.sexe }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Date de Naissance (jj/mm/aaaa)</label>
                                <input v-model="editForm.date_naissance" type="date" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5">
                                <p v-if="editForm.errors.date_naissance" class="text-red-500 text-[10px] mt-1 font-bold">{{ editForm.errors.date_naissance }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Lieu de Naissance</label>
                                <input v-model="editForm.lieu_naissance" type="text" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5">
                                <p v-if="editForm.errors.lieu_naissance" class="text-red-500 text-[10px] mt-1 font-bold">{{ editForm.errors.lieu_naissance }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Adresse Réelle</label>
                                <input v-model="editForm.adresse_reelle" type="text" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5">
                                <p v-if="editForm.errors.adresse_reelle" class="text-red-500 text-[10px] mt-1 font-bold">{{ editForm.errors.adresse_reelle }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION: Background -->
                    <div class="space-y-4 pt-4 border-t border-gray-100">
                        <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] flex items-center gap-2">
                             <AcademicCapIcon class="h-4 w-4" /> 
                             Parcours & Profession
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Niveau d'étude</label>
                                <input v-model="editForm.niveau_etude" type="text" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5">
                                <p v-if="editForm.errors.niveau_etude" class="text-red-500 text-[10px] mt-1 font-bold">{{ editForm.errors.niveau_etude }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Dernier Diplôme</label>
                                <input v-model="editForm.dernier_diplome_libelle" type="text" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5">
                                <p v-if="editForm.errors.dernier_diplome_libelle" class="text-red-500 text-[10px] mt-1 font-bold">{{ editForm.errors.dernier_diplome_libelle }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Fonction Actuelle</label>
                                <input v-model="editForm.fonction" type="text" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5">
                                <p v-if="editForm.errors.fonction" class="text-red-500 text-[10px] mt-1 font-bold">{{ editForm.errors.fonction }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Établissement (Optionnel)</label>
                                <input v-model="editForm.etablissement" type="text" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5">
                                <p v-if="editForm.errors.etablissement" class="text-red-500 text-[10px] mt-1 font-bold">{{ editForm.errors.etablissement }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 flex gap-4">
                        <button type="button" @click="isEditOpen = false" class="flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black transition">Annuler</button>
                        <button type="submit" :disabled="editForm.processing" class="flex-1 py-4 bg-blue-600 text-white rounded-2xl font-black shadow-lg shadow-blue-100 hover:bg-blue-700 transition disabled:opacity-50">Sauvegarder</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Document Preview Modal (Secondary) -->
        <div v-if="isPreviewOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gray-900/90 backdrop-blur-md">
            <div class="bg-white w-full max-w-5xl h-[90vh] rounded-[2.5rem] overflow-hidden shadow-2xl flex flex-col">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-black text-gray-900 tracking-tight capitalize">{{ previewType === 'cni' ? 'Carte d\'Identité' : 'Diplôme' }}</h3>
                        <p class="text-xs text-gray-400 font-black uppercase tracking-widest">{{ selectedApplication.nom_complet }}</p>
                    </div>
                    <button @click="closePreview" class="p-2 bg-gray-100 text-gray-500 rounded-xl hover:bg-red-600 hover:text-white transition">
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </div>
                <div class="flex-1 bg-gray-100">
                    <iframe 
                        :src="route('applications.preview', { application: selectedApplication.id, type: previewType })" 
                        class="w-full h-full border-0"
                    ></iframe>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}
</style>
