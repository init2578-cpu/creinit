<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import { 
    UserGroupIcon, 
    PlusIcon, 
    XMarkIcon,
    AcademicCapIcon,
    CalendarIcon,
    UserIcon,
    BookOpenIcon,
    PencilSquareIcon,
    TrashIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    groups: Array,
    modules: Array,
    formateurs: Array
})

const isCreateModalOpen = ref(false)
const isEditModalOpen = ref(false)
const editingGroup = ref(null)

const form = useForm({
    module_id: '',
    formateur_id: '',
    annee_academique: new Date().getFullYear() + '-' + (new Date().getFullYear() + 1),
    gps_check_required: true
})

const editForm = useForm({
    module_id: '',
    formateur_id: '',
    annee_academique: '',
    responsable_groupe_id: null,
    adjoint_groupe_id: null,
    gps_check_required: true
})

const submit = () => {
    form.post(route('groups.store'), {
        onSuccess: () => {
            isCreateModalOpen.value = false
            form.reset()
        }
    })
}

const openEditModal = (group) => {
    editingGroup.value = group
    editForm.module_id = group.module_id
    editForm.formateur_id = group.formateur_id
    editForm.annee_academique = group.annee_academique
    editForm.responsable_groupe_id = group.responsable_groupe_id
    editForm.adjoint_groupe_id = group.adjoint_groupe_id
    editForm.gps_check_required = group.gps_check_required === 1 || group.gps_check_required === true
    isEditModalOpen.value = true
}

const updateGroup = () => {
    editForm.put(route('groups.update', editingGroup.value.id), {
        onSuccess: () => {
            isEditModalOpen.value = false
            editingGroup.value = null
        }
    })
}

const deleteGroup = (id) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce groupe ? Cette action est irréversible.')) {
        useForm({}).delete(route('groups.destroy', id))
    }
}

const handleLeaderChange = () => {
    if (editForm.responsable_groupe_id && editForm.responsable_groupe_id === editForm.adjoint_groupe_id) {
        editForm.adjoint_groupe_id = null
    }
}

const handleDeputyChange = () => {
    if (editForm.adjoint_groupe_id && editForm.adjoint_groupe_id === editForm.responsable_groupe_id) {
        editForm.responsable_groupe_id = null
    }
}
</script>

