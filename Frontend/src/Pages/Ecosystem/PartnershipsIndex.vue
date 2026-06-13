<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DateInput from '@/Components/DateInput.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { formatTime, formatDate } from '@/utils/format'
import { 
    UsersIcon, 
    PlusIcon, 
    DocumentTextIcon,
    TrashIcon,
    BuildingOfficeIcon,
    CheckBadgeIcon,
    PencilSquareIcon,
    PauseCircleIcon,
    PlayCircleIcon,
    MapPinIcon,
    EyeIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    partnerships: Array
})

const showAddModal = ref(false)
const editingPartner = ref(null)
const showDetailModal = ref(false)
const selectedPartner = ref(null)

const form = useForm({
    nom: '',
    type: 'privé',
    description: '',
    date_signature: '',
    localisation_gps: '',
    heure_signature: '',
    website: '',
    logo: null,
    document: null
})

function openAddModal() {
    editingPartner.value = null
    form.reset()
    showAddModal.value = true
}

function openEditModal(partner) {
    editingPartner.value = partner
    form.nom = partner.nom
    form.type = partner.type
    form.description = partner.description
    form.date_signature = partner.date_signature ? partner.date_signature.substring(0, 10) : ''
    form.localisation_gps = partner.localisation_gps || ''
    form.heure_signature = partner.heure_signature || ''
    form.website = partner.website || ''
    form.logo = null
    form.document = null
    showAddModal.value = true
}

function submit() {
    if (editingPartner.value) {
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('ecosystem.partnerships.update', editingPartner.value.id), {
            forceFormData: true,
            onSuccess: () => {
                showAddModal.value = false
                editingPartner.value = null
                form.reset()
            }
        })
    } else {
        form.post(route('ecosystem.partnerships.store'), {
            forceFormData: true,
            onSuccess: () => {
                showAddModal.value = false
                form.reset()
            }
        })
    }
}

function deletePartner(id) {
    if (confirm('Supprimer ce partenariat ?')) {
        router.delete(route('ecosystem.partnerships.destroy', id))
    }
}

function toggleStatus(id) {
    router.patch(route('ecosystem.partnerships.toggle', id))
}

function openDetailModal(partner) {
    selectedPartner.value = partner
    console.log('Opening detail modal for partnership', {
        id: partner.id,
        logo_path: partner.logo_path,
        full_url: '/storage/' + partner.logo_path
    })
    showDetailModal.value = true
}
</script>

