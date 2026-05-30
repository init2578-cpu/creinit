<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import { 
    Cog6ToothIcon, 
    GlobeAltIcon, 
    ShieldCheckIcon, 
    CheckIcon,
    BellIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    settings: Object, // Grouped by 'group'
    debug_info: Object
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

const isInitializing = ref(false)
const initializeDefaults = () => {
    if (!confirm('Voulez-vous vraiment restaurer les paramètres par défaut ?')) return
    
    isInitializing.value = true
    router.post(route('settings.initialize'), {}, {
        onSuccess: () => {
            isInitializing.value = false
            initForm()
        },
        onError: () => {
            isInitializing.value = false
        }
    })
}

const labelMap = {
    // Émargement & Géo
    cre_latitude: 'Latitude du CRE',
    cre_longitude: 'Longitude du CRE',
    cre_radius: 'Rayon de tolérance (mètres)',
    attendance_buffer_before: 'Délai d\'ouverture avant le cours (min)',
    attendance_buffer_after: 'Délai de fermeture après le cours (min)',
    // Général
    site_name: 'Nom de la plateforme (Vitrine)',
    site_description: 'Description de l\'établissement',
    contact_email: 'Email de contact support',
    enable_registration: 'Ouverture des inscriptions publiques',
    // Notifications
    notify_new_application: 'Notifier Admin (Nouvelle pré-inscription)',
    notify_exercise_graded: 'Notifier Apprenant (Exercice corrigé)',
    notify_exam_result: 'Notifier Apprenant (Résultat d\'examen)',
    notify_certificate_issued: 'Notifier Apprenant (Certificat délivré)',
    notify_new_exercise: 'Notifier Apprenant (Nouvel exercice disponible)',
    notify_new_exam: 'Notifier Apprenant (Nouvel examen disponible)',
    notify_chapter_submitted: 'Notifier Formateur (Chapitre soumis pour validation)',
    notify_absence_student: 'Notifier Apprenant/Parent (Absence détectée)',
    notify_schedule_change: 'Notifier Formateur (Changement de planning)',
    email_sender_name: "Nom de l'expéditeur des emails"
}

const formatKey = (key) => {
    return labelMap[key] || key.replace(/_/g, ' ').toUpperCase()
}

// ========================
// Auto-fill Géolocalisation
// ========================
const isGeolocating = ref(false)
const geoError = ref('')

const autoFillLocation = () => {
    if (!navigator.geolocation) {
        geoError.value = "La géolocalisation n'est pas supportée par votre navigateur."
        return
    }
    
    isGeolocating.value = true
    geoError.value = ''
    
    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.settings['cre_latitude'] = position.coords.latitude.toFixed(6)
            form.settings['cre_longitude'] = position.coords.longitude.toFixed(6)
            if (!form.settings['cre_radius']) {
                form.settings['cre_radius'] = '20'
            }
            isGeolocating.value = false
        },
        (error) => {
            isGeolocating.value = false
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    geoError.value = "Accès à la localisation refusé. Veuillez l'autoriser dans votre navigateur."
                    break
                case error.POSITION_UNAVAILABLE:
                    geoError.value = "Position non disponible. Vérifiez votre connexion GPS."
                    break
                default:
                    geoError.value = "Impossible d'obtenir votre position. Saisissez les coordonnées manuellement."
            }
        },
        { enableHighAccuracy: true, timeout: 10000 }
    )
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
                            <h2 class="text-2xl font-black text-gray-900 capitalize tracking-tight flex items-center gap-3 flex-wrap">
                                <component :is="tabs.find(t => t.id === activeTab).icon" class="h-6 w-6 text-blue-600" />
                                Paramètres {{ tabs.find(t => t.id === activeTab).name }}
                                
                                <!-- Bouton Auto-Remplissage pour l'onglet Émargement -->
                                <button 
                                    v-if="activeTab === 'attendance'"
                                    type="button"
                                    @click="autoFillLocation"
                                    :disabled="isGeolocating"
                                    class="ml-auto flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-emerald-100 transition-all disabled:opacity-50"
                                >
                                    <svg v-if="!isGeolocating" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <svg v-else class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ isGeolocating ? 'Localisation...' : 'Auto-remplissage GPS' }}
                                </button>
                            </h2>
                            
                            <!-- Erreur de géolocalisation -->
                            <div v-if="activeTab === 'attendance' && geoError" class="flex items-start gap-3 p-4 bg-red-50 rounded-2xl border border-red-100 text-sm text-red-600 font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                {{ geoError }}
                            </div>

                            <div v-if="!props.settings[activeTab] || props.settings[activeTab].length === 0" class="text-center py-20 text-gray-400 bg-gray-50/30 rounded-3xl border-2 border-dashed border-gray-100">
                                <Cog6ToothIcon class="h-12 w-12 mx-auto mb-4 text-gray-200" />
                                <p class="text-sm font-medium mb-6">Aucun paramètre trouvé dans cette catégorie.</p>
                                <button 
                                    @click="initializeDefaults"
                                    :disabled="isInitializing"
                                    class="px-6 py-3 bg-blue-600 text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-blue-700 transition-all disabled:opacity-50"
                                >
                                    {{ isInitializing ? 'Initialisation...' : 'Initialiser les paramètres par défaut' }}
                                </button>
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
