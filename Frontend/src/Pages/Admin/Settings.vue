<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { 
    Cog6ToothIcon, 
    GlobeAltIcon, 
    ShieldCheckIcon, 
    CheckIcon,
    BellIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    settings: Object // Grouped by 'group'
})

const tabs = [
    { id: 'general', name: 'Général', icon: Cog6ToothIcon },
    { id: 'attendance', name: 'Émargement & Géo', icon: GlobeAltIcon },
    { id: 'notifications', name: 'Notifications', icon: BellIcon },
]

const activeTab = ref('general')

// Prepare form data
const form = useForm({
    settings: {}
})

// Initialize form data from props
const initForm = () => {
    if (!props.settings) return
    const initialData = {}
    Object.values(props.settings).flat().forEach(s => {
        initialData[s.key] = s.value
    })
    form.settings = { ...initialData }
}

onMounted(() => {
    initForm()
})

const submit = () => {
    form.post(route('settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success notification automatically handled by shared flash
        }
    })
}

const labelMap = {
    cre_latitude: 'Latitude du CRE',
    cre_longitude: 'Longitude du CRE',
    cre_radius: 'Rayon de tolérance (mètres)',
    attendance_buffer_before: 'Délai d\'ouverture avant le cours (min)',
    attendance_buffer_after: 'Délai de fermeture après le cours (min)',
    site_name: 'Nom de la plateforme (Vitrine)',
    site_description: 'Description de l\'établissement',
    contact_email: 'Email de contact support',
    enable_registration: 'Ouverture des inscriptions publiques',
    notify_new_application: 'Notifier Admin (Nouvelle inscription)',
    notify_absence_student: 'Notifier Parent/Élève (Absence)',
    notify_schedule_change: 'Notifier Formateur (Changement planning)',
    email_sender_name: "Nom de l'expéditeur des emails"
}

const formatKey = (key) => {
    return labelMap[key] || key.replace(/_/g, ' ').toUpperCase()
}
</script>

<template>
    <Head title="Paramètres Plateforme" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-8 px-4">
            <header class="mb-10">
                <h1 class="text-4xl font-black text-gray-900 tracking-tight">Configuration Système</h1>
                <p class="text-gray-500 mt-2 font-medium">Gérez les paramètres globaux de la plateforme E-CRE.</p>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Sidebar Tabs -->
                <div class="lg:col-span-1 space-y-2">
                    <button 
                        v-for="tab in tabs" 
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        class="w-full flex items-center gap-3 px-6 py-4 rounded-2xl font-black text-sm transition-all text-left"
                        :class="activeTab === tab.id ? 'bg-blue-600 text-white shadow-lg shadow-blue-100' : 'bg-white text-gray-500 hover:bg-gray-50'"
                    >
                        <component :is="tab.icon" class="h-5 w-5" />
                        {{ tab.name }}
                    </button>
                </div>

                <!-- Settings Form -->
                <div class="lg:col-span-3">
                    <form @submit.prevent="submit" class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm overflow-hidden mb-20">
                        <div class="p-10 space-y-8">
                            <h2 class="text-2xl font-black text-gray-900 capitalize tracking-tight flex items-center gap-3">
                                <component :is="tabs.find(t => t.id === activeTab).icon" class="h-6 w-6 text-blue-600" />
                                Paramètres {{ tabs.find(t => t.id === activeTab).name }}
                            </h2>

                            <div v-if="!props.settings[activeTab] || props.settings[activeTab].length === 0" class="text-center py-20 text-gray-400">
                                <p class="text-sm font-medium">Aucun paramètre trouvé dans cette catégorie.</p>
                            </div>

                            <div v-for="setting in props.settings[activeTab]" :key="setting.id" class="space-y-2 p-6 bg-gray-50/50 rounded-2xl border border-gray-50 group hover:border-gray-200 transition">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest group-hover:text-blue-600 transition">{{ formatKey(setting.key) }}</label>
                                
                                <input v-if="setting.type === 'string' || setting.type === 'integer'" 
                                    v-model="form.settings[setting.key]" 
                                    :type="setting.type === 'integer' ? 'number' : 'text'"
                                    class="w-full bg-white border border-gray-100 rounded-2xl font-bold py-4 px-6 focus:ring-4 focus:ring-blue-100 focus:border-blue-600 transition-all outline-none"
                                    :placeholder="'Entrez ' + formatKey(setting.key).toLowerCase()"
                                >
                                
                                <div v-if="form.errors['settings.' + setting.key]" class="text-xs text-red-500 font-bold mt-1">
                                    {{ form.errors['settings.' + setting.key] }}
                                </div>
                                
                                <div v-else-if="setting.type === 'boolean'" class="flex items-center gap-3 pt-2">
                                    <button 
                                        type="button"
                                        @click="form.settings[setting.key] = form.settings[setting.key] == '1' ? '0' : '1'"
                                        class="w-14 h-8 rounded-full transition-all relative border border-transparent shadow-inner"
                                        :class="form.settings[setting.key] == '1' ? 'bg-green-500' : 'bg-gray-300'"
                                    >
                                        <div class="w-6 h-6 bg-white rounded-full absolute top-1 transition-all shadow-md" :class="form.settings[setting.key] == '1' ? 'left-7' : 'left-1'"></div>
                                    </button>
                                    <span class="text-xs font-black uppercase tracking-widest" :class="form.settings[setting.key] == '1' ? 'text-green-600' : 'text-gray-400'">
                                        {{ form.settings[setting.key] == '1' ? 'Activé' : 'Désactivé' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="p-10 bg-gray-50/50 border-t border-gray-100 flex justify-end">
                            <button 
                                type="submit"
                                :disabled="form.processing"
                                class="px-10 py-5 bg-gray-900 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition-all shadow-2xl shadow-gray-200 flex items-center gap-3 disabled:opacity-50"
                            >
                                <CheckIcon v-if="!form.processing" class="h-4 w-4" />
                                {{ form.processing ? 'Enregistrement...' : 'Enregistrer les paramètres' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
