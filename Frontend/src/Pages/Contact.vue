<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { 
    EnvelopeIcon, 
    MapPinIcon, 
    PhoneIcon,
    SparklesIcon
} from '@heroicons/vue/24/outline'
import ContactForm from '@/Components/ContactForm.vue'

const props = defineProps({
    settings: Object,
})


</script>

<template>
    <Head title="Contact Nexus — E-CRE Kolda" />

    <GuestLayout>
        <div class="relative min-h-screen pt-32 pb-20 overflow-hidden bg-slate-950">
            <!-- Background Decorations -->
            <div class="absolute inset-0 z-0 pointer-events-none">
                <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-cyan-500/10 rounded-full blur-[120px] animate-pulse-slow"></div>
                <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-indigo-500/10 rounded-full blur-[100px] animate-pulse-slow" style="animation-delay: 2s"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_2px_2px,rgba(6,182,212,0.05)_1px,transparent_0)] bg-[size:40px_40px]"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-16 items-start">
                    
                    <!-- Left Side: Contact Info -->
                    <div class="lg:col-span-5">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-cyan-500/10 border border-cyan-500/20 mb-8">
                            <SparklesIcon class="h-4 w-4 text-cyan-400" />
                            <span class="text-[10px] font-black text-cyan-400 uppercase tracking-[0.3em]">Direct Link — Nexus</span>
                        </div>

                        <h1 class="text-5xl md:text-7xl font-black text-white tracking-tighter leading-none mb-8 font-display">
                            ENTREZ DANS LE <br> <span class="text-cyan-500 text-glow-cyan">RÉSEAU</span>.
                        </h1>

                        <p class="text-slate-400 text-lg font-medium leading-relaxed mb-12 max-w-md">
                            Une question ? Une collaboration ? Nos capteurs sont à l'écoute. Rejoignez l'élite numérique du Fouladou.
                        </p>

                        <div class="space-y-6">
                            <div v-for="(item, i) in [
                                { label: 'Localisation', val: 'Doumassou en face des arènes, Kolda', link: 'https://www.google.com/maps?q=12.895385642821164,-14.941531705909265', icon: MapPinIcon },
                                { label: 'Signal Email', val: settings?.contact_email || 'crekolda2014@gmail.com', icon: EnvelopeIcon },
                                { label: 'Fréquence Mobile', val: '+221 33 996 00 00', icon: PhoneIcon }
                            ]" :key="i" class="flex items-center gap-6 p-6 glass-dark rounded-3xl border border-white/5 group hover:border-cyan-500/30 transition-all">
                                <div class="h-12 w-12 rounded-2xl bg-slate-900 border border-white/5 flex items-center justify-center text-cyan-400 group-hover:bg-cyan-500 group-hover:text-slate-950 transition-all">
                                    <component :is="item.icon" class="h-6 w-6" />
                                </div>
                                <div>
                                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">{{ item.label }}</p>
                                    <a v-if="item.link" :href="item.link" target="_blank" rel="noopener noreferrer" class="text-white font-bold hover:text-cyan-400 transition-colors">{{ item.val }}</a>
                                    <p v-else class="text-white font-bold">{{ item.val }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Contact Form -->
                    <div class="lg:col-span-7">
                        <ContactForm />
                    </div>
                </div>
            </div>
            
            <!-- Floating Elements -->
            <div class="absolute left-10 top-1/2 -translate-y-1/2 hidden xl:block opacity-20 pointer-events-none">
                <div class="h-64 w-[1px] bg-gradient-to-b from-transparent via-cyan-500 to-transparent"></div>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.text-glow-cyan {
    text-shadow: 0 0 20px rgba(6, 182, 212, 0.4), 0 0 40px rgba(6, 182, 212, 0.2);
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
.animate-pulse-slow { animation: pulse-slow 8s ease-in-out infinite; }
</style>
