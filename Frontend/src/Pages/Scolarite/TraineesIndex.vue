<script setup>
import { ref, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { 
    AcademicCapIcon, 
    EnvelopeIcon, 
    ChevronRightIcon,
    MagnifyingGlassIcon,
    UserGroupIcon,
    EyeIcon,
    PencilSquareIcon,
    TrashIcon,
    PhoneIcon,
    MapPinIcon,
    XMarkIcon,
    PlusIcon,
    IdentificationIcon,
    GlobeAltIcon,
    BriefcaseIcon,
    CheckCircleIcon,
    ClipboardDocumentCheckIcon,
    CalendarIcon,
    UserIcon,
    StarIcon,
    DocumentArrowDownIcon,
    DocumentTextIcon,
    ArrowDownTrayIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    trainees: Array
})

// Modals state
const isViewModalOpen = ref(false)
const isFormModalOpen = ref(false)
const selectedTrainee = ref(null)
const editingTrainee = ref(null)
const activeTab = ref('id') // 'id', 'internship', 'dossier'

// Form state
const traineeForm = useForm({
    name: '',
    email: '',
    password: '',
    telephone: '',
    adresse: '',
    is_active: true,
    // Internship fields
    internship_type: 'management_assistant',
    criteria: '',
    start_date: '',
    end_date: '',
    status: 'pending',
    // Documents
    motivation_letter: null,
    cni: null,
    cv: null,
    diploma: null,
})

// Search state
const searchQuery = ref('')
const filteredTrainees = computed(() => {
    if (!searchQuery.value) return props.trainees
    const query = searchQuery.value.toLowerCase()
    return props.trainees.filter(t => 
        t.name.toLowerCase().includes(query) || 
        t.email.toLowerCase().includes(query)
    )
})

// Actions
const isPreviewOpen = ref(false)
const previewTrainee = ref(null)
const previewType = ref(null)

function openPreview(trainee, type) {
    previewTrainee.value = trainee
    previewType.value = type
    isPreviewOpen.value = true
}

function closePreview() {
    isPreviewOpen.value = false
    previewTrainee.value = null
    previewType.value = null
}

function openViewModal(trainee) {
    selectedTrainee.value = trainee
    isViewModalOpen.value = true
}

function closeViewModal() {
    isViewModalOpen.value = false
    selectedTrainee.value = null
}

function openCreateModal() {
    editingTrainee.value = null
    traineeForm.reset()
    activeTab.value = 'id'
    isFormModalOpen.value = true
}

function openEditModal(trainee) {
    editingTrainee.value = trainee
    traineeForm.name = trainee.name
    traineeForm.email = trainee.email
    traineeForm.password = ''
    traineeForm.telephone = trainee.telephone || ''
    traineeForm.adresse = trainee.adresse || ''
    traineeForm.is_active = !!trainee.is_active
    
    if (trainee.internship) {
        traineeForm.internship_type = trainee.internship.type || 'management_assistant'
        traineeForm.criteria = trainee.internship.criteria || ''
        traineeForm.start_date = trainee.internship.start_date || ''
        traineeForm.end_date = trainee.internship.end_date || ''
        traineeForm.status = trainee.internship.status || 'pending'
    } else {
        traineeForm.internship_type = 'management_assistant'
        traineeForm.criteria = ''
        traineeForm.start_date = ''
        traineeForm.end_date = ''
        traineeForm.status = 'pending'
    }
    
    activeTab.value = 'id'
    isFormModalOpen.value = true
}

function closeFormModal() {
    isFormModalOpen.value = false
    editingTrainee.value = null
    traineeForm.reset()
}

function submitForm() {
    if (editingTrainee.value) {
        // Inertia requirement for multipart updates: Use POST with _method: 'PUT'
        traineeForm.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('trainees.update', editingTrainee.value.id), {
            onSuccess: () => closeFormModal(),
            forceFormData: true,
        })
    } else {
        traineeForm.post(route('trainees.store'), {
            onSuccess: () => closeFormModal(),
        })
    }
}

function deleteTrainee(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce stagiaire ? Cette action est irréversible.')) {
        router.delete(route('trainees.destroy', id))
    }
}

