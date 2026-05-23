<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import { formatTime, formatDate } from '@/utils/format'
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
function resolveImagePath(path) {
    if (!path) return null
    if (path.startsWith('/') || path.startsWith('http')) return path
    return '/storage/' + path
}
</script>

<template>
    <Head title="Gestion des Événements" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <!-- Header Section -->
            <header class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <div class="h-1 w-8 bg-cyan-500 rounded-full"></div>
                        <span class="text-[10px] font-black text-cyan-500 uppercase tracking-widest">Nexus Intelligence</span>
                    </div>
                    <h1 class="text-4xl md:text-5xl font-black text-slate-900 tracking-tighter leading-none mb-4">
                        Flux de <span class="bg-gradient-to-r from-cyan-600 to-indigo-600 bg-clip-text text-transparent italic">Rayonnement</span>
                    </h1>
                    <p class="text-slate-500 font-medium max-w-xl">
                        Gérez les actualités, ateliers et événements qui propulsent l'image du CRE Kolda sur la scène numérique.
                    </p>
                </div>
                
                <button 
                    @click="openAddModal"
                    class="group relative px-6 py-4 bg-slate-900 text-white rounded-2xl font-black flex items-center gap-3 hover:bg-cyan-600 transition-all shadow-2xl hover:translate-y-[-2px] active:scale-95 overflow-hidden"
                >
                    <PlusIcon class="h-5 w-5 group-hover:rotate-90 transition-transform duration-500" />
                    Publier une Actualité
                    <div class="absolute inset-x-0 bottom-0 h-1 bg-white/20 scale-x-0 group-hover:scale-x-100 transition-transform origin-left"></div>
                </button>
            </header>

            <!-- Listing Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div v-for="(event, index) in events" :key="event.id" 
                    class="group relative bg-white/60 backdrop-blur-xl border border-white rounded-[2.5rem] overflow-hidden shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-2xl hover:shadow-cyan-500/10 transition-all duration-500 flex flex-col h-full"
                    :class="{ 'opacity-60 grayscale-[0.5]': event.status === 'suspendu' }"
                >
                    <!-- Visual Indicator -->
                    <div class="absolute top-6 right-6 z-20">
                        <div v-if="event.status === 'actif'" class="h-2 w-2 rounded-full bg-green-500 shadow-[0_0_10px_#22c55e] animate-pulse"></div>
                        <div v-else class="h-2 w-2 rounded-full bg-slate-400"></div>
                    </div>

                    <!-- Image Preview -->
                    <div class="aspect-video relative overflow-hidden bg-slate-100 border-b border-white">
                        <img v-if="event.image_path" :src="resolveImagePath(event.image_path)" class="w-full h-full object-cover group-hover:scale-105 transition duration-1000">
                        <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                            <PhotoIcon class="h-16 w-16 opacity-20" />
                        </div>
                        
                        <!-- Type Badge -->
                        <div class="absolute bottom-4 left-4">
                            <span class="px-3 py-1 bg-slate-900/80 backdrop-blur-md rounded-lg text-[10px] font-black uppercase tracking-widest text-white border border-white/10">
                                {{ event.type_activite }}
                            </span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-8 flex-1 flex flex-col bg-gradient-to-b from-transparent to-white/30">
                        <div class="flex items-center gap-2 text-slate-400 text-[10px] font-black uppercase tracking-[0.2em] mb-4">
                            <CalendarDaysIcon class="h-4 w-4 text-cyan-500" />
                            {{ formatDate(event.date) }}
                            <span v-if="event.heure_debut" class="ml-2 font-mono">
                                {{ formatTime(event.heure_debut) }}
                            </span>
                        </div>
                        
                        <h3 class="text-2xl font-black text-slate-900 leading-tight mb-4 tracking-tight group-hover:text-cyan-600 transition-colors">
                            {{ event.titre }}
                        </h3>
                        
                        <div v-if="event.lieu" class="flex items-center gap-2 mb-6 text-[10px] font-black text-slate-500 bg-slate-50 self-start px-3 py-1.5 rounded-full border border-slate-100 uppercase tracking-widest">
                            <MapPinIcon class="h-3.5 w-3.5 text-cyan-500" />
                            {{ event.lieu }}
                        </div>

                        <p class="text-slate-500 text-sm font-medium leading-relaxed line-clamp-2 mb-8 flex-1">
                            {{ event.description || 'Synthèse de l\'événement en cours de rédaction...' }}
                        </p>

                        <!-- Actions bar -->
                        <div class="mt-auto flex items-center justify-between pt-6 border-t border-slate-100">
                            <div class="flex items-center gap-2">
                                <div class="h-8 w-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-900 font-black text-xs">
                                    {{ event.audience_estimee }}
                                </div>
                                <span class="text-[9px] text-slate-400 font-black uppercase tracking-widest">Impact</span>
                            </div>

                            <div class="flex gap-1">
                                <button @click="openDetailModal(event)" class="p-2 hover:bg-cyan-50 rounded-xl text-slate-400 hover:text-cyan-600 transition-all" title="Aperçu">
                                    <EyeIcon class="h-5 w-5" />
                                </button>
                                <button @click="openEditModal(event)" class="p-2 hover:bg-slate-900 rounded-xl text-slate-400 hover:text-white transition-all" title="Modifier">
                                    <PencilSquareIcon class="h-5 w-5" />
                                </button>
                                <button @click="toggleStatus(event.id)" class="p-2 hover:bg-amber-50 rounded-xl text-slate-400 hover:text-amber-600 transition-all" :title="event.status === 'actif' ? 'Suspendre' : 'Activer'">
                                    <PauseCircleIcon v-if="event.status === 'actif'" class="h-5 w-5" />
                                    <PlayCircleIcon v-else class="h-5 w-5" />
                                </button>
                                <button @click="deleteEvent(event.id)" class="p-2 hover:bg-red-50 rounded-xl text-slate-400 hover:text-red-600 transition-all" title="Supprimer">
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="events.length === 0" class="col-span-full py-32 border-2 border-dashed border-slate-200 rounded-[3rem] text-center flex flex-col items-center justify-center">
                    <div class="h-20 w-20 bg-slate-50 rounded-full flex items-center justify-center mb-6">
                        <CalendarDaysIcon class="h-10 w-10 text-slate-300" />
                    </div>
                    <p class="text-slate-400 font-bold uppercase tracking-widest text-sm">Silence radio : aucun flux d'actualité</p>
                    <button @click="openAddModal" class="mt-6 text-cyan-600 font-black text-xs uppercase tracking-[0.2em] hover:underline">Initialiser une archive</button>
                </div>
            </div>

            <!-- Add/Edit Modal (Glassmorphic) -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-4 scale-95"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 translate-y-4 scale-95"
            >
                <div v-if="showAddModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-md" @click="showAddModal = false"></div>
                    
                    <div class="relative bg-white/90 backdrop-blur-2xl w-full max-w-2xl rounded-[3rem] p-10 shadow-3xl overflow-y-auto max-h-[90vh] border border-white">
                        <div class="mb-10 text-center">
                            <h2 class="text-3xl font-black text-slate-900 tracking-tighter mb-2">
                                {{ editingEvent ? 'Modifier le Rayonnement' : 'Émettre un Flux' }}
                            </h2>
                            <p class="text-slate-500 text-sm font-medium italic">Configurez les paramètres de diffusion de l'événement.</p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="col-span-full md:col-span-1">
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-4">Identification du flux</label>
                                    <input v-model="form.titre" type="text" placeholder="Titre de l'actualité" class="w-full bg-white border border-slate-100 rounded-2xl font-black py-4 px-6 focus:ring-4 focus:ring-cyan-500/10 focus:border-cyan-500 transition-all text-slate-900 placeholder:text-slate-300">
                                    <p v-if="form.errors.titre" class="mt-2 text-[9px] text-red-600 font-black uppercase tracking-widest pl-4">{{ form.errors.titre }}</p>
                                </div>
                                <div class="col-span-full md:col-span-1">
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-4">Localisation</label>
                                    <input v-model="form.lieu" type="text" placeholder="Ex: Campus Kolda" class="w-full bg-white border border-slate-100 rounded-2xl font-black py-4 px-6 focus:ring-4 focus:ring-cyan-500/10 focus:border-cyan-500 transition-all text-slate-900">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-4">Nature</label>
                                    <select v-model="form.type_activite" class="w-full bg-white border border-slate-100 rounded-2xl font-black py-4 px-6 focus:ring-4 focus:ring-cyan-500/10 focus:border-cyan-500 transition-all text-slate-900">
                                        <option value="">Sélectionner</option>
                                        <option value="Atelier">Atelier</option>
                                        <option value="Conférence">Conférence</option>
                                        <option value="Hackathon">Hackathon</option>
                                        <option value="Séminaire">Séminaire</option>
                                        <option value="Innovation">Innovation</option>
                                    </select>
                                    <p v-if="form.errors.type_activite" class="mt-2 text-[9px] text-red-600 font-black uppercase tracking-widest pl-4">{{ form.errors.type_activite }}</p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-4">Chronologie</label>
                                    <input v-model="form.date" type="date" class="w-full bg-white border border-slate-100 rounded-2xl font-black py-4 px-6 focus:ring-4 focus:ring-cyan-500/10 focus:border-cyan-500 transition-all text-slate-900">
                                    <p v-if="form.errors.date" class="mt-2 text-[9px] text-red-600 font-black uppercase tracking-widest pl-4">{{ form.errors.date }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-4">Début</label>
                                    <input v-model="form.heure_debut" type="time" class="w-full bg-white border border-slate-100 rounded-2xl font-black py-4 px-6 focus:ring-4 focus:ring-cyan-500/10 focus:border-cyan-500 transition-all text-slate-900">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-4">Fin</label>
                                    <input v-model="form.heure_fin" type="time" class="w-full bg-white border border-slate-100 rounded-2xl font-black py-4 px-6 focus:ring-4 focus:ring-cyan-500/10 focus:border-cyan-500 transition-all text-slate-900">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-4">Cœur Narrative</label>
                                <textarea v-model="form.description" rows="4" placeholder="Décrivez l'importance de cet événement..." class="w-full bg-white border border-slate-100 rounded-3xl font-medium py-4 px-6 focus:ring-4 focus:ring-cyan-500/10 focus:border-cyan-500 transition-all text-slate-900"></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-4">Impact (Audience)</label>
                                    <input v-model="form.audience_estimee" type="number" class="w-full bg-white border border-slate-100 rounded-2xl font-black py-4 px-6 focus:ring-4 focus:ring-cyan-500/10 focus:border-cyan-500 transition-all text-slate-900">
                                </div>
                                <div class="relative">
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-4">Visuel Archive</label>
                                    <input @change="e => form.image = e.target.files[0]" type="file" mode="image" class="w-full text-[10px] text-slate-500 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:bg-slate-900 file:text-white hover:file:bg-cyan-600 transition-all">
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-4 mt-8">
                                <button @click="showAddModal = false" type="button" class="flex-1 py-5 border border-slate-100 text-slate-400 rounded-3xl font-black uppercase tracking-widest text-xs hover:bg-slate-50 transition-all active:scale-95">Annuler</button>
                                <button :disabled="form.processing" type="submit" class="flex-[2] py-5 bg-slate-900 text-white rounded-3xl font-black uppercase tracking-widest text-xs shadow-2xl hover:bg-cyan-600 transition-all active:scale-95 flex items-center justify-center gap-3">
                                    <SparklesIcon class="h-4 w-4" />
                                    {{ editingEvent ? 'Actualiser le Flux' : 'Diffuser Maintenant' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>

            <!-- Detail Modal (Premium Glass) -->
            <Transition
                enter-active-class="transition duration-400 cubic-bezier(0.34, 1.56, 0.64, 1)"
                enter-from-class="opacity-0 scale-90 translate-y-10"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="transition duration-300 ease-in"
                leave-from-class="opacity-100 scale-100 translate-y-0"
                leave-to-class="opacity-0 scale-90 translate-y-10"
            >
                <div v-if="showDetailModal && selectedEvent" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-slate-900/90 backdrop-blur-xl" @click="showDetailModal = false"></div>
                    
                    <div class="relative bg-white w-full max-w-4xl rounded-[4rem] overflow-hidden shadow-4xl flex flex-col md:flex-row max-h-[90vh]">
                        <!-- Image Section -->
                        <div class="w-full md:w-5/12 h-64 md:h-auto relative bg-slate-100">
                            <img v-if="selectedEvent.image_path" :src="resolveImagePath(selectedEvent.image_path)" class="w-full h-full object-cover">
                            <div v-else class="w-full h-full flex items-center justify-center text-slate-300">
                                <PhotoIcon class="h-24 w-24 opacity-10" />
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-t md:bg-gradient-to-r from-white via-transparent to-transparent"></div>
                        </div>

                        <!-- Content Section -->
                        <div class="w-full md:w-7/12 p-10 md:p-16 overflow-y-auto flex flex-col">
                            <button @click="showDetailModal = false" class="absolute top-10 right-10 text-slate-300 hover:text-slate-900 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>

                            <div class="mb-8">
                                <span class="px-4 py-1.5 bg-cyan-50 text-cyan-600 rounded-xl text-xs font-black uppercase tracking-[0.2em] border border-cyan-100">
                                    {{ selectedEvent.type_activite }}
                                </span>
                            </div>

                            <h2 class="text-4xl md:text-5xl font-black text-slate-900 mb-8 tracking-tighter leading-tight uppercase font-display">
                                {{ selectedEvent.titre }}
                            </h2>

                            <div class="space-y-8 text-slate-500 leading-relaxed text-lg">
                                <p class="whitespace-pre-wrap">{{ selectedEvent.description || 'Pas de description détaillée disponible.' }}</p>
                                
                                <div class="grid grid-cols-2 gap-8 pt-10 border-t border-slate-50">
                                    <div>
                                        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-black mb-2">Localisation</p>
                                        <p class="text-slate-900 font-bold flex items-center gap-2">
                                            <MapPinIcon class="h-5 w-5 text-cyan-500" />
                                            {{ selectedEvent.lieu || 'Non spécifié' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-black mb-2">Chronologie</p>
                                        <p class="text-slate-900 font-bold flex items-center gap-2">
                                            <CalendarDaysIcon class="h-5 w-5 text-indigo-500" />
                                            {{ formatDate(selectedEvent.date) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-auto pt-16 flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="h-14 w-14 bg-slate-900 rounded-2xl flex items-center justify-center text-white shadow-xl">
                                        <UserGroupIcon class="h-8 w-8" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Impact Social</p>
                                        <p class="text-2xl font-black text-slate-900 leading-none">{{ selectedEvent.audience_estimee }} <span class="text-xs text-slate-400 italic">contacts</span></p>
                                    </div>
                                </div>
                                <button @click="showDetailModal = false" class="px-10 py-5 bg-slate-50 text-slate-400 rounded-3xl font-black uppercase tracking-widest text-[10px] hover:bg-slate-100 transition-all hover:text-slate-900">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </AuthenticatedLayout>
</template>
