<script setup>
import { ref, watch } from 'vue'
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
    EyeIcon,
    PhoneIcon,
    MapPinIcon,
    PhotoIcon,
    ArrowUpTrayIcon,
    CheckBadgeIcon,
    LockClosedIcon,
    AcademicCapIcon,
    CheckCircleIcon,
    StarIcon,
    UserGroupIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    users: Array,
    available_roles: Array,
    available_permissions: Array,
    filters: Object
})

const isModalOpen = ref(false)
const isViewModalOpen = ref(false)
const isEditing = ref(false)
const editingUser = ref(null)
const selectedUser = ref(null)

const photoInput = ref(null)
const photoPreview = ref(null)

const search = ref(props.filters.search || '')
const roleFilter = ref(props.filters.role || '')
const statusFilter = ref(props.filters.status || '')

let searchTimeout;
watch([search, roleFilter, statusFilter], ([s, r, st]) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('users.index'), { search: s, role: r, status: st }, {
            preserveState: true,
            replace: true,
            preserveScroll: true
        })
    }, 400);
})

const form = useForm({
    name: '',
    email: '',
    password: '',
    telephone: '',
    adresse: '',
    roles: [],
    permissions: [],
    is_active: true,
    profile_photo: null
})

function selectNewPhoto() {
    photoInput.value.click()
}

function handlePhotoChange() {
    const photo = photoInput.value.files[0]
    if (!photo) return
    form.profile_photo = photo
    const reader = new FileReader()
    reader.onload = (e) => {
        photoPreview.value = e.target.result
    }
    reader.readAsDataURL(photo)
}

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
    photoPreview.value = null
    form.reset()
    form.clearErrors()
    isModalOpen.value = true
}

function openEditModal(user) {
    isEditing.value = true
    editingUser.value = user
    photoPreview.value = null
    form.clearErrors()
    form.name = user.name
    form.email = user.email
    form.telephone = user.telephone || ''
    form.adresse = user.adresse || ''
    form.password = ''
    form.roles = [...user.roles]
    form.permissions = [...user.permissions]
    form.is_active = user.is_active
    form.profile_photo = null
    isModalOpen.value = true
}

function closeModal() {
    isModalOpen.value = false
    form.reset()
    photoPreview.value = null
}

