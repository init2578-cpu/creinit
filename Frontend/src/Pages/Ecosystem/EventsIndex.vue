<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DateInput from '@/Components/DateInput.vue'
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue'
import { formatTime, formatDate, storageUrl } from '@/utils/format'
import { 
    CalendarDaysIcon, 
    PlusIcon, 
    UserGroupIcon,
    PhotoIcon,
    PencilSquareIcon,
    TrashIcon,
    PauseCircleIcon,
    PlayCircleIcon,
    MapPinIcon,
    EyeIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    events: Array
})

const page = usePage()
const isDirecteur = computed(() => page.props.auth.user?.roles?.includes('Directeur'))

const showAddModal = ref(false)
const editingEvent = ref(null)
const showDetailModal = ref(false)
const selectedEvent = ref(null)

const form = useForm({
    titre: '',
    type_activite: '',
    date: '',
    audience_estimee: 0,
    description: '',
    lieu: '',
    heure_debut: '',
    heure_fin: '',
    image: null
})

function openAddModal() {
    editingEvent.value = null
    form.reset()
    showAddModal.value = true
}

function openEditModal(event) {
    editingEvent.value = event
    form.titre = event.titre
    form.type_activite = event.type_activite
    // Ensure date is in YYYY-MM-DD format for <input type="date">
    form.date = event.date ? event.date.substring(0, 10) : ''
    form.audience_estimee = event.audience_estimee
    form.description = event.description
    form.lieu = event.lieu || ''
    form.heure_debut = event.heure_debut || ''
    form.heure_fin = event.heure_fin || ''
    form.image = null
    showAddModal.value = true
}

function submit() {
    console.log('Form submission started', {
        editing: !!editingEvent.value,
        hasImage: !!form.image,
        imageName: form.image?.name,
        imageSize: form.image?.size
    })
    if (editingEvent.value) {
        // Use post with _method put for file uploads to work correctly in Laravel
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('ecosystem.events.update', editingEvent.value.id), {
            forceFormData: true,
            onSuccess: () => {
                showAddModal.value = false
                editingEvent.value = null
                form.reset()
            }
        })
    } else {
        form.post(route('ecosystem.events.store'), {
            forceFormData: true,
            onSuccess: () => {
                showAddModal.value = false
                form.reset()
            }
        })
    }
}

function deleteEvent(id) {
    if (confirm('Supprimer cet événement définitivement ?')) {
        router.delete(route('ecosystem.events.destroy', id))
    }
}

function toggleStatus(id) {
    router.patch(route('ecosystem.events.toggle', id))
}

function openDetailModal(event) {
    selectedEvent.value = event
    console.log('Opening detail modal for event', {
        id: event.id,
        image_path: event.image_path,
        full_url: '/storage/' + event.image_path
    })
    showDetailModal.value = true
}
</script>