function getInternshipTypeLabel(type) {
    switch(type) {
        case 'course_assistant': return 'Assistant de Cours'
        case 'course_substitute': return 'Suppléant Formateur'
        case 'management_assistant': return 'Assistant de Gestion'
        case 'secretary_assistant': return 'Assistant Secrétariat'
        case 'director_assistant': return 'Assistant de Direction'
        case 'field_internship': return 'Stage sur le terrain'
        default: return type
    }
}

function getStatusLabel(status) {
    switch(status) {
        case 'pending': return 'En attente'
        case 'active': return 'Actif'
        case 'completed': return 'Terminé'
        case 'terminated': return 'Interrompu'
        default: return status
    }
}

function getStatusClass(status) {
    switch(status) {
        case 'active': return 'bg-green-50 text-green-600'
        case 'completed': return 'bg-blue-50 text-blue-600'
        case 'terminated': return 'bg-red-50 text-red-600'
        case 'pending': return 'bg-amber-50 text-amber-600'
        default: return 'bg-gray-50 text-gray-600'
    }
}
</script>

<template>
    <Head title="Gestion des Stagiaires (Assistants)" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-8 px-4">
            <header class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight">Gestion des Stagiaires</h1>
                    <p class="text-gray-500 font-medium">Suivi des assistants et gestion du dossier administratif.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative group">
                        <MagnifyingGlassIcon class="h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-blue-600 transition-colors" />
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Rechercher un assistant..." 
                            class="pl-12 pr-6 py-3 bg-white border-2 border-transparent rounded-2xl shadow-sm focus:ring-0 focus:border-blue-600 w-64 font-bold text-sm transition-all"
                        >
                    </div>
                    <button 
                        @click="openCreateModal"
                        class="flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-2xl font-black text-sm hover:bg-indigo-700 hover:shadow-lg hover:shadow-indigo-200 transition-all active:scale-95"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Nouvel Assistant
                    </button>
                </div>
            </header>

            <!-- Table Section -->
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-black tracking-widest">
                            <th class="px-8 py-4">Assistant</th>
                            <th class="px-8 py-4">Type d'Assistanat</th>
                            <th class="px-8 py-4">Période</th>
                            <th class="px-8 py-4 text-center">Statut</th>
                            <th class="px-8 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="trainee in filteredTrainees" :key="trainee.id" class="hover:bg-gray-50/50 transition-colors group">
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center overflow-hidden font-black text-lg">
                                        <img v-if="trainee.profile_photo_url" :src="trainee.profile_photo_url" class="h-full w-full object-cover">
                                        <template v-else>{{ trainee.name.charAt(0) }}</template>
                                    </div>
                                    <div>
                                        <p class="font-black text-gray-900 leading-tight">{{ trainee.name }}</p>
                                        <p class="text-[10px] text-gray-400 font-bold">{{ trainee.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <div v-if="trainee.internship" class="flex flex-col">
                                    <span class="text-sm font-black text-gray-700">{{ getInternshipTypeLabel(trainee.internship.type) }}</span>
                                    <span class="text-[10px] text-gray-400 font-bold italic line-clamp-1 max-w-[200px]">{{ trainee.internship.criteria }}</span>
                                </div>
                                <span v-else class="text-xs text-gray-300 italic font-bold">Non configuré</span>
                            </td>
                            <td class="px-8 py-5">
                                <div v-if="trainee.internship && trainee.internship.start_date" class="flex flex-col gap-1">
                                    <div class="flex items-center gap-1.5 text-[10px] text-gray-500 font-bold uppercase tracking-wider">
                                        <CalendarIcon class="h-3 w-3" />
                                        {{ trainee.internship.start_date }}
                                    </div>
                                    <div v-if="trainee.internship.end_date" class="flex items-center gap-1.5 text-[10px] text-gray-300 font-bold uppercase tracking-wider">
                                        <ChevronRightIcon class="h-3 w-3" />
                                        {{ trainee.internship.end_date }}
                                    </div>
                                </div>
                                <span v-else class="text-[10px] text-gray-300 font-black italic">Date indéfinie</span>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider ring-1 ring-inset"
                                    :class="getStatusClass(trainee.internship?.status)"
                                >
                                    {{ getStatusLabel(trainee.internship?.status || 'pending') }}
                                </span>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <div class="flex justify-end gap-2 translate-x-4 opacity-0 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
                                    <button 
                                        @click="openViewModal(trainee)"
                                        class="p-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-600 hover:text-white transition-all shadow-sm"
                                        title="Voir Dossier"
                                    >
                                        <EyeIcon class="h-5 w-5" />
                                    </button>
                                    <button 
                                        @click="openEditModal(trainee)"
                                        class="p-2 bg-amber-50 text-amber-600 rounded-xl hover:bg-amber-600 hover:text-white transition-all shadow-sm"
                                        title="Modifier"
                                    >
                                        <PencilSquareIcon class="h-5 w-5" />
                                    </button>
                                    <button 
                                        @click="deleteTrainee(trainee.id)"
                                        class="p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm"
                                        title="Supprimer"
                                    >
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredTrainees.length === 0">
                            <td colspan="5" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <UserGroupIcon class="h-12 w-12 text-gray-200" />
                                    <p class="text-gray-400 font-black tracking-tight">Aucun assistant trouvé.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Form Modal (Create/Edit) -->
        <div v-if="isFormModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white w-full max-w-2xl rounded-[2.5rem] shadow-2xl relative overflow-hidden flex flex-col max-h-[90vh]">
                <header class="p-8 border-b border-gray-100 flex items-center justify-between shrink-0">
                    <div>
                        <h2 class="text-2xl font-black text-gray-900 tracking-tight">
                            {{ editingTrainee ? 'Modifier l\'Assistant' : 'Nouvel Assistant' }}
                        </h2>
                        <p class="text-gray-500 text-sm font-medium">Gestion du compte et du dossier administratif.</p>
                    </div>
                    <button @click="closeFormModal" class="p-2 text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-xl transition-all">
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </header>

                <!-- Tabs -->
                <div class="px-8 py-4 flex items-center gap-6 border-b border-gray-50 shrink-0 bg-gray-50/30">
                    <button 
                        @click="activeTab = 'id'"
                        class="flex items-center gap-2 pb-2 border-b-2 transition-all font-black text-[10px] uppercase tracking-widest"
                        :class="activeTab === 'id' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
                    >
                        <UserIcon class="h-4 w-4" />
                        Identité
                    </button>
                    <button 
                        @click="activeTab = 'internship'"
                        class="flex items-center gap-2 pb-2 border-b-2 transition-all font-black text-[10px] uppercase tracking-widest"
                        :class="activeTab === 'internship' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
                    >
                        <ClipboardDocumentCheckIcon class="h-4 w-4" />
                        Fiche de Stage
                    </button>
                    <button 
                        @click="activeTab = 'dossier'"
                        class="flex items-center gap-2 pb-2 border-b-2 transition-all font-black text-[10px] uppercase tracking-widest"
                        :class="activeTab === 'dossier' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
                    >
                        <DocumentTextIcon class="h-4 w-4" />
                        Dossier Documents
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto p-8">
                    <!-- Tab: Identité -->
                    <div v-show="activeTab === 'id'" class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Nom complet</label>
                                <input v-model="traineeForm.name" type="text" class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-indigo-600 transition-all outline-none" required>
                                <div v-if="traineeForm.errors.name" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ traineeForm.errors.name }}</div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Email professionnel</label>
                                <input v-model="traineeForm.email" type="email" class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-indigo-600 transition-all outline-none" required>
                                <div v-if="traineeForm.errors.email" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ traineeForm.errors.email }}</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Mot de passe</label>
                                <input v-model="traineeForm.password" type="password" class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-indigo-600 transition-all outline-none" :required="!editingTrainee" :placeholder="editingTrainee ? 'Laisser vide pour ne pas changer' : ''">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Téléphone</label>
                                <input v-model="traineeForm.telephone" type="text" class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-indigo-600 transition-all outline-none">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Adresse de résidence</label>
                            <input v-model="traineeForm.adresse" type="text" class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-indigo-600 transition-all outline-none">
                        </div>
                        <div v-if="editingTrainee" class="flex items-center gap-3 p-4 bg-gray-50 rounded-2xl mt-4">
                            <input type="checkbox" v-model="traineeForm.is_active" id="is_active_t" class="h-6 w-6 text-indigo-600 rounded-lg border-gray-200 focus:ring-indigo-600">
                            <label for="is_active_t" class="text-sm font-black text-gray-700">Autoriser l'accès à la plateforme</label>
                        </div>
                    </div>

                    <!-- Tab: Internship Record -->
                    <div v-show="activeTab === 'internship'" class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Type d'Assistanat</label>
                                <select v-model="traineeForm.internship_type" class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-indigo-600 appearance-none transition-all outline-none">
                                    <option value="course_assistant">Assistant de Cours</option>
                                    <option value="course_substitute">Suppléant Formateur (Salle de cours)</option>
                                    <option value="management_assistant">Assistant de Gestion</option>
                                    <option value="secretary_assistant">Assistant au Secrétariat</option>
                                    <option value="director_assistant">Assistant au Directeur</option>
                                    <option value="field_internship">Stage sur le Terrain (avec Formateur)</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Statut du Dossier</label>
                                <select v-model="traineeForm.status" class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-indigo-600 appearance-none transition-all outline-none">
                                    <option value="pending">En attente (Documents)</option>
                                    <option value="active">Actif (En poste)</option>
                                    <option value="completed">Stage Terminé</option>
                                    <option value="terminated">Contrat Interrompu</option>
                                </select>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Missions & Objectifs (Fiche de stage)</label>
                            <textarea v-model="traineeForm.criteria" placeholder="Détaillez les tâches confiées à l'assistant..." class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-indigo-600 min-h-[140px] transition-all outline-none"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Date de début</label>
                                <input v-model="traineeForm.start_date" type="date" class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-indigo-600 transition-all outline-none">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Date de fin prévue</label>
                                <input v-model="traineeForm.end_date" type="date" class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-indigo-600 transition-all outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Dossier Documents -->
                    <div v-show="activeTab === 'dossier'" class="space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
                        <div class="p-4 bg-indigo-50/50 rounded-2xl border border-indigo-100 flex items-start gap-3">
                            <IdentificationIcon class="h-6 w-6 text-indigo-600 shrink-0" />
                            <p class="text-xs text-indigo-700 font-bold leading-relaxed">
                                Veuillez téléverser les documents constitutifs du dossier. Formats supportés : PDF, JPG, PNG (Max 5Mo par fichier).
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Lettre de motivation -->
                            <div class="space-y-2 p-6 bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200 hover:border-indigo-300 transition-all group">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Lettre de Motivation</label>
                                <input type="file" @input="traineeForm.motivation_letter = $event.target.files[0]" class="hidden" id="file_ml">
                                <label for="file_ml" class="flex flex-col items-center gap-2 cursor-pointer py-4">
                                    <DocumentTextIcon class="h-8 w-8 text-gray-300 group-hover:text-indigo-500 transition-colors" />
                                    <span class="text-xs font-black text-gray-500 group-hover:text-indigo-700 transition-colors">
                                        {{ traineeForm.motivation_letter ? traineeForm.motivation_letter.name : 'Choisir un fichier' }}
                                    </span>
                                </label>
                            </div>

                            <!-- CNI -->
                            <div class="space-y-2 p-6 bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200 hover:border-indigo-300 transition-all group">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Carte d'Identité (CNI)</label>
                                <input type="file" @input="traineeForm.cni = $event.target.files[0]" class="hidden" id="file_cni">
                                <label for="file_cni" class="flex flex-col items-center gap-2 cursor-pointer py-4">
                                    <IdentificationIcon class="h-8 w-8 text-gray-300 group-hover:text-indigo-500 transition-colors" />
                                    <span class="text-xs font-black text-gray-500 group-hover:text-indigo-700 transition-colors">
                                        {{ traineeForm.cni ? traineeForm.cni.name : 'Choisir un fichier' }}
                                    </span>
                                </label>
                            </div>

                            <!-- CV -->
                            <div class="space-y-2 p-6 bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200 hover:border-indigo-300 transition-all group">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Curriculum Vitae (CV)</label>
                                <input type="file" @input="traineeForm.cv = $event.target.files[0]" class="hidden" id="file_cv">
                                <label for="file_cv" class="flex flex-col items-center gap-2 cursor-pointer py-4">
                                    <DocumentArrowDownIcon class="h-8 w-8 text-gray-300 group-hover:text-indigo-500 transition-colors" />
                                    <span class="text-xs font-black text-gray-500 group-hover:text-indigo-700 transition-colors">
                                        {{ traineeForm.cv ? traineeForm.cv.name : 'Choisir un fichier' }}
                                    </span>
                                </label>
                            </div>

                            <!-- Diplômes -->
                            <div class="space-y-2 p-6 bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200 hover:border-indigo-300 transition-all group">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Diplômes & Certificats</label>
                                <input type="file" @input="traineeForm.diploma = $event.target.files[0]" class="hidden" id="file_dip">
                                <label for="file_dip" class="flex flex-col items-center gap-2 cursor-pointer py-4">
                                    <AcademicCapIcon class="h-8 w-8 text-gray-300 group-hover:text-indigo-500 transition-colors" />
                                    <span class="text-xs font-black text-gray-500 group-hover:text-indigo-700 transition-colors">
                                        {{ traineeForm.diploma ? traineeForm.diploma.name : 'Choisir un fichier' }}
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div v-if="traineeForm.progress" class="mt-4 p-4 bg-gray-50 rounded-2xl relative overflow-hidden">
                            <div class="h-1 bg-indigo-600 absolute bottom-0 left-0 transition-all duration-300" :style="{ width: traineeForm.progress.percentage + '%' }"></div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Envoi en cours... {{ traineeForm.progress.percentage }}%</p>
                        </div>
                    </div>
                </form>

                <footer class="p-8 border-t border-gray-100 bg-gray-50/50 flex items-center justify-end gap-4 shrink-0">
                    <button @click="closeFormModal" class="px-6 py-3 text-gray-500 font-black text-sm uppercase tracking-widest hover:text-gray-900 transition-colors">Annuler</button>
                    <button 
                        @click="submitForm"
                        :disabled="traineeForm.processing"
                        class="px-10 py-3 bg-indigo-600 text-white rounded-2xl font-black text-sm shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center gap-2 disabled:opacity-50"
                    >
                        <CheckCircleIcon v-if="!traineeForm.processing" class="h-5 w-5" />
                        <div v-else class="h-5 w-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                        {{ editingTrainee ? 'Mettre à jour le dossier' : 'Créer l\'assistant' }}
                    </button>
                </footer>
            </div>
        </div>

        <!-- Trainee Details View Modal -->
        <div v-if="isViewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white w-full max-w-xl rounded-[3rem] shadow-2xl relative overflow-hidden flex flex-col max-h-[95vh]">
                <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800"></div>
                <button @click="closeViewModal" class="absolute right-8 top-8 p-3 text-white/50 hover:text-white hover:bg-white/10 rounded-[1.25rem] transition-all z-10">
                    <XMarkIcon class="h-7 w-7" />
                </button>

                <div class="relative px-8 pt-12 pb-6 flex flex-col items-center shrink-0">
                    <div class="h-32 w-32 bg-white rounded-[2.5rem] shadow-2xl flex items-center justify-center overflow-hidden text-4xl font-black text-indigo-600 border-[6px] border-white mb-6 transform hover:rotate-3 transition-transform">
                        <img v-if="selectedTrainee.profile_photo_url" :src="selectedTrainee.profile_photo_url" class="h-full w-full object-cover">
                        <template v-else>{{ selectedTrainee.name.charAt(0) }}</template>
                    </div>
                    <h2 class="text-3xl font-black text-gray-900 tracking-tight leading-none mb-2">{{ selectedTrainee.name }}</h2>
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-indigo-50 text-indigo-700 rounded-full text-[10px] font-black uppercase tracking-widest">
                        <BriefcaseIcon class="h-3.5 w-3.5" />
                        {{ getInternshipTypeLabel(selectedTrainee.internship?.type) }}
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto px-10 pb-10 space-y-8">
                    <!-- Missions & Criteria -->
                    <div class="p-8 bg-gray-50/80 rounded-[2.5rem] border border-gray-100 relative group overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-10">
                            <StarIcon class="h-16 w-16 text-indigo-600" />
                        </div>
                        <div class="flex items-center gap-2 text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-4">
                            <ClipboardDocumentCheckIcon class="h-4 w-4" />
                            Missions & Objectifs de Stage
                        </div>
                        <p class="text-gray-600 font-bold italic leading-relaxed relative z-10">
                            "{{ selectedTrainee.internship?.criteria || 'Aucune mission spécifique n\'a encore été définie dans la fiche de stage.' }}"
                        </p>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-2 gap-8">
                        <div class="space-y-1">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Statut du Stage</p>
                            <span class="inline-flex items-center px-4 py-1.5 rounded-2xl text-[10px] font-black uppercase tracking-wider ring-1 ring-inset" :class="getStatusClass(selectedTrainee.internship?.status)">
                                {{ getStatusLabel(selectedTrainee.internship?.status || 'Active') }}
                            </span>
                        </div>
                        <div class="space-y-1 text-right">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mr-1">Période du Stage</p>
                            <div class="flex flex-col text-xs font-black text-gray-700">
                                <span>Du {{ selectedTrainee.internship?.start_date || '...' }}</span>
                                <span class="text-gray-400">Au {{ selectedTrainee.internship?.end_date || '...' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Dossier Numérique (The new parts) -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-black text-gray-900 uppercase tracking-widest italic ml-1 flex items-center gap-2">
                                <IdentificationIcon class="h-4 w-4 text-indigo-600" />
                                Dossier Numérique
                            </h3>
                            <span class="text-[10px] font-bold text-gray-400">4 documents requis</span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Motivation Letter View -->
                            <div class="p-4 bg-white border border-gray-100 rounded-3xl flex items-center justify-between group hover:border-indigo-200 transition-all shadow-sm">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div class="p-2 bg-blue-50 text-blue-600 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition-colors shrink-0">
                                        <DocumentTextIcon class="h-5 w-5" />
                                    </div>
                                    <span class="text-[10px] font-black text-gray-600 uppercase tracking-widest truncate">Motivation</span>
                                </div>
                                <button v-if="selectedTrainee.internship?.motivation_letter" @click="openPreview(selectedTrainee, 'motivation_letter')" class="p-2 text-gray-300 hover:text-indigo-600 transition-colors" title="Visualiser">
                                    <EyeIcon class="h-5 w-5" />
                                </button>
                                <span v-else class="text-[10px] font-bold text-red-300 italic">Manquant</span>
                            </div>

                            <!-- CNI View -->
                            <div class="p-4 bg-white border border-gray-100 rounded-3xl flex items-center justify-between group hover:border-indigo-200 transition-all shadow-sm">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div class="p-2 bg-purple-50 text-purple-600 rounded-xl group-hover:bg-purple-600 group-hover:text-white transition-colors shrink-0">
                                        <IdentificationIcon class="h-5 w-5" />
                                    </div>
                                    <span class="text-[10px] font-black text-gray-600 uppercase tracking-widest truncate">CNI</span>
                                </div>
                                <button v-if="selectedTrainee.internship?.cni" @click="openPreview(selectedTrainee, 'cni')" class="p-2 text-gray-300 hover:text-indigo-600 transition-colors" title="Visualiser">
                                    <EyeIcon class="h-5 w-5" />
                                </button>
                                <span v-else class="text-[10px] font-bold text-red-300 italic">Manquant</span>
                            </div>

                            <!-- CV View -->
                            <div class="p-4 bg-white border border-gray-100 rounded-3xl flex items-center justify-between group hover:border-indigo-200 transition-all shadow-sm">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div class="p-2 bg-emerald-50 text-emerald-600 rounded-xl group-hover:bg-emerald-600 group-hover:text-white transition-colors shrink-0">
                                        <DocumentArrowDownIcon class="h-5 w-5" />
                                    </div>
                                    <span class="text-[10px] font-black text-gray-600 uppercase tracking-widest truncate">CV</span>
                                </div>
                                <button v-if="selectedTrainee.internship?.cv" @click="openPreview(selectedTrainee, 'cv')" class="p-2 text-gray-300 hover:text-indigo-600 transition-colors" title="Visualiser">
                                    <EyeIcon class="h-5 w-5" />
                                </button>
                                <span v-else class="text-[10px] font-bold text-red-300 italic px-2">Manquant</span>
                            </div>

                            <!-- Diploma View -->
                            <div class="p-4 bg-white border border-gray-100 rounded-3xl flex items-center justify-between group hover:border-indigo-200 transition-all shadow-sm">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div class="p-2 bg-orange-50 text-orange-600 rounded-xl group-hover:bg-orange-600 group-hover:text-white transition-colors shrink-0">
                                        <AcademicCapIcon class="h-5 w-5" />
                                    </div>
                                    <span class="text-[10px] font-black text-gray-600 uppercase tracking-widest truncate">Diplôme</span>
                                </div>
                                <button v-if="selectedTrainee.internship?.diploma" @click="openPreview(selectedTrainee, 'diploma')" class="p-2 text-gray-300 hover:text-indigo-600 transition-colors" title="Visualiser">
                                    <EyeIcon class="h-5 w-5" />
                                </button>
                                <span v-else class="text-[10px] font-bold text-red-300 italic px-2">Manquant</span>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Info -->
                    <div class="pt-6 border-t border-gray-100 grid grid-cols-2 gap-8">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-gray-50 rounded-2xl text-gray-400">
                                <EnvelopeIcon class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Email</p>
                                <p class="text-xs font-black text-gray-700">{{ selectedTrainee.email }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-gray-50 rounded-2xl text-gray-400">
                                <PhoneIcon class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Téléphone</p>
                                <p class="text-xs font-black text-gray-700">{{ selectedTrainee.telephone || 'Non renseigné' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <footer class="px-8 pb-8 pt-4 flex gap-4 shrink-0 bg-white shadow-[0_-20px_40px_-20px_rgba(0,0,0,0.1)]">
                    <button 
                        @click="openEditModal(selectedTrainee); closeViewModal()"
                        class="flex-1 py-4 bg-amber-400 text-white rounded-3xl font-black text-sm hover:bg-amber-500 transition-all flex items-center justify-center gap-2 shadow-lg shadow-amber-100 active:scale-95"
                    >
                        <PencilSquareIcon class="h-5 w-5" />
                        Mettre à jour le dossier
                    </button>
                    <button 
                        @click="closeViewModal"
                        class="px-8 py-4 bg-gray-100 text-gray-600 rounded-3xl font-black text-xs uppercase tracking-widest hover:bg-gray-200 transition-all active:scale-95"
                    >
                        Fermer
                    </button>
                </footer>
            </div>
        </div>

        <!-- Document Preview Modal -->
        <div v-if="isPreviewOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gray-900/95 backdrop-blur-md">
            <div class="bg-white w-full max-w-5xl h-[90vh] rounded-[2.5rem] overflow-hidden shadow-2xl flex flex-col animate-in">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-white relative z-10 shrink-0 shadow-sm px-8">
                    <div>
                        <h3 class="text-xl font-black text-gray-900 tracking-tight capitalize flex items-center gap-2">
                             <EyeIcon class="h-5 w-5 text-indigo-600" />
                             {{ previewType.replace('_', ' ') }}
                        </h3>
                        <p class="text-xs text-gray-400 font-black uppercase tracking-widest">{{ previewTrainee.name }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                         <a :href="route('trainees.preview', { trainee: previewTrainee.id, type: previewType })" download class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-600 hover:text-white transition-all shadow-sm" title="Télécharger">
                            <ArrowDownTrayIcon class="h-5 w-5" />
                        </a>
                        <button @click="closePreview" class="p-2.5 bg-gray-100 text-gray-500 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm" title="Fermer">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
                <div class="flex-1 bg-gray-100 overflow-hidden relative">
                    <iframe 
                        :src="route('trainees.preview', { trainee: previewTrainee.id, type: previewType })" 
                        class="w-full h-full border-0 shadow-inner"
                    ></iframe>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Custom Scrollbar */
.overflow-y-auto::-webkit-scrollbar {
  width: 4px;
}
.overflow-y-auto::-webkit-scrollbar-track {
  background: transparent;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #e5e7eb;
  border-radius: 10px;
}
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #d1d5db;
}

@keyframes fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slide-in-from-bottom-2 {
  from { transform: translateY(0.5rem); }
  to { transform: translateY(0); }
}

.animate-in {
  animation: fade-in 0.3s ease-out, slide-in-from-bottom-2 0.3s ease-out;
}
</style>