<template>
    <Head title="Gestion des Partenariats" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-8 px-4">
            <header class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight">Partenariats</h1>
                    <p class="text-gray-500">Rayonnement institutionnel et alliances stratégiques.</p>
                </div>
                <button 
                    @click="openAddModal"
                    class="px-5 py-3 bg-indigo-600 text-white rounded-2xl font-black flex items-center gap-2 hover:bg-black transition shadow-lg shadow-indigo-100"
                >
                    <PlusIcon class="h-5 w-5" />
                    Nouveau Partenaire
                </button>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="partner in partnerships" :key="partner.id" 
                    class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 flex flex-col group hover:shadow-xl hover:shadow-gray-100 transition-all duration-500"
                    :class="{ 'opacity-60 grayscale-[0.5]': partner.status === 'suspendu' }"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div class="h-12 w-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                            <BuildingOfficeIcon class="h-6 w-6" />
                        </div>
                        <div class="flex gap-2">
                             <span v-if="partner.status === 'suspendu'" class="px-3 py-1 bg-red-600 text-white text-[10px] font-black uppercase tracking-widest rounded-lg">
                                Suspendu
                            </span>
                            <span class="px-3 py-1 bg-gray-100 text-gray-500 text-[10px] font-black uppercase tracking-widest rounded-lg">
                                {{ partner.type }}
                            </span>
                        </div>
                    </div>
                    
                    <h3 class="text-xl font-black text-gray-900 mb-2 truncate">{{ partner.nom }}</h3>
                    
                    <div v-if="partner.localisation_gps" class="flex items-center gap-2 mb-4 text-[10px] font-bold text-indigo-500 bg-indigo-50/50 self-start px-2 py-1 rounded-lg border border-indigo-100">
                        <MapPinIcon class="h-3 w-3" />
                        {{ partner.localisation_gps }}
                    </div>

                    <p class="text-gray-500 text-sm font-medium line-clamp-2 mb-6 flex-1">
                        {{ partner.description || 'Aucune description fournie.' }}
                    </p>

                    <div class="pt-4 border-t border-gray-50 flex items-center justify-between">
                        <div class="flex items-center gap-2 text-gray-400">
                            <CheckBadgeIcon class="h-4 w-4" />
                            <span class="text-[10px] font-black uppercase tracking-widest">
                                {{ formatDate(partner.date_signature) }}
                                <span v-if="partner.heure_signature" class="ml-1 opacity-60">
                                    à {{ formatTime(partner.heure_signature) }}
                                </span>
                            </span>
                        </div>
                        <div class="flex gap-1">
                            <button @click="openDetailModal(partner)" class="p-2 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-indigo-600 transition">
                                <EyeIcon class="h-4 w-4" />
                            </button>
                            <button @click="openEditModal(partner)" class="p-2 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-indigo-600 transition">
                                <PencilSquareIcon class="h-4 w-4" />
                            </button>
                            <button @click="toggleStatus(partner.id)" class="p-2 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-orange-600 transition">
                                <PauseCircleIcon v-if="partner.status === 'actif'" class="h-4 w-4" />
                                <PlayCircleIcon v-else class="h-4 w-4" />
                            </button>
                            <button @click="deletePartner(partner.id)" class="p-2 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-red-600 transition">
                                <TrashIcon class="h-4 w-4" />
                            </button>
                            <a v-if="partner.document_path" :href="'/storage/' + partner.document_path" target="_blank" class="p-2 bg-gray-50 text-gray-400 hover:text-indigo-600 rounded-lg transition">
                                <DocumentTextIcon class="h-5 w-5" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Modal -->
            <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
                <div class="bg-white w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl">
                    <h2 class="text-2xl font-black text-gray-900 mb-6 tracking-tight">
                         {{ editingPartner ? 'Modifier' : 'Nouveau' }} Partenariat
                    </h2>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-1">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Nom de l'organisation</label>
                                <input v-model="form.nom" type="text" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4 focus:ring-2 focus:ring-indigo-600">
                                <p v-if="form.errors.nom" class="mt-1 text-[10px] text-red-600 font-bold uppercase tracking-widest">{{ form.errors.nom }}</p>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Localisation GPS</label>
                                <input v-model="form.localisation_gps" type="text" placeholder="Ex: 33.5731, -7.5898" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4 focus:ring-2 focus:ring-indigo-600">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Type</label>
                                <select v-model="form.type" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4">
                                    <option value="institutionnel">Institutionnel</option>
                                    <option value="privé">Privé</option>
                                    <option value="ONG">ONG</option>
                                    <option value="académique">Académique</option>
                                </select>
                                <p v-if="form.errors.type" class="mt-1 text-[10px] text-red-600 font-bold uppercase tracking-widest">{{ form.errors.type }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Date Signature (jj/mm/aaaa)</label>
                                <DateInput v-model="form.date_signature" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4" />
                                <p v-if="form.errors.date_signature" class="mt-1 text-[10px] text-red-600 font-bold uppercase tracking-widest">{{ form.errors.date_signature }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-1">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Heure Signature</label>
                                <input v-model="form.heure_signature" type="time" step="1" lang="fr" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Description</label>
                            <textarea v-model="form.description" rows="3" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4"></textarea>
                            <p v-if="form.errors.description" class="mt-1 text-[10px] text-red-600 font-bold uppercase tracking-widest">{{ form.errors.description }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Site Web (URL)</label>
                            <input v-model="form.website" type="url" placeholder="https://exemple.com" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4 focus:ring-2 focus:ring-indigo-600">
                            <p v-if="form.errors.website" class="mt-1 text-[10px] text-red-600 font-bold uppercase tracking-widest">{{ form.errors.website }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Logo (Image)</label>
                            <input @change="e => form.logo = e.target.files[0]" type="file" accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-black file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            <p v-if="form.errors.logo" class="mt-1 text-[10px] text-red-600 font-bold uppercase tracking-widest">{{ form.errors.logo }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Convention (PDF)</label>
                            <input @change="e => form.document = e.target.files[0]" type="file" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-black file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            <p v-if="form.errors.document" class="mt-1 text-[10px] text-red-600 font-bold uppercase tracking-widest">{{ form.errors.document }}</p>
                        </div>
                        <div class="flex gap-4 mt-8">
                            <button @click="showAddModal = false" type="button" class="flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black">Annuler</button>
                            <button :disabled="form.processing" type="submit" class="flex-[2] py-4 bg-indigo-600 text-white rounded-2xl font-black shadow-lg shadow-indigo-100">
                                {{ editingPartner ? 'Mettre à jour' : 'Enregistrer' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Detail Modal -->
            <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
                <div class="bg-white w-full max-w-2xl rounded-[2.5rem] overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
                    <div class="p-10 overflow-y-auto">
                        <div class="flex items-center justify-between mb-8">
                            <div class="h-20 w-20 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center">
                                <BuildingOfficeIcon class="h-10 w-10" />
                            </div>
                            <div class="text-right">
                                <span class="px-4 py-1.5 bg-indigo-50 text-indigo-600 rounded-xl text-xs font-black uppercase tracking-widest border border-indigo-100">
                                    {{ selectedPartner.type }}
                                </span>
                                <div class="mt-4 flex items-center justify-end gap-2 text-gray-400 text-xs font-bold font-mono">
                                    <CheckBadgeIcon class="h-4 w-4" />
                                    {{ formatDate(selectedPartner.date_signature) }}
                                    <span v-if="selectedPartner.heure_signature" class="ml-1 opacity-60">
                                        à {{ formatTime(selectedPartner.heure_signature) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <h2 class="text-4xl font-black text-gray-900 mb-6 tracking-tight leading-tight">{{ selectedPartner.nom }}</h2>

                        <div v-if="selectedPartner.localisation_gps" class="flex items-center gap-2 mb-8 text-sm font-bold text-gray-600 bg-gray-50 self-start px-4 py-2 rounded-xl border border-gray-100">
                            <MapPinIcon class="h-4 w-4 text-indigo-500" />
                            {{ selectedPartner.localisation_gps }}
                        </div>

                        <div class="prose prose-indigo max-w-none mb-10">
                            <p class="text-gray-500 text-lg leading-relaxed whitespace-pre-wrap">{{ selectedPartner.description || 'Pas de description détaillée disponible.' }}</p>
                        </div>

                        <div v-if="selectedPartner.document_path" class="p-6 bg-gray-50 rounded-3xl border border-gray-100 flex items-center justify-between mb-10">
                            <div class="flex items-center gap-4">
                                <div class="h-12 w-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-indigo-600 border border-gray-50">
                                    <DocumentTextIcon class="h-6 w-6" />
                                </div>
                                <div>
                                    <p class="text-sm font-black text-gray-900">Convention Signée</p>
                                    <p class="text-xs text-gray-400 font-bold">Document PDF accessible</p>
                                </div>
                            </div>
                            <a :href="'/storage/' + selectedPartner.document_path" target="_blank" class="px-6 py-3 bg-indigo-600 text-white rounded-xl font-black hover:bg-black transition shadow-lg shadow-indigo-100">
                                Consulter
                            </a>
                        </div>

                        <div class="flex justify-end pt-8 border-t border-gray-100">
                            <button @click="showDetailModal = false" class="px-8 py-4 bg-gray-900 text-white rounded-2xl font-black hover:bg-black transition">Fermer</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
