<script setup>
import { ref } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { 
    UserIcon, 
    KeyIcon, 
    CheckBadgeIcon,
    ExclamationTriangleIcon,
    PhotoIcon,
    ArrowUpTrayIcon
} from '@heroicons/vue/24/outline'

const user = usePage().props.auth.user

const form = useForm({
    _method: 'patch',
    name: user.name,
    email: user.email,
    password: '',
    password_confirmation: '',
    profile_photo: null,
})

const photoPreview = ref(null)
const photoInput = ref(null)

function updateProfile() {
    if (form.password && !form.profile_photo) {
        form.setError('profile_photo', 'Une photo de profil est obligatoire pour changer votre mot de passe.')
        return
    }
    
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
</script>

<template>
    <Head title="Mon Profil" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto py-12 px-4">
            <header class="mb-12 text-center">
                <div class="mb-4">
                    <span v-if="form.password" class="text-[10px] font-black text-red-600 bg-red-50 px-4 py-1.5 rounded-full border border-red-100 uppercase tracking-widest animate-pulse">
                        Photo obligatoire pour changer le MDP
                    </span>
                </div>
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