<template>
    <Head title="Gestion des Événements" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-8 px-4">
            <header class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight">E-CRE Rayonnement</h1>
                    <p class="text-gray-500">Suivre les activités externes et événements communautaires.</p>
                </div>
                <button 
                    v-if="isDirecteur"
                    @click="openAddModal"
                    class="px-5 py-3 bg-pink-600 text-white rounded-2xl font-black flex items-center gap-2 hover:bg-black transition shadow-lg shadow-pink-100"
                >
                    <PlusIcon class="h-5 w-5" />
                    Nouvel Événement
                </button>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div v-for="event in events" :key="event.id" 
                    class="group bg-white rounded-[2.5rem] overflow-hidden shadow-sm border border-gray-100 hover:shadow-2xl hover:shadow-gray-100 transition-all duration-500 flex flex-col"
                    :class="{ 'opacity-60 grayscale-[0.5]': event.status === 'suspendu' }"
                >
                    <!-- Event Image -->
                    <div class="aspect-video relative overflow-hidden bg-gray-100">
                        <img v-if="event.image_path" :src="storageUrl(event.image_path)" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                            <PhotoIcon class="h-12 w-12" />
                        </div>
                        <div class="absolute top-4 left-4 flex gap-2">
                            <span class="px-3 py-1 bg-white/90 backdrop-blur-md rounded-lg text-[10px] font-black uppercase tracking-widest text-gray-900 shadow-sm">
                                {{ event.type_activite }}
                            </span>
                            <span v-if="event.status === 'suspendu'" class="px-3 py-1 bg-red-600 text-white rounded-lg text-[10px] font-black uppercase tracking-widest shadow-sm">
                                Suspendu
                            </span>
                        </div>
                    </div>

                    <div class="p-8 flex-1 flex flex-col">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3 text-gray-400 text-[10px] font-black uppercase tracking-[0.2em]">
                                <CalendarDaysIcon class="h-4 w-4" />
                                {{ formatDate(event.date) }}
                                <span v-if="event.heure_debut" class="ml-2 lowercase">
                                    à {{ formatTime(event.heure_debut) }}
                                </span>
                            </div>
                            <div class="flex gap-1">
                                <button @click="openDetailModal(event)" class="p-2 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-pink-600 transition">
                                    <EyeIcon class="h-4 w-4" />
                                </button>
                                <template v-if="isDirecteur">
                                    <button @click="openEditModal(event)" class="p-2 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-indigo-600 transition">
                                        <PencilSquareIcon class="h-4 w-4" />
                                    </button>
                                    <button @click="toggleStatus(event.id)" class="p-2 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-orange-600 transition">
                                        <PauseCircleIcon v-if="event.status === 'actif'" class="h-4 w-4" />
                                        <PlayCircleIcon v-else class="h-4 w-4" />
                                    </button>
                                    <button @click="deleteEvent(event.id)" class="p-2 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-red-600 transition">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </template>
                            </div>
                        </div>
                        
                        <h3 class="text-2xl font-black text-gray-900 leading-tight mb-4">{{ event.titre }}</h3>
                        
                        <div v-if="event.lieu" class="flex items-center gap-2 mb-4 text-xs font-bold text-gray-500 bg-gray-50 self-start px-3 py-1.5 rounded-full border border-gray-100">
                            <MapPinIcon class="h-3.5 w-3.5 text-pink-500" />
                            {{ event.lieu }}
                        </div>

                        <p class="text-gray-500 text-sm font-medium line-clamp-3 mb-8">
                            {{ event.description || 'Synthèse de l\'événement en cours de rédaction...' }}
                        </p>

                        <div class="mt-auto flex items-center justify-between pt-6 border-t border-gray-50">
                            <div class="flex items-center gap-2">
                                <UserGroupIcon class="h-5 w-5 text-pink-600" />
                                <span class="font-black text-gray-900">{{ event.audience_estimee }} <span class="text-[10px] text-gray-400 font-bold uppercase ml-1">Audience</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Modal -->
            <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
                <div class="bg-white w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl overflow-y-auto max-h-[90vh]">
                    <h2 class="text-2xl font-black text-gray-900 mb-6 tracking-tight">
                        {{ editingEvent ? 'Modifier' : 'Ajouter' }} un Événement
                    </h2>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-1">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Titre</label>
                                <input v-model="form.titre" type="text" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4 focus:ring-2 focus:ring-pink-600">
                                <p v-if="form.errors.titre" class="mt-1 text-[10px] text-red-600 font-bold uppercase tracking-widest">{{ form.errors.titre }}</p>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Lieu</label>
                                <input v-model="form.lieu" type="text" placeholder="Ex: Casablanca, Siege" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4 focus:ring-2 focus:ring-pink-600">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Type d'activité</label>
                                <input v-model="form.type_activite" type="text" placeholder="Ex: Salon, Conférence" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4">
                                <p v-if="form.errors.type_activite" class="mt-1 text-[10px] text-red-600 font-bold uppercase tracking-widest">{{ form.errors.type_activite }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Date (jj/mm/aaaa)</label>
                                <DateInput v-model="form.date" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4" />
                                <p v-if="form.errors.date" class="mt-1 text-[10px] text-red-600 font-bold uppercase tracking-widest">{{ form.errors.date }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Heure Début</label>
                                <input v-model="form.heure_debut" type="time" step="1" lang="fr" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Heure Fin</label>
                                <input v-model="form.heure_fin" type="time" step="1" lang="fr" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Audience Estimée</label>
                            <input v-model="form.audience_estimee" type="number" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4">
                            <p v-if="form.errors.audience_estimee" class="mt-1 text-[10px] text-red-600 font-bold uppercase tracking-widest">{{ form.errors.audience_estimee }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Description</label>
                            <textarea v-model="form.description" rows="3" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4"></textarea>
                            <p v-if="form.errors.description" class="mt-1 text-[10px] text-red-600 font-bold uppercase tracking-widest">{{ form.errors.description }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Photo de couverture</label>
                            <input @change="e => form.image = e.target.files[0]" type="file" mode="image" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-black file:bg-pink-50 file:text-pink-700 hover:file:bg-pink-100">
                            <p v-if="form.errors.image" class="mt-1 text-[10px] text-red-600 font-bold uppercase tracking-widest">{{ form.errors.image }}</p>
                        </div>
                        <div class="flex gap-4 mt-8">
                            <button @click="showAddModal = false" type="button" class="flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black">Annuler</button>
                            <button :disabled="form.processing" type="submit" class="flex-[2] py-4 bg-pink-600 text-white rounded-2xl font-black shadow-lg shadow-pink-100">
                                {{ editingEvent ? 'Mettre à jour' : 'Publier' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Detail Modal -->
            <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
                <div class="bg-white w-full max-w-2xl rounded-[2.5rem] overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
                    <div class="aspect-video relative bg-gray-100">
                        <img v-if="selectedEvent.image_path" :src="storageUrl(selectedEvent.image_path)" class="w-full h-full object-cover">
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                            <PhotoIcon class="h-20 w-20" />
                        </div>
                        <button @click="showDetailModal = false" class="absolute top-6 right-6 p-2 bg-white/20 backdrop-blur-md rounded-full text-white hover:bg-white/40 transition">
                            <PlusIcon class="h-6 w-6 rotate-45" />
                        </button>
                    </div>
                    <div class="p-10 overflow-y-auto">
                        <div class="flex items-center gap-4 mb-6">
                            <span class="px-4 py-1.5 bg-pink-50 text-pink-600 rounded-xl text-xs font-black uppercase tracking-widest border border-pink-100">
                                {{ selectedEvent.type_activite }}
                            </span>
                            <div class="flex items-center gap-2 text-gray-400 text-xs font-bold font-mono">
                                <CalendarDaysIcon class="h-4 w-4" />
                                {{ formatDate(selectedEvent.date) }}
                            </div>
                        </div>

                        <div v-if="selectedEvent.heure_debut" class="flex items-center gap-4 mb-8">
                            <div class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl text-sm font-black border border-indigo-100 flex items-center gap-2">
                                <span class="text-[10px] uppercase opacity-60">De</span>
                                {{ formatTime(selectedEvent.heure_debut) }}
                            </div>
                            <div v-if="selectedEvent.heure_fin" class="px-4 py-2 bg-gray-50 text-gray-600 rounded-xl text-sm font-black border border-gray-100 flex items-center gap-2">
                                <span class="text-[10px] uppercase opacity-60">À</span>
                                {{ formatTime(selectedEvent.heure_fin) }}
                            </div>
                        </div>

                        <h2 class="text-4xl font-black text-gray-900 mb-6 tracking-tight leading-tight">{{ selectedEvent.titre }}</h2>

                        <div v-if="selectedEvent.lieu" class="flex items-center gap-2 mb-8 text-sm font-bold text-gray-600 bg-gray-50 self-start px-4 py-2 rounded-xl border border-gray-100">
                            <MapPinIcon class="h-4 w-4 text-pink-500" />
                            {{ selectedEvent.lieu }}
                        </div>

                        <div class="prose prose-pink max-w-none">
                            <p class="text-gray-500 text-lg leading-relaxed whitespace-pre-wrap">{{ selectedEvent.description || 'Pas de description détaillée disponible.' }}</p>
                        </div>

                        <div class="mt-10 flex items-center justify-between pt-8 border-t border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="h-12 w-12 bg-pink-50 rounded-2xl flex items-center justify-center text-pink-600">
                                    <UserGroupIcon class="h-6 w-6" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Impact estimé</p>
                                    <p class="text-xl font-black text-gray-900 leading-none">{{ selectedEvent.audience_estimee }} <span class="text-xs text-gray-400">personnes</span></p>
                                </div>
                            </div>
                            <button @click="showDetailModal = false" class="px-8 py-4 bg-gray-900 text-white rounded-2xl font-black hover:bg-black transition">Fermer</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
