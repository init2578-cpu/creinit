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
    CheckCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    students: Array
})

// Modals state
const isViewModalOpen = ref(false)
const isFormModalOpen = ref(false)
const selectedStudent = ref(null)
const editingStudent = ref(null)
const activeTab = ref('id') // 'id', 'contact', 'profile'

// Form state
const studentForm = useForm({
    name: '',
    email: '',
    password: '',
    telephone: '',
    adresse: '',
    is_active: true,
    // Profile fields
    date_naissance: '',
    lieu_naissance: '',
    niveau_etude: '',
    dernier_diplome: '',
    sexe: 'M',
})

// Search state
const searchQuery = ref('')
const filteredStudents = computed(() => {
    if (!props.students) return []
    if (!searchQuery.value) return props.students
    const query = searchQuery.value.toLowerCase()
    return props.students.filter(s => {
        const name = String(s.name || '').toLowerCase()
        const email = String(s.email || '').toLowerCase()
        const telephone = String(s.telephone || '').toLowerCase()
        return name.includes(query) || email.includes(query) || telephone.includes(query)
    })
})

// Actions
function openViewModal(student) {
    selectedStudent.value = student
    isViewModalOpen.value = true
}

function closeViewModal() {
    isViewModalOpen.value = false
    selectedStudent.value = null
}

function openCreateModal() {
    editingStudent.value = null
    studentForm.reset()
    activeTab.value = 'id'
    isFormModalOpen.value = true
}

function openEditModal(student) {
    editingStudent.value = student
    studentForm.name = student.name
    studentForm.email = student.email
    studentForm.password = ''
    studentForm.telephone = student.telephone || ''
    studentForm.adresse = student.adresse || ''
    studentForm.is_active = !!student.is_active
    
    if (student.profile) {
        studentForm.date_naissance = student.profile.date_naissance || ''
        studentForm.lieu_naissance = student.profile.lieu_naissance || ''
        studentForm.niveau_etude = student.profile.niveau_etude || ''
        studentForm.dernier_diplome = student.profile.dernier_diplome || ''
        studentForm.sexe = student.profile.sexe || 'M'
    } else {
        studentForm.date_naissance = ''
        studentForm.lieu_naissance = ''
        studentForm.niveau_etude = ''
        studentForm.dernier_diplome = ''
        studentForm.sexe = 'M'
    }
    
    activeTab.value = 'id'
    isFormModalOpen.value = true
}

function closeFormModal() {
    isFormModalOpen.value = false
    editingStudent.value = null
    studentForm.reset()
}

function submitForm() {
    if (editingStudent.value) {
        studentForm.put(route('students.update', editingStudent.value.id), {
            onSuccess: () => closeFormModal(),
        })
    } else {
        studentForm.post(route('students.store'), {
            onSuccess: () => closeFormModal(),
        })
    }
}

function deleteLearner(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet apprenant ? Cette action est irréversible.')) {
        router.delete(route('students.destroy', id))
    }
}
</script>

