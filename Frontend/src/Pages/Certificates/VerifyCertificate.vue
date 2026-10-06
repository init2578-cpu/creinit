<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head } from '@inertiajs/vue3'
import { 
    CheckCircleIcon, 
    XCircleIcon, 
    ShieldCheckIcon,
    AcademicCapIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    valid: Boolean,
    message: String,
    certificate: Object
})
</script>

<template>
    <Head title="Vérification de Diplôme" />

    <GuestLayout>
        <div class="min-h-[80vh] flex flex-col items-center justify-center p-4 sm:p-8">
            <div class="w-full max-w-xl">
                <!-- Brand Header -->
                <div class="flex flex-col items-center mb-12">
                    <div class="h-20 w-20 bg-blue-600 rounded-[2rem] flex items-center justify-center text-white shadow-2xl shadow-blue-200 mb-6 rotate-3">
                        <AcademicCapIcon class="h-10 w-10" />
                    </div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tighter uppercase">E-CRE <span class="text-blue-600">KOLDA</span></h1>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em] mt-2">Services de Certification Officiels</p>
                </div>

                <!-- Verification Enclosure -->
                <div class="relative bg-white p-10 sm:p-16 rounded-[4rem] shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Decor -->
                    <div class="absolute -top-10 -right-10 h-40 w-40 bg-gray-50 rounded-full opacity-50 blur-3xl"></div>
                    
                    <div v-if="valid" class="text-center relative z-10">
                        <div class="h-24 w-24 bg-green-50 text-green-600 rounded-[2rem] flex items-center justify-center mx-auto mb-8 ring-8 ring-green-50/50">
                            <CheckCircleIcon class="h-12 w-12" />
                        </div>
                        
                        <h2 class="text-3xl font-black text-gray-900 tracking-tight mb-4">Certificat Authentique</h2>
                        <div class="p-6 bg-green-50/50 rounded-3xl border border-green-100 mb-8">
                            <p class="text-green-800 font-bold leading-relaxed">
                                {{ message }}
                            </p>
                        </div>

                        <div class="space-y-6 text-left border-t border-gray-100 pt-8">
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Titulaire</label>
                                <p class="text-xl font-black text-gray-900">{{ certificate.learner }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Module</label>
                                    <p class="font-black text-gray-900">{{ certificate.module }}</p>
                                    <p class="text-xs text-blue-600 font-bold uppercase">{{ certificate.code_module }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Date d'émission</label>
                                    <p class="font-black text-gray-900">{{ certificate.issued_at }}</p>
                                </div>
                            </div>
                            <div class="p-4 rounded-2xl border" :class="certificate.type === 'participation' ? 'bg-amber-50/80 border-amber-200' : 'bg-emerald-50/80 border-emerald-200'">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-[10px] font-black uppercase tracking-widest" :class="certificate.type === 'participation' ? 'text-amber-700' : 'text-emerald-700'">Certification</span>
                                        <p class="text-sm font-black" :class="certificate.type === 'participation' ? 'text-amber-900' : 'text-emerald-900'">
                                            {{ certificate.type_label || (certificate.type === 'participation' ? 'Attestation de Participation' : 'Attestation de Réussite') }}
                                        </p>
                                    </div>
                                    <div v-if="certificate.score !== null && certificate.score !== undefined" class="text-right">
                                        <span class="text-[10px] font-black uppercase tracking-widest" :class="certificate.type === 'participation' ? 'text-amber-700' : 'text-emerald-700'">Moyenne</span>
                                        <p class="text-sm font-black" :class="certificate.type === 'participation' ? 'text-amber-900' : 'text-emerald-900'">{{ certificate.score }} / 20</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-12 flex items-center justify-center gap-3 py-4 border-2 border-dashed border-gray-100 rounded-3xl">
                            <ShieldCheckIcon class="h-5 w-5 text-blue-600" />
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">ID Unique : {{ certificate.uuid.substring(0, 13) }}...</span>
                        </div>
                    </div>

                    <div v-else class="text-center relative z-10">
                        <div class="h-24 w-24 bg-red-50 text-red-600 rounded-[2rem] flex items-center justify-center mx-auto mb-8 ring-8 ring-red-50/50">
                            <XCircleIcon class="h-12 w-12" />
                        </div>
                        
                        <h2 class="text-3xl font-black text-red-600 tracking-tight mb-6 uppercase">Alerte de Sécurité</h2>
                        <div class="p-8 bg-red-50 rounded-3xl border border-red-100">
                            <p class="text-red-900 font-black leading-relaxed">
                                {{ message }}
                            </p>
                        </div>
                        <p class="mt-8 text-gray-500 font-medium text-sm">
                            Si vous pensez qu'il s'agit d'une erreur, veuillez contacter le service scolarité du CRE Kolda.
                        </p>
                    </div>
                </div>

                <div class="mt-12 text-center">
                    <p class="text-[10px] font-black text-gray-300 uppercase tracking-widest">
                        © 2026 Centre de Recherche et d'Essai de Kolda
                    </p>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