function submitForm() {
    if (isEditing.value) {
        form.transform((data) => ({
            ...data,
            _method: 'put'
        })).post(route('users.update', editingUser.value.id), {
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
            <header class="mb-12">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
                    <div class="space-y-1">
                        <div class="flex items-center gap-3">
                            <div class="h-12 w-12 bg-gradient-to-tr from-indigo-600 to-violet-600 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-200">
                                <UsersIcon class="h-7 w-7 text-white" />
                            </div>
                            <h1 class="text-4xl font-black text-gray-900 tracking-tight">Gestion des Utilisateurs</h1>
                        </div>
                        <p class="text-gray-500 font-medium ml-15">Administration des comptes et contrôle des accès plateforme.</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="relative group">
                            <MagnifyingGlassIcon class="h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-indigo-600 transition-colors" />
                            <input 
                                v-model="search"
                                type="text" 
                                placeholder="Rechercher..." 
                                class="pl-12 pr-6 py-4 bg-white border-0 rounded-2xl shadow-sm focus:ring-2 focus:ring-indigo-600 w-64 font-bold text-sm transition-all"
                            >
                        </div>
                        <button 
                            @click="openCreateModal"
                            class="flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-indigo-600 to-violet-600 text-white rounded-2xl font-black text-sm hover:shadow-2xl hover:shadow-indigo-200 transition-all active:scale-95 group"
                        >
                            <PlusIcon class="h-5 w-5 group-hover:rotate-90 transition-transform duration-300" />
                            <span>Nouveau Compte</span>
                        </button>
                    </div>
                </div>

                <!-- Glassmorphic Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white/60 backdrop-blur-xl border border-white rounded-[2rem] p-6 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl">
                                <UsersIcon class="h-6 w-6" />
                            </div>
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Utilisateurs</span>
                        </div>
                        <p class="text-3xl font-black text-gray-900">{{ users.length }}</p>
                        <div class="mt-2 flex items-center gap-1.5 text-green-500 text-[10px] font-black uppercase">
                            <CheckCircleIcon class="h-3 w-3" />
                            Comptes Actifs
                        </div>
                    </div>

                    <div class="bg-white/60 backdrop-blur-xl border border-white rounded-[2rem] p-6 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl">
                                <ShieldCheckIcon class="h-6 w-6" />
                            </div>
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Administrateurs</span>
                        </div>
                        <p class="text-3xl font-black text-gray-900">{{ users.filter(u => u.roles.includes('Administrateur') || u.roles.includes('Directeur')).length }}</p>
                        <div class="mt-2 flex items-center gap-1.5 text-amber-500 text-[10px] font-black uppercase">
                            <StarIcon class="h-3 w-3" />
                            Accès Privilégiés
                        </div>
                    </div>

                    <!-- Filters Section as a Glass Board -->
                    <div class="col-span-1 md:col-span-2 bg-indigo-50/50 backdrop-blur-xl border border-indigo-100/50 rounded-[2rem] p-6 flex items-center justify-between gap-6">
                        <div class="space-y-4 w-full">
                            <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Filtres de recherche</p>
                            <div class="flex gap-4 w-full">
                                <select 
                                    v-model="roleFilter"
                                    class="flex-1 pl-4 pr-10 py-3 bg-white/80 border-0 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-600 font-bold text-xs appearance-none"
                                >
                                    <option value="">Tous les rôles</option>
                                    <option v-for="role in available_roles" :key="role" :value="role">{{ role }}</option>
                                </select>

                                <select 
                                    v-model="statusFilter"
                                    class="flex-1 pl-4 pr-10 py-3 bg-white/80 border-0 rounded-xl shadow-sm focus:ring-2 focus:ring-indigo-600 font-bold text-xs appearance-none"
                                >
                                    <option value="">Tous les statuts</option>
                                    <option value="active">Actif</option>
                                    <option value="inactive">Inactif</option>
                                </select>
                            </div>
                        </div>
                        <div class="h-full w-1 px-4 border-l border-indigo-100 hidden md:block"></div>
                        <div class="hidden md:flex flex-col items-end gap-1 shrink-0">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Tri par défaut</span>
                            <span class="text-sm font-black text-indigo-600">Ordre Alphabétique</span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="bg-white/40 backdrop-blur-md rounded-[2.5rem] border border-white/60 overflow-hidden shadow-sm">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-gray-400 text-[10px] uppercase font-black tracking-widest border-b border-gray-100/50">
                            <th class="px-8 py-6">Profil Utilisateur</th>
                            <th class="px-8 py-6">Rôles & Permissions</th>
                            <th class="px-8 py-6 text-center">Accès Plateforme</th>
                            <th class="px-8 py-6">Inscrit le</th>
                            <th class="px-8 py-6"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100/30">
                        <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50/50 transition-colors group">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center font-black overflow-hidden border-2 border-white shadow-inner">
                                        <img v-if="user.profile_photo_url" :src="user.profile_photo_url" class="h-full w-full object-cover">
                                        <template v-else>{{ user.name.charAt(0) }}</template>
                                    </div>
                                    <div class="space-y-0.5">
                                        <p class="font-black text-gray-900 leading-tight">{{ user.name }}</p>
                                        <div class="flex items-center gap-1.5 text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                                            <EnvelopeIcon class="h-3.5 w-3.5" />
                                            {{ user.email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                             <td class="px-8 py-6">
                                <div class="flex flex-wrap gap-1.5 max-w-[200px]">
                                    <span v-for="role in user.roles" :key="role" class="px-2.5 py-0.5 bg-indigo-50 text-indigo-600 rounded-lg text-[9px] font-black uppercase tracking-wider border border-indigo-100">
                                        {{ role }}
                                    </span>
                                    <span v-for="perm in user.permissions" :key="perm" class="px-2.5 py-0.5 bg-gray-50 text-gray-400 rounded-lg text-[9px] font-black uppercase tracking-wider border border-gray-100">
                                        {{ perm }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-sm ring-1 ring-inset"
                                    :class="user.is_active ? 'bg-green-50 text-green-600 ring-green-500/10' : 'bg-red-50 text-red-600 ring-red-500/10'"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full animate-pulse" :class="user.is_active ? 'bg-green-500' : 'bg-red-500'"></span>
                                    {{ user.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-gray-700">{{ user.created_at }}</span>
                                    <span class="text-[9px] text-gray-300 font-bold uppercase tracking-tighter">Date d'admission</span>
                                </div>
                            </td>
                            <td class="px-8 py-5 text-right">
                            <td class="px-8 py-6 text-right">
                                <div class="flex justify-end gap-3 translate-x-4 opacity-0 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-500">
                                    <button 
                                        @click="openViewModal(user)"
                                        class="p-2.5 bg-white text-indigo-600 rounded-xl hover:bg-indigo-600 hover:text-white transition-all shadow-sm border border-indigo-100 hover:border-indigo-600 active:scale-90"
                                        title="Voir Détails"
                                    >
                                        <EyeIcon class="h-5 w-5" />
                                    </button>
                                    <button 
                                        @click="openEditModal(user)"
                                        class="p-2.5 bg-white text-indigo-600 rounded-xl hover:bg-indigo-600 hover:text-white transition-all shadow-sm border border-indigo-100 hover:border-indigo-600 active:scale-90"
                                        title="Modifier"
                                    >
                                        <PencilSquareIcon class="h-5 w-5" />
                                    </button>
                                    <button 
                                        @click="deleteUser(user.id)"
                                        class="p-2.5 bg-white text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm border border-red-100 hover:border-red-600 active:scale-90"
                                        title="Supprimer"
                                    >
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </td>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- User Management Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-indigo-900/20 backdrop-blur-xl">
            <div class="bg-white/90 backdrop-blur-2xl w-full max-w-lg rounded-[3rem] shadow-[0_32px_64px_-16px_rgba(0,0,0,0.2)] relative max-h-[90vh] flex flex-col overflow-hidden border border-white animate-in zoom-in duration-300">
                <div class="p-10 pb-6 flex justify-between items-center border-b border-gray-100/50 bg-gradient-to-r from-white to-indigo-50/30 sticky top-0 z-10">
                    <div class="space-y-1">
                        <h2 class="text-3xl font-black text-gray-900 tracking-tight">
                            {{ isEditing ? 'Modifier l\'Utilisateur' : 'Nouvel Utilisateur' }}
                        </h2>
                        <p class="text-sm text-gray-500 font-medium">{{ isEditing ? 'Mettre à jour les informations de cet utilisateur.' : 'Créer un nouveau compte système.' }}</p>
                    </div>
                    <button @click="closeModal" class="p-3 text-gray-400 hover:text-indigo-600 hover:bg-white rounded-2xl transition-all shadow-sm group">
                        <XMarkIcon class="h-6 w-6 group-hover:rotate-90 transition-transform" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-8 pt-6 custom-scrollbar">
                    <form @submit.prevent="submitForm" class="space-y-5">
                    <!-- Profile Photo Selection -->
                    <div class="flex flex-col items-center mb-6">
                        <div class="relative group">
                            <div class="h-32 w-32 rounded-[2.5rem] bg-white shadow-xl border-4 border-white flex items-center justify-center overflow-hidden transition-all group-hover:scale-105 group-hover:rotate-2">
                                <img v-if="photoPreview || (isEditing && editingUser?.profile_photo_url)" 
                                     :src="photoPreview || editingUser.profile_photo_url" 
                                     class="h-full w-full object-cover">
                                <PhotoIcon v-else class="h-12 w-12 text-gray-200" />
                            </div>
                            <button 
                                type="button"
                                @click="selectNewPhoto"
                                class="absolute -bottom-2 -right-2 p-3 bg-gradient-to-tr from-indigo-600 to-violet-600 text-white rounded-2xl shadow-xl hover:shadow-indigo-200 transition-all active:scale-95 z-10"
                            >
                                <ArrowUpTrayIcon class="h-5 w-5" />
                            </button>
                        </div>
                        <input ref="photoInput" type="file" class="hidden" @change="handlePhotoChange">
                        <p v-if="form.errors.profile_photo" class="text-[10px] text-red-500 mt-3 font-black uppercase tracking-widest">{{ form.errors.profile_photo }}</p>
                    </div>
                    <!-- Name -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Nom Complet <span class="text-red-500">*</span></label>
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
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Adresse Email <span class="text-red-500">*</span></label>
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
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Téléphone <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <PhoneIcon class="h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
                                <input v-model="form.telephone" type="text" required placeholder="77 000 00 00" autocomplete="none" name="user_phone" class="w-full pl-12 pr-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Adresse <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <MapPinIcon class="h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
                                <input v-model="form.adresse" type="text" required placeholder="Kolda, Dahra" autocomplete="none" name="user_address" class="w-full pl-12 pr-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm">
                            </div>
                        </div>
                    </div>

                    <!-- Roles -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3 ml-1">Rôles Système <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3 p-4 bg-gray-50 rounded-[1.25rem]">
                            <label v-for="role in available_roles" :key="role" class="flex items-center gap-2 cursor-pointer group">
                                <div class="relative flex items-center justify-center h-5 w-5 rounded-lg border-2 border-gray-200 bg-white transition-all group-hover:border-indigo-400"
                                    :class="form.roles.includes(role) ? 'border-indigo-600 bg-indigo-600' : ''"
                                >
                                    <input type="checkbox" :value="role" v-model="form.roles" class="hidden">
                                    <CheckBadgeIcon v-if="form.roles.includes(role)" class="h-3 w-3 text-white" />
                                </div>
                                <span class="text-xs font-bold text-gray-700 select-none">{{ role }}</span>
                            </label>
                        </div>
                        <p v-if="form.errors.roles" class="text-xs text-red-500 mt-1.5 font-bold ml-1">{{ form.errors.roles }}</p>
                    </div>

                    <!-- Permissions -->
                    <div v-if="available_permissions.length > 0">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3 ml-1">Permissions Spécifiques</label>
                        <div class="space-y-2 p-4 bg-gray-50 rounded-[1.25rem]">
                            <label v-for="permission in available_permissions" :key="permission" class="flex items-center gap-2 cursor-pointer group">
                                <div class="relative flex items-center justify-center h-5 w-5 rounded-lg border-2 border-gray-200 bg-white transition-all group-hover:border-indigo-400"
                                    :class="form.permissions.includes(permission) ? 'border-indigo-600 bg-indigo-600' : ''"
                                >
                                    <input type="checkbox" :value="permission" v-model="form.permissions" class="hidden">
                                    <ShieldCheckIcon v-if="form.permissions.includes(permission)" class="h-3 w-3 text-white" />
                                </div>
                                <span class="text-xs font-bold text-gray-600 select-none">{{ permission }}</span>
                            </label>
                        </div>
                        <p v-if="form.errors.permissions" class="text-xs text-red-500 mt-1.5 font-bold ml-1">{{ form.errors.permissions }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">
                            Mot de Passe {{ isEditing ? '(Optionnel)' : '' }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <LockClosedIcon class="h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input 
                                v-model="form.password" 
                                type="password" 
                                :required="!isEditing"
                                placeholder="••••••••"
                                autocomplete="new-password"
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

                    <div class="flex gap-4 pt-6 border-t border-gray-100 pb-2">
                        <button 
                            type="button" 
                            @click="closeModal"
                            class="flex-1 py-4 bg-gray-100 text-gray-400 font-black text-xs uppercase tracking-widest rounded-2xl hover:text-indigo-600 hover:bg-white transition-all shadow-sm"
                        >
                            Annuler
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="flex-[2] py-4 bg-gradient-to-r from-indigo-600 to-violet-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-indigo-100 hover:shadow-indigo-200 transition-all disabled:opacity-50 active:scale-95"
                        >
                            {{ isEditing ? 'Sauvegarder les modifications' : 'Confirmer la création' }}
                        </button>
                    </div>
                </form>
            </div> <!-- End of scrollable content -->
            </div>
        </div>

        <div v-if="isViewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-indigo-900/10 backdrop-blur-2xl">
            <div class="bg-white/90 backdrop-blur-3xl w-full max-w-lg rounded-[4rem] shadow-[0_32px_128px_-32px_rgba(0,0,0,0.3)] relative overflow-hidden max-h-[95vh] flex flex-col border border-white animate-in zoom-in duration-500">
                <div class="h-32 bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 w-full sticky top-0 z-0 shrink-0"></div>
                <button @click="closeViewModal" class="absolute right-8 top-8 p-3 text-white/50 hover:text-white hover:bg-white/10 rounded-[1.25rem] transition-all z-20">
                    <XMarkIcon class="h-7 w-7" />
                </button>

                <div class="relative p-10 pt-4 flex flex-col items-center">
                    <div class="h-32 w-32 bg-white rounded-[2.5rem] shadow-2xl flex items-center justify-center text-4xl font-black text-indigo-600 border-[6px] border-white mb-6 transform hover:rotate-3 transition-transform overflow-hidden relative z-10 -mt-16">
                        <img v-if="selectedUser.profile_photo_url" :src="selectedUser.profile_photo_url" class="h-full w-full object-cover">
                        <template v-else>{{ selectedUser.name.charAt(0) }}</template>
                    </div>
                    <h2 class="text-2xl font-black text-gray-900 tracking-tight">{{ selectedUser.name }}</h2>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <span v-for="role in selectedUser.roles" :key="role" class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full text-[10px] font-black uppercase tracking-wider">
                            {{ role }}
                        </span>
                        <span v-for="perm in selectedUser.permissions" :key="perm" class="px-3 py-1 bg-amber-50 text-amber-600 rounded-full text-[10px] font-black uppercase tracking-wider border border-amber-100">
                            {{ perm }}
                        </span>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto px-10 pb-10 space-y-8 custom-scrollbar">
                    <div class="grid grid-cols-2 gap-8 pt-4">
                        <div class="space-y-1">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Adresse Email</p>
                            <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-2xl border border-gray-100/50">
                                <EnvelopeIcon class="h-5 w-5 text-indigo-400" />
                                <p class="font-black text-gray-700 text-sm truncate">{{ selectedUser.email }}</p>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Téléphone</p>
                            <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-2xl border border-gray-100/50">
                                <PhoneIcon class="h-5 w-5 text-indigo-400" />
                                <p class="font-black text-gray-700 text-sm">{{ selectedUser.telephone || 'Non renseigné' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Adresse de résidence</p>
                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-2xl border border-gray-100/50">
                            <MapPinIcon class="h-5 w-5 text-indigo-400" />
                            <p class="font-black text-gray-700 text-sm">{{ selectedUser.adresse || 'Non renseignée' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-8">
                        <div class="space-y-1">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Statut du Compte</p>
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-sm ring-1 ring-inset"
                                :class="selectedUser.is_active ? 'bg-green-50 text-green-600 ring-green-600/10' : 'bg-red-50 text-red-600 ring-red-600/10'"
                            >
                                <span class="h-1.5 w-1.5 rounded-full animate-pulse" :class="selectedUser.is_active ? 'bg-green-600' : 'bg-red-600'"></span>
                                {{ selectedUser.is_active ? 'Compte Actif' : 'Compte Suspendu' }}
                            </span>
                        </div>
                        <div class="space-y-1 text-right">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mr-1">Date d'inscription</p>
                            <div class="flex flex-col">
                                <span class="text-sm font-black text-gray-700">{{ selectedUser.created_at }}</span>
                                <span class="text-[9px] text-indigo-400 font-bold uppercase tracking-tighter italic">Admission validée</span>
                            </div>
                        </div>
                    </div>
                </div>

                <footer class="p-8 pb-10 px-10 flex gap-4 shrink-0 bg-white shadow-[0_-20px_40px_-20px_rgba(0,0,0,0.1)]">
                    <button 
                        @click="openEditModal(selectedUser); closeViewModal()"
                        class="flex-1 py-4 bg-amber-400 text-white rounded-3xl font-black text-xs uppercase tracking-widest hover:bg-amber-500 transition-all flex items-center justify-center gap-3 shadow-lg shadow-amber-100 active:scale-95"
                    >
                        <PencilSquareIcon class="h-5 w-5" />
                        Modifier le profil
                    </button>
                    <button 
                        @click="closeViewModal"
                        class="px-10 py-4 bg-gray-100 text-gray-400 rounded-3xl font-black text-[10px] uppercase tracking-widest hover:bg-gray-200 hover:text-gray-600 transition-all active:scale-95"
                    >
                        Fermer
                    </button>
                </footer>
            </div> <!-- End of modal inner -->
        </div> <!-- End of modal backdrop -->
    </AuthenticatedLayout>
</template>
