<script setup>
import { ref } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, usePage, router } from '@inertiajs/vue3'
import { usePasskeyRegister } from '@laravel/passkeys/vue'
import { 
    UserIcon, 
    KeyIcon, 
    CheckBadgeIcon,
    ExclamationTriangleIcon,
    PhotoIcon,
    ArrowUpTrayIcon,
    FingerPrintIcon,
    TrashIcon,
    ShieldCheckIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    passkeys: {
        type: Array,
        default: () => []
    }
})

const user = usePage().props.auth.user

const form = useForm({
    _method: 'patch',
    name: user.name,
    email: user.email,
    telephone: user.telephone || '',
    adresse: user.adresse || '',
    password: '',
    password_confirmation: '',
    profile_photo: null,
})

const photoPreview = ref(null)
const photoInput = ref(null)

function updateProfile() {
    form.post(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('password', 'password_confirmation')
            photoPreview.value = null
        },
    })
}

function selectNewPhoto() {
    photoInput.value.click()
}

function updatePhotoPreview() {
    const photo = photoInput.value.files[0]
    if (!photo) return
    form.profile_photo = photo
    const reader = new FileReader()
    reader.onload = (e) => {
        photoPreview.value = e.target.result
    }
    reader.readAsDataURL(photo)
}

const deviceName = ref('')

const { register, isLoading: registerLoading, isSupported: isPasskeySupported, error: registerError } = usePasskeyRegister({
    onSuccess: () => {
        deviceName.value = ''
        router.reload({ only: ['passkeys'] })
        if (window.platformAlert) {
            window.platformAlert("Empreinte digitale / Clé d'accès ajoutée avec succès !", "success")
        } else {
            alert("Empreinte digitale / Clé d'accès ajoutée avec succès !")
        }
    },
    onError: (err) => {
        if (window.platformAlert) {
            window.platformAlert("Erreur lors de l'enregistrement : " + err.message, "error")
        } else {
            alert("Erreur lors de l'enregistrement : " + err.message)
        }
    }
})

function handleRegister() {
    const name = deviceName.value.trim() || "Mon appareil"
    register(name)
}

function deletePasskey(id) {
    if (!confirm("Voulez-vous vraiment supprimer cette clé d'accès ?")) return
    
    router.delete(route('passkey.destroy', id), {
        preserveScroll: true,
        onSuccess: () => {
            if (window.platformAlert) {
                window.platformAlert("Clé d'accès supprimée avec succès !", "success")
            }
        }
    })
}
</script>

