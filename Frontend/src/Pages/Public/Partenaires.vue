<script setup>
import { Head, Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import {
    BuildingOffice2Icon,
    ArrowTopRightOnSquareIcon,
    CalendarIcon,
    TagIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    partnerships: Array,
})

const typeColors = {
    'institutionnel': 'bg-blue-500/10 text-blue-400 border-blue-500/20',
    'privé': 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20',
    'ONG': 'bg-green-500/10 text-green-400 border-green-500/20',
    'académique': 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
}

function getTypeColor(type) {
    return typeColors[type] || 'bg-slate-500/10 text-slate-400 border-slate-500/20'
}

// Group partners by type for a nicer visual layout
const partnerTypes = [...new Set(props.partnerships.map(p => p.type))]
</script>

<template>
    <Head title="Nos Partenaires — CRE Kolda" />

    <GuestLayout>
        <!-- Hero -->
        <section class="relative pt-32 pb-20 bg-slate-950 overflow-hidden">
            <div class="absolute inset-0 bg-cyber-grid opacity-20 pointer-events-none"></div>
            <div class="absolute inset-0 bg-gradient-to-b from-cyan-500/8 via-slate-950 to-slate-950 pointer-events-none"></div>

            <!-- Animated glow orbs -->
            <div class="absolute top-1/4 left-1/3 w-[40vw] h-[40vw] bg-cyan-500/10 rounded-full blur-[150px] animate-pulse-slow pointer-events-none"></div>

            <div class="max-w-7xl mx-auto px-4 relative z-10 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-cyan-500/10 border border-cyan-500/20 mb-6">
                    <span class="text-[10px] font-black text-cyan-400 uppercase tracking-[0.3em]">Réseau d'Alliance</span>
                </div>
                <h1 class="text-5xl md:text-7xl font-black text-white tracking-tighter mb-6 font-display leading-tight">
                    NOS <span class="text-cyan-500 text-glow-cyan">PARTENAIRES</span>
                </h1>
                <p class="text-slate-400 max-w-2xl mx-auto text-lg leading-relaxed">
                    Le CRE Kolda avance grâce à un réseau solide d'institutions, d'entreprises et d'organisations partageant la même vision d'un avenir numérique pour le Fouladou.
                </p>
            </div>
        </section>

        <!-- Partners Section -->
        <section class="py-12 bg-slate-950 min-h-[50vh]">
            <div class="max-w-7xl mx-auto px-4">

                <!-- Empty state -->
                <div v-if="partnerships.length === 0" class="text-center py-24 glass-dark rounded-[3rem] border border-white/5">
                    <BuildingOffice2Icon class="h-16 w-16 text-slate-700 mx-auto mb-4" />
                    <h3 class="text-2xl font-black text-white mb-2">Aucun partenaire actif</h3>
                    <p class="text-slate-500">Les informations seront disponibles prochainement.</p>
                </div>

                <!-- Partners grid by type -->
                <div v-else class="space-y-16">
                    <div v-for="type in partnerTypes" :key="type">
                        <div class="flex items-center gap-4 mb-8">
                            <span 
                                class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border"
                                :class="getTypeColor(type)"
                            >
                                {{ type }}
                            </span>
                            <div class="flex-1 h-px bg-white/5"></div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
                            <a 
                                v-for="partner in partnerships.filter(p => p.type === type)" 
                                :key="partner.id"
                                :href="partner.website || '#'"
                                :target="partner.website ? '_blank' : '_self'"
                                :rel="partner.website ? 'noopener noreferrer' : ''"
                                class="glass-dark border border-white/5 rounded-[2rem] p-6 flex flex-col items-center justify-center gap-4 group hover:border-cyan-500/30 transition-all duration-500 hover:-translate-y-2 relative"
                                :class="{ 'cursor-default': !partner.website }"
                            >
                                <!-- External link badge -->
                                <div v-if="partner.website" class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <ArrowTopRightOnSquareIcon class="h-4 w-4 text-cyan-400" />
                                </div>

                                <!-- Logo or icon -->
                                <div class="h-20 w-full flex items-center justify-center">
                                    <img 
                                        v-if="partner.logo_path" 
                                        :src="'/storage/' + partner.logo_path" 
                                        :alt="partner.nom"
                                        class="max-h-16 max-w-full object-contain filter grayscale group-hover:grayscale-0 opacity-60 group-hover:opacity-100 transition-all duration-500"
                                    >
                                    <div v-else class="h-16 w-16 rounded-2xl bg-slate-800 flex items-center justify-center border border-white/5 group-hover:bg-cyan-500/10 transition-all">
                                        <BuildingOffice2Icon class="h-8 w-8 text-slate-600 group-hover:text-cyan-400 transition-colors" />
                                    </div>
                                </div>

                                <!-- Info -->
                                <div class="text-center">
                                    <p class="text-sm font-black text-white group-hover:text-cyan-400 transition-colors line-clamp-2 leading-tight">{{ partner.nom }}</p>
                                    <div v-if="partner.date_signature" class="flex items-center justify-center gap-1 mt-2 text-[9px] font-bold text-slate-600">
                                        <CalendarIcon class="h-3 w-3" />
                                        {{ new Date(partner.date_signature).getFullYear() }}
                                    </div>
                                </div>

                                <!-- Description tooltip on hover -->
                                <div v-if="partner.description" class="w-full pt-3 border-t border-white/5 hidden group-hover:block">
                                    <p class="text-[9px] text-slate-500 line-clamp-2 text-center leading-relaxed">{{ partner.description }}</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Call to action -->
                <div class="mt-24 text-center glass-dark border border-white/5 rounded-[3rem] p-12 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none">
                        <div class="absolute top-[-30%] left-[-10%] w-[50%] h-[50%] bg-cyan-500 rounded-full blur-[150px]"></div>
                    </div>
                    <div class="relative z-10">
                        <h2 class="text-3xl md:text-5xl font-black text-white tracking-tighter mb-4">Devenir partenaire ?</h2>
                        <p class="text-slate-400 mb-8 max-w-xl mx-auto">Rejoignez notre écosystème et contribuez à transformer Kolda en un hub numérique de référence en Afrique de l'Ouest.</p>
                        <Link 
                            :href="route('contact.index')"
                            class="inline-flex items-center gap-3 px-10 py-5 bg-cyan-500 text-slate-950 rounded-2xl font-black text-sm hover:bg-cyan-400 hover:shadow-[0_0_40px_rgba(6,182,212,0.4)] transition-all"
                        >
                            Nous contacter
                            <ArrowTopRightOnSquareIcon class="h-5 w-5" />
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </GuestLayout>
</template>

<style scoped>
@keyframes pulse-slow {
    0%, 100% { opacity: 0.3; }
    50% { opacity: 0.6; }
}
.animate-pulse-slow { animation: pulse-slow 4s ease-in-out infinite; }

.glass-dark {
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.text-glow-cyan {
    text-shadow: 0 0 20px rgba(6, 182, 212, 0.5), 0 0 40px rgba(6, 182, 212, 0.2);
}

.bg-cyber-grid {
    background-size: 50px 50px;
    background-image: 
        linear-gradient(to right, rgba(6, 182, 212, 0.05) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(6, 182, 212, 0.05) 1px, transparent 1px);
}
</style>
