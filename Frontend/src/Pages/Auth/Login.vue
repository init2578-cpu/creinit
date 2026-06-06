<script setup>
import { ref } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { usePasskeyVerify } from '@laravel/passkeys/vue'
import { 
    EnvelopeIcon, 
    LockClosedIcon, 
    ArrowRightIcon,
    ExclamationCircleIcon,
    EyeIcon,
    EyeSlashIcon,
    UserIcon,
    FingerPrintIcon
} from '@heroicons/vue/24/outline'

const showPassword = ref(false)

const form = useForm({
    login: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}

const { verify, isLoading: passkeyLoading, isSupported: isPasskeySupported, error: passkeyError } = usePasskeyVerify({
    onSuccess: (response) => {
        if (response && response.redirect) {
            window.location.href = response.redirect
        }
    }
})
</script>

<template>
    <Head title="Connexion" />

    <GuestLayout>
        <div class="min-h-[80vh] flex flex-col items-center justify-center p-4 font-sans focus:outline-none">
            <!-- Glassy Card -->
            <div class="w-full max-w-md bg-white rounded-[2.5rem] shadow-2xl shadow-blue-100 border border-gray-50 overflow-hidden relative">
                <!-- Top Accent -->
                <div class="h-2 bg-gradient-to-r from-blue-600 to-indigo-600 font-sans"></div>
                
                <div class="p-10 md:p-12 focus:outline-none">
                    <div class="mb-10 text-center font-sans">
                        <h1 class="text-3xl font-black text-gray-900 tracking-tighter mb-2">Bon retour !</h1>
                        <p class="text-gray-500 font-medium">Espace sécurisé du CRE de Kolda</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Login field (Email or Phone) -->
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-400 mb-2 ml-1">Email ou Téléphone</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-600 transition-colors">
                                    <UserIcon v-if="!form.login.includes('@')" class="h-5 w-5" />
                                    <EnvelopeIcon v-else class="h-5 w-5" />
                                </div>
                                <input 
                                    v-model="form.login"
                                    type="text"
                                    required
                                    placeholder="nom@exemple.sn ou 77..."
                                    class="block w-full pl-12 pr-4 py-4 bg-gray-50 border-transparent rounded-2xl focus:ring-2 focus:ring-blue-600 focus:bg-white transition"
                                    :class="{'ring-2 ring-red-500 bg-red-50': form.errors.login}"
                                />
                            </div>
                            <span v-if="form.errors.login" class="text-red-500 text-xs mt-2 flex items-center gap-1">
                                <ExclamationCircleIcon class="h-4 w-4" />
                                {{ form.errors.login }}
                            </span>
                        </div>

                        <!-- Password -->
                        <div>
                            <div class="flex items-center justify-between mb-2 px-1">
                                <label class="block text-xs font-black uppercase tracking-widest text-gray-400">Mot de passe</label>
                                <Link :href="route('password.request')" class="text-xs font-bold text-blue-600 hover:text-indigo-600 transition">Oublié ?</Link>
                            </div>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-600 transition-colors">
                                    <LockClosedIcon class="h-5 w-5" />
                                </div>
                                <input 
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    placeholder="••••••••"
                                    class="block w-full pl-12 pr-12 py-4 bg-gray-50 border-transparent rounded-2xl focus:ring-2 focus:ring-blue-600 focus:bg-white transition"
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
                            <span v-if="form.errors.password" class="text-red-500 text-xs mt-2 flex items-center gap-1 font-sans">
                                <ExclamationCircleIcon class="h-4 w-4" />
                                {{ form.errors.password }}
                            </span>
                        </div>

                        <!-- Remember -->
                        <div class="flex items-center">
                            <input 
                                id="remember-me" 
                                v-model="form.remember"
                                type="checkbox" 
                                class="h-5 w-5 rounded-lg border-gray-300 text-blue-600 focus:ring-blue-600"
                            />
                            <label for="remember-me" class="ml-3 text-sm font-bold text-gray-600">Se souvenir de moi</label>
                        </div>

                        <!-- Submit -->
                        <button 
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-4 bg-gray-900 text-white rounded-2xl font-black text-lg shadow-xl shadow-gray-200 hover:bg-black transition flex items-center justify-center gap-2 group disabled:opacity-50"
                        >
                            <span v-if="form.processing">Connexion en cours...</span>
                            <span v-else>Se connecter</span>
                            <ArrowRightIcon v-if="!form.processing" class="h-5 w-5 group-hover:translate-x-1 transition-transform font-sans" />
                        </button>

                        <!-- Biometric Login (Passkey) -->
                        <div v-if="isPasskeySupported" class="flex flex-col gap-2 pt-2">
                            <div class="relative flex py-2 items-center">
                                <div class="flex-grow border-t border-gray-200"></div>
                                <span class="flex-shrink mx-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Ou</span>
                                <div class="flex-grow border-t border-gray-200"></div>
                            </div>
                            
                            <button 
                                type="button"
                                @click="verify"
                                :disabled="passkeyLoading"
                                class="w-full py-4 bg-white hover:bg-gray-50 text-gray-800 border-2 border-gray-200 rounded-2xl font-black text-sm transition flex items-center justify-center gap-2"
                            >
                                <FingerPrintIcon class="h-5 w-5 text-blue-600 animate-pulse" />
                                <span>{{ passkeyLoading ? "Vérification..." : "Empreinte digitale / FaceID" }}</span>
                            </button>
                            <span v-if="passkeyError" class="text-red-500 text-xs mt-1 text-center font-bold">
                                {{ passkeyError }}
                            </span>
                        </div>
                    </form>
                </div>

                <!-- Bottom Footer -->
                <div class="bg-gray-50 py-6 px-10 text-center border-t border-gray-100 font-sans">
                    <p class="text-sm text-gray-500 font-bold">
                        Pas encore inscrit ? 
                        <Link :href="route('applications.create')" class="text-blue-600 hover:underline ml-1">Candidater ici</Link>
                    </p>
                </div>
            </div>

            <!-- Accents -->
            <div class="mt-8 flex items-center gap-8 opacity-40 grayscale flex-wrap justify-center font-sans">
                <span class="text-xs font-black uppercase tracking-widest text-gray-400">CRE de Kolda</span>
                <span class="text-xs font-black uppercase tracking-widest text-gray-400">Sénégal Émergent</span>
                <span class="text-xs font-black uppercase tracking-widest text-gray-400">Excellence Inclusion</span>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
/* Optional micro-animations */
input:focus {
    transform: translateY(-1px);
}
</style>
