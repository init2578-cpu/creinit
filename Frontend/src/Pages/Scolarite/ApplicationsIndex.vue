<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DateInput from '@/Components/DateInput.vue'
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
    ArrowPathIcon,
    ChevronDownIcon,
    UserGroupIcon
} from '@heroicons/vue/24/outline'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    applications: Array,
    modules: Array
})

const searchQuery = ref('')
const statusFilter = ref([])
const moduleFilter = ref([])
const niveauFilter = ref([])
const docFilter = ref('all') // 'all', 'missing', 'provided'
const groupFilter = ref('all') // 'all', 'no_group', 'has_group'
const openDropdown = ref(null)

function hasGroup(app) {
    if (!app || !app.user) return false
    return Array.isArray(app.user.student_groups) && app.user.student_groups.length > 0
}

function toggleDropdown(name) {
    openDropdown.value = openDropdown.value === name ? null : name
}

function toggleStatus(val) {
    const idx = statusFilter.value.indexOf(val)
    if (idx > -1) {
        statusFilter.value.splice(idx, 1)
    } else {
        statusFilter.value.push(val)
    }
}

function toggleModule(id) {
    const numId = Number(id)
    const idx = moduleFilter.value.indexOf(numId)
    if (idx > -1) {
        moduleFilter.value.splice(idx, 1)
    } else {
        moduleFilter.value.push(numId)
    }
}

function toggleNiveau(val) {
    const idx = niveauFilter.value.indexOf(val)
    if (idx > -1) {
        niveauFilter.value.splice(idx, 1)
    } else {
        niveauFilter.value.push(val)
    }
}

const missingDocsCount = computed(() => {
    if (!props.applications) return 0
    return props.applications.filter(a => !hasUploadedDocuments(a)).length
})

const providedDocsCount = computed(() => {
    if (!props.applications) return 0
    return props.applications.filter(a => hasUploadedDocuments(a)).length
})

const admittedNoGroupCount = computed(() => {
    if (!props.applications) return 0
    return props.applications.filter(a => a.status === 'admitted' && !hasGroup(a)).length
})

function filterAdmittedNoGroup() {
    resetFilters()
    statusFilter.value = ['admitted']
    groupFilter.value = 'no_group'
}

const niveauOptions = computed(() => {
    if (!props.applications) return []
    const levels = props.applications.map(a => a.niveau_etude).filter(Boolean)
    return [...new Set(levels)].sort()
})

const hasActiveFilters = computed(() => {
    return searchQuery.value !== '' || statusFilter.value.length > 0 || moduleFilter.value.length > 0 || niveauFilter.value.length > 0 || docFilter.value !== 'all' || groupFilter.value !== 'all'
})

function resetFilters() {
    searchQuery.value = ''
    statusFilter.value = []
    moduleFilter.value = []
    niveauFilter.value = []
    docFilter.value = 'all'
    groupFilter.value = 'all'
    openDropdown.value = null
}

const statusLabel = computed(() => {
    if (statusFilter.value.length === 0) return 'Tous les statuts'
    if (statusFilter.value.length === 1) {
        const map = { pending: 'En attente', admitted: 'Admis', rejected: 'Rejeté' }
        return map[statusFilter.value[0]] || statusFilter.value[0]
    }
    return `Statuts (${statusFilter.value.length})`
})

const moduleLabel = computed(() => {
    if (moduleFilter.value.length === 0) return 'Tous les modules'
    if (moduleFilter.value.length === 1) {
        const found = props.modules?.find(m => Number(m.id) === Number(moduleFilter.value[0]))
        return found ? (found.titre || found.nom_module) : '1 module'
    }
    return `Modules (${moduleFilter.value.length})`
})

const niveauLabel = computed(() => {
    if (niveauFilter.value.length === 0) return 'Tous les niveaux'
    if (niveauFilter.value.length === 1) return niveauFilter.value[0]
    return `Niveaux (${niveauFilter.value.length})`
})

const docLabel = computed(() => {
    if (docFilter.value === 'missing') return 'Docs manquants'
    if (docFilter.value === 'provided') return 'Docs fournis'
    return 'Tous les documents'
})

const groupLabel = computed(() => {
    if (groupFilter.value === 'no_group') return 'Sans groupe'
    if (groupFilter.value === 'has_group') return 'Avec groupe'
    return 'Tous les groupes'
})