<template>
    <Head title="Gestion des Apprenants" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-8 px-4">
            <header class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight">Gestion des Apprenants</h1>
                    <p class="text-gray-500 font-medium">Suivi administratif et pédagogique des élèves.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative group">
                        <MagnifyingGlassIcon class="h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-blue-600 transition-colors" />
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Rechercher..." 
                            class="pl-12 pr-6 py-3 bg-white border-2 border-transparent rounded-2xl shadow-sm focus:ring-0 focus:border-blue-600 w-64 font-bold text-sm transition-all"
                        >
                    </div>
                    <button 
                        @click="openCreateModal"
                        class="flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-2xl font-black text-sm hover:bg-blue-700 hover:shadow-lg hover:shadow-blue-200 transition-all active:scale-95"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Nouvel Apprenant
                    </button>
                </div>
            </header>

            <!-- Table Section -->
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-black tracking-widest">
                            <th class="px-8 py-4">Apprenant</th>
                            <th class="px-8 py-4">Groupes & Modules</th>
                            <th class="px-8 py-4 text-center">Statut</th>
                            <th class="px-8 py-4">Inscrit le</th>
                            <th class="px-8 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="student in filteredStudents" :key="student.id" class="hover:bg-gray-50/50 transition-colors group">
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center overflow-hidden font-black">
                                        <img v-if="student.profile_photo_url" :src="student.profile_photo_url" class="h-full w-full object-cover">
                                        <template v-else>{{ student.name.charAt(0) }}</template>
                                    </div>
                                    <div>
                                        <p class="font-black text-gray-900">{{ student.name }}</p>
                                        <div class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
                                            <EnvelopeIcon class="h-3.5 w-3.5" />
                                            {{ student.email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <div class="flex flex-wrap gap-2">
                                    <div v-for="group in student.groups" :key="group.id" class="flex flex-col">
                                        <span class="px-2.5 py-0.5 bg-green-50 text-green-700 rounded-lg text-[10px] font-black uppercase tracking-wider flex items-center gap-1">
                                            <UserGroupIcon class="h-3 w-3" />
                                            {{ group.nom_groupe }}
                                        </span>
                                        <span class="text-[9px] text-gray-400 font-bold ml-1">{{ group.module }}</span>
                                    </div>
                                    <span v-if="student.groups.length === 0" class="text-[10px] text-gray-300 font-bold italic">
                                        Libre (Aucun groupe)
                                    </span>
                                </div>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                                    :class="student.is_active ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="student.is_active ? 'bg-green-600' : 'bg-red-600'"></span>
                                    {{ student.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="px-8 py-5 text-sm text-gray-400 font-bold">
                                {{ student.created_at }}
                            </td>
                            <td class="px-8 py-5 text-right">
                                <div class="flex justify-end gap-2 translate-x-4 opacity-0 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
                                    <button 
                                        @click="openViewModal(student)"
                                        class="p-2 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all shadow-sm"
                                        title="Voir Détails"
                                    >
                                        <EyeIcon class="h-5 w-5" />
                                    </button>
                                    <button 
                                        @click="openEditModal(student)"
                                        class="p-2 bg-amber-50 text-amber-600 rounded-xl hover:bg-amber-600 hover:text-white transition-all shadow-sm"
                                        title="Modifier"
                                    >
                                        <PencilSquareIcon class="h-5 w-5" />
                                    </button>
                                    <button 
                                        @click="deleteLearner(student.id)"
                                        class="p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm"
                                        title="Supprimer"
                                    >
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredStudents.length === 0">
                            <td colspan="5" class="px-8 py-12 text-center text-gray-400 font-bold italic">
                                Aucun apprenant trouvé.
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
                            {{ editingStudent ? 'Modifier l\'Apprenant' : 'Nouvel Apprenant' }}
                        </h2>
                        <p class="text-gray-500 text-sm font-medium">Remplissez les informations du compte et du profil.</p>
                    </div>
                    <button @click="closeFormModal" class="p-2 text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-xl transition-all">
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </header>

                <!-- Tabs -->
                <div class="px-8 py-4 flex items-center gap-6 border-b border-gray-50 shrink-0">
                    <button 
                        @click="activeTab = 'id'"
                        class="flex items-center gap-2 pb-2 border-b-2 transition-all font-black text-xs uppercase tracking-widest"
                        :class="activeTab === 'id' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
                    >
                        <IdentificationIcon class="h-4 w-4" />
                        Identité
                    </button>
                    <button 
                        @click="activeTab = 'contact'"
                        class="flex items-center gap-2 pb-2 border-b-2 transition-all font-black text-xs uppercase tracking-widest"
                        :class="activeTab === 'contact' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
                    >
                        <GlobeAltIcon class="h-4 w-4" />
                        Contact
                    </button>
                    <button 
                        @click="activeTab = 'profile'"
                        class="flex items-center gap-2 pb-2 border-b-2 transition-all font-black text-xs uppercase tracking-widest"
                        :class="activeTab === 'profile' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
                    >
                        <AcademicCapIcon class="h-4 w-4" />
                        Profil Académique
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto p-8">
                    <!-- Tab: Identité -->
                    <div v-if="activeTab === 'id'" class="space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Nom complet</label>
                                <input v-model="studentForm.name" type="text" class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-blue-600" required>
                                <div v-if="studentForm.errors.name" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ studentForm.errors.name }}</div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Email professionnel</label>
                                <input v-model="studentForm.email" type="email" class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-blue-600" required>
                                <div v-if="studentForm.errors.email" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ studentForm.errors.email }}</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Mot de passe</label>
                                <input v-model="studentForm.password" type="password" class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-blue-600" :required="!editingStudent" :placeholder="editingStudent ? 'Laisser vide pour ne pas changer' : ''">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Sexe</label>
                                <select v-model="studentForm.sexe" class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-blue-600">
                                    <option value="M">Masculin</option>
                                    <option value="F">Féminin</option>
                                </select>
                            </div>
                        </div>
                        <div v-if="editingStudent" class="flex items-center gap-3 p-4 bg-gray-50 rounded-2xl mt-4">
                            <input type="checkbox" v-model="studentForm.is_active" id="is_active" class="h-5 w-5 text-blue-600 rounded-lg border-gray-200 focus:ring-blue-600">
                            <label for="is_active" class="text-sm font-bold text-gray-700">Compte actif</label>
                        </div>
                    </div>

                    <!-- Tab: Contact -->
                    <div v-if="activeTab === 'contact'" class="space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Téléphone</label>
                                <input v-model="studentForm.telephone" type="text" class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-blue-600">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Ville / Lieu de naissance</label>
                                <input v-model="studentForm.lieu_naissance" type="text" class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-blue-600">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Adresse complète</label>
                            <textarea v-model="studentForm.adresse" class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-blue-600 min-h-[100px]"></textarea>
                        </div>
                    </div>

                    <!-- Tab: Profile -->
                    <div v-if="activeTab === 'profile'" class="space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Date de naissance</label>
                                <input v-model="studentForm.date_naissance" type="date" class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-blue-600">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Niveau d'étude</label>
                                <input v-model="studentForm.niveau_etude" type="text" placeholder="ex: Master 2" class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-blue-600">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Dernier diplôme obtenu</label>
                            <input v-model="studentForm.dernier_diplome" type="text" placeholder="Saisissez le libellé du diplôme..." class="w-full bg-gray-50 border-0 rounded-2xl p-4 font-bold text-gray-700 focus:ring-2 focus:ring-blue-600">
                        </div>
                    </div>
                </form>

                <footer class="p-8 border-t border-gray-100 bg-gray-50/50 flex items-center justify-end gap-4 shrink-0">
                    <button @click="closeFormModal" class="px-6 py-3 text-gray-500 font-black text-sm uppercase tracking-widest hover:text-gray-900">Annuler</button>
                    <button 
                        @click="submitForm"
                        :disabled="studentForm.processing"
                        class="px-10 py-3 bg-blue-600 text-white rounded-2xl font-black text-sm shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all flex items-center gap-2"
                    >
                        <CheckCircleIcon class="h-5 w-5" />
                        {{ editingStudent ? 'Enregistrer les modifications' : 'Créer l\'apprenant' }}
                    </button>
                </footer>
            </div>
        </div>

        <!-- Student Details View Modal (Enhanced) -->
        <div v-if="isViewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-24 bg-gradient-to-r from-blue-600 to-indigo-700"></div>
                <button @click="closeViewModal" class="absolute right-8 top-8 p-2 text-white/50 hover:text-white hover:bg-white/10 rounded-xl transition-all z-10">
                    <XMarkIcon class="h-6 w-6" />
                </button>

                <div class="relative mt-4 flex flex-col items-center">
                    <div class="h-24 w-24 bg-white rounded-3xl shadow-xl flex items-center justify-center overflow-hidden text-3xl font-black text-blue-600 border-4 border-white mb-4">
                        <img v-if="selectedStudent.profile_photo_url" :src="selectedStudent.profile_photo_url" class="h-full w-full object-cover">
                        <template v-else>{{ selectedStudent.name.charAt(0) }}</template>
                    </div>
                    <h2 class="text-2xl font-black text-gray-900 tracking-tight">{{ selectedStudent.name }}</h2>
                    <p class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] mt-1">Apprenant — Inscrit le {{ selectedStudent.created_at }}</p>
                </div>

                <div class="mt-8 space-y-6 overflow-y-auto max-h-[60vh] pr-2">
                    <div class="grid grid-cols-2 gap-8">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 flex items-center gap-1.5"><EnvelopeIcon class="h-3 w-3" /> Email</p>
                            <p class="font-bold text-gray-700 text-sm">{{ selectedStudent.email }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 flex items-center gap-1.5"><PhoneIcon class="h-3 w-3" /> Téléphone</p>
                            <p class="font-bold text-gray-700 text-sm">{{ selectedStudent.telephone || 'Non renseigné' }}</p>
                        </div>
                    </div>
                    
                    <div v-if="selectedStudent.profile">
                        <div class="grid grid-cols-2 gap-8 mb-6">
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Date & Lieu de Naissance</p>
                                <p class="font-bold text-gray-700 text-sm italic">{{ selectedStudent.profile.date_naissance || '...' }} à {{ selectedStudent.profile.lieu_naissance || '...' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Sexe</p>
                                <p class="font-bold text-gray-700 text-sm">{{ selectedStudent.profile.sexe === 'M' ? 'Masculin' : 'Féminin' }}</p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 flex items-center gap-1.5"><AcademicCapIcon class="h-3 w-3" /> Niveau d'études</p>
                                <p class="font-bold text-gray-700 text-sm">{{ selectedStudent.profile.niveau_etude || '...' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Dernier Diplôme</p>
                                <p class="font-bold text-gray-700 text-sm">{{ selectedStudent.profile.dernier_diplome || '...' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-50">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 flex items-center gap-1.5"><UserGroupIcon class="h-3 w-3" /> Groupes & Formation Actuels</p>
                        <div class="flex flex-wrap gap-2">
                            <div v-for="group in selectedStudent.groups" :key="group.id" class="px-3 py-1 bg-green-50 text-green-700 rounded-lg text-[9px] font-black uppercase tracking-wider">
                                {{ group.nom_groupe }} — {{ group.module }}
                            </div>
                            <p v-if="selectedStudent.groups.length === 0" class="text-xs text-gray-400 italic">Aucun groupe affecté</p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex gap-3">
                    <button 
                        @click="openEditModal(selectedStudent); closeViewModal()"
                        class="flex-1 py-4 bg-amber-50 text-amber-600 rounded-2xl font-black text-sm hover:bg-amber-100 transition-all flex items-center justify-center gap-2"
                    >
                        <PencilSquareIcon class="h-5 w-5" />
                        Modifier Profil
                    </button>
                    <button 
                        @click="closeViewModal"
                        class="flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black text-sm hover:bg-gray-200 transition-all"
                    >
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Custom Scrollbar for the overflow area in the modal */
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
</style>
