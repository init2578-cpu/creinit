<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import DateInput from '@/Components/DateInput.vue'
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
    IdentificationIcon,
    SparklesIcon,
    CameraIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    modules: Array
})

const step = ref(1)

const isCameraOpen = ref(false)
const activeCameraTarget = ref(null)
const videoStream = ref(null)
const videoElement = ref(null)
const cameraError = ref(null)

const cameraTargetTitle = computed(() => {
    if (activeCameraTarget.value === 'cni_recto') return "CNI - Photo du RECTO"
    if (activeCameraTarget.value === 'cni_verso') return "CNI - Photo du VERSO"
    if (activeCameraTarget.value === 'other_identity_doc') return "Extrait de naissance / Pièce d'identité"
    if (activeCameraTarget.value === 'diploma') return "Dernier Diplôme"
    return "Appareil photo"
})

async function openCamera(targetField) {
    activeCameraTarget.value = targetField
    isCameraOpen.value = true
    cameraError.value = null
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } }
        })
        videoStream.value = stream
        if (videoElement.value) {
            videoElement.value.srcObject = stream
        }
    } catch (err) {
        console.error("Camera access error:", err)
        cameraError.value = "Accès à l'appareil photo non disponible. Veuillez autoriser la caméra ou sélectionner un fichier."
    }
}

function setVideoRef(el) {
    videoElement.value = el
    if (el && videoStream.value) {
        el.srcObject = videoStream.value
    }
}

function stopCamera() {
    if (videoStream.value) {
        videoStream.value.getTracks().forEach(track => track.stop())
        videoStream.value = null
    }
    isCameraOpen.value = false
    activeCameraTarget.value = null
    cameraError.value = null
}

function capturePhoto() {
    if (!videoElement.value || !activeCameraTarget.value) return
    const canvas = document.createElement('canvas')
    canvas.width = videoElement.value.videoWidth || 1280
    canvas.height = videoElement.value.videoHeight || 720
    const ctx = canvas.getContext('2d')
    ctx.drawImage(videoElement.value, 0, 0, canvas.width, canvas.height)
    
    canvas.toBlob((blob) => {
        if (blob) {
            const fileName = `${activeCameraTarget.value}_${Date.now()}.jpg`
            const file = new File([blob], fileName, { type: 'image/jpeg' })
            form[activeCameraTarget.value] = file
        }
        stopCamera()
    }, 'image/jpeg', 0.9)
}

