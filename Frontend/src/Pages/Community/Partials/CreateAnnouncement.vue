<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { XMarkIcon, MegaphoneIcon, ExclamationTriangleIcon, CheckCircleIcon, CalendarIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    show: Boolean,
    roles: Array
})

const emit = defineEmits(['close'])

const form = useForm({
    title: '',
    content: '',
    category: 'info',
    visibility_roles: [],
    is_pinned: false,
    expires_at: ''
})

const categories = [
    { id: 'info', name: 'Information', icon: MegaphoneIcon, color: 'text-blue-600 bg-blue-50' },
    { id: 'warning', name: 'Alerte / Avis', icon: ExclamationTriangleIcon, color: 'text-amber-600 bg-amber-50' },
    { id: 'event', name: 'Événement', icon: CalendarIcon, color: 'text-purple-600 bg-purple-50' },
    { id: 'success', name: 'Succès / Félicitations', icon: CheckCircleIcon, color: 'text-emerald-600 bg-emerald-50' },
]

const submit = () => {
    form.post(route('community.store'), {
        onSuccess: () => {
            form.reset()
            emit('close')
        }
    })
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
                            <h3 class="text-2xl font-black text-gray-900" id="modal-title">Publier un message</h3>
                            <p class="text-sm font-medium text-gray-500">Partagez des informations avec la communauté</p>
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
                            />
                        </div>

                        <!-- Content -->
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Contenu du message</label>
                            <textarea 
                                v-model="form.content" 
                                rows="5"
                                placeholder="Rédigez votre message ici..."
                                class="w-full rounded-2xl border-gray-100 bg-gray-50/50 px-4 py-3 text-sm font-medium focus:border-blue-500 focus:ring-blue-500 transition-all resize-none"
                            ></textarea>
                        </div>

                        <!-- Visibility Filters -->
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Visibilité (Filtrer par rôle)</label>
                            <p class="text-[10px] text-gray-400 mb-2 italic">Laissez vide pour rendre le message public à tous les utilisateurs.</p>
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    v-for="role in roles" 
                                    :key="role.id" 
                                    type="button"
                                    @click="toggleRole(role.name)"
                                    :class="[
                                        'px-4 py-2 rounded-xl text-xs font-bold transition-all',
                                        form.visibility_roles.includes(role.name)
                                            ? 'bg-blue-600 text-white shadow-md shadow-blue-100'
                                            : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
                                    ]"
                                >
                                    {{ role.name }}
                                </button>
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
                                />
                            </div>
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
                                {{ form.processing ? 'Chargement...' : 'Publier le message' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
