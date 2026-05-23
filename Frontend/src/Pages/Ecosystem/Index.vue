<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { 
    UsersIcon, 
    GlobeAltIcon, 
    BuildingOfficeIcon,
    CalendarIcon,
    MicrophoneIcon,
    ArrowRightIcon
} from '@heroicons/vue/24/outline'
import { formatTime, formatDate } from '@/utils/format'

const props = defineProps({
    partnerships: Array,
    events: Array,
    mediaMentions: Array
})
</script>

<template>
    <Head title="Écosystème & Rayonnement" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <header class="mb-12">
                <div class="flex items-center gap-2 mb-2">
                    <div class="h-1 w-8 bg-cyan-500 rounded-full"></div>
                    <span class="text-[10px] font-black text-cyan-500 uppercase tracking-widest">Écosystème & Impact</span>
                </div>
                <h1 class="text-5xl font-black text-slate-900 tracking-tighter mb-4 italic">
                    Nexus <span class="bg-gradient-to-r from-cyan-600 to-indigo-600 bg-clip-text text-transparent not-italic">Rayonnement</span>
                </h1>
                <p class="text-xl text-slate-500 font-medium tracking-tight">Vue d'ensemble de l'influence communautaire et des alliances stratégiques du CRE.</p>
            </header>

            <!-- Stats Grid (Glassmorphic) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
                <div class="relative group bg-slate-900 p-8 rounded-[3rem] text-white shadow-2xl shadow-slate-900/20 overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-cyan-600/20 to-transparent"></div>
                    <div class="relative z-10">
                        <div class="flex justify-between items-start mb-6">
                            <div class="h-14 w-14 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/10 group-hover:scale-110 transition-transform">
                                <BuildingOfficeIcon class="h-7 w-7 text-cyan-400" />
                            </div>
                        </div>
                        <div class="text-5xl font-black mb-2 tracking-tighter">{{ partnerships.length }}</div>
                        <div class="text-cyan-400 font-black uppercase tracking-[0.2em] text-[10px]">Partenaires Actifs</div>
                    </div>
                </div>

                <div class="relative group bg-white p-8 rounded-[3rem] border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-transparent via-cyan-500 to-transparent scale-x-0 group-hover:scale-x-100 transition-transform duration-700"></div>
                    <div class="flex justify-between items-start mb-6">
                        <div class="h-14 w-14 bg-slate-50 text-slate-900 rounded-2xl flex items-center justify-center group-hover:bg-slate-900 group-hover:text-white transition-colors">
                            <CalendarIcon class="h-7 w-7" />
                        </div>
                    </div>
                    <div class="text-5xl font-black mb-2 text-slate-900 tracking-tighter">{{ events.length }}</div>
                    <div class="text-slate-400 font-black uppercase tracking-[0.2em] text-[10px]">Événements réalisés</div>
                </div>

                <div class="relative group bg-white p-8 rounded-[3rem] border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-transparent via-indigo-500 to-transparent scale-x-0 group-hover:scale-x-100 transition-transform duration-700"></div>
                    <div class="flex justify-between items-start mb-6">
                        <div class="h-14 w-14 bg-slate-50 text-slate-900 rounded-2xl flex items-center justify-center group-hover:bg-slate-900 group-hover:text-white transition-colors">
                            <MicrophoneIcon class="h-7 w-7" />
                        </div>
                    </div>
                    <div class="text-5xl font-black mb-2 text-slate-900 tracking-tighter">{{ mediaMentions.length }}</div>
                    <div class="text-slate-400 font-black uppercase tracking-[0.2em] text-[10px]">Mentions Médias</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                <!-- Partnerships Section -->
                <section>
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-1 bg-cyan-500 rounded-full"></div>
                            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Derniers Partenariats</h2>
                        </div>
                        <Link :href="route('ecosystem.partnerships')" class="text-cyan-600 font-black text-xs uppercase tracking-widest flex items-center gap-2 hover:gap-3 transition-all">
                            Explorer <ArrowRightIcon class="h-4 w-4" />
                        </Link>
                    </div>
                    <div class="space-y-4">
                        <div v-for="partner in partnerships" :key="partner.id" class="group flex items-center gap-5 p-6 bg-white/60 backdrop-blur-md rounded-[2rem] border border-white shadow-sm hover:shadow-xl hover:shadow-cyan-500/5 transition-all">
                            <div class="h-14 w-14 bg-slate-900 rounded-2xl flex items-center justify-center text-white font-black text-sm group-hover:scale-110 transition-transform">
                                {{ partner.nom.substring(0,2).toUpperCase() }}
                            </div>
                            <div class="flex-1">
                                <h3 class="font-black text-slate-900 text-lg tracking-tight">{{ partner.nom }}</h3>
                                <div class="flex items-center justify-between">
                                    <p class="text-[10px] text-cyan-600 font-black uppercase tracking-[0.15em]">{{ partner.type }}</p>
                                    <p class="text-[10px] text-slate-400 font-bold font-mono">{{ formatDate(partner.date_signature) }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-if="partnerships.length === 0" class="p-16 border-2 border-dashed border-slate-100 rounded-[3rem] text-center flex flex-col items-center justify-center">
                            <BuildingOfficeIcon class="h-10 w-10 text-slate-200 mb-4" />
                            <p class="text-slate-400 font-black uppercase tracking-widest text-[10px]">Désertification partenariale</p>
                        </div>
                    </div>
                </section>

                <!-- Events Section -->
                <section>
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-1 bg-indigo-500 rounded-full"></div>
                            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Événements Récents</h2>
                        </div>
                        <Link :href="route('ecosystem.events')" class="text-indigo-600 font-black text-xs uppercase tracking-widest flex items-center gap-2 hover:gap-3 transition-all">
                            Tout voir <ArrowRightIcon class="h-4 w-4" />
                        </Link>
                    </div>
                    <div class="space-y-4">
                        <div v-for="event in events" :key="event.id" class="group p-6 bg-white/60 backdrop-blur-md rounded-[2rem] border border-white shadow-sm hover:translate-x-2 transition-all">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-black text-slate-900 text-lg tracking-tight leading-none group-hover:text-indigo-600 transition-colors">{{ event.titre }}</h3>
                                <div v-if="event.status === 'actif'" class="h-2 w-2 rounded-full bg-green-500 shadow-[0_0_8px_#22c55e]"></div>
                            </div>
                            <div class="flex items-center gap-3 text-[10px] text-slate-400 font-black uppercase tracking-[0.15em]">
                                <CalendarIcon class="h-4 w-4 text-indigo-500" />
                                {{ formatDate(event.date) }}
                                <span v-if="event.heure_debut" class="ml-1 opacity-60 font-mono">
                                    {{ formatTime(event.heure_debut) }}
                                </span>
                            </div>
                        </div>
                        <div v-if="events.length === 0" class="p-16 border-2 border-dashed border-slate-100 rounded-[3rem] text-center flex flex-col items-center justify-center">
                            <CalendarIcon class="h-10 w-10 text-slate-200 mb-4" />
                            <p class="text-slate-400 font-black uppercase tracking-widest text-[10px]">Aucune activité détectée</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
