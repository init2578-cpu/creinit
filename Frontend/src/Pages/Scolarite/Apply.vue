<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue'
import { 
    UserIcon, 
    AcademicCapIcon, 
    CloudArrowUpIcon,
    CheckCircleIcon,
    ArrowRightIcon,
    ArrowLeftIcon,
    MapPinIcon,
    CalendarDaysIcon,
    BriefcaseIcon,
    IdentificationIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    modules: Array
})

const step = ref(1)

const form = useForm({
    nom_complet: '',
    email: '',
    telephone: '',
    adresse_reelle: '',
    date_naissance: '',
    lieu_naissance: '',
    niveau_etude: '',
    dernier_diplome_libelle: '',
    fonction: '',
    etablissement: '',
    module_id: '',
    sexe: '',
    cni: null,
    diploma: null,
    commentaires: '',
})

const maxBirthDate = computed(() => {
    const d = new Date()
    d.setFullYear(d.getFullYear() - 7)
    return d.toISOString().split('T')[0]
})

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search)
    const moduleId = urlParams.get('module')
    if (moduleId) {
        form.module_id = parseInt(moduleId)
    }
})

const selectedModule = computed(() => {
    return props.modules.find(m => m.id === form.module_id)
})

function nextStep() {
    if (step.value < 5) step.value++
}

function prevStep() {
    if (step.value > 1) step.value--
}

function submit() {
    form.post(route('applications.store'), {
        forceFormData: true,
        onSuccess: () => {
            step.value = 6 // Success step
        }
    })
}

function handleFile(e, field) {
    const file = e.target.files[0]
    if (file) {
        // Validation de la taille (2Mo max)
        if (file.size > 2 * 1024 * 1024) {
            window.platformAlert('Le fichier est trop volumineux (Max 2Mo). Veuillez compresser votre document.', 'error')
            e.target.value = ''
            form[field] = null
            return
        }

        // Validation du type de fichier
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png']
        const allowedExtensions = ['.pdf', '.jpg', '.jpeg', '.png']
        const fileName = file.name.toLowerCase()
        const hasValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext))
        
        if (!allowedTypes.includes(file.type) && !hasValidExtension) {
            window.platformAlert('Format de fichier non supporté. Veuillez utiliser PDF, JPG, JPEG ou PNG.', 'error')
            e.target.value = ''
            form[field] = null
            return
        }
        
        form[field] = file
    }
}
</script>

