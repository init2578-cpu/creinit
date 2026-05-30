<script setup>
import { Link } from '@inertiajs/vue3'
import { AcademicCapIcon } from '@heroicons/vue/24/outline'
import AIAssistant from '@/Components/AIAssistant.vue'
</script>

<template>
    <div class="min-h-screen bg-slate-950 flex flex-col font-sans selection:bg-cyan-500/30 selection:text-cyan-200">
        <header class="fixed top-0 left-0 right-0 z-[60] bg-slate-950/20 backdrop-blur-md border-b border-white/5 py-0 px-4 transition-all duration-500">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <Link :href="'/'" class="flex items-center gap-4 group">
                    <img src="/images/logo-cre.png" alt="CRE Kolda Logo" class="h-16 w-16 object-contain drop-shadow-[0_0_10px_rgba(34,211,238,0.3)] transition-transform group-hover:scale-110">
                    <div class="flex flex-col">
                        <span class="text-xl font-black text-white tracking-tighter leading-none">E-CRE</span>
                        <span class="text-[8px] font-black text-cyan-500 uppercase tracking-[0.4em]">KOLDA HUB</span>
                    </div>
                </Link>
                
                <div class="flex items-center gap-8">
                    <Link 
                        :href="route('contact.index')" 
                        class="hidden md:block text-xs font-black text-slate-400 hover:text-cyan-400 transition uppercase tracking-widest"
                    >
                        Contact
                    </Link>
                    <Link 
                        :href="route('applications.create')" 
                        class="hidden md:block text-xs font-black text-slate-400 hover:text-cyan-400 transition uppercase tracking-widest"
                    >
                        Candidater
                    </Link>
                    <Link 
                        v-if="!$page.props.auth.user"
                        :href="route('login')" 
                        class="px-5 py-2.5 bg-white text-slate-950 rounded-xl text-xs font-black shadow-2xl hover:bg-cyan-400 transition-colors"
                    >
                        Accès Nexus
                    </Link>
                    <Link 
                        v-else
                        :href="route('dashboard.director')" 
                        class="px-5 py-2.5 bg-slate-800 text-white rounded-xl text-xs font-black border border-slate-700 hover:bg-slate-700 transition"
                    >
                        Console Admn
                    </Link>
                </div>
            </div>
        </header>

        <main class="flex-1 pt-20">
            <slot />
        </main>

        <!-- Dynamic Footer 2.0 (Shared) -->
        <footer class="py-24 bg-slate-950 border-t border-white/5 relative z-30">
            <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-16">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-6 mb-0">
                        <img src="/images/logo-cre.png" alt="CRE Logo" class="h-20 w-auto object-contain">
                        <div class="flex flex-col">
                            <span class="text-xl font-black text-white tracking-tighter leading-none font-display">{{ $page.props.settings?.site_name || 'E-CRE' }}</span>
                            <span class="text-[9px] font-black text-cyan-500 uppercase tracking-[0.4em] mt-1">Sénégal 2.0</span>
                        </div>
                    </div>
                    <p class="text-slate-500 font-medium max-w-sm leading-relaxed mb-10">
                        Le pivot numérique de la région de Kolda. Nous formons, nous innovons, nous impactons par le code et l'IA.
                    </p>
                    <div class="flex items-center gap-3">
                        <div v-for="i in 3" :key="i" class="h-10 w-10 rounded-xl glass-dark flex items-center justify-center text-slate-600 hover:text-cyan-400 hover:border-cyan-500/30 transition-all cursor-pointer">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                        </div>
                    </div>
                </div>

                <div class="space-y-8">
                    <h4 class="text-[10px] font-black text-white uppercase tracking-[0.3em]">Navigation Core</h4>
                    <ul class="space-y-3">
                        <li><Link :href="'/'" class="text-sm font-bold text-slate-500 hover:text-cyan-400 transition-colors">Vision</Link></li>
                        <li><Link :href="'/'" class="text-sm font-bold text-slate-500 hover:text-cyan-400 transition-colors">Curriculum</Link></li>
                        <li><Link :href="route('contact.index')" class="text-sm font-bold text-slate-500 hover:text-cyan-400 transition-colors">Plateforme</Link></li>
                        <li><Link :href="route('contact.index')" class="text-sm font-bold text-slate-500 hover:text-cyan-400 transition-colors">Contact</Link></li>
                    </ul>
                </div>

                <div class="space-y-8">
                    <h4 class="text-[10px] font-black text-white uppercase tracking-[0.3em]">IA Nexus</h4>
                    <div class="space-y-4">
                        <div class="flex items-center gap-3 text-xs text-slate-500 font-bold">
                            <div class="h-1.5 w-1.5 rounded-full bg-cyan-500 animate-pulse"></div>
                            Serveurs Actifs
                        </div>
                        <div class="flex items-center gap-3 text-xs text-slate-500 font-bold">
                            <div class="h-1.5 w-1.5 rounded-full bg-green-500"></div>
                            99.9% Uptime
                        </div>
                        <div class="p-5 glass-dark rounded-2xl border border-white/5">
                             <p class="text-[9px] uppercase text-slate-600 mb-1">Dernière Mise à jour</p>
                             <p class="text-[10px] text-slate-400 font-bold">v3.4.0 — IA Core v2</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="max-w-7xl mx-auto px-4 mt-12 pt-8 border-t border-white/5 text-center">
                <p class="text-[9px] font-black text-slate-600 uppercase tracking-[0.5em]">
                    © 2026 E-CRE Kolda — Neural Learning Network
                </p>
            </div>
        </footer>

        <!-- Neural AI Assistant (Nexus Chat) -->
        <AIAssistant />
    </div>
</template>

<style>
.glass-dark {
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
</style>
