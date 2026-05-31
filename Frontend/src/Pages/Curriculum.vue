<script setup>
import { Head, Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { 
    CommandLineIcon, 
    AcademicCapIcon, 
    BeakerIcon,
    BoltIcon,
    ArrowRightIcon,
    SparklesIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline'

const courses = [
    {
        title: 'Pôle Numérique',
        icon: CommandLineIcon,
        color: 'cyan',
        modules: ['Bureautique Avancée', 'Maintenance Informatique', 'Maintenance Réseaux', 'Web Design Basis']
    },
    {
        title: 'Pôle Agro-Industrie',
        icon: BeakerIcon,
        color: 'emerald',
        modules: ['Transformation de Fruits & Légumes', 'Conservation des Aliments', 'Saponification & Cosmétique', 'Fabrication Eau de Javel']
    },
    {
        title: 'Pôle Énergie',
        icon: BoltIcon,
        color: 'amber',
        modules: ['Installation Solaire Photovoltaïque', 'Maintenance de Kits Solaires', 'Énergie Biomasse', 'Économie d\'Énergie']
    }
]
</script>

<template>
    <Head title="Curriculum & Formations — E-CRE Kolda" />

    <GuestLayout>
        <div class="relative min-h-screen pt-32 pb-20 overflow-hidden bg-slate-950">
            <!-- Background Decorations -->
            <div class="absolute inset-0 z-0 pointer-events-none">
                <div class="absolute top-[20%] right-0 w-[600px] h-[600px] bg-emerald-500/5 rounded-full blur-[150px]"></div>
                <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-cyan-500/10 rounded-full blur-[120px]"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="mb-20 text-center">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 border border-emerald-500/20 mb-8">
                        <SparklesIcon class="h-4 w-4 text-emerald-400" />
                        <span class="text-[10px] font-black text-emerald-400 uppercase tracking-[0.3em]">Knowledge Hub — Nexus Learning</span>
                    </div>
                    <h1 class="text-6xl md:text-8xl font-black text-white tracking-tighter leading-none mb-8 font-display">
                        FORGEZ VOTRE <span class="text-glow-emerald text-emerald-400">AVENIR</span>.
                    </h1>
                    <p class="text-slate-400 text-xl font-medium max-w-2xl mx-auto">
                        Découvrez nos parcours de formation certifiants conçus pour répondre aux besoins réels de développement du Sénégal.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-24">
                    <div v-for="(category, i) in courses" :key="i" 
                        class="glass-dark rounded-[2.5rem] border border-white/5 overflow-hidden group hover:border-emerald-500/20 transition-all"
                    >
                        <div class="p-8 border-b border-white/5 bg-slate-900/50 flex items-center justify-between">
                            <div class="h-12 w-12 rounded-xl bg-slate-950 border border-white/10 flex items-center justify-center" :class="`text-${category.color}-400`">
                                <component :is="category.icon" class="h-6 w-6" />
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">PROGRAMME CERTIFIANT</span>
                        </div>
                        <div class="p-8">
                            <h3 class="text-2xl font-black text-white mb-6">{{ category.title }}</h3>
                            <ul class="space-y-4">
                                <li v-for="mod in category.modules" :key="mod" class="flex items-start gap-3">
                                    <CheckCircleIcon class="h-5 w-5 text-emerald-500 shrink-0" />
                                    <span class="text-slate-400 font-medium">{{ mod }}</span>
                                </li>
                            </ul>
                            
                            <Link :href="route('applications.create')" 
                                class="mt-10 w-full py-4 glass-dark border border-white/5 rounded-2xl flex items-center justify-center gap-3 text-white font-bold hover:bg-emerald-500 hover:text-slate-950 transition-all group/btn"
                            >
                                S'inscrire au Pôle
                                <ArrowRightIcon class="h-5 w-5 group-hover/btn:translate-x-1 transition-transform" />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Learning Methodology -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center bg-emerald-500/5 rounded-[3rem] p-12 border border-emerald-500/10">
                    <div>
                        <h2 class="text-4xl font-black text-white mb-6">Pédagogie Active & Hub Numérique</h2>
                        <p class="text-slate-400 text-lg leading-relaxed mb-8 font-medium">
                            Au CRE de Kolda, nous privilégions la pratique. Chaque module est accompagné d'ateliers concrets dans nos laboratoires équipés.
                        </p>
                        <div class="grid grid-cols-2 gap-6">
                            <div class="p-6 glass-dark rounded-2xl border border-white/5">
                                <p class="text-3xl font-black text-emerald-400 mb-1">80%</p>
                                <p class="text-[10px] text-slate-500 uppercase font-black">Pratique Immédiate</p>
                            </div>
                            <div class="p-6 glass-dark rounded-2xl border border-white/5">
                                <p class="text-3xl font-black text-cyan-400 mb-1">Nexus</p>
                                <p class="text-[10px] text-slate-500 uppercase font-black">Certificat Numérique</p>
                            </div>
                        </div>
                    </div>
                    <div class="relative rounded-3xl overflow-hidden aspect-video">
                        <img src="/images/learning-at-cre.png" alt="Learning at CRE" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-emerald-500/10 mix-blend-overlay"></div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.text-glow-emerald {
    text-shadow: 0 0 30px rgba(16, 185, 129, 0.4), 0 0 60px rgba(16, 185, 129, 0.2);
}
.glass-dark {
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
</style>