<template>
    <Head title="Candidature E-CRE" />

    <GuestLayout>
        <div class="max-w-2xl mx-auto px-4 pt-32 pb-12">
            <!-- Stepper -->
            <div v-if="step <= 5" class="mb-12 flex items-center justify-between overflow-x-auto pb-4 sm:pb-0">
                <div v-for="i in 5" :key="i" class="flex items-center flex-shrink-0">
                    <div 
                        class="h-10 w-10 rounded-full flex items-center justify-center font-black transition-all border-2"
                        :class="step >= i ? 'bg-blue-600 border-blue-600 text-white shadow-lg shadow-blue-100' : 'bg-white border-gray-200 text-gray-300'"
                    >
                        {{ i }}
                    </div>
                    <div v-if="i < 5" class="w-8 sm:w-12 h-1 mx-2 rounded-full" :class="step > i ? 'bg-blue-600' : 'bg-gray-100'"></div>
                </div>
            </div>

            <!-- STEP 1: Etat Civil -->
            <div v-if="step === 1" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                <h2 class="text-2xl font-black text-gray-900 mb-6 flex items-center gap-3">
                    <UserIcon class="h-6 w-6 text-blue-600" />
                    État Civil & Naissance
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-1">Nom Complet <span class="text-red-500 font-bold ml-0.5">*</span></label>
                        <input v-model="form.nom_complet" type="text" required class="w-full bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 font-bold px-4 py-3" placeholder="Ex: Moussa Diop">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Sexe <span class="text-red-500">*</span></label>
                            <select v-model="form.sexe" required class="w-full bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 font-bold px-4 py-3 appearance-none">
                                <option value="">Choisir...</option>
                                <option value="M">Masculin</option>
                                <option value="F">Féminin</option>
                            </select>
                            <p v-if="form.errors.sexe" class="text-red-500 text-[10px] mt-1 font-bold">{{ form.errors.sexe }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Date de Naissance <span class="text-red-500">*</span></label>
                            <input v-model="form.date_naissance" type="date" :max="maxBirthDate" required class="w-full bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 font-bold px-4 py-3">
                            <p v-if="form.errors.date_naissance" class="text-red-500 text-[10px] mt-1 font-bold">{{ form.errors.date_naissance }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Lieu de Naissance <span class="text-red-500">*</span></label>
                        <input v-model="form.lieu_naissance" type="text" required class="w-full bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 font-bold px-4 py-3" placeholder="Ex: Kolda">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Email (Optionnel)</label>
                            <input v-model="form.email" type="email" class="w-full bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 font-bold px-4 py-3" placeholder="moussa@exemple.com">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-1">Téléphone <span class="text-red-500 font-bold ml-0.5">*</span></label>
                            <input v-model="form.telephone" type="tel" required class="w-full bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 font-bold px-4 py-3" placeholder="77 000 00 00">
                        </div>
                    </div>
                </div>
                <button @click="nextStep" :disabled="!form.nom_complet || !form.telephone || !form.date_naissance || !form.lieu_naissance || !form.sexe" class="w-full mt-6 py-4 bg-blue-600 text-white rounded-2xl font-black flex items-center justify-center gap-2 hover:bg-blue-700 transition shadow-lg shadow-blue-100 disabled:opacity-50">
                    Continuer
                    <ArrowRightIcon class="h-5 w-5" />
                </button>
            </div>

            <!-- STEP 2: Parcours & Localisation -->
            <div v-if="step === 2" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                <h2 class="text-2xl font-black text-gray-900 mb-6 flex items-center gap-3">
                    <MapPinIcon class="h-6 w-6 text-blue-600" />
                    Localisation & Études
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Adresse Réelle <span class="text-red-500">*</span></label>
                        <input v-model="form.adresse_reelle" type="text" required class="w-full bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 font-bold px-4 py-3" placeholder="Ex: Quartier Sikilo, Kolda">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Niveau d'étude <span class="text-red-500">*</span></label>
                            <select v-model="form.niveau_etude" required class="w-full bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 font-bold px-4 py-3">
                                <option value="">Choisir...</option>
                                <option value="Primaire">Élémentaire (Primaire)</option>
                                <option value="CEM">Moyen (CEM)</option>
                                <option value="BFEM">BFEM</option>
                                <option value="Lycée">Enseignement Secondaire (Lycée)</option>
                                <option value="Bac">Bac</option>
                                <option value="Bac+1">Bac+1</option>
                                <option value="Bac+2">Bac+2</option>
                                <option value="Licence">Licence (Bac+3)</option>
                                <option value="Master">Master</option>
                                <option value="Doctorat">Doctorat</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Dernier Diplôme (Libellé) <span class="text-red-500">*</span></label>
                            <input v-model="form.dernier_diplome_libelle" type="text" required class="w-full bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 font-bold px-4 py-3" placeholder="Ex: Licence en Informatique">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Fonction Actuelle <span class="text-red-500">*</span></label>
                            <input v-model="form.fonction" type="text" required class="w-full bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 font-bold px-4 py-3" placeholder="Ex: Étudiant, Sans emploi, Salarié">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Établissement (Si élève/étudiant)</label>
                            <input v-model="form.etablissement" type="text" class="w-full bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 font-bold px-4 py-3" placeholder="Ex: Université de Ziguinchor">
                        </div>
                    </div>
                    <div class="flex gap-4 mt-8">
                        <button @click="prevStep" class="flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black flex items-center justify-center gap-2">
                            <ArrowLeftIcon class="h-5 w-5" />
                            Retour
                        </button>
                        <button @click="nextStep" :disabled="!form.adresse_reelle || !form.niveau_etude || !form.fonction || !form.dernier_diplome_libelle" class="flex-[2] py-4 bg-blue-600 text-white rounded-2xl font-black flex items-center justify-center gap-2 disabled:opacity-50">
                            Continuer
                            <ArrowRightIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Choix du Parcours -->
            <div v-if="step === 3" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                <h2 class="text-2xl font-black text-gray-900 mb-6 flex items-center gap-3">
                    <AcademicCapIcon class="h-6 w-6 text-blue-600" />
                    Choix du Parcours
                </h2>
                <div v-if="modules.length > 0" class="grid grid-cols-1 gap-4">
                    <div 
                        v-for="module in modules" 
                        :key="module.id"
                        @click="form.module_id = module.id"
                        class="p-4 rounded-2xl border-2 cursor-pointer transition-all"
                        :class="form.module_id === module.id ? 'border-blue-600 bg-blue-50/50' : 'border-gray-50 bg-gray-50 hover:border-gray-200'"
                    >
                        <p class="font-black text-gray-900">{{ module.titre || module.nom_module }}</p>
                        <p class="text-xs text-gray-500 mt-1">Formation intensive au CRE Kolda</p>
                    </div>
                </div>
                <div v-else class="text-center py-10 bg-gray-50 rounded-3xl border border-dashed border-gray-200">
                    <AcademicCapIcon class="h-12 w-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500 font-bold">Aucune formation n'est actuellement disponible pour inscription.</p>
                    <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mt-2">Revenez vers nous prochainement.</p>
                </div>
                <div class="flex gap-4 mt-8">
                    <button @click="prevStep" class="flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black flex items-center justify-center gap-2">
                        <ArrowLeftIcon class="h-5 w-5" />
                        Retour
                    </button>
                    <button @click="nextStep" :disabled="!form.module_id" class="flex-[2] py-4 bg-blue-600 text-white rounded-2xl font-black flex items-center justify-center gap-2 disabled:opacity-50">
                        Continuer
                        <ArrowRightIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- STEP 4: Pièces Justificatives -->
            <div v-if="step === 4" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                <h2 class="text-2xl font-black text-gray-900 mb-6 flex items-center gap-3">
                    <CloudArrowUpIcon class="h-6 w-6 text-blue-600" />
                    Pièces Justificatives
                </h2>
                <div class="space-y-6">
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Copie de la CNI (PDF/Image) <span class="text-red-500">*</span></label>
                        <div class="relative group">
                            <input @change="e => handleFile(e, 'cni')" type="file" accept=".pdf,.jpg,.jpeg,.png" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            <div class="p-6 border-2 border-dashed border-gray-200 rounded-2xl text-center group-hover:border-blue-400 transition">
                                <p class="text-sm font-bold text-gray-600">{{ form.cni ? form.cni.name : 'Cliquez pour choisir un fichier' }}</p>
                                <p class="text-[10px] text-gray-400 mt-1">Format supporté: PDF, JPG, JPEG, PNG (Max 2Mo)</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Dernier Diplôme (Scan PDF/Image) <span class="text-red-500">*</span></label>
                        <div class="relative group">
                            <input @change="e => handleFile(e, 'diploma')" type="file" accept=".pdf,.jpg,.jpeg,.png" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            <div class="p-6 border-2 border-dashed border-gray-200 rounded-2xl text-center group-hover:border-blue-400 transition">
                                <p class="text-sm font-bold text-gray-600">{{ form.diploma ? form.diploma.name : 'Cliquez pour choisir un fichier' }}</p>
                                <p class="text-[10px] text-gray-400 mt-1">Format supporté: PDF, JPG, JPEG, PNG (Max 2Mo)</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-4 mt-10">
                    <button @click="prevStep" class="flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black flex items-center justify-center gap-2">
                        <ArrowLeftIcon class="h-5 w-5" />
                        Retour
                    </button>
                    <button @click="nextStep" :disabled="!form.cni || !form.diploma" class="flex-[2] py-4 bg-blue-600 text-white rounded-2xl font-black flex items-center justify-center gap-2 disabled:opacity-50">
                        Continuer
                        <ArrowRightIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- STEP 5: Récapitulatif -->
            <div v-if="step === 5" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                <h2 class="text-2xl font-black text-gray-900 mb-6 flex items-center gap-3">
                    <CheckCircleIcon class="h-6 w-6 text-blue-600" />
                    Récapitulatif de votre candidature
                </h2>
                
                <div class="space-y-6">
                    <div class="p-6 bg-gray-50 rounded-2xl grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-8">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Nom Complet</p>
                            <p class="font-bold text-gray-900 leading-tight">{{ form.nom_complet }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Naissance & Genre</p>
                            <p class="font-bold text-gray-900 leading-tight">Le {{ form.date_naissance }} à {{ form.lieu_naissance }} ({{ form.sexe === 'M' ? 'Masculin' : 'Féminin' }})</p>
                        </div>
                        <div class="sm:col-span-2 border-t border-gray-100 pt-4">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Adresse Réelle</p>
                            <p class="font-bold text-gray-900 leading-tight">{{ form.adresse_reelle }}</p>
                        </div>
                        <div class="border-t border-gray-100 pt-4">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Niveau & Diplôme</p>
                            <p class="font-bold text-gray-900 leading-tight">{{ form.niveau_etude }} ({{ form.dernier_diplome_libelle }})</p>
                        </div>
                        <div class="border-t border-gray-100 pt-4">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Profession / Fonction</p>
                            <p class="font-bold text-gray-900 leading-tight">{{ form.fonction }} <span v-if="form.etablissement">@ {{ form.etablissement }}</span></p>
                        </div>
                        <div class="sm:col-span-2 border-t border-gray-100 pt-4">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Formation choisie</p>
                            <p class="font-bold text-blue-600 leading-tight">{{ selectedModule ? (selectedModule.titre || selectedModule.nom_module) : 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-indigo-50 rounded-[1.5rem] border border-indigo-100">
                            <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-1">Pièce d'identité</p>
                            <p class="text-xs font-bold text-indigo-700 truncate">{{ form.cni.name }}</p>
                        </div>
                        <div class="p-4 bg-purple-50 rounded-[1.5rem] border border-purple-100">
                            <p class="text-[10px] font-black text-purple-400 uppercase tracking-widest mb-1">Dernier Diplôme</p>
                            <p class="text-xs font-bold text-purple-700 truncate">{{ form.diploma.name }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex gap-4 mt-10">
                    <button @click="prevStep" class="flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black flex items-center justify-center gap-2">
                        <ArrowLeftIcon class="h-5 w-5" />
                        Modifier
                    </button>
                    <button @click="submit" :disabled="form.processing" class="flex-[2] py-4 bg-green-600 text-white rounded-2xl font-black flex items-center justify-center gap-2 disabled:opacity-50 shadow-lg shadow-green-100">
                        Confirmer et Soumettre
                        <ArrowRightIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- STEP 6: Success -->
            <div v-if="step === 6" class="text-center bg-white p-12 rounded-[3rem] shadow-sm border border-gray-100">
                <div class="h-24 w-24 bg-green-50 text-green-600 rounded-[2rem] flex items-center justify-center mx-auto mb-8 animate-bounce">
                    <CheckCircleIcon class="h-12 w-12" />
                </div>
                <h2 class="text-3xl font-black text-gray-900 mb-4 tracking-tight">Candidature Reçue !</h2>
                <p class="text-gray-500 font-medium leading-relaxed">
                    Merci pour votre intérêt pour le CRE Kolda. <br>
                    Votre dossier est en cours de traitement par notre équipe pédagogique. <br>
                    Vous recevrez une réponse par email ou un appel téléphonique dans les plus brefs délais.
                </p>
                <div class="mt-10">
                    <a href="/" class="text-blue-600 font-black flex items-center justify-center gap-2 hover:underline">
                        Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
/* Custom styled date for consistent look */
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(0.5);
    cursor: pointer;
}
</style>
