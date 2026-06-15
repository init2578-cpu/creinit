<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    XMarkIcon,
    HomeModernIcon,
    UsersIcon,
    TagIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    rooms: Array
})

const isModalOpen = ref(false)
const isEditing = ref(false)
const editingId = ref(null)

const form = useForm({
    nom: '',
    capacite: '',
    type_salle: ''
})

const page = usePage()
const isDirecteur = computed(() => page.props.auth.user?.roles?.includes('Directeur'))

const openCreateModal = () => {
    isEditing.value = false
    form.reset()
    isModalOpen.value = true
}

const openEditModal = (room) => {
    isEditing.value = true
    editingId.value = room.id
    form.nom = room.nom
    form.capacite = room.capacite
    form.type_salle = room.type_salle
    isModalOpen.value = true
}

const submit = () => {
    if (isEditing.value) {
        form.put(route('rooms.update', editingId.value), {
            onSuccess: () => closeModal()
        })
    } else {
        form.post(route('rooms.store'), {
            onSuccess: () => closeModal()
        })
    }
}

const closeModal = () => {
    isModalOpen.value = false
    form.reset()
}

const deleteRoom = (id) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette salle ?')) {
        router.delete(route('rooms.destroy', id))
    }
}
</script>

<template>
    <Head title="Gestion des Salles" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-3xl font-black text-gray-900 tracking-tight">Gestion des Salles</h2>
                    <p class="text-gray-500 font-medium">Configurez les espaces physiques de formation.</p>
                </div>
                <button 
                    v-if="isDirecteur"
                    @click="openCreateModal"
                    class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3.5 rounded-2xl font-black transition-all shadow-xl shadow-indigo-100 active:scale-95"
                >
                    <PlusIcon class="h-5 w-5" />
                    Ajouter une Salle
                </button>
            </div>

            <!-- Rooms List -->
            <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Nom de la Salle</th>
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Capacité</th>
                            <th class="px-8 py-5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Type</th>
                            <th v-if="isDirecteur" class="px-8 py-5 text-[10px] font-black text-gray-400 text-right uppercase tracking-widest">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="room in rooms" :key="room.id" class="group hover:bg-gray-50/50 transition-colors">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600">
                                        <HomeModernIcon class="h-5 w-5" />
                                    </div>
                                    <span class="font-black text-gray-900">{{ room.nom }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2 text-gray-500 font-bold">
                                    <UsersIcon class="h-4 w-4 text-gray-300" />
                                    {{ room.capacite }} places
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <span 
                                    class="px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-full"
                                    :class="room.type_salle === 'Site de Stage' || room.type_salle === 'Espace Extérieur' 
                                        ? 'bg-amber-100 text-amber-700'
                                        : 'bg-gray-100 text-gray-600'"
                                >
                                    {{ room.type_salle }}
                                </span>
                            </td>
                            <td v-if="isDirecteur" class="px-8 py-6">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        @click="openEditModal(room)"
                                        class="p-2.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition"
                                    >
                                        <PencilSquareIcon class="h-5 w-5" />
                                    </button>
                                    <button 
                                        @click="deleteRoom(room.id)"
                                        class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition"
                                    >
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="rooms.length === 0">
                            <td colspan="4" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <HomeModernIcon class="h-12 w-12 text-gray-200 mb-4" />
                                    <p class="text-gray-400 font-bold">Aucune salle configurée.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm">
            <div class="bg-white w-full max-w-lg rounded-[2.5rem] overflow-hidden shadow-2xl animate-in fade-in zoom-in duration-300">
                <div class="p-8 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-2xl font-black text-gray-900 tracking-tight">
                        {{ isEditing ? 'Modifier la Salle' : 'Nouvelle Salle' }}
                    </h3>
                    <button @click="closeModal" class="p-2 hover:bg-gray-100 rounded-xl transition">
                        <XMarkIcon class="h-6 w-6 text-gray-400" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-8 space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Nom de la Salle</label>
                        <input 
                            v-model="form.nom" 
                            type="text" 
                            placeholder="Ex: Salle A1, Labo Info..."
                            required 
                            class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 font-bold px-5 py-4 transition-all"
                        >
                        <p v-if="form.errors.nom" class="text-red-500 text-xs mt-1 font-bold">{{ form.errors.nom }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Capacité</label>
                            <input 
                                v-model="form.capacite" 
                                type="number" 
                                min="1"
                                placeholder="30"
                                required 
                                class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 font-bold px-5 py-4 transition-all"
                            >
                            <p v-if="form.errors.capacite" class="text-red-500 text-xs mt-1 font-bold">{{ form.errors.capacite }}</p>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Type de Salle</label>
                            <select v-model="form.type_salle" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-indigo-500 font-bold px-5 py-4 appearance-none transition-all">
                                <option value="">Choisir un type</option>
                                <option value="Salle de cours">Salle de cours</option>
                                <option value="Labo Informatique">Labo Informatique</option>
                                <option value="Atelier">Atelier</option>
                                <option value="Conférence">Salle de Conférence</option>
                                <option value="Site de Stage">Site de Stage (Chantier Terrain)</option>
                                <option value="Espace Extérieur">Espace Extérieur / Terrain</option>
                            </select>
                            <p v-if="form.errors.type_salle" class="text-red-500 text-xs mt-1 font-bold">{{ form.errors.type_salle }}</p>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="w-full bg-gray-900 hover:bg-black text-white py-5 rounded-[1.5rem] font-black transition-all shadow-xl active:scale-[0.98] disabled:opacity-50"
                        >
                            {{ form.processing ? 'Traitement...' : (isEditing ? 'Mettre à jour' : 'Créer la Salle') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