<template>
    <Head title="Gestion des Groupes" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-3xl font-black text-gray-900 tracking-tight">Groupes de Formation</h2>
                    <p class="text-gray-500 font-medium">Gérez vos sessions d'apprentissage et formateurs.</p>
                </div>
                <button 
                    @click="isCreateModalOpen = true"
                    class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3.5 rounded-2xl font-black transition-all shadow-xl shadow-blue-200 active:scale-95"
                >
                    <PlusIcon class="h-5 w-5" />
                    Créer un Groupe
                </button>
            </div>

            <!-- Groups Grid -->
            <div v-if="groups.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="group in groups" :key="group.id" class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="p-8">
                        <div class="flex items-start justify-between mb-6">
                            <div class="h-14 w-14 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
                                <UserGroupIcon class="h-8 w-8" />
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="openEditModal(group)" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all">
                                    <PencilSquareIcon class="h-5 w-5" />
                                </button>
                                <button @click="deleteGroup(group.id)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all">
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                                <span v-if="!group.gps_check_required" class="px-3 py-1.5 bg-amber-50 text-amber-600 text-[10px] font-black uppercase tracking-widest rounded-full border border-amber-100">
                                    Sans GPS
                                </span>
                                <span class="px-4 py-1.5 bg-gray-50 text-gray-500 text-[10px] font-black uppercase tracking-widest rounded-full">
                                    {{ group.annee_academique }}
                                </span>
                            </div>
                        </div>

                        <h3 class="text-xl font-black text-gray-900 mb-2 truncate">{{ group.nom_groupe }}</h3>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 text-sm text-gray-500 font-medium">
                                <BookOpenIcon class="h-4 w-4 text-blue-500" />
                                {{ group.module?.titre || group.module?.nom_module }}
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-500 font-medium">
                                <UserIcon class="h-4 w-4 text-indigo-500" />
                                {{ group.formateur?.name || 'N/A' }}
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-50 flex flex-col gap-4">
                            <div class="flex items-center justify-between">
                                <div class="flex -space-x-2">
                                    <div v-for="i in Math.min(group.students_count, 3)" :key="i" class="h-8 w-8 rounded-full border-2 border-white bg-gray-100 flex items-center justify-center text-[10px] font-bold text-gray-400">
                                        <UserIcon class="h-4 w-4" />
                                    </div>
                                    <div v-if="group.students_count > 3" class="h-8 w-8 rounded-full border-2 border-white bg-blue-50 flex items-center justify-center text-[10px] font-bold text-blue-600">
                                        +{{ group.students_count - 3 }}
                                    </div>
                                </div>
                                <span class="text-xs font-black text-gray-400 uppercase tracking-widest">
                                    {{ group.students_count }} Apprenants
                                </span>
                            </div>

                            <Link 
                                :href="route('groups.students.index', group.id)"
                                class="w-full bg-blue-50 hover:bg-blue-600 text-blue-600 hover:text-white py-3 rounded-2xl font-black text-xs text-center transition-all duration-300 flex items-center justify-center gap-2"
                            >
                                <AcademicCapIcon class="h-4 w-4" />
                                Gérer les Apprenants
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-[3rem] p-16 text-center border-2 border-dashed border-gray-100">
                <div class="h-20 w-20 bg-gray-50 rounded-3xl flex items-center justify-center mx-auto mb-6">
                    <UserGroupIcon class="h-10 w-10 text-gray-300" />
                </div>
                <h3 class="text-xl font-black text-gray-900 mb-2">Aucun groupe trouvé</h3>
                <p class="text-gray-500 font-medium mb-8">Commencez par créer votre premier groupe de formation.</p>
                <button 
                    @click="isCreateModalOpen = true"
                    class="inline-flex items-center justify-center gap-2 bg-gray-900 hover:bg-black text-white px-8 py-4 rounded-2xl font-black transition-all shadow-xl active:scale-95"
                >
                    <PlusIcon class="h-6 w-6" />
                    Créer mon premier groupe
                </button>
            </div>
        </div>

        <!-- Create Modal -->
        <div v-if="isCreateModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm">
            <div class="bg-white w-full max-w-lg rounded-[2.5rem] overflow-hidden shadow-2xl animate-in fade-in zoom-in duration-300">
                <div class="p-8 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-2xl font-black text-gray-900 tracking-tight">Nouveau Groupe</h3>
                    <button @click="isCreateModalOpen = false" class="p-2 hover:bg-gray-100 rounded-xl transition">
                        <XMarkIcon class="h-6 w-6 text-gray-400" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-8 space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Module</label>
                            <select v-model="form.module_id" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-4 appearance-none transition-all">
                                <option value="">Choisir un module</option>
                                <option v-for="m in modules" :key="m.id" :value="m.id">{{ m.titre || m.nom_module }}</option>
                            </select>
                            <p v-if="form.errors.module_id" class="text-red-500 text-xs mt-1 font-bold">{{ form.errors.module_id }}</p>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Formateur</label>
                            <select v-model="form.formateur_id" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-4 appearance-none transition-all">
                                <option value="">Choisir un formateur</option>
                                <option v-for="f in formateurs" :key="f.id" :value="f.id">{{ f.name }}</option>
                            </select>
                            <p v-if="form.errors.formateur_id" class="text-red-500 text-xs mt-1 font-bold">{{ form.errors.formateur_id }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Année Académique</label>
                        <input 
                            v-model="form.annee_academique" 
                            type="text" 
                            placeholder="2024-2025"
                            required 
                            class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-4 transition-all"
                        >
                        <p v-if="form.errors.annee_academique" class="text-red-500 text-xs mt-1 font-bold">{{ form.errors.annee_academique }}</p>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-gray-900">Soumettre au contrôle GPS</span>
                            <span class="text-xs text-gray-400 font-medium">Exiger la présence au CRE lors de l'émargement</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.gps_check_required" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="pt-4">
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="w-full bg-gray-900 hover:bg-black text-white py-5 rounded-[1.5rem] font-black transition-all shadow-xl active:scale-[0.98] disabled:opacity-50"
                        >
                            {{ form.processing ? 'Création en cours...' : 'Créer le Groupe' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm">
            <div class="bg-white w-full max-w-lg rounded-[2.5rem] overflow-hidden shadow-2xl animate-in fade-in zoom-in duration-300">
                <div class="p-8 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-black text-gray-900 tracking-tight">Modifier le Groupe</h3>
                        <p class="text-xs font-bold text-blue-600 uppercase tracking-widest">{{ editingGroup?.nom_groupe }}</p>
                    </div>
                    <button @click="isEditModalOpen = false" class="p-2 hover:bg-gray-100 rounded-xl transition">
                        <XMarkIcon class="h-6 w-6 text-gray-400" />
                    </button>
                </div>

                <form @submit.prevent="updateGroup" class="p-8 space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Module</label>
                            <select v-model="editForm.module_id" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-4 appearance-none transition-all">
                                <option value="">Choisir un module</option>
                                <option v-for="m in modules" :key="m.id" :value="m.id">{{ m.titre || m.nom_module }}</option>
                            </select>
                            <p v-if="editForm.errors.module_id" class="text-red-500 text-xs mt-1 font-bold">{{ editForm.errors.module_id }}</p>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Formateur</label>
                            <select v-model="editForm.formateur_id" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-4 appearance-none transition-all">
                                <option value="">Choisir un formateur</option>
                                <option v-for="f in formateurs" :key="f.id" :value="f.id">{{ f.name }}</option>
                            </select>
                            <p v-if="editForm.errors.formateur_id" class="text-red-500 text-xs mt-1 font-bold">{{ editForm.errors.formateur_id }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Année Académique</label>
                        <input 
                            v-model="editForm.annee_academique" 
                            type="text" 
                            placeholder="2024-2025"
                            required 
                            class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-4 transition-all"
                        >
                        <p v-if="editForm.errors.annee_academique" class="text-red-500 text-xs mt-1 font-bold">{{ editForm.errors.annee_academique }}</p>
                    </div>

                    <div v-if="editingGroup?.students?.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Chef de Groupe</label>
                            <select 
                                v-model="editForm.responsable_groupe_id" 
                                @change="handleLeaderChange"
                                class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-4 appearance-none transition-all"
                            >
                                <option :value="null">Aucun</option>
                                <option v-for="s in editingGroup.students" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Adjoint</label>
                            <select 
                                v-model="editForm.adjoint_groupe_id" 
                                @change="handleDeputyChange"
                                class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-4 appearance-none transition-all"
                            >
                                <option :value="null">Aucun</option>
                                <option v-for="s in editingGroup.students" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-gray-900">Soumettre au contrôle GPS</span>
                            <span class="text-xs text-gray-400 font-medium">Exiger la présence au CRE lors de l'émargement</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="editForm.gps_check_required" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="pt-4">
                        <button 
                            type="submit" 
                            :disabled="editForm.processing"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white py-5 rounded-[1.5rem] font-black transition-all shadow-xl active:scale-[0.98] disabled:opacity-50"
                        >
                            {{ editForm.processing ? 'Mise à jour...' : 'Enregistrer les modifications' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