const filteredApplications = computed(() => {
    if (!props.applications) return []
    return props.applications.filter(app => {
        const name = String(app.nom_complet || (app.user ? app.user.name : '')).toLowerCase()
        const email = String(app.user ? app.user.email : '').toLowerCase()
        const phone = String(app.telephone || (app.user ? app.user.telephone : '')).toLowerCase()
        const query = searchQuery.value.toLowerCase()
        const matchesSearch = name.includes(query) || email.includes(query) || phone.includes(query)

        const matchesStatus = statusFilter.value.length === 0 || statusFilter.value.includes(app.status)
        const matchesModule = moduleFilter.value.length === 0 || moduleFilter.value.includes(Number(app.module_id))
        const matchesNiveau = niveauFilter.value.length === 0 || niveauFilter.value.includes(app.niveau_etude)
        
        const hasDocs = hasUploadedDocuments(app)
        const matchesDoc = docFilter.value === 'all' || 
            (docFilter.value === 'missing' && !hasDocs) || 
            (docFilter.value === 'provided' && hasDocs)

        const userHasGroup = hasGroup(app)
        const matchesGroup = groupFilter.value === 'all' ||
            (groupFilter.value === 'no_group' && !userHasGroup) ||
            (groupFilter.value === 'has_group' && userHasGroup)

        return matchesSearch && matchesStatus && matchesModule && matchesNiveau && matchesDoc && matchesGroup
    })
})

const selectedApplication = ref(null)
const previewType = ref(null) // 'cni' or 'diploma'
const isPreviewOpen = ref(false)
const isDetailsOpen = ref(false)
const applicationForDetails = ref(null)

// Editing
const maxBirthDate = `${new Date().getFullYear() - 6}-12-31`

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
    cni: null,
    diploma: null,
    remove_cni: false,
    remove_diploma: false,
    has_cni: false,
    has_diploma: false,
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
const isZoomed = ref(false)

const isPreviewImage = computed(() => {
    if (!selectedApplication.value || !previewType.value) return false
    let path = null
    if (previewType.value === 'cni_recto') path = selectedApplication.value.cni_recto_path || selectedApplication.value.cni_path
    else if (previewType.value === 'cni_verso') path = selectedApplication.value.cni_verso_path
    else if (previewType.value === 'other_identity_doc' || previewType.value === 'extrait') path = selectedApplication.value.other_identity_doc_path || selectedApplication.value.cni_path
    else if (previewType.value === 'cni') path = selectedApplication.value.cni_path || selectedApplication.value.cni_recto_path
    else if (previewType.value === 'diploma') path = selectedApplication.value.diploma_path

    if (!path) return false
    const ext = path.split('.').pop().toLowerCase()
    return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)
})

function openPreview(app, type) {
    selectedApplication.value = app
    previewType.value = type
    isPreviewOpen.value = true
    isZoomed.value = false
}

function closePreview() {
    isPreviewOpen.value = false
    selectedApplication.value = null
    isZoomed.value = false
}

function hasUploadedDocuments(app) {
    if (!app) return false
    const hasIdentity = !!(
        app.cni_recto_path ||
        (app.cni_path && app.cni_path !== 'manual_enrollment') ||
        app.other_identity_doc_path
    )
    const hasDiploma = !!(app.diploma_path && app.diploma_path !== 'manual_enrollment')
    return hasIdentity || hasDiploma
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
    editForm.cni = null
    editForm.diploma = null
    editForm.remove_cni = false
    editForm.remove_diploma = false
    editForm.has_cni = !!(app.cni_path && app.cni_path !== 'manual_enrollment')
    editForm.has_diploma = !!(app.diploma_path && app.diploma_path !== 'manual_enrollment')
    isEditOpen.value = true
}

