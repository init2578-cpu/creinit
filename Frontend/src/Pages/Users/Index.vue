<script setup>
import { ref, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { 
    UsersIcon, 
    EnvelopeIcon, 
    ShieldCheckIcon,
    ChevronRightIcon,
    MagnifyingGlassIcon,
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    XMarkIcon,
    LockClosedIcon,
    EyeIcon,
    PhoneIcon,
    MapPinIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    users: Array,
    available_roles: Array
})

const searchQuery = ref('')
const selectedRole = ref('')
const isModalOpen = ref(false)
const isViewModalOpen = ref(false)
const isEditing = ref(false)
const editingUser = ref(null)
const selectedUser = ref(null)

const filteredUsers = computed(() => {
    let list = props.users

    // Filter by role dropdown selection
    if (selectedRole.value) {
        list = list.filter(user => user.roles.includes(selectedRole.value))
    }

    // Filter by text search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        list = list.filter(user => {
            return user.name?.toLowerCase().includes(query) ||
                   user.email?.toLowerCase().includes(query) ||
                   user.telephone?.toLowerCase().includes(query) ||
                   user.adresse?.toLowerCase().includes(query) ||
                   user.roles?.some(role => role?.toLowerCase().includes(query))
        })
    }

    return list
})

const form = useForm({
    name: '',
    email: '',
    password: '',
    telephone: '',
    adresse: '',
    role: '',
    is_active: true
})

function openViewModal(user) {
    selectedUser.value = user
    isViewModalOpen.value = true
}

function closeViewModal() {
    isViewModalOpen.value = false
    selectedUser.value = null
}

function openCreateModal() {
    isEditing.value = false
    editingUser.value = null
    form.reset()
    form.clearErrors()
    isModalOpen.value = true
}

function openEditModal(user) {
    isEditing.value = true
    editingUser.value = user
    form.clearErrors()
    form.name = user.name
    form.email = user.email
    form.telephone = user.telephone || ''
    form.adresse = user.adresse || ''
    form.password = ''
    form.role = user.roles[0] || ''
    form.is_active = user.is_active
    isModalOpen.value = true
}

function closeModal() {
    isModalOpen.value = false
    form.reset()
}

function submitForm() {
    if (isEditing.value) {
        form.put(route('users.update', editingUser.value.id), {
            onSuccess: () => closeModal()
        })
    } else {
        form.post(route('users.store'), {
            onSuccess: () => closeModal()
        })
    }
}

function deleteUser(id) {
    if (confirm("Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.")) {
        router.delete(route('users.destroy', id))
    }
}
</script>

