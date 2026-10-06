<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { 
    AcademicCapIcon, 
    CheckBadgeIcon, 
    ArrowDownTrayIcon,
    CalendarIcon,
    QrCodeIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    certificates: Array
})

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    })
}

function suppressCelebration(uuid) {
    if (window.markCertificateAsCelebrated) {
        window.markCertificateAsCelebrated(uuid)
    }
}
</script>

<template>
    <Head title="Mes Attestations" />

    <AuthenticatedLayout>
        <div class="min-h-[calc(100vh-64px)] bg-gray-50/50 relative overflow-hidden font-sans">
            <!-- Subtle Tech Background -->
            <div class="absolute inset-0 pointer-events-none opacity-[0.05]" 
                style="background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 40px 40px;">
            </div>

            <!-- Page Header -->
            <div class="relative z-10 pt-10 pb-6 px-6 sm:px-10 max-w-7xl mx-auto">
                <div class="flex flex-col md:flex-row items-center justify-between gap-8 border-b border-gray-200/60 pb-10">
                    <div class="flex items-center gap-6">
                        <div class="h-20 w-20 rounded-3xl bg-slate-900 flex items-center justify-center overflow-hidden text-white text-2xl font-black shadow-xl shadow-slate-200 shrink-0 border-2 border-white">
                            <img v-if="$page.props.auth.user.profile_photo_url" :src="$page.props.auth.user.profile_photo_url" class="h-full w-full object-cover">
                            <template v-else>{{ $page.props.auth.user.name.charAt(0) }}</template>
                        </div>
                        <div>
                            <h2 class="text-3xl font-black text-slate-900 tracking-tight leading-none mb-2">Mes Attestations</h2>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-[0.3em]">Certifications de {{ $page.props.auth.user.name }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="px-5 py-2.5 bg-white rounded-2xl border border-gray-200 shadow-sm flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-[10px] font-black text-slate-600 uppercase tracking-widest">{{ certificates.length }} UNITÉS VALIDÉES</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="py-8 relative z-10">
                <div class="max-w-7xl mx-auto px-6 sm:px-10">
                    
                    <div v-if="certificates.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 auto-rows-fr">
                        <div 
                            v-for="cert in certificates" 
                            :key="cert.id"
                            class="group relative bg-white/70 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-slate-200/40 hover:shadow-2xl hover:shadow-blue-500/10 transition-all duration-500 flex flex-col"
                        >
                            <!-- Premium Accent -->
                            <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-cyan-500 to-blue-600 rounded-t-[2.5rem] opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            
                            <div class="relative z-10 flex flex-col h-full">
                                <!-- Card Header -->
                                <div class="flex items-start justify-between mb-8">
                                    <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 shadow-inner group-hover:scale-105 transition-transform duration-500">
                                        <AcademicCapIcon class="h-8 w-8" />
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <div 
                                            class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest mb-1 border"
                                            :class="cert.type === 'participation' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-emerald-50 text-emerald-600 border-emerald-100'"
                                        >
                                            {{ cert.type === 'participation' ? 'Participation' : 'Réussite' }}
                                        </div>
                                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">ID: {{ cert.uuid.substring(0, 8) }}</span>
                                    </div>
                                </div>

                                <!-- Card Content -->
                                <h3 class="text-2xl font-black text-slate-900 leading-tight mb-4 group-hover:text-blue-700 transition-colors">
                                    {{ cert.module.titre }}
                                </h3>

                                <div class="space-y-3 mb-10">
                                    <div v-if="cert.score !== null && cert.score !== undefined" class="flex items-center gap-3 text-[10px] font-bold uppercase tracking-widest" :class="cert.type === 'participation' ? 'text-amber-700' : 'text-emerald-700'">
                                        <CheckBadgeIcon class="h-4 w-4 text-slate-400" />
                                        Moyenne obtenue : {{ cert.score }} / 20
                                    </div>
                                    <div class="flex items-center gap-3 text-[10px] text-slate-500 font-bold uppercase tracking-widest">
                                        <CalendarIcon class="h-4 w-4 text-slate-300" />
                                        Obtenue le {{ formatDate(cert.issued_at) }}
                                    </div>
                                    <div class="flex items-center gap-3 text-[10px] text-slate-500 font-bold uppercase tracking-widest">
                                        <QrCodeIcon class="h-4 w-4 text-slate-300" />
                                        Sceau Digital : QN-{{ cert.uuid.substring(0, 6) }}
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="mt-auto flex flex-col gap-3">
                                    <Link 
                                        :href="route('certificates.view', cert.uuid)"
                                        @click="suppressCelebration(cert.uuid)"
                                        class="flex items-center justify-center gap-3 py-4.5 bg-slate-900 hover:bg-blue-700 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] transition-all shadow-lg hover:shadow-blue-500/20 active:scale-95"
                                    >
                                        <QrCodeIcon class="h-4 w-4" />
                                        Consulter le Diplôme
                                    </Link>
                                    <div class="flex gap-3">
                                        <a 
                                            :href="route('certificates.download', cert.id)"
                                            @click="suppressCelebration(cert.uuid)"
                                            class="flex-1 flex items-center justify-center gap-3 py-4 bg-white hover:bg-slate-50 text-slate-600 rounded-2xl font-black text-[9px] uppercase tracking-[0.2em] transition-all border border-gray-200 shadow-sm"
                                        >
                                            <ArrowDownTrayIcon class="h-4 w-4" />
                                            PDF
                                        </a>
                                        <Link 
                                            :href="route('certificates.verify', cert.uuid)"
                                            target="_blank"
                                            class="p-4 bg-white hover:bg-amber-50 text-slate-400 hover:text-amber-600 rounded-2xl transition-all border border-gray-200 group/verify shadow-sm"
                                            title="Vérifier l'authenticité"
                                        >
                                            <CheckBadgeIcon class="h-4 w-4" />
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Professional Empty State -->
                    <div v-else class="py-24 text-center bg-white/70 backdrop-blur-xl rounded-[3rem] border border-gray-200/60 shadow-xl shadow-slate-200/20">
                        <div class="w-24 h-24 bg-slate-50 rounded-[2rem] flex items-center justify-center mx-auto mb-8 border border-gray-100 shadow-inner">
                            <InformationCircleIcon class="h-10 w-10 text-slate-300" />
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight uppercase">Pas d'attestation acquise</h3>
                        <p class="text-slate-400 mt-4 max-w-sm mx-auto font-bold uppercase tracking-widest text-[10px] leading-relaxed">
                            Complétez vos modules d'apprentissage et validez vos examens pour débloquer vos accréditations.
                        </p>
                        <div class="mt-10">
                            <Link 
                                :href="route('student.courses')"
                                class="inline-flex items-center gap-3 px-8 py-5 bg-blue-600 hover:bg-blue-500 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl shadow-blue-500/20 transition-all active:scale-95"
                            >
                                <AcademicCapIcon class="w-4 h-4" />
                                Voir le Catalogue des Cours
                            </Link>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;700&display=swap');

.font-sans {
    font-family: 'Space Grotesk', sans-serif;
}

.py-4\.5 {
    padding-top: 1.125rem;
    padding-bottom: 1.125rem;
}
</style>