function submitEdit() {
    editForm.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(route('applications.update', editForm.id), {
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
        <div class="w-full max-w-7xl mx-auto py-3 px-2 sm:px-4">
            <header class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">Admissions</h1>
                        <span class="px-2.5 py-0.5 bg-blue-50 text-blue-700 text-xs font-black rounded-full border border-blue-100 shadow-xs">
                            {{ filteredApplications.length }} {{ filteredApplications.length > 1 ? 'lignes' : 'ligne' }}
                        </span>
                        <span v-if="missingDocsCount > 0" @click="docFilter = 'missing'" class="cursor-pointer px-2.5 py-0.5 bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold rounded-full border border-rose-200/60 transition flex items-center gap-1.5 shadow-2xs" title="Cliquer pour filtrer les candidats sans documents">
                            <span class="h-2 w-2 rounded-full bg-rose-500 animate-pulse"></span>
                            {{ missingDocsCount }} sans document
                        </span>
                        <span v-if="admittedNoGroupCount > 0" @click="filterAdmittedNoGroup" class="cursor-pointer px-2.5 py-0.5 bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs font-bold rounded-full border border-amber-200/60 transition flex items-center gap-1.5 shadow-2xs" title="Cliquer pour filtrer les apprenants admis sans groupe">
                            <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                            {{ admittedNoGroupCount }} admis sans groupe
                        </span>
                    </div>
                    <p class="text-gray-500 font-medium text-xs sm:text-sm mt-0.5">Gérer et valider les dossiers d'inscription des candidats.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <button @click="isLinkGenOpen = true" class="flex items-center gap-2 px-4 py-2 bg-white text-blue-600 border border-blue-100 rounded-xl font-black text-xs sm:text-sm hover:bg-blue-50 transition shadow-xs">
                        <LinkIcon class="h-4 w-4 sm:h-5 sm:w-5" />
                        Générer un lien
                    </button>
                    <button @click="isManualEnrollOpen = true" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl font-black text-xs sm:text-sm hover:bg-blue-700 transition shadow-md shadow-blue-100">
                        <UserPlusIcon class="h-4 w-4 sm:h-5 sm:w-5" />
                        Inscrire un candidat
                    </button>
                </div>
            </header>

            <!-- Filter Bar -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-[2rem] border border-gray-100 shadow-xs mb-6 space-y-4 relative">
                <!-- Overlay to close popovers when clicking outside -->
                <div v-if="openDropdown" @click="openDropdown = null" class="fixed inset-0 z-10 opacity-0"></div>

                <div class="flex flex-wrap items-center gap-2.5 relative z-20">
                    <!-- Search Input -->
                    <div class="relative flex-1 min-w-[200px] max-w-full sm:max-w-xs">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <MagnifyingGlassIcon class="h-4 w-4" />
                        </span>
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            placeholder="Rechercher un candidat..." 
                            class="w-full bg-gray-50/80 border border-gray-200/80 rounded-xl pl-9 pr-3.5 py-2 font-semibold text-xs sm:text-sm text-gray-900 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 placeholder-gray-400 transition"
                        />
                    </div>
                    
                    <!-- Statut Filter Dropdown -->
                    <div class="relative flex-1 sm:flex-none min-w-[140px]">
                        <button 
                            @click="toggleDropdown('status')" 
                            type="button" 
                            class="w-full bg-gray-50/80 border border-gray-200/80 rounded-xl px-3.5 py-2 font-semibold text-xs sm:text-sm text-gray-800 focus:bg-white focus:border-blue-500 flex items-center justify-between gap-2 transition"
                            :class="{ 'border-blue-500 bg-blue-50/40 text-blue-700 font-bold': statusFilter.length > 0 }"
                        >
                            <span class="truncate">{{ statusLabel }}</span>
                            <ChevronDownIcon class="h-4 w-4 text-gray-400 shrink-0 transition-transform duration-150" :class="{ 'rotate-180': openDropdown === 'status' }" />
                        </button>

                        <div v-if="openDropdown === 'status'" class="absolute left-0 mt-2 w-56 bg-white border border-gray-100 rounded-2xl shadow-xl p-2 z-30 space-y-1">
                            <div @click="statusFilter = []" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-gray-50 cursor-pointer transition">
                                <input type="checkbox" :checked="statusFilter.length === 0" readonly class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-xs font-bold text-gray-700">Tous les statuts</span>
                            </div>
                            <div class="h-px bg-gray-100 my-1"></div>
                            <div @click="toggleStatus('pending')" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-amber-50/50 cursor-pointer transition">
                                <input type="checkbox" :checked="statusFilter.includes('pending')" readonly class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                <span class="text-xs font-bold text-amber-700">En attente</span>
                            </div>
                            <div @click="toggleStatus('admitted')" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-emerald-50/50 cursor-pointer transition">
                                <input type="checkbox" :checked="statusFilter.includes('admitted')" readonly class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs font-bold text-emerald-700">Admis</span>
                            </div>
                            <div @click="toggleStatus('rejected')" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-red-50/50 cursor-pointer transition">
                                <input type="checkbox" :checked="statusFilter.includes('rejected')" readonly class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                <span class="text-xs font-bold text-red-700">Rejeté</span>
                            </div>
                        </div>
                    </div>

                    <!-- Module Filter Dropdown -->
                    <div class="relative flex-1 sm:flex-none min-w-[160px]">
                        <button 
                            @click="toggleDropdown('module')" 
                            type="button" 
                            class="w-full bg-gray-50/80 border border-gray-200/80 rounded-xl px-3.5 py-2 font-semibold text-xs sm:text-sm text-gray-800 focus:bg-white focus:border-blue-500 flex items-center justify-between gap-2 transition"
                            :class="{ 'border-blue-500 bg-blue-50/40 text-blue-700 font-bold': moduleFilter.length > 0 }"
                        >
                            <span class="truncate">{{ moduleLabel }}</span>
                            <ChevronDownIcon class="h-4 w-4 text-gray-400 shrink-0 transition-transform duration-150" :class="{ 'rotate-180': openDropdown === 'module' }" />
                        </button>

                        <div v-if="openDropdown === 'module'" class="absolute left-0 mt-2 w-64 max-h-64 overflow-y-auto bg-white border border-gray-100 rounded-2xl shadow-xl p-2 z-30 space-y-1">
                            <div @click="moduleFilter = []" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-gray-50 cursor-pointer transition">
                                <input type="checkbox" :checked="moduleFilter.length === 0" readonly class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-xs font-bold text-gray-700">Tous les modules</span>
                            </div>
                            <div class="h-px bg-gray-100 my-1"></div>
                            <div v-for="m in modules" :key="m.id" @click="toggleModule(m.id)" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-purple-50/50 cursor-pointer transition">
                                <input type="checkbox" :checked="moduleFilter.includes(Number(m.id))" readonly class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                <span class="text-xs font-bold text-gray-700 truncate">{{ m.titre || m.nom_module }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Niveau d'étude Filter Dropdown -->
                    <div class="relative flex-1 sm:flex-none min-w-[140px]">
                        <button 
                            @click="toggleDropdown('niveau')" 
                            type="button" 
                            class="w-full bg-gray-50/80 border border-gray-200/80 rounded-xl px-3.5 py-2 font-semibold text-xs sm:text-sm text-gray-800 focus:bg-white focus:border-blue-500 flex items-center justify-between gap-2 transition"
                            :class="{ 'border-blue-500 bg-blue-50/40 text-blue-700 font-bold': niveauFilter.length > 0 }"
                        >
                            <span class="truncate">{{ niveauLabel }}</span>
                            <ChevronDownIcon class="h-4 w-4 text-gray-400 shrink-0 transition-transform duration-150" :class="{ 'rotate-180': openDropdown === 'niveau' }" />
                        </button>

                        <div v-if="openDropdown === 'niveau'" class="absolute left-0 mt-2 w-60 max-h-64 overflow-y-auto bg-white border border-gray-100 rounded-2xl shadow-xl p-2 z-30 space-y-1">
                            <div @click="niveauFilter = []" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-gray-50 cursor-pointer transition">
                                <input type="checkbox" :checked="niveauFilter.length === 0" readonly class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-xs font-bold text-gray-700">Tous les niveaux</span>
                            </div>
                            <div class="h-px bg-gray-100 my-1"></div>
                            <div v-for="niv in niveauOptions" :key="niv" @click="toggleNiveau(niv)" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-emerald-50/50 cursor-pointer transition">
                                <input type="checkbox" :checked="niveauFilter.includes(niv)" readonly class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs font-bold text-gray-700 truncate">{{ niv }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Filter Dropdown -->
                    <div class="relative flex-1 sm:flex-none min-w-[150px]">
                        <button 
                            @click="toggleDropdown('doc')" 
                            type="button" 
                            class="w-full bg-gray-50/80 border border-gray-200/80 rounded-xl px-3 py-2 font-semibold text-xs sm:text-sm text-gray-800 focus:bg-white focus:border-blue-500 flex items-center justify-between gap-2 transition"
                            :class="{ 'border-rose-400 bg-rose-50/50 text-rose-700 font-bold': docFilter !== 'all' }"
                        >
                            <span class="truncate">{{ docLabel }}</span>
                            <ChevronDownIcon class="h-4 w-4 text-gray-400 shrink-0 transition-transform duration-150" :class="{ 'rotate-180': openDropdown === 'doc' }" />
                        </button>

                        <div v-if="openDropdown === 'doc'" class="absolute right-0 mt-2 w-56 bg-white border border-gray-100 rounded-2xl shadow-xl p-2 z-30 space-y-1">
                            <div @click="docFilter = 'all'; openDropdown = null" class="flex items-center justify-between px-3 py-2 rounded-xl hover:bg-gray-50 cursor-pointer transition">
                                <span class="text-xs font-bold text-gray-700">Tous les documents</span>
                                <span class="text-[10px] font-extrabold text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{ applications?.length || 0 }}</span>
                            </div>
                            <div class="h-px bg-gray-100 my-1"></div>
                            <div @click="docFilter = 'missing'; openDropdown = null" class="flex items-center justify-between px-3 py-2 rounded-xl hover:bg-rose-50/60 cursor-pointer transition">
                                <span class="text-xs font-bold text-rose-700 flex items-center gap-1.5">
                                    <XCircleIcon class="h-4 w-4 text-rose-500" />
                                    Docs manquants
                                </span>
                                <span class="text-[10px] font-black text-rose-700 bg-rose-100 px-2 py-0.5 rounded-full">{{ missingDocsCount }}</span>
                            </div>
                            <div @click="docFilter = 'provided'; openDropdown = null" class="flex items-center justify-between px-3 py-2 rounded-xl hover:bg-emerald-50/60 cursor-pointer transition">
                                <span class="text-xs font-bold text-emerald-700 flex items-center gap-1.5">
                                    <CheckCircleIcon class="h-4 w-4 text-emerald-500" />
                                    Docs fournis
                                </span>
                                <span class="text-[10px] font-black text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">{{ providedDocsCount }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Groupe Filter Dropdown -->
                    <div class="relative flex-1 sm:flex-none min-w-[150px]">
                        <button 
                            @click="toggleDropdown('group')" 
                            type="button" 
                            class="w-full bg-gray-50/80 border border-gray-200/80 rounded-xl px-3 py-2 font-semibold text-xs sm:text-sm text-gray-800 focus:bg-white focus:border-blue-500 flex items-center justify-between gap-2 transition"
                            :class="{ 'border-amber-400 bg-amber-50/50 text-amber-700 font-bold': groupFilter !== 'all' }"
                        >
                            <span class="truncate">{{ groupLabel }}</span>
                            <ChevronDownIcon class="h-4 w-4 text-gray-400 shrink-0 transition-transform duration-150" :class="{ 'rotate-180': openDropdown === 'group' }" />
                        </button>

                        <div v-if="openDropdown === 'group'" class="absolute right-0 mt-2 w-60 bg-white border border-gray-100 rounded-2xl shadow-xl p-2 z-30 space-y-1">
                            <div @click="groupFilter = 'all'; openDropdown = null" class="flex items-center justify-between px-3 py-2 rounded-xl hover:bg-gray-50 cursor-pointer transition">
                                <span class="text-xs font-bold text-gray-700">Tous les groupes</span>
                                <span class="text-[10px] font-extrabold text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{ applications?.length || 0 }}</span>
                            </div>
                            <div class="h-px bg-gray-100 my-1"></div>
                            <div @click="groupFilter = 'no_group'; openDropdown = null" class="flex items-center justify-between px-3 py-2 rounded-xl hover:bg-amber-50/60 cursor-pointer transition">
                                <span class="text-xs font-bold text-amber-700 flex items-center gap-1.5">
                                    <UserGroupIcon class="h-4 w-4 text-amber-500" />
                                    Sans groupe
                                </span>
                                <span class="text-[10px] font-black text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">
                                    {{ applications?.filter(a => !hasGroup(a)).length || 0 }}
                                </span>
                            </div>
                            <div @click="groupFilter = 'has_group'; openDropdown = null" class="flex items-center justify-between px-3 py-2 rounded-xl hover:bg-emerald-50/60 cursor-pointer transition">
                                <span class="text-xs font-bold text-emerald-700 flex items-center gap-1.5">
                                    <UserGroupIcon class="h-4 w-4 text-emerald-500" />
                                    Avec groupe
                                </span>
                                <span class="text-[10px] font-black text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">
                                    {{ applications?.filter(a => hasGroup(a)).length || 0 }}
                                </span>
                            </div>
                            <div class="h-px bg-gray-100 my-1"></div>
                            <div @click="filterAdmittedNoGroup(); openDropdown = null" class="flex items-center justify-between px-3 py-2 rounded-xl hover:bg-indigo-50/60 cursor-pointer transition">
                                <span class="text-xs font-black text-indigo-700 flex items-center gap-1.5">
                                    ⚡ Admis sans groupe
                                </span>
                                <span class="text-[10px] font-black text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded-full">
                                    {{ admittedNoGroupCount }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Reset Button -->
                    <div v-if="hasActiveFilters" class="ml-auto">
                        <button 
                            @click="resetFilters"
                            title="Réinitialiser tous les filtres"
                            class="px-3 py-2 bg-red-50 text-red-600 hover:bg-red-100 border border-red-200/60 rounded-xl font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-xs"
                        >
                            <ArrowPathIcon class="h-4 w-4" />
                            <span>Effacer</span>
                        </button>
                    </div>
                </div>

                <!-- Active Filters Bar -->
                <div v-if="hasActiveFilters" class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-gray-100 relative z-20">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-black text-gray-400 uppercase tracking-wider mr-1">Filtres actifs :</span>
                        
                        <span v-if="searchQuery" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100">
                            Recherche: "{{ searchQuery }}"
                            <button @click="searchQuery = ''" class="hover:text-blue-900 transition"><XMarkIcon class="h-3.5 w-3.5" /></button>
                        </span>

                        <template v-if="statusFilter.length > 0">
                            <span v-for="st in statusFilter" :key="st" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-50 text-amber-700 text-xs font-bold border border-amber-100">
                                Statut: {{ st === 'pending' ? 'En attente' : st === 'admitted' ? 'Admis' : 'Rejeté' }}
                                <button @click="toggleStatus(st)" class="hover:text-amber-900 transition"><XMarkIcon class="h-3.5 w-3.5" /></button>
                            </span>
                        </template>

                        <template v-if="moduleFilter.length > 0">
                            <span v-for="mId in moduleFilter" :key="mId" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-purple-50 text-purple-700 text-xs font-bold border border-purple-100">
                                Module: {{ modules.find(m => Number(m.id) === Number(mId))?.titre || 'Module' }}
                                <button @click="toggleModule(mId)" class="hover:text-purple-900 transition"><XMarkIcon class="h-3.5 w-3.5" /></button>
                            </span>
                        </template>

                        <template v-if="niveauFilter.length > 0">
                            <span v-for="niv in niveauFilter" :key="niv" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-100">
                                Niveau: {{ niv }}
                                <button @click="toggleNiveau(niv)" class="hover:text-emerald-900 transition"><XMarkIcon class="h-3.5 w-3.5" /></button>
                            </span>
                        </template>

                        <span v-if="docFilter !== 'all'" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-rose-50 text-rose-700 text-xs font-bold border border-rose-100">
                            Documents: {{ docFilter === 'missing' ? 'Docs manquants' : 'Docs fournis' }}
                            <button @click="docFilter = 'all'" class="hover:text-rose-900 transition"><XMarkIcon class="h-3.5 w-3.5" /></button>
                        </span>

                        <span v-if="groupFilter !== 'all'" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-50 text-amber-700 text-xs font-bold border border-amber-100">
                            Groupe: {{ groupFilter === 'no_group' ? 'Sans groupe' : 'Avec groupe' }}
                            <button @click="groupFilter = 'all'" class="hover:text-amber-900 transition"><XMarkIcon class="h-3.5 w-3.5" /></button>
                        </span>
                    </div>

                    <button @click="resetFilters" class="text-xs font-bold text-red-500 hover:text-red-700 underline ml-auto transition">
                        Tout réinitialiser
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
                            <tr v-for="app in filteredApplications" :key="app.id" class="hover:bg-slate-50/80 transition-colors border-b border-gray-50/80">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center overflow-hidden font-black border border-blue-100/50 shrink-0">
                                            <img v-if="app.user?.profile_photo_url" :src="app.user.profile_photo_url" class="h-full w-full object-cover">
                                            <template v-else>{{ (app.nom_complet || (app.user ? app.user.name : 'N/A')).charAt(0) }}</template>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900 text-sm leading-tight">{{ app.nom_complet || (app.user ? app.user.name : 'N/A') }}</p>
                                            <div class="flex flex-wrap items-center gap-2 mt-0.5">
                                                <span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">{{ app.user ? app.user.email : 'Candidat public' }}</span>
                                                <span v-if="app.niveau_etude" class="text-[9px] font-extrabold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100">
                                                    {{ app.niveau_etude }}
                                                </span>
                                                <span v-if="hasGroup(app)" class="inline-flex items-center gap-1 text-[9px] font-extrabold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-100" title="Affecté au groupe">
                                                    <UserGroupIcon class="h-3 w-3 text-emerald-600" />
                                                    {{ app.user.student_groups.map(g => g.nom_groupe || g.nom).join(', ') }}
                                                </span>
                                                <span v-else-if="app.status === 'admitted'" class="inline-flex items-center gap-1 text-[9px] font-extrabold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200/80" title="Admis sans groupe">
                                                    <UserGroupIcon class="h-3 w-3 text-amber-600" />
                                                    Sans groupe
                                                </span>
                                                <span v-if="!hasUploadedDocuments(app)" class="inline-flex items-center gap-1 text-[9px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-100">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                                    Doc manquant
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span v-if="app.module" class="px-3 py-1 bg-blue-50 text-blue-700 text-xs font-bold rounded-lg border border-blue-100 italic truncate max-w-[150px] inline-block">
                                        {{ app.module?.titre || app.module?.nom_module }}
                                    </span>
                                    <span v-else class="text-[10px] text-gray-400 font-bold italic">N/A (Module non défini)</span>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium">
                                    <span v-if="hasUploadedDocuments(app)" class="px-3 py-1.5 bg-emerald-50/80 text-emerald-700 text-xs font-bold rounded-xl border border-emerald-200/60 inline-flex items-center gap-1.5 shadow-2xs">
                                        <CheckCircleIcon class="h-4 w-4 text-emerald-600 shrink-0" />
                                        {{ app.cni_path === 'manual_enrollment' ? 'Docs partiellement fournis' : '2 Documents fournis' }}
                                    </span>
                                    <span v-else class="px-3 py-1.5 bg-rose-50/90 text-rose-700 text-xs font-bold rounded-xl border border-rose-200/80 inline-flex items-center gap-1.5 shadow-2xs">
                                        <XCircleIcon class="h-4 w-4 text-rose-600 shrink-0" />
                                        Non fournis
                                    </span>
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
                        <div class="flex flex-wrap gap-4">
                            <!-- SI AVEC CNI OU ANCIEN STOCKAGE -->
                            <template v-if="applicationForDetails.has_cni !== false && (applicationForDetails.cni_recto_path || applicationForDetails.cni_path)">
                                <button 
                                    v-if="applicationForDetails.cni_recto_path || (applicationForDetails.cni_path && applicationForDetails.cni_path !== 'manual_enrollment')" 
                                    @click="openPreview(applicationForDetails, applicationForDetails.cni_recto_path ? 'cni_recto' : 'cni')"
                                    class="flex-1 min-w-[140px] p-4 bg-white border border-gray-200 rounded-2xl flex items-center justify-between hover:border-blue-600 transition group"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 bg-gray-100 text-gray-500 rounded-lg flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition">
                                            <DocumentIcon class="h-5 w-5" />
                                        </div>
                                        <span class="text-xs font-black text-gray-900 uppercase">CNI (Recto)</span>
                                    </div>
                                    <EyeIcon class="h-4 w-4 text-gray-300 group-hover:text-blue-600" />
                                </button>
                                <button 
                                    v-if="applicationForDetails.cni_verso_path" 
                                    @click="openPreview(applicationForDetails, 'cni_verso')"
                                    class="flex-1 min-w-[140px] p-4 bg-white border border-gray-200 rounded-2xl flex items-center justify-between hover:border-blue-600 transition group"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 bg-gray-100 text-gray-500 rounded-lg flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition">
                                            <DocumentIcon class="h-5 w-5" />
                                        </div>
                                        <span class="text-xs font-black text-gray-900 uppercase">CNI (Verso)</span>
                                    </div>
                                    <EyeIcon class="h-4 w-4 text-gray-300 group-hover:text-blue-600" />
                                </button>
                            </template>
                            <!-- SI SANS CNI (EXTRAIT / AUTRE PIECE) -->
                            <template v-else-if="applicationForDetails.other_identity_doc_path || applicationForDetails.has_cni === false">
                                <button 
                                    v-if="applicationForDetails.other_identity_doc_path || (applicationForDetails.cni_path && applicationForDetails.cni_path !== 'manual_enrollment')"
                                    @click="openPreview(applicationForDetails, 'other_identity_doc')"
                                    class="flex-1 min-w-[180px] p-4 bg-white border border-gray-200 rounded-2xl flex items-center justify-between hover:border-blue-600 transition group"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 bg-gray-100 text-gray-500 rounded-lg flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition">
                                            <DocumentIcon class="h-5 w-5" />
                                        </div>
                                        <span class="text-xs font-black text-gray-900 uppercase">Extrait / Pièce d'identité</span>
                                    </div>
                                    <EyeIcon class="h-4 w-4 text-gray-300 group-hover:text-blue-600" />
                                </button>
                            </template>
                            <button 
                                v-if="applicationForDetails.diploma_path && applicationForDetails.diploma_path !== 'manual_enrollment'" 
                                @click="openPreview(applicationForDetails, 'diploma')"
                                class="flex-1 min-w-[140px] p-4 bg-white border border-gray-200 rounded-2xl flex items-center justify-between hover:border-blue-600 transition group"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 bg-gray-100 text-gray-500 rounded-lg flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition">
                                        <DocumentIcon class="h-5 w-5" />
                                    </div>
                                    <span class="text-xs font-black text-gray-900 uppercase">Scan Diplôme</span>
                                </div>
                                <EyeIcon class="h-4 w-4 text-gray-300 group-hover:text-blue-600" />
                            </button>
                            <div v-if="!hasUploadedDocuments(applicationForDetails)" class="w-full text-center py-4 bg-red-50 rounded-2xl border border-dashed border-red-200 text-red-600 text-xs font-black flex items-center justify-center gap-2">
                                <XCircleIcon class="h-5 w-5 text-red-600" />
                                <span>Aucun document téléversé (Docs non fournis)</span>
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
                                    <DateInput :max-date="maxBirthDate" v-model="enrollForm.date_naissance" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5" />
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
                                <DateInput :max-date="maxBirthDate" v-model="editForm.date_naissance" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5" />
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

                    <!-- SECTION: Documents -->
                    <div class="space-y-4 pt-4 border-t border-gray-100">
                        <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] flex items-center gap-2">
                             <DocumentIcon class="h-4 w-4" /> 
                             Documents Associés
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-4 bg-gray-50 rounded-2xl border border-dashed border-gray-200 hover:border-blue-300 transition">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Mettre à jour CNI <span class="text-gray-300 text-[9px]">(Optionnel)</span></label>
                                <input @input="editForm.cni = $event.target.files[0]" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs font-bold text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-blue-600 file:text-white hover:file:bg-blue-700 file:cursor-pointer file:transition cursor-pointer" :disabled="editForm.remove_cni">
                                <p class="text-[9px] text-gray-400 mt-1.5">PDF, JPG ou PNG · max 5 Mo. Laissez vide pour conserver l'actuel.</p>
                                <p v-if="editForm.errors.cni" class="text-red-500 text-[10px] mt-1 font-bold">{{ editForm.errors.cni }}</p>
                                <div v-if="editForm.has_cni" class="mt-3 flex items-center gap-2 bg-white p-2 rounded-xl border border-gray-200">
                                    <input type="checkbox" id="remove_cni" v-model="editForm.remove_cni" class="rounded border-gray-300 text-red-600 focus:ring-red-500 cursor-pointer">
                                    <label for="remove_cni" class="text-xs font-bold text-red-600 cursor-pointer select-none">Supprimer le document actuel</label>
                                </div>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-2xl border border-dashed border-gray-200 hover:border-blue-300 transition">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Mettre à jour Diplôme <span class="text-gray-300 text-[9px]">(Optionnel)</span></label>
                                <input @input="editForm.diploma = $event.target.files[0]" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs font-bold text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-blue-600 file:text-white hover:file:bg-blue-700 file:cursor-pointer file:transition cursor-pointer" :disabled="editForm.remove_diploma">
                                <p class="text-[9px] text-gray-400 mt-1.5">PDF, JPG ou PNG · max 5 Mo. Laissez vide pour conserver l'actuel.</p>
                                <p v-if="editForm.errors.diploma" class="text-red-500 text-[10px] mt-1 font-bold">{{ editForm.errors.diploma }}</p>
                                <div v-if="editForm.has_diploma" class="mt-3 flex items-center gap-2 bg-white p-2 rounded-xl border border-gray-200">
                                    <input type="checkbox" id="remove_diploma" v-model="editForm.remove_diploma" class="rounded border-gray-300 text-red-600 focus:ring-red-500 cursor-pointer">
                                    <label for="remove_diploma" class="text-xs font-bold text-red-600 cursor-pointer select-none">Supprimer le document actuel</label>
                                </div>
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
                        <h3 class="text-xl font-black text-gray-900 tracking-tight capitalize">{{ previewType === 'cni_recto' ? 'Carte d\'Identité (Recto)' : previewType === 'cni_verso' ? 'Carte d\'Identité (Verso)' : (previewType === 'other_identity_doc' || previewType === 'extrait') ? 'Extrait / Pièce d\'Identité' : previewType === 'cni' ? 'Carte d\'Identité' : 'Diplôme' }}</h3>
                        <p class="text-xs text-gray-400 font-black uppercase tracking-widest">{{ selectedApplication.nom_complet }}</p>
                    </div>
                    <button @click="closePreview" class="p-2 bg-gray-100 text-gray-500 rounded-xl hover:bg-red-600 hover:text-white transition">
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </div>
                <div class="flex-1 bg-gray-100 overflow-hidden flex flex-col min-h-0">
                    <div v-if="isPreviewImage" class="flex-1 bg-gray-900 overflow-auto custom-scrollbar flex items-center justify-center p-6 relative">
                        <button 
                            type="button"
                            @click="isZoomed = !isZoomed"
                            class="absolute bottom-6 right-6 z-10 px-4 py-2 bg-white/90 backdrop-blur text-gray-800 rounded-xl font-black text-xs shadow-lg hover:bg-white transition flex items-center gap-1.5"
                        >
                            <MagnifyingGlassIcon class="h-4 w-4" />
                            {{ isZoomed ? 'Ajuster à l\'écran' : 'Taille réelle' }}
                        </button>
                        <img 
                            :src="route('applications.preview', { application: selectedApplication.id, type: previewType })" 
                            :class="[
                                isZoomed ? 'max-w-none max-h-none' : 'max-w-full max-h-full object-contain',
                                'rounded-2xl shadow-2xl transition-all duration-300'
                            ]"
                            alt="Aperçu du document"
                        />
                    </div>
                    <iframe 
                        v-else
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
