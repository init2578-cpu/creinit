<script setup>
import { Head, Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { 
    CpuChipIcon, 
    ChatBubbleLeftRightIcon, 
    ShieldCheckIcon,
    ArrowRightIcon,
    UserGroupIcon,
    SparklesIcon
} from '@heroicons/vue/24/outline'

const features = [
    {
        title: 'Nexus Core',
        desc: "Notre système de gestion centralisé (SIS) qui pilote l'ensemble de la scolarité et des ressources matérielles.",
        icon: CpuChipIcon,
        color: 'cyan'
    },
    {
        title: 'IA Assane',
        desc: "L'assistant intelligent basé sur Gemini Pro, intégré pour aider les étudiants et le personnel au quotidien.",
        icon: SparklesIcon,
        color: 'indigo'
    },
    {
        title: 'Hub Communautaire',
        desc: "Un espace d'échange sécurisé pour les étudiants et alumni afin de favoriser le networking local.",
        icon: UserGroupIcon,
        color: 'blue'
    }
]
</script>

<template>
    <Head title="Espace Plateforme Nexus — E-CRE Kolda" />

    <GuestLayout>
        <div class="relative min-h-screen pt-32 pb-20 overflow-hidden bg-slate-950">
            <!-- Background Decorations -->
            <div class="absolute inset-0 z-0 pointer-events-none">
                <div class="absolute top-[10%] left-[10%] w-[400px] h-[400px] bg-indigo-500/10 rounded-full blur-[100px] animate-pulse-slow"></div>
                <div class="absolute bottom-[20%] right-[5%] w-[600px] h-[600px] bg-cyan-500/5 rounded-full blur-[150px]"></div>
                <!-- Scanning Line effect -->
                <div class="absolute inset-0 bg-[linear-gradient(to_bottom,transparent,rgba(6,182,212,0.05),transparent)] bg-[size:100%_4px] bg-repeat"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center mb-24">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 mb-8">
                        <CpuChipIcon class="h-4 w-4 text-indigo-400" />
                        <span class="text-[10px] font-black text-indigo-400 uppercase tracking-[0.3em]">Neural System — Platform Hub</span>
                    </div>
                    <h1 class="text-4xl sm:text-6xl md:text-8xl font-black text-white tracking-tighter leading-none mb-8 font-display">
                        L'ÉCOSYSTÈME <br> <span class="text-glow-indigo text-indigo-500">DIGITAL</span>.
                    </h1>
                    <p class="text-slate-400 text-xl font-medium max-w-2xl mx-auto">
                        E-CRE Nexus est une plateforme d'innovation intégrée, offrant des outils de pointe pour l'apprentissage, la collaboration et la gestion institutionnelle.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-32">
                    <div v-for="(feature, i) in features" :key="i" 
                        class="p-6 sm:p-10 glass-dark rounded-[2.5rem] border border-white/5 group hover:border-indigo-500/30 transition-all"
                    >
                        <div class="h-16 w-16 rounded-2xl bg-slate-900 border border-white/5 flex items-center justify-center mb-8" :class="`text-${feature.color}-400`">
                            <component :is="feature.icon" class="h-8 w-8 group-hover:rotate-12 transition-transform" />
                        </div>
                        <h3 class="text-2xl font-black text-white mb-4">{{ feature.title }}</h3>
                        <p class="text-slate-500 font-medium leading-relaxed">{{ feature.desc }}</p>
                    </div>
                </div>

                <!-- AI Showcase -->
                <div class="glass-dark rounded-[2rem] sm:rounded-[3.5rem] border border-white/10 p-2 overflow-hidden relative">
                    <div class="flex flex-col lg:flex-row items-center gap-12 bg-slate-900/50 rounded-[1.8rem] sm:rounded-[3.2rem] p-6 sm:p-12 md:p-20">
                        <div class="lg:w-1/2">
                            <h2 class="text-4xl font-black text-white mb-8">Faites connaissance avec Assane Chat Bot</h2>
                            <p class="text-slate-400 text-lg leading-relaxed mb-10">
                                Notre agent IA est là pour vous guider. Que vous cherchiez des informations sur une formation ou que vous ayez besoin d'aide pour votre dossier, Assane répond en temps réel.
                            </p>
                            <Link :href="route('login')" class="inline-flex items-center gap-3 px-8 py-4 bg-indigo-500 text-white rounded-2xl font-black hover:bg-indigo-400 transition-all shadow-[0_0_30px_rgba(99,102,241,0.3)]">
                                Essayer l'IA Nexus
                                <ArrowRightIcon class="h-5 w-5" />
                            </Link>
                        </div>
                        <div class="lg:w-1/2 relative">
                            <div class="absolute -inset-10 bg-indigo-500/20 blur-3xl rounded-full"></div>
                            <img src="/images/cre-ai-hero.png" alt="IA Assane" class="relative z-10 w-full max-w-md mx-auto drop-shadow-2xl rounded-3xl border border-white/10">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.text-glow-indigo {
    text-shadow: 0 0 30px rgba(99, 102, 241, 0.4), 0 0 60px rgba(99, 102, 241, 0.2);
}
.glass-dark {
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
</style>
