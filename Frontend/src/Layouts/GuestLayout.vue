<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AIAssistant from '@/Components/AIAssistant.vue'
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline'

const mobileMenuOpen = ref(false)
const toggleMenu = () => mobileMenuOpen.value = !mobileMenuOpen.value
const closeMenu = () => mobileMenuOpen.value = false

const navLinks = [
    { label: 'Vision', route: 'vision' },
    { label: 'Curriculum', route: 'curriculum' },
    { label: 'Plateforme', route: 'plateforme' },
    { label: 'Contact', route: 'contact.index' },
    { label: 'Candidater', route: 'applications.create' },
]
</script>

<template>
    <div class="min-h-screen bg-slate-950 flex flex-col font-sans selection:bg-cyan-500/30 selection:text-cyan-200">
        <!-- Header -->
        <header class="fixed top-0 left-0 right-0 z-[60] bg-slate-950/80 backdrop-blur-md border-b border-white/5 transition-all duration-500">
            <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
                <!-- Logo -->
                <Link :href="'/'" class="flex items-center gap-3 group shrink-0">
                    <img src="/images/logo-cre.png" alt="CRE Kolda Logo" class="h-12 w-12 object-contain drop-shadow-[0_0_10px_rgba(34,211,238,0.3)] transition-transform group-hover:scale-110">
                    <div class="flex flex-col">
                        <span class="text-lg font-black text-white tracking-tighter leading-none">E-CRE</span>
                        <span class="text-[7px] font-black text-cyan-500 uppercase tracking-[0.4em]">KOLDA HUB</span>
                    </div>
                </Link>
                
                <!-- Desktop Nav -->
                <nav class="hidden md:flex items-center gap-5">
                    <Link v-for="link in navLinks" :key="link.route"
                        :href="route(link.route)" 
                        class="text-[10px] font-black text-slate-400 hover:text-cyan-400 transition uppercase tracking-widest"
                    >
                        {{ link.label }}
                    </Link>
                </nav>

                <!-- Right side -->
                <div class="flex items-center gap-3">
                    <Link 
                        v-if="!$page.props.auth.user"
                        :href="route('login')" 
                        class="px-4 py-2 bg-white text-slate-950 rounded-xl text-xs font-black shadow-2xl hover:bg-cyan-400 transition-colors"
                    >
                        Accès Nexus
                    </Link>
                    <Link 
                        v-else
                        :href="route('dashboard.director')" 
                        class="px-4 py-2 bg-slate-800 text-white rounded-xl text-xs font-black border border-slate-700 hover:bg-slate-700 transition"
                    >
                        Console
                    </Link>

                    <!-- Hamburger (Mobile only) -->
                    <button @click="toggleMenu" class="md:hidden p-2 rounded-xl text-slate-400 hover:text-cyan-400 hover:bg-slate-800 transition">
                        <XMarkIcon v-if="mobileMenuOpen" class="h-6 w-6" />
                        <Bars3Icon v-else class="h-6 w-6" />
                    </button>
                </div>
            </div>

            <!-- Mobile Slide-Down Menu -->
            <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
                <div v-if="mobileMenuOpen" class="md:hidden bg-slate-950/95 backdrop-blur-xl border-t border-white/5 px-4 py-6">
                    <nav class="flex flex-col gap-1">
                        <Link 
                            v-for="link in navLinks" :key="link.route"
                            :href="route(link.route)"
                            @click="closeMenu"
                            class="px-4 py-3 rounded-xl text-sm font-black text-slate-300 hover:text-cyan-400 hover:bg-slate-800 transition uppercase tracking-widest"
                        >
                            {{ link.label }}
                        </Link>
                    </nav>
                </div>
            </Transition>
        </header>

        <main class="flex-1 pt-16">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="py-16 bg-slate-950 border-t border-white/5 relative z-30">
            <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-3 gap-10 mb-10">
                <!-- Brand -->
                <div class="md:col-span-1">
                    <div class="flex items-center gap-4 mb-4">
                        <img src="/images/logo-cre.png" alt="CRE Logo" class="h-14 w-auto object-contain">
                        <div>
                            <p class="text-base font-black text-white tracking-tighter">{{ $page.props.settings?.site_name || 'E-CRE' }}</p>
                            <p class="text-[8px] font-black text-cyan-500 uppercase tracking-[0.3em]">Sénégal 2.0</p>
                        </div>
                    </div>
                    <p class="text-slate-500 text-sm leading-relaxed mb-4">
                        Interface entre la recherche et la population du Fouladou.
                    </p>
                    <a 
                        href="https://www.google.com/maps?q=12.895385642821164,-14.941531705909265" 
                        target="_blank" 
                        rel="noopener noreferrer"
                        class="inline-block text-xs text-cyan-400 hover:text-cyan-300 transition font-bold"
                    >
                        📍 Doumassou, Kolda (V3W5+49)
                    </a>
                </div>

                <!-- Navigation Links -->
                <div>
                    <h4 class="text-[10px] font-black text-white uppercase tracking-[0.3em] mb-4">Navigation</h4>
                    <ul class="space-y-3">
                        <li v-for="link in navLinks" :key="link.route">
                            <Link :href="route(link.route)" class="text-sm font-bold text-slate-500 hover:text-cyan-400 transition-colors">
                                {{ link.label }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Status -->
                <div>
                    <h4 class="text-[10px] font-black text-white uppercase tracking-[0.3em] mb-4">IA Nexus</h4>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 text-xs text-slate-500 font-bold">
                            <div class="h-1.5 w-1.5 rounded-full bg-cyan-500 animate-pulse"></div>
                            Serveurs Actifs
                        </div>
                        <div class="flex items-center gap-3 text-xs text-slate-500 font-bold">
                            <div class="h-1.5 w-1.5 rounded-full bg-green-500"></div>
                            99.9% Uptime
                        </div>
                        <div class="p-4 glass-dark rounded-2xl border border-white/5">
                            <p class="text-[9px] uppercase text-slate-600 mb-1">Email</p>
                            <a href="mailto:crekolda2014@gmail.com" class="text-[10px] text-cyan-400 font-bold hover:underline">crekolda2014@gmail.com</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="max-w-7xl mx-auto px-4 pt-6 border-t border-white/5 text-center">
                <p class="text-[9px] font-black text-slate-600 uppercase tracking-[0.5em]">
                    © 2026 E-CRE Kolda — Neural Learning Network — MESRI
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
