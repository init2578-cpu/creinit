<script setup>
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { 
    XMarkIcon, 
    MegaphoneIcon, 
    ExclamationTriangleIcon, 
    CheckCircleIcon, 
    CheckIcon,
    CalendarIcon,
    PaperClipIcon,
    PhotoIcon,
    VideoCameraIcon,
    DocumentTextIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    show: Boolean,
    roles: Array,
    announcement: Object
})

const emit = defineEmits(['close'])

const form = useForm({
    title: '',
    content: '',
    category: 'info',
    visibility_roles: [],
    is_pinned: false,
    is_anonymous: false,
    expires_at: '',
    files: []
})

watch(() => props.show, (newShow) => {
    if (newShow && props.announcement) {
        form.title = props.announcement.title
        form.content = props.announcement.content
        form.category = props.announcement.category || 'info'
        form.visibility_roles = props.announcement.visibility_roles || []
        form.is_pinned = !!props.announcement.is_pinned
        form.is_anonymous = !!props.announcement.is_anonymous
        form.expires_at = props.announcement.expires_at ? props.announcement.expires_at.slice(0, 16) : ''
    } else if (!newShow) {
        form.reset()
        previews.value = []
    }
})

const fileInput = ref(null)
const previews = ref([])

const handleFileSelect = (event) => {
    const selectedFiles = Array.from(event.target.files)
    form.files = [...form.files, ...selectedFiles]
    
    selectedFiles.forEach(file => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader()
            reader.onload = (e) => previews.value.push({ url: e.target.result, type: 'image', name: file.name })
            reader.readAsDataURL(file)
        } else if (file.type.startsWith('video/')) {
            previews.value.push({ type: 'video', name: file.name })
        } else {
            previews.value.push({ type: 'doc', name: file.name })
        }
    })
}

const removeFile = (index) => {
    form.files.splice(index, 1)
    previews.value.splice(index, 1)
}

const categories = [
    { id: 'info', name: 'Information', icon: MegaphoneIcon, color: 'text-blue-600 bg-blue-50' },
    { id: 'warning', name: 'Alerte / Avis', icon: ExclamationTriangleIcon, color: 'text-amber-600 bg-amber-50' },
    { id: 'event', name: 'Événement', icon: CalendarIcon, color: 'text-purple-600 bg-purple-50' },
    { id: 'success', name: 'Succès / Félicitations', icon: CheckCircleIcon, color: 'text-emerald-600 bg-emerald-50' },
]

const submit = () => {
    if (props.announcement) {
        form.put(route('community.update', props.announcement.id), {
            onSuccess: () => {
                emit('close')
            }
        })
    } else {
        form.post(route('community.store'), {
            forceFormData: true,
            onSuccess: () => {
                form.reset()
                emit('close')
                previews.value = []
            }
        })
    }
}

const toggleRole = (roleName) => {
    const index = form.visibility_roles.indexOf(roleName)
    if (index === -1) {
        form.visibility_roles.push(roleName)
    } else {
        form.visibility_roles.splice(index, 1)
    }
}
</script>

