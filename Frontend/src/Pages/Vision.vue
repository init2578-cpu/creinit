<script setup>
import { Head, Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { 
    SparklesIcon, 
    AcademicCapIcon, 
    LightBulbIcon,
    RocketLaunchIcon,
    ArrowRightIcon
} from '@heroicons/vue/24/outline'

const visionItems = [
    {
        title: 'Démocratisation',
        desc: "L'innovation n'est plus un luxe. Nous brisons les barrières d'accès à la technologie pour chaque habitant du Fouladou.",
        icon: LightBulbIcon
    },
    {
        title: 'Recherche Appliquée',
        desc: "Transformer les théories universitaires en solutions pratiques pour l'agriculture, l'énergie et l'artisanat local.",
        icon: AcademicCapIcon
    },
    {
        title: 'Émergence Kolda',
        desc: "Positionner notre région comme un pôle technologique majeur dans le cadre du Plan Sénégal Émergent.",
        icon: RocketLaunchIcon
    }
]
</script>

<template>
    <Head title="Notre Vision — E-CRE Kolda" />

    <GuestLayout>
        <div class="relative min-h-screen pt-32 pb-20 overflow-hidden bg-slate-950">
            <!-- Background Decorations -->
            <div class="absolute inset-0 z-0 pointer-events-none">
                <div class="absolute top-0 left-0 w-[600px] h-[600px] bg-cyan-500/10 rounded-full blur-[150px] animate-pulse-slow"></div>
                <div class="absolute bottom-0 right-0 w-[500px] h-[500px] bg-indigo-500/10 rounded-full blur-[120px] animate-pulse-slow" style="animation-delay: 3s"></div>
                <!-- Cyber Grid -->
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_2px_2px,rgba(6,182,212,0.05)_1px,transparent_0)] bg-[size:50px_50px]"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center mb-24">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-cyan-500/10 border border-cyan-500/20 mb-8">
                        <SparklesIcon class="h-4 w-4 text-cyan-400" />
                        <span class="text-[10px] font-black text-cyan-400 uppercase tracking-[0.3em]">Horizon 2030 — Nexus Vision</span>
                    </div>
                    <h1 class="text-6xl md:text-8xl font-black text-white tracking-tighter leading-none mb-8 font-display">
                        PENSER <span class="text-glow-cyan text-cyan-500">DEMAIN</span>.<br>
                        AGIR MAINTENANT.
                    </h1>
                    <p class="text-slate-400 text-xl font-medium max-w-2xl mx-auto leading-relaxed">
                        Le CRE de Kolda est l'interface stratégique entre la recherche scientifique et les populations. Notre mission est de transformer le savoir en progrès tangible.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-32">
                    <div v-for="(item, i) in visionItems" :key="i" 
                        class="p-10 glass-dark rounded-[2.5rem] border border-white/5 group hover:border-cyan-500/30 transition-all flex flex-col items-center text-center"
                    >
                        <div class="h-16 w-16 rounded-2xl bg-slate-900 border border-white/5 flex items-center justify-center text-cyan-400 mb-8 group-hover:scale-110 transition-transform">
                            <component :is="item.icon" class="h-8 w-8" />
                        </div>
                        <h3 class="text-2xl font-black text-white mb-4">{{ item.title }}</h3>
                        <p class="text-slate-500 font-medium leading-relaxed italic">"{{ item.desc }}"</p>
                    </div>
                </div>

                <div class="glass-dark rounded-[3rem] border border-white/10 p-12 md:p-20 relative overflow-hidden">
                    <div class="absolute -right-20 -top-20 w-64 h-64 bg-cyan-500/10 rounded-full blur-[100px]"></div>
                    <div class="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                        <div>
                            <h2 class="text-4xl font-black text-white mb-8 tracking-tight">L'Engagement du MESRI</h2>
                            <p class="text-slate-400 text-lg leading-relaxed mb-8">
                                Sous la tutelle du Ministère de l'Enseignement Supérieur, de la Recherche et de l'Innovation, nous portons une ambition nationale : faire de la science un moteur du Plan Sénégal Émergent.
                            </p>
                            <div class="flex flex-col gap-4">
                                <div class="flex items-center gap-4 text-cyan-400 font-bold">
                                    <div class="h-2 w-2 rounded-full bg-cyan-500"></div>
                                    Vulgarisation Scientifique Totale
                                </div>
                                <div class="flex items-center gap-4 text-cyan-400 font-bold">
                                    <div class="h-2 w-2 rounded-full bg-cyan-500"></div>
                                    Infrastructures Technologiques de Pointe
                                </div>
                                <div class="flex items-center gap-4 text-cyan-400 font-bold">
                                    <div class="h-2 w-2 rounded-full bg-cyan-500"></div>
                                    Soutien à l'Innovation Locale
                                </div>
                            </div>
                        </div>
                        <div class="relative">
                            <img src="/images/vision-mesri.png" alt="MESRI Vision" class="rounded-3xl shadow-2xl relative z-10 border border-white/10">
                            <div class="absolute -inset-4 bg-cyan-500/20 blur-2xl z-0"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.font-display { font-family: 'Inter', sans-serif; }
.text-glow-cyan {
    text-shadow: 0 0 30px rgba(6, 182, 212, 0.4), 0 0 60px rgba(6, 182, 212, 0.2);
}
.glass-dark {
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
@keyframes pulse-slow {
    0%, 100% { opacity: 0.1; transform: scale(1); }
    50% { opacity: 0.3; transform: scale(1.1); }
}
.animate-pulse-slow { animation: pulse-slow 10s ease-in-out infinite; }
</style>