const maxBirthDate = `${new Date().getFullYear() - 6}-12-31`

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
    has_cni: true,
    cni_recto: null,
    cni_verso: null,
    other_identity_doc: null,
    cni: null,
    diploma: null,
    commentaires: '',
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
        if (file.size > 2 * 1024 * 1024) {
            window.platformAlert('Le fichier est trop volumineux (Max 2Mo). Veuillez compresser votre document.', 'error')
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
        <div class="relative min-h-screen pt-32 pb-24 overflow-hidden bg-slate-950">
            <!-- Background Decorations -->
            <div class="absolute inset-0 z-0 pointer-events-none">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-cyan-500/10 rounded-full blur-[120px] animate-pulse-slow"></div>
                <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-indigo-500/10 rounded-full blur-[100px] animate-pulse-slow" style="animation-delay: 2s"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_2px_2px,rgba(6,182,212,0.05)_1px,transparent_0)] bg-[size:40px_40px]"></div>
            </div>

            <div class="max-w-2xl mx-auto px-4 relative z-10">
                <!-- Header Title -->
                <div class="text-center mb-12">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-cyan-500/10 border border-cyan-500/20 mb-4">
                        <SparklesIcon class="h-4 w-4 text-cyan-400" />
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-[0.3em]">Portail Admissions</span>
                    </div>
                    <h1 class="text-4xl md:text-5xl font-black text-white tracking-tighter leading-none mb-3 font-display">
                        REJOINDRE LE <span class="text-cyan-500 text-glow-cyan">CRE</span>
                    </h1>
                    <p class="text-slate-400 text-sm font-medium">
                        Remplissez le formulaire de candidature en quelques étapes simples.
                    </p>
                </div>

                <!-- Stepper -->
                <div v-if="step <= 5" class="mb-12 flex items-center justify-between overflow-x-auto pb-4 sm:pb-0 scrollbar-none">
                    <div v-for="i in 5" :key="i" class="flex items-center flex-shrink-0">
                        <div 
                            class="h-10 w-10 rounded-full flex items-center justify-center font-black transition-all border-2 text-sm"
                            :class="step >= i ? 'bg-cyan-500 border-cyan-500 text-slate-950 shadow-lg shadow-cyan-500/20' : 'bg-slate-900 border-white/10 text-slate-500'"
                        >
                            {{ i }}
                        </div>
                        <div v-if="i < 5" class="w-8 sm:w-12 h-0.5 mx-2 rounded-full" :class="step > i ? 'bg-cyan-500' : 'bg-white/5'"></div>
                    </div>
                </div>

                <!-- STEP 1: Etat Civil -->
                <div v-if="step === 1" class="glass-dark border border-white/10 rounded-[2.5rem] p-8 md:p-12 shadow-2xl relative overflow-hidden">
                    <div class="absolute -right-20 -top-20 w-40 h-40 bg-cyan-500/10 rounded-full blur-3xl"></div>
                    <h2 class="text-2xl font-black text-white mb-8 flex items-center gap-3">
                        <UserIcon class="h-6 w-6 text-cyan-400" />
                        État Civil & Naissance
                    </h2>
                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nom Complet</label>
                            <input v-model="form.nom_complet" type="text" required class="w-full bg-slate-900/50 border border-white/5 rounded-2xl p-4 text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 transition-all" placeholder="Ex: Moussa Diop">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Sexe <span class="text-red-500">*</span></label>
                                <select v-model="form.sexe" required class="w-full bg-slate-900/50 border border-white/5 rounded-2xl p-4 text-white focus:border-cyan-500 focus:ring-0 transition-all appearance-none">
                                    <option value="" class="bg-slate-950 text-slate-400">Choisir...</option>
                                    <option value="M" class="bg-slate-950 text-white">Masculin</option>
                                    <option value="F" class="bg-slate-950 text-white">Féminin</option>
                                </select>
                                <p v-if="form.errors.sexe" class="text-red-500 text-[10px] mt-1 font-bold">{{ form.errors.sexe }}</p>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Date de Naissance (jj/mm/aaaa) <span class="text-red-500">*</span></label>
                                <DateInput :max-date="maxBirthDate" v-model="form.date_naissance" required class="w-full bg-slate-900/50 border border-white/5 rounded-2xl p-4 text-white focus:border-cyan-500 focus:ring-0 transition-all" />
                                <p v-if="form.errors.date_naissance" class="text-red-500 text-[10px] mt-1 font-bold">{{ form.errors.date_naissance }}</p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Lieu de Naissance</label>
                            <input v-model="form.lieu_naissance" type="text" required class="w-full bg-slate-900/50 border border-white/5 rounded-2xl p-4 text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 transition-all" placeholder="Ex: Kolda">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Email (Optionnel)</label>
                                <input v-model="form.email" type="email" class="w-full bg-slate-900/50 border border-white/5 rounded-2xl p-4 text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 transition-all" placeholder="moussa@exemple.com">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Téléphone</label>
                                <input v-model="form.telephone" type="tel" required class="w-full bg-slate-900/50 border border-white/5 rounded-2xl p-4 text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 transition-all" placeholder="77 000 00 00">
                            </div>
                        </div>
                    </div>
                    <button @click="nextStep" :disabled="!form.nom_complet || !form.telephone || !form.date_naissance || !form.lieu_naissance || !form.sexe" class="w-full mt-10 py-5 bg-cyan-500 text-slate-950 rounded-2xl font-black text-lg transition-all hover:bg-cyan-400 hover:shadow-[0_0_40px_rgba(6,182,212,0.4)] active:scale-95 flex items-center justify-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span>Continuer</span>
                        <ArrowRightIcon class="h-5 w-5" />
                    </button>
                </div>

                <!-- STEP 2: Parcours & Localisation -->
                <div v-if="step === 2" class="glass-dark border border-white/10 rounded-[2.5rem] p-8 md:p-12 shadow-2xl relative overflow-hidden">
                    <div class="absolute -right-20 -top-20 w-40 h-40 bg-cyan-500/10 rounded-full blur-3xl"></div>
                    <h2 class="text-2xl font-black text-white mb-8 flex items-center gap-3">
                        <MapPinIcon class="h-6 w-6 text-cyan-400" />
                        Localisation & Études
                    </h2>
                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Adresse Réelle</label>
                            <input v-model="form.adresse_reelle" type="text" required class="w-full bg-slate-900/50 border border-white/5 rounded-2xl p-4 text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 transition-all" placeholder="Ex: Quartier Sikilo, Kolda">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Niveau d'étude</label>
                                <select v-model="form.niveau_etude" required class="w-full bg-slate-900/50 border border-white/5 rounded-2xl p-4 text-white focus:border-cyan-500 focus:ring-0 transition-all appearance-none">
                                    <option value="" class="bg-slate-950 text-slate-400">Choisir...</option>
                                    <option value="CM2" class="bg-slate-950 text-white">CM2</option>
                                    <option value="6ème" class="bg-slate-950 text-white">6ème</option>
                                    <option value="5ème" class="bg-slate-950 text-white">5ème</option>
                                    <option value="4ème" class="bg-slate-950 text-white">4ème</option>
                                    <option value="3ème" class="bg-slate-950 text-white">3ème</option>
                                    <option value="2nd" class="bg-slate-950 text-white">2nd</option>
                                    <option value="Première" class="bg-slate-950 text-white">Première</option>
                                    <option value="Terminal (sans Bac)" class="bg-slate-950 text-white">Terminal (sans Bac)</option>
                                    <option value="Bac" class="bg-slate-950 text-white">Bac</option>
                                    <option value="Bac+1" class="bg-slate-950 text-white">Bac+1</option>
                                    <option value="Bac+2" class="bg-slate-950 text-white">Bac+2</option>
                                    <option value="Licence" class="bg-slate-950 text-white">Licence (Bac+3)</option>
                                    <option value="Master" class="bg-slate-950 text-white">Master</option>
                                    <option value="Doctorat" class="bg-slate-950 text-white">Doctorat</option>
                                    <option value="Daara" class="bg-slate-950 text-white">Daara</option>
                                    <option value="Autre" class="bg-slate-950 text-white">Autre</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Dernier Diplôme (Libellé)</label>
                                <input v-model="form.dernier_diplome_libelle" type="text" required class="w-full bg-slate-900/50 border border-white/5 rounded-2xl p-4 text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 transition-all" placeholder="Ex: Licence en Informatique">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Fonction Actuelle</label>
                                <input v-model="form.fonction" type="text" required class="w-full bg-slate-900/50 border border-white/5 rounded-2xl p-4 text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 transition-all" placeholder="Ex: Étudiant, Sans emploi, Salarié">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Établissement (Si élève/étudiant)</label>
                                <input v-model="form.etablissement" type="text" class="w-full bg-slate-900/50 border border-white/5 rounded-2xl p-4 text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 transition-all" placeholder="Ex: Université de Ziguinchor">
                            </div>
                        </div>
                        <div class="flex gap-4 mt-10">
                            <button @click="prevStep" class="flex-1 py-4 bg-white/5 hover:bg-white/10 border border-white/10 text-white rounded-2xl font-black transition-all flex items-center justify-center gap-2">
                                <ArrowLeftIcon class="h-5 w-5" />
                                Retour
                            </button>
                            <button @click="nextStep" :disabled="!form.adresse_reelle || !form.niveau_etude || !form.fonction" class="flex-[2] py-4 bg-cyan-500 hover:bg-cyan-400 text-slate-950 rounded-2xl font-black transition-all hover:shadow-[0_0_30px_rgba(6,182,212,0.4)] disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                Continuer
                                <ArrowRightIcon class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: Choix du Parcours -->
                <div v-if="step === 3" class="glass-dark border border-white/10 rounded-[2.5rem] p-8 md:p-12 shadow-2xl relative overflow-hidden">
                    <div class="absolute -right-20 -top-20 w-40 h-40 bg-cyan-500/10 rounded-full blur-3xl"></div>
                    <h2 class="text-2xl font-black text-white mb-8 flex items-center gap-3">
                        <AcademicCapIcon class="h-6 w-6 text-cyan-400" />
                        Choix du Parcours
                    </h2>
                    <div class="grid grid-cols-1 gap-4">
                        <div 
                            v-for="module in modules" 
                            :key="module.id"
                            @click="form.module_id = module.id"
                            class="p-5 rounded-2xl border cursor-pointer transition-all duration-300 relative group"
                            :class="form.module_id === module.id ? 'border-cyan-500 bg-cyan-500/10 text-cyan-400' : 'border-white/5 bg-slate-900/50 hover:border-white/20 text-slate-300'"
                        >
                            <p class="font-black text-white group-hover:text-cyan-400 transition-colors">{{ module.titre || module.nom_module }}</p>
                            <p class="text-xs text-slate-500 mt-1">Formation intensive au CRE Kolda</p>
                        </div>
                    </div>
                    <div class="flex gap-4 mt-10">
                        <button @click="prevStep" class="flex-1 py-4 bg-white/5 hover:bg-white/10 border border-white/10 text-white rounded-2xl font-black transition-all flex items-center justify-center gap-2">
                            <ArrowLeftIcon class="h-5 w-5" />
                            Retour
                        </button>
                        <button @click="nextStep" :disabled="!form.module_id" class="flex-[2] py-4 bg-cyan-500 hover:bg-cyan-400 text-slate-950 rounded-2xl font-black transition-all hover:shadow-[0_0_30px_rgba(6,182,212,0.4)] disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                            Continuer
                            <ArrowRightIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- STEP 4: Pièces Justificatives -->
                <div v-if="step === 4" class="glass-dark border border-white/10 rounded-[2.5rem] p-8 md:p-12 shadow-2xl relative overflow-hidden">
                    <div class="absolute -right-20 -top-20 w-40 h-40 bg-cyan-500/10 rounded-full blur-3xl"></div>
                    <h2 class="text-2xl font-black text-white mb-8 flex items-center gap-3">
                        <CloudArrowUpIcon class="h-6 w-6 text-cyan-400" />
                        Pièces Justificatives
                    </h2>
                    <div class="space-y-6">
                        <div class="space-y-3 p-4 rounded-2xl bg-slate-900/40 border border-white/5">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Document d'identité <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <button 
                                    type="button" 
                                    @click="form.has_cni = true"
                                    class="p-3.5 rounded-xl border text-left text-xs font-bold transition-all flex items-center gap-3"
                                    :class="form.has_cni ? 'bg-cyan-500/20 border-cyan-500 text-cyan-400' : 'bg-slate-900/60 border-white/10 text-slate-400 hover:border-white/20'"
                                >
                                    <div class="h-4 w-4 rounded-full border-2 flex items-center justify-center" :class="form.has_cni ? 'border-cyan-400 bg-cyan-400' : 'border-slate-500'">
                                        <div v-if="form.has_cni" class="h-1.5 w-1.5 rounded-full bg-slate-950"></div>
                                    </div>
                                    <span>Carte Nationale d'Identité (CNI)</span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="form.has_cni = false"
                                    class="p-3.5 rounded-xl border text-left text-xs font-bold transition-all flex items-center gap-3"
                                    :class="!form.has_cni ? 'bg-cyan-500/20 border-cyan-500 text-cyan-400' : 'bg-slate-900/60 border-white/10 text-slate-400 hover:border-white/20'"
                                >
                                    <div class="h-4 w-4 rounded-full border-2 flex items-center justify-center" :class="!form.has_cni ? 'border-cyan-400 bg-cyan-400' : 'border-slate-500'">
                                        <div v-if="!form.has_cni" class="h-1.5 w-1.5 rounded-full bg-slate-950"></div>
                                    </div>
                                    <span>Sans CNI (Extrait / Autre pièce)</span>
                                </button>
                            </div>
                        </div>

                        <!-- SI AVEC CNI -->
                        <template v-if="form.has_cni">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between ml-1">
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Photo de la CNI (Recto)</label>
                                    <button @click="openCamera('cni_recto')" type="button" class="text-xs text-cyan-400 hover:text-cyan-300 font-bold flex items-center gap-1.5 bg-cyan-500/10 px-3 py-1 rounded-lg border border-cyan-500/20">
                                        <CameraIcon class="h-4 w-4" />
                                        <span>Prendre photo</span>
                                    </button>
                                </div>
                                <div class="relative group">
                                    <input @change="e => handleFile(e, 'cni_recto')" type="file" accept="image/*,application/pdf" capture="environment" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                    <div class="p-6 border-2 border-dashed border-white/10 rounded-2xl text-center group-hover:border-cyan-500/50 transition-all bg-slate-900/30">
                                        <p class="text-sm font-bold text-white mb-1">{{ form.cni_recto ? form.cni_recto.name : 'Cliquez pour choisir la photo du RECTO ou Prendre une photo' }}</p>
                                        <p class="text-[10px] text-slate-500">Format supporté: PDF, JPG, PNG (Max 2Mo)</p>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between ml-1">
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Photo de la CNI (Verso)</label>
                                    <button @click="openCamera('cni_verso')" type="button" class="text-xs text-cyan-400 hover:text-cyan-300 font-bold flex items-center gap-1.5 bg-cyan-500/10 px-3 py-1 rounded-lg border border-cyan-500/20">
                                        <CameraIcon class="h-4 w-4" />
                                        <span>Prendre photo</span>
                                    </button>
                                </div>
                                <div class="relative group">
                                    <input @change="e => handleFile(e, 'cni_verso')" type="file" accept="image/*,application/pdf" capture="environment" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                    <div class="p-6 border-2 border-dashed border-white/10 rounded-2xl text-center group-hover:border-cyan-500/50 transition-all bg-slate-900/30">
                                        <p class="text-sm font-bold text-white mb-1">{{ form.cni_verso ? form.cni_verso.name : 'Cliquez pour choisir la photo du VERSO ou Prendre une photo' }}</p>
                                        <p class="text-[10px] text-slate-500">Format supporté: PDF, JPG, PNG (Max 2Mo)</p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- SI SANS CNI -->
                        <template v-else>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between ml-1">
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Extrait de naissance ou pièce disponible (Photo/Scan)</label>
                                    <button @click="openCamera('other_identity_doc')" type="button" class="text-xs text-cyan-400 hover:text-cyan-300 font-bold flex items-center gap-1.5 bg-cyan-500/10 px-3 py-1 rounded-lg border border-cyan-500/20">
                                        <CameraIcon class="h-4 w-4" />
                                        <span>Prendre photo</span>
                                    </button>
                                </div>
                                <div class="relative group">
                                    <input @change="e => handleFile(e, 'other_identity_doc')" type="file" accept="image/*,application/pdf" capture="environment" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                    <div class="p-6 border-2 border-dashed border-white/10 rounded-2xl text-center group-hover:border-cyan-500/50 transition-all bg-slate-900/30">
                                        <p class="text-sm font-bold text-white mb-1">{{ form.other_identity_doc ? form.other_identity_doc.name : 'Cliquez pour choisir la photo de l\'Extrait de naissance ou Prendre une photo' }}</p>
                                        <p class="text-[10px] text-slate-500">Format supporté: PDF, JPG, PNG (Max 2Mo)</p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between ml-1">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Dernier Diplôme (Scan PDF/Image)</label>
                                <button @click="openCamera('diploma')" type="button" class="text-xs text-cyan-400 hover:text-cyan-300 font-bold flex items-center gap-1.5 bg-cyan-500/10 px-3 py-1 rounded-lg border border-cyan-500/20">
                                    <CameraIcon class="h-4 w-4" />
                                    <span>Prendre photo</span>
                                </button>
                            </div>
                            <div class="relative group">
                                <input @change="e => handleFile(e, 'diploma')" type="file" accept="image/*,application/pdf" capture="environment" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                <div class="p-6 border-2 border-dashed border-white/10 rounded-2xl text-center group-hover:border-cyan-500/50 transition-all bg-slate-900/30">
                                    <p class="text-sm font-bold text-white mb-1">{{ form.diploma ? form.diploma.name : 'Cliquez pour choisir un fichier ou Prendre une photo' }}</p>
                                    <p class="text-[10px] text-slate-500">Format supporté: PDF, JPG, PNG (Max 2Mo)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-4 mt-10">
                        <button @click="prevStep" class="flex-1 py-4 bg-white/5 hover:bg-white/10 border border-white/10 text-white rounded-2xl font-black transition-all flex items-center justify-center gap-2">
                            <ArrowLeftIcon class="h-5 w-5" />
                            Retour
                        </button>
                        <button @click="nextStep" :disabled="(form.has_cni && (!form.cni_recto || !form.cni_verso)) || (!form.has_cni && !form.other_identity_doc) || !form.diploma" class="flex-[2] py-4 bg-cyan-500 hover:bg-cyan-400 text-slate-950 rounded-2xl font-black transition-all hover:shadow-[0_0_30px_rgba(6,182,212,0.4)] disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                            Continuer
                            <ArrowRightIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- STEP 5: Récapitulatif -->
                <div v-if="step === 5" class="glass-dark border border-white/10 rounded-[2.5rem] p-8 md:p-12 shadow-2xl relative overflow-hidden">
                    <div class="absolute -right-20 -top-20 w-40 h-40 bg-cyan-500/10 rounded-full blur-3xl"></div>
                    <h2 class="text-2xl font-black text-white mb-8 flex items-center gap-3">
                        <CheckCircleIcon class="h-6 w-6 text-cyan-400" />
                        Récapitulatif
                    </h2>
                    
                    <div class="space-y-6">
                        <div class="p-6 bg-slate-900/50 rounded-2xl border border-white/5 grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-8">
                            <div>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Nom Complet</p>
                                <p class="font-bold text-white leading-tight">{{ form.nom_complet }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Naissance & Genre</p>
                                <p class="font-bold text-white leading-tight">Le {{ form.date_naissance }} à {{ form.lieu_naissance }} ({{ form.sexe === 'M' ? 'Masculin' : 'Féminin' }})</p>
                            </div>
                            <div class="sm:col-span-2 border-t border-white/5 pt-4">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Adresse Réelle</p>
                                <p class="font-bold text-white leading-tight">{{ form.adresse_reelle }}</p>
                            </div>
                            <div class="border-t border-white/5 pt-4">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Niveau & Diplôme</p>
                                <p class="font-bold text-white leading-tight">{{ form.niveau_etude }} ({{ form.dernier_diplome_libelle }})</p>
                            </div>
                            <div class="border-t border-white/5 pt-4">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Profession / Fonction</p>
                                <p class="font-bold text-white leading-tight">{{ form.fonction }} <span v-if="form.etablissement" class="text-slate-400">@ {{ form.etablissement }}</span></p>
                            </div>
                            <div class="sm:col-span-2 border-t border-white/5 pt-4">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Formation choisie</p>
                                <p class="font-bold text-cyan-400 leading-tight">{{ selectedModule ? (selectedModule.titre || selectedModule.nom_module) : 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <template v-if="form.has_cni">
                                <div class="p-4 bg-cyan-500/10 rounded-[1.5rem] border border-cyan-500/20">
                                    <p class="text-[10px] font-black text-cyan-400/80 uppercase tracking-widest mb-1">CNI Recto</p>
                                    <p class="text-xs font-bold text-cyan-400 truncate">{{ form.cni_recto ? form.cni_recto.name : 'Non fourni' }}</p>
                                </div>
                                <div class="p-4 bg-cyan-500/10 rounded-[1.5rem] border border-cyan-500/20">
                                    <p class="text-[10px] font-black text-cyan-400/80 uppercase tracking-widest mb-1">CNI Verso</p>
                                    <p class="text-xs font-bold text-cyan-400 truncate">{{ form.cni_verso ? form.cni_verso.name : 'Non fourni' }}</p>
                                </div>
                            </template>
                            <template v-else>
                                <div class="p-4 bg-cyan-500/10 rounded-[1.5rem] border border-cyan-500/20 sm:col-span-2">
                                    <p class="text-[10px] font-black text-cyan-400/80 uppercase tracking-widest mb-1">Extrait de naissance / Autre pièce</p>
                                    <p class="text-xs font-bold text-cyan-400 truncate">{{ form.other_identity_doc ? form.other_identity_doc.name : 'Non fourni' }}</p>
                                </div>
                            </template>
                            <div class="p-4 bg-indigo-500/10 rounded-[1.5rem] border border-indigo-500/20">
                                <p class="text-[10px] font-black text-indigo-400/80 uppercase tracking-widest mb-1">Dernier Diplôme</p>
                                <p class="text-xs font-bold text-indigo-400 truncate">{{ form.diploma ? form.diploma.name : 'Non fourni' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 mt-10">
                        <button @click="prevStep" class="flex-1 py-4 bg-white/5 hover:bg-white/10 border border-white/10 text-white rounded-2xl font-black transition-all flex items-center justify-center gap-2">
                            <ArrowLeftIcon class="h-5 w-5" />
                            Modifier
                        </button>
                        <button @click="submit" :disabled="form.processing" class="flex-[2] py-4 bg-emerald-500 hover:bg-emerald-400 text-slate-950 rounded-2xl font-black transition-all hover:shadow-[0_0_30px_rgba(16,185,129,0.4)] disabled:opacity-50 flex items-center justify-center gap-2">
                            Confirmer et Soumettre
                            <ArrowRightIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- STEP 6: Success -->
                <div v-if="step === 6" class="text-center glass-dark border border-white/10 p-12 rounded-[3rem] shadow-2xl relative overflow-hidden">
                    <div class="absolute -right-20 -top-20 w-40 h-40 bg-emerald-500/10 rounded-full blur-3xl"></div>
                    <div class="h-24 w-24 bg-emerald-500/10 text-emerald-400 rounded-[2rem] border border-emerald-500/20 flex items-center justify-center mx-auto mb-8 animate-bounce">
                        <CheckCircleIcon class="h-12 w-12" />
                    </div>
                    <h2 class="text-3xl font-black text-white mb-4 tracking-tight">Candidature Reçue !</h2>
                    <p class="text-slate-400 font-medium leading-relaxed mb-10">
                        Merci pour votre intérêt pour le CRE Kolda. <br>
                        Votre dossier est en cours de traitement par notre équipe pédagogique. <br>
                        Vous recevrez une réponse par email dans les plus brefs délais.
                    </p>
                    <div>
                        <a href="/" class="text-cyan-400 font-black flex items-center justify-center gap-2 hover:underline">
                            Retour à l'accueil
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Camera Modal -->
        <div v-if="isCameraOpen" class="fixed inset-0 z-[100] bg-slate-950/95 backdrop-blur-md flex flex-col justify-between p-4 md:p-8">
            <div class="flex items-center justify-between z-10 max-w-4xl mx-auto w-full">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 flex items-center justify-center">
                        <CameraIcon class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="text-white font-black text-sm sm:text-base">{{ cameraTargetTitle }}</h3>
                        <p class="text-xs text-slate-400">Positionnez le document dans le cadre</p>
                    </div>
                </div>
                <button @click="stopCamera" type="button" class="p-3 bg-white/10 hover:bg-white/20 text-white rounded-2xl transition">
                    <XMarkIcon class="h-6 w-6" />
                </button>
            </div>

            <!-- Viewfinder Area -->
            <div class="relative flex-1 my-4 bg-black rounded-3xl overflow-hidden flex items-center justify-center border border-white/10 shadow-2xl max-w-4xl mx-auto w-full">
                <div v-if="cameraError" class="p-6 text-center text-red-400 text-sm max-w-md">
                    <p class="font-bold mb-2">{{ cameraError }}</p>
                    <button @click="stopCamera" type="button" class="mt-4 px-6 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-black">
                        Fermer et Importer un fichier
                    </button>
                </div>
                <template v-else>
                    <video :ref="setVideoRef" autoplay playsinline class="w-full h-full object-cover"></video>
                    <!-- Card Frame Guide Overlay -->
                    <div class="absolute inset-6 sm:inset-12 border-2 border-dashed border-cyan-400/80 rounded-2xl pointer-events-none flex flex-col justify-between p-4 shadow-[0_0_50px_rgba(6,182,212,0.2)]">
                        <div class="flex justify-between">
                            <div class="w-6 h-6 border-t-4 border-l-4 border-cyan-400"></div>
                            <div class="w-6 h-6 border-t-4 border-r-4 border-cyan-400"></div>
                        </div>
                        <div class="text-center bg-slate-950/70 py-1.5 px-4 rounded-full self-center backdrop-blur">
                            <span class="text-[11px] font-black text-cyan-300 uppercase tracking-wider">Cadre de capture</span>
                        </div>
                        <div class="flex justify-between">
                            <div class="w-6 h-6 border-b-4 border-l-4 border-cyan-400"></div>
                            <div class="w-6 h-6 border-b-4 border-r-4 border-cyan-400"></div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Controls -->
            <div v-if="!cameraError" class="flex items-center justify-center gap-6 py-2 max-w-4xl mx-auto w-full">
                <button @click="stopCamera" type="button" class="px-6 py-3 bg-white/10 hover:bg-white/20 text-white rounded-2xl text-xs font-bold transition">
                    Annuler
                </button>
                <button @click="capturePhoto" type="button" class="h-16 w-16 rounded-full bg-cyan-400 hover:bg-cyan-300 text-slate-950 flex items-center justify-center shadow-[0_0_30px_rgba(6,182,212,0.6)] transition transform active:scale-95">
                    <div class="h-12 w-12 rounded-full border-2 border-slate-950/40 flex items-center justify-center">
                        <CameraIcon class="h-6 w-6" />
                    </div>
                </button>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
/* Custom styled date for consistent look */
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    cursor: pointer;
}
.scrollbar-none::-webkit-scrollbar {
    display: none;
}
.scrollbar-none {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