<template>
    <div v-show="show" class="fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div @click="emit('close')" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-middle bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">
                <div class="bg-white px-8 pt-8 pb-6">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-2xl font-black text-gray-900" id="modal-title">
                                {{ announcement ? 'Modifier le message' : 'Publier un message' }}
                            </h3>
                            <p class="text-sm font-medium text-gray-500">
                                {{ announcement ? 'Mettez à jour vos informations' : 'Partagez des informations avec la communauté' }}
                            </p>
                        </div>
                        <button @click="emit('close')" class="p-2 rounded-xl hover:bg-gray-100 transition-colors">
                            <XMarkIcon class="h-6 w-6 text-gray-400" />
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Type of Announcement -->
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Catégorie du message</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <button 
                                    v-for="cat in categories" 
                                    :key="cat.id" 
                                    type="button"
                                    @click="form.category = cat.id"
                                    :class="[
                                        'p-3 rounded-2xl border-2 transition-all duration-200 flex flex-col items-center gap-2 group',
                                        form.category === cat.id 
                                            ? 'border-blue-600 bg-blue-50/50 ring-4 ring-blue-50' 
                                            : 'border-gray-100 hover:border-gray-200 bg-white'
                                    ]"
                                >
                                    <component :is="cat.icon" :class="['h-6 w-6', form.category === cat.id ? 'text-blue-600' : 'text-gray-400']" />
                                    <span :class="['text-[10px] font-bold uppercase tracking-tight', form.category === cat.id ? 'text-blue-700' : 'text-gray-500']">
                                        {{ cat.name }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Title -->
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Titre du message</label>
                            <input 
                                v-model="form.title" 
                                type="text"
                                placeholder="Indiquez l'objet de votre message..."
                                class="w-full rounded-2xl border-gray-100 bg-gray-50/50 px-4 py-3 text-sm font-medium focus:border-blue-500 focus:ring-blue-500 transition-all"
                                :class="{ 'border-red-300 ring-1 ring-red-100': form.errors.title }"
                            />
                            <p v-if="form.errors.title" class="mt-1 text-[10px] text-red-500 font-bold uppercase tracking-tight">{{ form.errors.title }}</p>
                        </div>

                        <!-- Content -->
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Contenu du message</label>
                            <textarea 
                                v-model="form.content" 
                                rows="5"
                                placeholder="Rédigez votre message ici..."
                                class="w-full rounded-2xl border-gray-100 bg-gray-50/50 px-4 py-3 text-sm font-medium focus:border-blue-500 focus:ring-blue-500 transition-all resize-none"
                                :class="{ 'border-red-300 ring-1 ring-red-100': form.errors.content }"
                            ></textarea>
                            <p v-if="form.errors.content" class="mt-1 text-[10px] text-red-500 font-bold uppercase tracking-tight">{{ form.errors.content }}</p>
                        </div>

                        <!-- Visibility Filters -->
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Visibilité (Filtrer par rôle)</label>
                            <p class="text-[10px] text-gray-400 mb-3 italic">
                                Cliquez sur un rôle pour le sélectionner. Laissez vide pour tous les utilisateurs.
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    v-for="role in roles" 
                                    :key="role.id" 
                                    type="button"
                                    @click="toggleRole(role.name)"
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-black transition-all duration-200 border-2',
                                        form.visibility_roles.includes(role.name)
                                            ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-100 ring-4 ring-blue-50'
                                            : 'bg-white text-gray-500 border-gray-200 hover:border-blue-300 hover:text-blue-600'
                                    ]"
                                >
                                    <span 
                                        class="h-3.5 w-3.5 rounded-full border-2 flex items-center justify-center shrink-0 transition-all"
                                        :class="form.visibility_roles.includes(role.name) 
                                            ? 'border-white bg-white' 
                                            : 'border-gray-300'"
                                    >
                                        <svg v-if="form.visibility_roles.includes(role.name)" xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 00-1.414 0L8 12.586 4.707 9.293a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l8-8a1 1 0 000-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                    {{ role.name }}
                                </button>
                            </div>
                            <p v-if="form.visibility_roles.length > 0" class="mt-2 text-[10px] text-blue-600 font-black">
                                ✔ {{ form.visibility_roles.length }} rôle(s) sélectionné(s) &mdash; seuls ces utilisateurs verront ce message.
                            </p>
                            <p v-else class="mt-2 text-[10px] text-gray-400 font-bold">
                                Aucun filtre &mdash; visible par tout le monde.
                            </p>
                        </div>

                        <!-- Attachments Section -->
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Pièces Jointes (Images, Vidéos, Documents)</label>
                            
                            <div class="space-y-4">
                                <div class="flex items-center gap-4">
                                    <button 
                                        type="button"
                                        @click="fileInput.click()"
                                        class="inline-flex items-center px-4 py-2 bg-white border-2 border-dashed border-gray-200 rounded-xl text-xs font-bold text-gray-600 hover:border-blue-400 hover:text-blue-600 transition-all group"
                                    >
                                        <PaperClipIcon class="h-4 w-4 mr-2 group-hover:rotate-12 transition-transform" />
                                        Ajouter des fichiers
                                    </button>
                                    <input 
                                        ref="fileInput" 
                                        type="file" 
                                        multiple 
                                        class="hidden" 
                                        @change="handleFileSelect"
                                        accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx"
                                    />
                                    <span class="text-[10px] text-gray-400 font-medium">Taille max : 20Mo par fichier</span>
                                </div>
                                <p v-if="form.errors.files || form.errors['files.0']" class="text-[10px] text-red-500 font-bold uppercase tracking-tight">
                                    {{ form.errors.files || form.errors['files.0'] }}
                                </p>

                                <!-- Previews Grid -->
                                <div v-if="previews.length > 0" class="grid grid-cols-3 sm:grid-cols-4 gap-4">
                                    <div 
                                        v-for="(file, index) in previews" 
                                        :key="index"
                                        class="relative group rounded-2xl overflow-hidden aspect-square bg-gray-50 border border-gray-100 flex flex-col items-center justify-center p-2"
                                    >
                                        <img v-if="file.type === 'image'" :src="file.url" class="absolute inset-0 w-full h-full object-cover" />
                                        
                                        <template v-else>
                                            <component :is="file.type === 'video' ? VideoCameraIcon : DocumentTextIcon" class="h-8 w-8 text-gray-400 mb-1" />
                                            <span class="text-[8px] font-bold text-gray-500 text-center truncate w-full px-1">{{ file.name }}</span>
                                        </template>

                                        <button 
                                            @click="removeFile(index)"
                                            type="button" 
                                            class="absolute top-1 right-1 p-1 bg-red-500 text-white rounded-lg opacity-0 group-hover:opacity-100 transition-opacity"
                                        >
                                            <XMarkIcon class="h-3 w-3" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <!-- Toggle Pin -->
                            <div class="flex items-center gap-3 bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                                <div class="flex-1">
                                    <h4 class="text-xs font-black text-gray-900 uppercase tracking-widest">Épingler</h4>
                                    <p class="text-[10px] text-gray-500 font-medium">Toujours en haut</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.is_pinned" class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <!-- Expiry date -->
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Date d'expiration</label>
                                <input 
                                    v-model="form.expires_at" 
                                    type="datetime-local"
                                    class="w-full rounded-xl border-gray-100 bg-gray-50/50 px-3 py-2 text-xs font-bold focus:border-blue-500 focus:ring-blue-500 transition-all"
                                    :class="{ 'border-red-300 ring-1 ring-red-100': form.errors.expires_at }"
                                />
                                <p v-if="form.errors.expires_at" class="mt-1 text-[10px] text-red-500 font-bold uppercase tracking-tight">{{ form.errors.expires_at }}</p>
                            </div>
                        </div>

                        <!-- Anonymous Toggle -->
                        <div class="pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-between p-4 bg-gray-50/50 rounded-2xl border border-gray-100/50 group cursor-pointer" @click="form.is_anonymous = !form.is_anonymous">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-300"
                                         :class="form.is_anonymous ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-400'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Confidentialité</p>
                                        <p class="text-xs font-bold text-gray-700">Publier de manière anonyme</p>
                                    </div>
                                </div>
                                <div class="w-12 h-6 rounded-full p-1 transition-all duration-300 relative"
                                     :class="form.is_anonymous ? 'bg-indigo-600' : 'bg-gray-200'">
                                    <div class="w-4 h-4 bg-white rounded-full shadow-sm transition-all duration-300 transform"
                                         :class="form.is_anonymous ? 'translate-x-6' : 'translate-x-0'"></div>
                                </div>
                            </div>
                            <p v-if="form.is_anonymous" class="mt-2 px-4 text-[10px] text-indigo-500 font-bold italic">
                                * Personne ne pourra voir l'auteur original de ce message.
                            </p>
                        </div>

                        <div class="flex items-center gap-3 pt-4 font-bold uppercase tracking-widest text-xs">
                            <button 
                                type="button" 
                                @click="emit('close')"
                                class="flex-1 px-6 py-4 bg-gray-100 text-gray-500 rounded-2xl hover:bg-gray-200 transition-all border-none"
                            >
                                Annuler
                            </button>
                            <button 
                                type="submit"
                                :disabled="form.processing"
                                class="flex-[2] px-6 py-4 bg-blue-600 text-white rounded-2xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-200 border-none"
                            >
                                {{ form.processing ? 'Chargement...' : (announcement ? 'Enregistrer les modifications' : 'Publier le message') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
