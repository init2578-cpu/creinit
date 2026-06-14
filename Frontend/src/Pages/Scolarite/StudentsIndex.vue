<script setup>
import { ref, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DateInput from '@/Components/DateInput.vue'
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
    UserIcon,
    LockClosedIcon,
    CalendarIcon
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

// Statistics
const totalStudentsCount = computed(() => props.students?.length ?? 0)
const activeStudentsCount = computed(() => props.students?.filter(s => s.is_active).length ?? 0)
const newStudentsCount = computed(() => {
    if (!props.students) return 0
    const now = new Date()
    const currentMonth = now.getMonth()
    const currentYear = now.getFullYear()
    return props.students.filter(s => {
        if (!s.created_at) return false
        const d = new Date(s.created_at.replace(/-/g, '/'))
        return !isNaN(d.getTime()) && d.getMonth() === currentMonth && d.getFullYear() === currentYear
    }).length
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
            <!-- Header Section -->
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
                            class="pl-12 pr-6 py-3 bg-white border-2 border-gray-200 hover:border-gray-300 focus:border-blue-600 focus:ring-4 focus:ring-blue-50/50 rounded-2xl shadow-sm w-64 focus:w-80 font-bold text-sm transition-all duration-300 outline-none"
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

            <!-- Stats Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Total Students -->
                <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm flex items-center justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:border-blue-100 group">
                    <div class="space-y-2">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Apprenants</p>
                        <p class="text-3xl font-black text-gray-900 group-hover:text-blue-600 transition-colors">{{ totalStudentsCount }}</p>
                        <p class="text-xs text-gray-400 font-medium">Inscrits au total</p>
                    </div>
                    <div class="h-14 w-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-all duration-300">
                        <AcademicCapIcon class="h-7 w-7" />
                    </div>
                </div>

                <!-- Active Students -->
                <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm flex items-center justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:border-green-100 group">
                    <div class="space-y-2">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Comptes Actifs</p>
                        <p class="text-3xl font-black text-gray-900 group-hover:text-green-600 transition-colors">{{ activeStudentsCount }}</p>
                        <p class="text-xs text-green-600 font-bold flex items-center gap-1">
                            {{ totalStudentsCount > 0 ? Math.round((activeStudentsCount / totalStudentsCount) * 100) : 0 }}% du total
                        </p>
                    </div>
                    <div class="h-14 w-14 rounded-2xl bg-green-50 text-green-600 flex items-center justify-center group-hover:bg-green-600 group-hover:text-white transition-all duration-300">
                        <CheckCircleIcon class="h-7 w-7" />
                    </div>
                </div>

                <!-- New Students (this month) -->
                <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm flex items-center justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:border-purple-100 group">
                    <div class="space-y-2">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Nouveaux Inscrits</p>
                        <p class="text-3xl font-black text-gray-900 group-hover:text-purple-600 transition-colors">{{ newStudentsCount }}</p>
                        <p class="text-xs text-gray-400 font-medium">Ce mois-ci</p>
                    </div>
                    <div class="h-14 w-14 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition-all duration-300">
                        <UserGroupIcon class="h-7 w-7" />
                    </div>
                </div>
            </div>

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
                                <div class="flex justify-end gap-2 transition-all duration-300 md:opacity-0 md:translate-x-4 md:group-hover:opacity-100 md:group-hover:translate-x-0">
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

                <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto p-8 relative">
                    <transition name="fade-slide" mode="out-in">
                        <!-- Tab: Identité -->
                        <div v-if="activeTab === 'id'" key="id" class="space-y-6">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Nom complet</label>
                                    <div class="relative group">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-blue-600 transition-colors">
                                            <UserIcon class="h-5 w-5" />
                                        </span>
                                        <input v-model="studentForm.name" type="text" class="w-full pl-12 pr-4 py-4 bg-gray-50 focus:bg-white border-2 border-transparent focus:border-blue-600 rounded-2xl font-bold text-gray-700 focus:ring-0 transition-all outline-none" placeholder="ex: Jean Dupont" required>
                                    </div>
                                    <div v-if="studentForm.errors.name" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ studentForm.errors.name }}</div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Email professionnel</label>
                                    <div class="relative group">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-blue-600 transition-colors">
                                            <EnvelopeIcon class="h-5 w-5" />
                                        </span>
                                        <input v-model="studentForm.email" type="email" class="w-full pl-12 pr-4 py-4 bg-gray-50 focus:bg-white border-2 border-transparent focus:border-blue-600 rounded-2xl font-bold text-gray-700 focus:ring-0 transition-all outline-none" placeholder="ex: jean.dupont@email.com" required>
                                    </div>
                                    <div v-if="studentForm.errors.email" class="text-xs text-red-500 font-bold mt-1 ml-1">{{ studentForm.errors.email }}</div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Mot de passe</label>
                                    <div class="relative group">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-blue-600 transition-colors">
                                            <LockClosedIcon class="h-5 w-5" />
                                        </span>
                                        <input v-model="studentForm.password" type="password" class="w-full pl-12 pr-4 py-4 bg-gray-50 focus:bg-white border-2 border-transparent focus:border-blue-600 rounded-2xl font-bold text-gray-700 focus:ring-0 transition-all outline-none" :required="!editingStudent" :placeholder="editingStudent ? 'Laisser vide pour ne pas changer' : '••••••••'">
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Sexe</label>
                                    <div class="relative group">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-blue-600 transition-colors pointer-events-none">
                                            <IdentificationIcon class="h-5 w-5" />
                                        </span>
                                        <select v-model="studentForm.sexe" class="w-full pl-12 pr-10 py-4 bg-gray-50 focus:bg-white border-2 border-transparent focus:border-blue-600 rounded-2xl font-bold text-gray-700 focus:ring-0 transition-all">
                                            <option value="M">Masculin</option>
                                            <option value="F">Féminin</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div v-if="editingStudent" class="flex items-center gap-3 p-4 bg-gray-50 rounded-2xl mt-4">
                                <input type="checkbox" v-model="studentForm.is_active" id="is_active" class="h-5 w-5 text-blue-600 rounded-lg border-gray-200 focus:ring-blue-600">
                                <label for="is_active" class="text-sm font-bold text-gray-700">Compte actif</label>
                            </div>
                        </div>

                        <!-- Tab: Contact -->
                        <div v-else-if="activeTab === 'contact'" key="contact" class="space-y-6">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Téléphone</label>
                                    <div class="relative group">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-blue-600 transition-colors">
                                            <PhoneIcon class="h-5 w-5" />
                                        </span>
                                        <input v-model="studentForm.telephone" type="text" class="w-full pl-12 pr-4 py-4 bg-gray-50 focus:bg-white border-2 border-transparent focus:border-blue-600 rounded-2xl font-bold text-gray-700 focus:ring-0 transition-all outline-none" placeholder="ex: +221 77 123 45 67">
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Ville / Lieu de naissance</label>
                                    <div class="relative group">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-blue-600 transition-colors">
                                            <MapPinIcon class="h-5 w-5" />
                                        </span>
                                        <input v-model="studentForm.lieu_naissance" type="text" class="w-full pl-12 pr-4 py-4 bg-gray-50 focus:bg-white border-2 border-transparent focus:border-blue-600 rounded-2xl font-bold text-gray-700 focus:ring-0 transition-all outline-none" placeholder="ex: Dakar">
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Adresse complète</label>
                                <div class="relative group">
                                    <span class="absolute top-4 left-0 flex items-start pl-4 text-gray-400 group-focus-within:text-blue-600 transition-colors">
                                        <MapPinIcon class="h-5 w-5" />
                                    </span>
                                    <textarea v-model="studentForm.adresse" class="w-full pl-12 pr-4 py-4 bg-gray-50 focus:bg-white border-2 border-transparent focus:border-blue-600 rounded-2xl font-bold text-gray-700 focus:ring-0 transition-all min-h-[100px] outline-none" placeholder="Saisissez l'adresse de résidence..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Tab: Profile -->
                        <div v-else-if="activeTab === 'profile'" key="profile" class="space-y-6">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Date de naissance (jj/mm/aaaa)</label>
                                    <div class="relative group">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-blue-600 transition-colors">
                                            <CalendarIcon class="h-5 w-5" />
                                        </span>
                                        <DateInput v-model="studentForm.date_naissance" class="w-full pl-12 pr-4 py-4 bg-gray-50 focus:bg-white border-2 border-transparent focus:border-blue-600 rounded-2xl font-bold text-gray-700 focus:ring-0 transition-all outline-none" />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Niveau d'étude</label>
                                    <div class="relative group">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-blue-600 transition-colors">
                                            <AcademicCapIcon class="h-5 w-5" />
                                        </span>
                                        <input v-model="studentForm.niveau_etude" type="text" placeholder="ex: Master 2" class="w-full pl-12 pr-4 py-4 bg-gray-50 focus:bg-white border-2 border-transparent focus:border-blue-600 rounded-2xl font-bold text-gray-700 focus:ring-0 transition-all outline-none">
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Dernier diplôme obtenu</label>
                                <div class="relative group">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-blue-600 transition-colors">
                                        <BriefcaseIcon class="h-5 w-5" />
                                    </span>
                                    <input v-model="studentForm.dernier_diplome" type="text" placeholder="Saisissez le libellé du diplôme..." class="w-full pl-12 pr-4 py-4 bg-gray-50 focus:bg-white border-2 border-transparent focus:border-blue-600 rounded-2xl font-bold text-gray-700 focus:ring-0 transition-all outline-none">
                                </div>
                            </div>
                        </div>
                    </transition>
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

        <!-- Student Details View Modal (Enhanced Premium Card) -->
        <div v-if="isViewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl relative overflow-hidden transition-all duration-300 animate-in fade-in zoom-in-95">
                <!-- Top Gradient Banner -->
                <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600">
                    <div class="absolute inset-0 bg-white/10 backdrop-blur-[2px]"></div>
                </div>
                
                <!-- Close Button -->
                <button @click="closeViewModal" class="absolute right-6 top-6 p-2 text-white/70 hover:text-white hover:bg-white/15 rounded-xl transition-all z-10">
                    <XMarkIcon class="h-6 w-6" />
                </button>

                <!-- Profile Info Header -->
                <div class="relative mt-12 flex flex-col items-center z-10">
                    <div class="h-28 w-28 bg-white rounded-[2rem] shadow-2xl flex items-center justify-center overflow-hidden text-4xl font-black text-blue-600 border-4 border-white mb-4 transition-transform duration-300 hover:scale-105">
                        <img v-if="selectedStudent.profile_photo_url" :src="selectedStudent.profile_photo_url" class="h-full w-full object-cover">
                        <template v-else>{{ selectedStudent.name.charAt(0) }}</template>
                    </div>
                    
                    <h2 class="text-2xl font-black text-gray-900 tracking-tight px-4 text-center">{{ selectedStudent.name }}</h2>
                    
                    <!-- Pulsing Status Badge -->
                    <div class="flex items-center gap-2 mt-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                            :class="selectedStudent.is_active ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'"
                        >
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" :class="selectedStudent.is_active ? 'bg-green-400' : 'bg-red-400'"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2" :class="selectedStudent.is_active ? 'bg-green-600' : 'bg-red-600'"></span>
                            </span>
                            {{ selectedStudent.is_active ? 'Compte Actif' : 'Compte Inactif' }}
                        </span>
                    </div>

                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mt-2">Membre depuis le {{ selectedStudent.created_at }}</p>
                </div>

                <!-- Info Body Sections -->
                <div class="mt-8 space-y-6 overflow-y-auto max-h-[50vh] pr-2 custom-scrollbar px-6">
                    <!-- Contact Card Section -->
                    <div class="bg-gray-50/60 rounded-[2rem] p-6 border border-gray-100 space-y-4">
                        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 pb-2">Informations de Contact</h3>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <EnvelopeIcon class="h-3.5 w-3.5 text-blue-500" /> Email
                                </p>
                                <p class="font-extrabold text-gray-800 text-sm truncate" :title="selectedStudent.email">{{ selectedStudent.email }}</p>
                            </div>
                            
                            <div class="space-y-1">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <PhoneIcon class="h-3.5 w-3.5 text-blue-500" /> Téléphone
                                </p>
                                <p class="font-extrabold text-gray-800 text-sm">{{ selectedStudent.telephone || 'Non renseigné' }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 pt-2">
                            <div class="space-y-1">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <CalendarIcon class="h-3.5 w-3.5 text-blue-500" /> Naissance
                                </p>
                                <p class="font-extrabold text-gray-800 text-sm">
                                    {{ selectedStudent.profile?.date_naissance || 'Non renseignée' }}
                                </p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <MapPinIcon class="h-3.5 w-3.5 text-blue-500" /> Lieu
                                </p>
                                <p class="font-extrabold text-gray-800 text-sm truncate" :title="selectedStudent.profile?.lieu_naissance">
                                    {{ selectedStudent.profile?.lieu_naissance || 'Non renseigné' }}
                                </p>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-gray-100/50">
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider flex items-center gap-1.5 mb-1">
                                <MapPinIcon class="h-3.5 w-3.5 text-blue-500" /> Adresse Résidence
                            </p>
                            <p class="font-extrabold text-gray-700 text-xs leading-relaxed">
                                {{ selectedStudent.adresse || 'Aucune adresse renseignée.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Academic Card Section -->
                    <div v-if="selectedStudent.profile" class="bg-gray-50/60 rounded-[2rem] p-6 border border-gray-100 space-y-4">
                        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 pb-2">Profil Académique</h3>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <AcademicCapIcon class="h-3.5 w-3.5 text-indigo-500" /> Niveau d'études
                                </p>
                                <p class="font-extrabold text-gray-800 text-sm">{{ selectedStudent.profile.niveau_etude || 'Non renseigné' }}</p>
                            </div>
                            
                            <div class="space-y-1">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <BriefcaseIcon class="h-3.5 w-3.5 text-indigo-500" /> Dernier Diplôme
                                </p>
                                <p class="font-extrabold text-gray-800 text-sm truncate" :title="selectedStudent.profile.dernier_diplome">
                                    {{ selectedStudent.profile.dernier_diplome || 'Non renseigné' }}
                                </p>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-gray-100/50 grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <IdentificationIcon class="h-3.5 w-3.5 text-indigo-500" /> Genre / Sexe
                                </p>
                                <p class="font-extrabold text-gray-800 text-sm">
                                    {{ selectedStudent.profile.sexe === 'M' ? 'Masculin' : selectedStudent.profile.sexe === 'F' ? 'Féminin' : 'Non spécifié' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Groups Card Section -->
                    <div class="bg-gray-50/60 rounded-[2rem] p-6 border border-gray-100 space-y-3">
                        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 pb-2">Groupes & Formations Affectés</h3>
                        
                        <div class="flex flex-wrap gap-2 pt-1">
                            <span v-for="group in selectedStudent.groups" :key="group.id" 
                                class="inline-flex flex-col px-3 py-2 bg-white border border-green-100 text-green-700 rounded-xl text-[10px] font-extrabold shadow-sm transition-all hover:border-green-300"
                            >
                                <span class="flex items-center gap-1">
                                    <UserGroupIcon class="h-3.5 w-3.5 text-green-600" />
                                    {{ group.nom_groupe }}
                                </span>
                                <span class="text-[8px] text-gray-400 font-bold ml-5 mt-0.5">{{ group.module }}</span>
                            </span>
                            
                            <div v-if="selectedStudent.groups.length === 0" class="flex flex-col items-center justify-center w-full py-4 text-center">
                                <span class="text-xs text-gray-400 italic">Cet apprenant n'est affecté à aucun groupe.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div class="mt-8 flex gap-4 px-6 pb-8">
                    <button 
                        @click="openEditModal(selectedStudent); closeViewModal()"
                        class="flex-1 py-4 bg-amber-50 text-amber-600 hover:bg-amber-100 rounded-2xl font-black text-xs uppercase tracking-widest transition-all flex items-center justify-center gap-2 border border-amber-100 hover:shadow-md active:scale-95"
                    >
                        <PencilSquareIcon class="h-4.5 w-4.5" />
                        Modifier Profil
                    </button>
                    <button 
                        @click="closeViewModal"
                        class="flex-1 py-4 bg-gray-100 text-gray-600 hover:bg-gray-200 rounded-2xl font-black text-xs uppercase tracking-widest transition-all hover:shadow-md active:scale-95"
                    >
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Tab transition animations */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.25s ease;
}
.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* Custom Scrollbar for the overflow area in the modal */
.overflow-y-auto::-webkit-scrollbar,
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.overflow-y-auto::-webkit-scrollbar-track,
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.overflow-y-auto::-webkit-scrollbar-thumb,
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e5e7eb;
  border-radius: 10px;
}
.overflow-y-auto::-webkit-scrollbar-thumb:hover,
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #d1d5db;
}
</style>
