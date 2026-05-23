<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { 
    LockClosedIcon, 
    ArrowRightIcon,
    ExclamationCircleIcon,
    ShieldCheckIcon,
    EyeIcon,
    EyeSlashIcon,
    CameraIcon,
    UserIcon
} from '@heroicons/vue/24/outline'
import { ref } from 'vue'

const showPassword = ref(false)
const photoPreview = ref(null)

const form = useForm({
    password: '',
    password_confirmation: '',
    profile_photo: null,
})

const onFileChange = (e) => {
    const file = e.target.files[0]
    if (!file) return
    
    form.profile_photo = file
    const reader = new FileReader()
    reader.onload = (e) => {
        photoPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
}

const submit = () => {
    form.post(route('password.change.update'), {
        forceFormData: true,
    })
}
</script>

<template>
    <Head :title="$page.props.auth.user.must_change_password ? 'Finaliser mon profil' : 'Changer le mot de passe'" />

    <GuestLayout>
        <div class="min-h-[90vh] flex flex-col items-center justify-center p-4 font-sans">
            <div class="w-full max-w-xl bg-white rounded-[3rem] shadow-[0_32px_64px_-16px_rgba(0,0,0,0.1)] border border-gray-100 overflow-hidden relative">
                <div class="h-2 bg-gradient-to-r from-blue-600 to-indigo-600"></div>
                
                <div class="p-10 md:p-14">
                    <div class="mb-12 text-center">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-50 text-blue-600 rounded-3xl mb-6 shadow-sm ring-1 ring-blue-100">
                            <UserIcon v-if="$page.props.auth.user.must_change_password" class="h-10 w-10" />
                            <ShieldCheckIcon v-else class="h-10 w-10 text-blue-600" />
                        </div>
                        <h1 class="text-4xl font-black text-gray-900 tracking-tighter mb-3 font-sans">
                            {{ $page.props.auth.user.must_change_password ? 'Finaliser mon Profil' : 'Sécurité de mon Compte' }}
                        </h1>
                        <p class="text-gray-500 font-bold text-lg max-w-sm mx-auto font-sans leading-relaxed">
                            {{ $page.props.auth.user.must_change_password 
                                ? 'Bienvenue ! Veuillez configurer votre compte pour accéder à votre espace.'
                                : 'Mettez à jour votre mot de passe pour maintenir la sécurité de votre accès.' 
                            }}
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-10">
                        <!-- Profile Photo Upload (Only for first login) -->
                        <div v-if="$page.props.auth.user.must_change_password" class="flex flex-col items-center gap-6">
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-400 self-start ml-1">Photo de profil (Obligatoire)</label>
                            
                            <div class="relative group">
                                <div 
                                    class="w-40 h-40 rounded-[2.5rem] overflow-hidden bg-gray-50 border-4 border-white shadow-xl flex items-center justify-center ring-1 ring-gray-100 transition-all group-hover:ring-blue-400 group-hover:ring-2"
                                    :class="{'ring-red-200 ring-2': form.errors.profile_photo}"
                                >
                                    <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover" />
                                    <div v-else class="flex flex-col items-center text-gray-300">
                                        <CameraIcon class="h-12 w-12" />
                                        <span class="text-[10px] font-black uppercase tracking-widest mt-2">Cliquer ici</span>
                                    </div>
                                    
                                    <input 
                                        type="file" 
                                        @change="onFileChange"
                                        accept="image/*"
                                        class="absolute inset-0 opacity-0 cursor-pointer z-10"
                                    />
                                    
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                        <CameraIcon class="h-10 w-10 text-white" />
                                    </div>
                                </div>
                                <div v-if="photoPreview" class="absolute -bottom-2 -right-2 bg-blue-600 text-white p-2 rounded-xl shadow-lg shadow-blue-200">
                                    <ShieldCheckIcon class="h-5 w-5" />
                                </div>
                            </div>
                            
                            <span v-if="form.errors.profile_photo" class="text-red-500 text-xs font-black uppercase tracking-widest flex items-center gap-1">
                                <ExclamationCircleIcon class="h-4 w-4" />
                                {{ form.errors.profile_photo }}
                            </span>
                            <p class="text-xs text-gray-400 font-bold italic text-center">Une photo claire est requise pour votre carte d'apprenant.</p>
                        </div>

                        <div class="space-y-8">
                            <!-- Password -->
                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest text-gray-400 mb-2 ml-1">Nouveau mot de passe</label>
                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-600 transition-colors">
                                        <LockClosedIcon class="h-5 w-5" />
                                    </div>
                                    <input 
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="••••••••"
                                        class="block w-full pl-12 pr-12 py-5 bg-gray-50 border-transparent rounded-[1.5rem] focus:ring-2 focus:ring-blue-600 focus:bg-white transition font-bold"
                                        :class="{'ring-2 ring-red-500 bg-red-50': form.errors.password}"
                                    />
                                    <button 
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-blue-600 transition"
                                    >
                                        <component :is="showPassword ? EyeSlashIcon : EyeIcon" class="h-5 w-5" />
                                    </button>
                                </div>
                                <span v-if="form.errors.password" class="text-red-500 text-xs mt-3 flex items-center gap-1 font-bold">
                                    <ExclamationCircleIcon class="h-4 w-4" />
                                    {{ form.errors.password }}
                                </span>
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest text-gray-400 mb-2 ml-1">Confirmer mot de passe</label>
                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-600 transition-colors">
                                        <LockClosedIcon class="h-5 w-5" />
                                    </div>
                                    <input 
                                        v-model="form.password_confirmation"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="••••••••"
                                        class="block w-full pl-12 pr-4 py-5 bg-gray-50 border-transparent rounded-[1.5rem] focus:ring-2 focus:ring-blue-600 focus:bg-white transition font-bold"
                                        :class="{'ring-2 ring-red-500 bg-red-50': form.errors.password_confirmation}"
                                    />
                                </div>
                            </div>
                        </div>

                        <button 
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-6 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-[1.5rem] font-black text-lg shadow-2xl shadow-blue-200 hover:scale-[1.02] transition-all flex items-center justify-center gap-3 group disabled:opacity-50 active:scale-95"
                        >
                            <span v-if="form.processing">Traitement en cours...</span>
                            <span v-else>
                                {{ $page.props.auth.user.must_change_password ? 'Enregistrer & Commencer' : 'Mettre à jour le mot de passe' }}
                            </span>
                            <ArrowRightIcon v-if="!form.processing" class="h-6 w-6 group-hover:translate-x-1 transition-transform" />
                        </button>
                    </form>
                </div>
            </div>
            
            <p class="mt-8 text-gray-400 text-xs font-bold uppercase tracking-widest">&copy; 2026 E-CRE. TOUS DROITS RÉSERVÉS.</p>
        </div>
    </GuestLayout>
</template>