<template>
    <Head title="Mon Profil" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto py-12 px-4">
            <header class="mb-12 text-center">
                <div class="relative inline-block group">
                    <div v-if="!photoPreview" class="h-32 w-32 bg-indigo-600 text-white rounded-[3rem] flex items-center justify-center mx-auto mb-6 shadow-2xl shadow-indigo-200 border-4 border-white relative overflow-hidden transition-all group-hover:scale-105">
                        <img v-if="user.profile_photo_url" :src="user.profile_photo_url" class="h-full w-full object-cover">
                        <UserIcon v-else class="h-16 w-16" />
                    </div>
                    <div v-else class="h-32 w-32 rounded-[3rem] overflow-hidden mx-auto mb-6 shadow-2xl shadow-indigo-200 border-4 border-white transition-all scale-105">
                        <img :src="photoPreview" class="h-full w-full object-cover">
                    </div>
                    
                    <button @click="selectNewPhoto" class="absolute bottom-4 -right-2 p-3 bg-white rounded-2xl shadow-lg border border-gray-100 text-indigo-600 hover:bg-black hover:text-white transition-all active:scale-95">
                        <ArrowUpTrayIcon class="w-5 h-5" />
                    </button>
                    <input ref="photoInput" type="file" class="hidden" @change="updatePhotoPreview">
                </div>

                <h1 class="text-4xl font-black text-gray-900 tracking-tight mb-2">{{ user.name }}</h1>
                <p class="text-gray-500 font-medium tracking-wide italic">Personnalisez votre identité numérique sur E-CRE.</p>
                <div v-if="form.errors.profile_photo" class="mt-4 text-xs text-red-600 font-bold uppercase tracking-widest bg-red-50 py-2 px-4 rounded-full inline-block border border-red-100">{{ form.errors.profile_photo }}</div>
            </header>

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 p-8 md:p-12">
                <form @submit.prevent="updateProfile" class="space-y-8">
                    <!-- Basic Info -->
                    <section class="space-y-6">
                        <h2 class="text-lg font-black text-gray-900 flex items-center gap-2">
                             <CheckBadgeIcon class="h-5 w-5 text-green-500" />
                             Informations Personnelles
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Nom Complet</label>
                                <input v-model="form.name" type="text" class="w-full bg-gray-50 border-0 rounded-2xl font-bold py-4 px-6 focus:ring-2 focus:ring-indigo-600 transition-all">
                                <p v-if="form.errors.name" class="mt-2 text-xs text-red-600 font-bold uppercase tracking-widest">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Adresse Email</label>
                                <input v-model="form.email" type="email" class="w-full bg-gray-50 border-0 rounded-2xl font-bold py-4 px-6 focus:ring-2 focus:ring-indigo-600 transition-all">
                                <p v-if="form.errors.email" class="mt-2 text-xs text-red-600 font-bold uppercase tracking-widest">{{ form.errors.email }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Numéro de Téléphone</label>
                                <input v-model="form.telephone" type="text" class="w-full bg-gray-50 border-0 rounded-2xl font-bold py-4 px-6 focus:ring-2 focus:ring-indigo-600 transition-all" placeholder="+212 600 000 000">
                                <p v-if="form.errors.telephone" class="mt-2 text-xs text-red-600 font-bold uppercase tracking-widest">{{ form.errors.telephone }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Adresse Résidentielle</label>
                                <input v-model="form.adresse" type="text" class="w-full bg-gray-50 border-0 rounded-2xl font-bold py-4 px-6 focus:ring-2 focus:ring-indigo-600 transition-all" placeholder="Votre adresse physique">
                                <p v-if="form.errors.adresse" class="mt-2 text-xs text-red-600 font-bold uppercase tracking-widest">{{ form.errors.adresse }}</p>
                            </div>
                        </div>
                    </section>

                    <hr class="border-gray-50">

                    <!-- Security -->
                    <section class="space-y-6">
                        <h2 class="text-lg font-black text-gray-900 flex items-center gap-2">
                             <KeyIcon class="h-5 w-5 text-orange-500" />
                             Sécurité (Changer le mot de passe)
                        </h2>
                        <p class="text-sm text-gray-400 font-medium italic">Laissez vide pour conserver votre mot de passe actuel.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Nouveau Mot de Passe</label>
                                <input v-model="form.password" type="password" class="w-full bg-gray-50 border-0 rounded-2xl font-bold py-4 px-6 focus:ring-2 focus:ring-indigo-600 transition-all">
                                <p v-if="form.errors.password" class="mt-2 text-xs text-red-600 font-bold uppercase tracking-widest">{{ form.errors.password }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Confirmer le Mot de Passe</label>
                                <input v-model="form.password_confirmation" type="password" class="w-full bg-gray-50 border-0 rounded-2xl font-bold py-4 px-6 focus:ring-2 focus:ring-indigo-600 transition-all">
                            </div>
                        </div>
                    </section>

                    <div class="pt-8 border-t border-gray-50 flex items-center justify-between gap-6">
                        <div v-if="form.recentlySuccessful" class="flex items-center gap-2 text-green-600 font-black text-sm animate-bounce">
                            <CheckBadgeIcon class="h-5 w-5" />
                            Modifications enregistrées !
                        </div>
                        <div v-else-if="form.wasSuccessful" class="text-gray-400 font-bold text-sm">
                            En attente de changements...
                        </div>
                        <div v-else></div>

                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="px-10 py-4 bg-indigo-600 text-white rounded-2xl font-black shadow-xl shadow-indigo-100 hover:bg-black transition-all disabled:opacity-50 flex items-center gap-3"
                        >
                            <span v-if="form.processing">Traitement...</span>
                            <span v-else>Enregistrer les modifications</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Passkeys Section -->
            <div class="mt-6 bg-white rounded-[2.5rem] shadow-sm border border-gray-100 p-8 md:p-12">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-black text-gray-900 flex items-center gap-2">
                        <FingerPrintIcon class="h-6 w-6 text-indigo-600 animate-pulse" />
                        Connexion Biométrique (Empreinte digitale / FaceID)
                    </h2>
                    <span v-if="!isPasskeySupported" class="px-3 py-1 bg-red-50 text-red-600 text-xs font-bold rounded-full border border-red-100">
                        Non supporté par ce navigateur
                    </span>
                    <span v-else class="px-3 py-1 bg-green-50 text-green-600 text-xs font-bold rounded-full border border-green-100">
                        Prêt
                    </span>
                </div>

                <p class="text-sm text-gray-500 font-medium mb-6">
                    Enregistrez des clés d'accès (passkeys) sur vos appareils pour vous connecter rapidement et en toute sécurité sans mot de passe.
                </p>

                <!-- Registered Passkeys List -->
                <div class="space-y-4 mb-8">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Vos appareils enregistrés</h3>
                    
                    <div v-if="passkeys.length === 0" class="p-6 bg-gray-50 rounded-2xl border border-dashed border-gray-200 text-center">
                        <ShieldCheckIcon class="h-8 w-8 text-gray-400 mx-auto mb-2" />
                        <p class="text-xs text-gray-500 font-bold">Aucun appareil enregistré pour le moment.</p>
                    </div>

                    <div v-else class="divide-y divide-gray-100">
                        <div v-for="passkey in passkeys" :key="passkey.id" class="py-4 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
                                    <FingerPrintIcon class="h-5 w-5" />
                                </div>
                                <div>
                                    <h4 class="font-bold text-sm text-gray-800">{{ passkey.name }}</h4>
                                    <p class="text-xs text-gray-400">
                                        Ajouté le {{ new Date(passkey.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                                        <span v-if="passkey.last_used_at">
                                            • Utilisé le {{ new Date(passkey.last_used_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' }) }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <button 
                                type="button" 
                                @click="deletePasskey(passkey.id)"
                                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition"
                                title="Supprimer cet appareil"
                            >
                                <TrashIcon class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Register New Passkey form -->
                <div v-if="isPasskeySupported" class="bg-gray-50 p-6 rounded-2xl border border-gray-100">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4">Enregistrer un nouvel appareil</h3>
                    
                    <div class="flex flex-col md:flex-row gap-4 items-end">
                        <div class="flex-grow w-full">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Nom de l'appareil (ex: Mon Téléphone, MacBook, etc.)</label>
                            <input 
                                v-model="deviceName" 
                                type="text" 
                                placeholder="ex: Mon Téléphone Personnel" 
                                class="w-full bg-white border-gray-200 rounded-xl font-bold py-3 px-4 focus:ring-2 focus:ring-indigo-600 text-sm transition"
                                :disabled="registerLoading"
                            />
                        </div>
                        <button 
                            type="button" 
                            @click="handleRegister"
                            :disabled="registerLoading"
                            class="w-full md:w-auto px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-black text-sm shadow-sm transition disabled:opacity-50 flex items-center justify-center gap-2"
                        >
                            <span v-if="registerLoading">Configuration...</span>
                            <span v-else>Enregistrer cet appareil</span>
                        </button>
                    </div>
                    <p v-if="registerError" class="mt-2 text-xs text-red-600 font-bold uppercase tracking-widest">
                        {{ registerError }}
                    </p>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="mt-12 p-8 bg-red-50 rounded-[2.5rem] border border-red-100 flex items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 bg-white rounded-2xl flex items-center justify-center text-red-600 shadow-sm">
                        <ExclamationTriangleIcon class="h-6 w-6" />
                    </div>
                    <div>
                        <h3 class="font-black text-red-900 tracking-tight">Zone Dangereuse</h3>
                        <p class="text-sm text-red-600 font-medium">Une fois supprimé, votre compte ne pourra plus être récupéré.</p>
                    </div>
                </div>
                <button class="px-6 py-3 bg-white text-red-600 border border-red-100 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-black hover:text-white transition-all">
                    Supprimer mon compte
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