<template>
    <Head title="Gestion des Utilisateurs" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-8 px-4">
            <header class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight">Utilisateurs</h1>
                    <p class="text-gray-500">Gestion des comptes et des permissions d'accès.</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <MagnifyingGlassIcon class="h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Rechercher..." 
                            class="pl-12 pr-6 py-3 bg-white border-gray-100 rounded-2xl shadow-sm focus:ring-2 focus:ring-indigo-600 border-0 w-64 font-bold text-sm"
                        >
                    </div>
                    <div class="relative">
                        <select 
                            v-model="selectedRole"
                            class="pl-6 pr-10 py-3 bg-white border-gray-100 rounded-2xl shadow-sm focus:ring-2 focus:ring-indigo-600 border-0 font-bold text-sm appearance-none cursor-pointer min-w-[150px]"
                        >
                            <option value="">Tous les rôles</option>
                            <option v-for="role in available_roles" :key="role" :value="role">
                                {{ role }}
                            </option>
                        </select>
                        <ChevronRightIcon class="h-4 w-4 absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 rotate-90 pointer-events-none" />
                    </div>
                    <button 
                        @click="openCreateModal"
                        class="px-6 py-3 bg-indigo-600 text-white rounded-2xl font-black text-sm flex items-center gap-2 hover:bg-indigo-700 transition shadow-lg shadow-indigo-100"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Nouveau
                    </button>
                </div>
            </header>

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-black tracking-widest">
                            <th class="px-8 py-4">Utilisateur</th>
                            <th class="px-8 py-4">Rôles</th>
                            <th class="px-8 py-4">Statut</th>
                            <th class="px-8 py-4">Inscrit le</th>
                            <th class="px-8 py-4">Dernière Connexion</th>
                            <th class="px-8 py-4">Temps Actif</th>
                            <th class="px-8 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-gray-50/50 transition-colors group">
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center font-black overflow-hidden border border-gray-100">
                                        <img v-if="user.profile_photo_url" :src="user.profile_photo_url" class="h-full w-full object-cover">
                                        <template v-else>{{ user.name.charAt(0) }}</template>
                                    </div>
                                    <div>
                                        <p class="font-black text-gray-900">{{ user.name }}</p>
                                        <div class="flex items-center gap-1.5 text-xs text-gray-400 font-medium">
                                            <EnvelopeIcon class="h-3.5 w-3.5" />
                                            {{ user.email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <div class="flex flex-wrap gap-1.5">
                                    <span v-for="role in user.roles" :key="role" class="px-2.5 py-0.5 bg-indigo-50 text-indigo-600 rounded-lg text-[10px] font-black uppercase tracking-wider">
                                        {{ role }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                                    :class="user.is_active ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="user.is_active ? 'bg-green-600' : 'bg-red-600'"></span>
                                    {{ user.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="px-8 py-5 text-sm text-gray-400 font-bold">
                                {{ user.created_at }}
                            </td>
                            <td class="px-8 py-5 text-sm text-gray-400 font-bold">
                                <span v-if="user.last_seen === 'Jamais'" class="text-gray-300 italic font-medium">Jamais</span>
                                <span v-else>{{ user.last_seen }}</span>
                            </td>
                            <td class="px-8 py-5 text-sm text-gray-500 font-bold">
                                {{ user.time_spent }}
                            </td>
                            <td class="px-8 py-5 text-right">
                                <div class="flex justify-end gap-2 translate-x-4 opacity-0 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
                                    <button 
                                        @click="openViewModal(user)"
                                        class="p-2 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all shadow-sm shadow-blue-50"
                                        title="Voir Détails"
                                    >
                                        <EyeIcon class="h-5 w-5" />
                                    </button>
                                    <button 
                                        @click="openEditModal(user)"
                                        class="p-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-600 hover:text-white transition-all shadow-sm shadow-indigo-50"
                                        title="Modifier"
                                    >
                                        <PencilSquareIcon class="h-5 w-5" />
                                    </button>
                                    <button 
                                        @click="deleteUser(user.id)"
                                        class="p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm shadow-red-50"
                                        title="Supprimer"
                                    >
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- User Management Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl relative">
                <button @click="closeModal" class="absolute right-8 top-8 p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all">
                    <XMarkIcon class="h-6 w-6" />
                </button>

                <div class="mb-8">
                    <h2 class="text-2xl font-black text-gray-900 tracking-tight">
                        {{ isEditing ? 'Modifier l\'Utilisateur' : 'Nouvel Utilisateur' }}
                    </h2>
                    <p class="text-sm text-gray-500">{{ isEditing ? 'Mettre à jour les informations du compte.' : 'Créer un nouveau compte utilisateur.' }}</p>
                </div>

                <form @submit.prevent="submitForm" class="space-y-5">
                    <!-- Name -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Nom Complet</label>
                        <div class="relative">
                            <UsersIcon class="h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input 
                                v-model="form.name" 
                                type="text" 
                                required
                                placeholder="Jean Dupont"
                                class="w-full pl-12 pr-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm"
                            >
                        </div>
                        <p v-if="form.errors.name" class="text-xs text-red-500 mt-1.5 font-bold ml-1">{{ form.errors.name }}</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Adresse Email</label>
                        <div class="relative">
                            <EnvelopeIcon class="h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input 
                                v-model="form.email" 
                                type="email" 
                                required
                                placeholder="exemple@crekolda.sn"
                                class="w-full pl-12 pr-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm"
                            >
                        </div>
                        <p v-if="form.errors.email" class="text-xs text-red-500 mt-1.5 font-bold ml-1">{{ form.errors.email }}</p>
                    </div>

                    <!-- Phone & Address -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Téléphone</label>
                            <div class="relative">
                                <PhoneIcon class="h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
                                <input v-model="form.telephone" type="text" placeholder="77 000 00 00" class="w-full pl-12 pr-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Adresse</label>
                            <div class="relative">
                                <MapPinIcon class="h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
                                <input v-model="form.adresse" type="text" placeholder="Kolda, Dahra" class="w-full pl-12 pr-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm">
                            </div>
                        </div>
                    </div>

                    <!-- Role -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Rôle Système</label>
                        <div class="relative">
                            <ShieldCheckIcon class="h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
                            <select 
                                v-model="form.role" 
                                required
                                class="w-full pl-12 pr-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm appearance-none"
                            >
                                <option value="" disabled>Choisir un rôle...</option>
                                <option v-for="role in available_roles" :key="role" :value="role">{{ role }}</option>
                            </select>
                        </div>
                        <p v-if="form.errors.role" class="text-xs text-red-500 mt-1.5 font-bold ml-1">{{ form.errors.role }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">
                            Mot de Passe {{ isEditing ? '(Optionnel)' : '' }}
                        </label>
                        <div class="relative">
                            <LockClosedIcon class="h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input 
                                v-model="form.password" 
                                type="password" 
                                :required="!isEditing"
                                placeholder="••••••••"
                                class="w-full pl-12 pr-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm"
                            >
                        </div>
                        <p v-if="form.errors.password" class="text-xs text-red-500 mt-1.5 font-bold ml-1">{{ form.errors.password }}</p>
                    </div>

                    <!-- Active Toggle (only for editing) -->
                    <div v-if="isEditing" class="flex items-center justify-between p-4 bg-gray-50 rounded-[1.25rem]">
                        <span class="text-sm font-black text-gray-700">Compte Actif</span>
                        <button 
                            type="button"
                            @click="form.is_active = !form.is_active"
                            class="w-12 h-6 rounded-full transition-all duration-300 relative"
                            :class="form.is_active ? 'bg-green-500' : 'bg-gray-300'"
                        >
                            <span 
                                class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition-all duration-300"
                                :class="form.is_active ? 'translate-x-6' : 'translate-x-0'"
                            ></span>
                        </button>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button 
                            type="button" 
                            @click="closeModal"
                            class="flex-1 py-4 bg-gray-100 text-gray-600 rounded-[1.25rem] font-black text-sm hover:bg-gray-200 transition-all"
                        >
                            Annuler
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="flex-[2] py-4 bg-indigo-600 text-white rounded-[1.25rem] font-black text-sm hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 disabled:opacity-50"
                        >
                            {{ isEditing ? 'Enregistrer les modifications' : 'Créer le compte' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- User Details View Modal -->
        <div v-if="isViewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-24 bg-indigo-600"></div>
                <button @click="closeViewModal" class="absolute right-8 top-8 p-2 text-white/50 hover:text-white hover:bg-white/10 rounded-xl transition-all z-10">
                    <XMarkIcon class="h-6 w-6" />
                </button>

                <div class="relative mt-4 flex flex-col items-center">
                    <div class="h-24 w-24 bg-white rounded-3xl shadow-xl flex items-center justify-center text-3xl font-black text-indigo-600 border-4 border-white mb-4 overflow-hidden">
                        <img v-if="selectedUser.profile_photo_url" :src="selectedUser.profile_photo_url" class="h-full w-full object-cover">
                        <template v-else>{{ selectedUser.name.charAt(0) }}</template>
                    </div>
                    <h2 class="text-2xl font-black text-gray-900 tracking-tight">{{ selectedUser.name }}</h2>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <span v-for="role in selectedUser.roles" :key="role" class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full text-[10px] font-black uppercase tracking-wider">
                            {{ role }}
                        </span>
                    </div>
                </div>

                <div class="mt-8 space-y-6">
                    <div class="grid grid-cols-2 gap-8">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Email</p>
                            <p class="font-bold text-gray-700">{{ selectedUser.email }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Téléphone</p>
                            <p class="font-bold text-gray-700">{{ selectedUser.telephone || 'Non renseigné' }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Adresse</p>
                        <p class="font-bold text-gray-700">{{ selectedUser.adresse || 'Non renseignée' }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-8">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Statut</p>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                                :class="selectedUser.is_active ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'"
                            >
                                {{ selectedUser.is_active ? 'Compte Actif' : 'Compte Inactif' }}
                            </span>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Membre depuis</p>
                            <p class="font-bold text-gray-700">{{ selectedUser.created_at }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Dernière Connexion</p>
                        <p class="font-bold text-gray-700">
                            <span v-if="selectedUser.last_seen === 'Jamais'" class="text-gray-300 italic font-medium">Jamais connecté</span>
                            <span v-else>{{ selectedUser.last_seen }}</span>
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Temps sur la Plateforme</p>
                        <p class="font-bold text-gray-700">
                            {{ selectedUser.time_spent || '0s' }}
                        </p>
                    </div>
                </div>

                <div class="mt-10">
                    <button 
                        @click="closeViewModal"
                        class="w-full py-4 bg-gray-100 text-gray-600 rounded-2xl font-black text-sm hover:bg-gray-200 transition-all"
                    >
                        Fermer le Profil
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
