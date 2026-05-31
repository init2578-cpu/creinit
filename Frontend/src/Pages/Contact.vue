<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { 
    EnvelopeIcon, 
    MapPinIcon, 
    PhoneIcon,
    SparklesIcon,
    ArrowRightIcon,
    PaperAirplaneIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    settings: Object,
})

const form = useForm({
    name: '',
    email: '',
    subject: '',
    message: '',
})

const submit = () => {
    form.post(route('contact.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    })
}
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
                                { label: 'Localisation', val: 'Doumassou en face des arènes, Kolda', link: 'https://www.google.com/maps/search/V3W5%2B49+Kolda', icon: MapPinIcon },
                                { label: 'Signal Email', val: settings?.contact_email || 'contact@cre-kolda.sn', icon: EnvelopeIcon },
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
                        <div class="glass-dark border border-white/10 rounded-[2.5rem] p-8 md:p-12 shadow-2xl relative overflow-hidden">
                            <!-- Form Backdrop Decoration -->
                            <div class="absolute -right-20 -top-20 w-40 h-40 bg-cyan-500/10 rounded-full blur-3xl"></div>

                            <form @submit.prevent="submit" class="space-y-8 relative z-10">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Identité / Nom</label>
                                        <input 
                                            v-model="form.name"
                                            type="text" 
                                            placeholder="Ex: Amadou Diallo"
                                            class="w-full bg-slate-900/50 border-white/5 rounded-2xl p-4 text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 transition-all"
                                        >
                                        <div v-if="form.errors.name" class="text-red-500 text-[10px] mt-1 ml-4 uppercase font-bold">{{ form.errors.name }}</div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Adresse de Retour (Email)</label>
                                        <input 
                                            v-model="form.email"
                                            type="email" 
                                            placeholder="amadou@nexus.sn"
                                            class="w-full bg-slate-900/50 border-white/5 rounded-2xl p-4 text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 transition-all"
                                        >
                                        <div v-if="form.errors.email" class="text-red-500 text-[10px] mt-1 ml-4 uppercase font-bold">{{ form.errors.email }}</div>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Sujet de la Transmission</label>
                                    <input 
                                        v-model="form.subject"
                                        type="text" 
                                        placeholder="Ex: Collaboration IA / Demande d'information"
                                        class="w-full bg-slate-900/50 border-white/5 rounded-2xl p-4 text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 transition-all"
                                    >
                                    <div v-if="form.errors.subject" class="text-red-500 text-[10px] mt-1 ml-4 uppercase font-bold">{{ form.errors.subject }}</div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Flux de Message</label>
                                    <textarea 
                                        v-model="form.message"
                                        rows="6"
                                        placeholder="Décrivez votre projet ou votre question..."
                                        class="w-full bg-slate-900/50 border-white/5 rounded-2xl p-4 text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 transition-all resize-none"
                                    ></textarea>
                                    <div v-if="form.errors.message" class="text-red-500 text-[10px] mt-1 ml-4 uppercase font-bold">{{ form.errors.message }}</div>
                                </div>

                                <button 
                                    type="submit" 
                                    :disabled="form.processing"
                                    class="w-full py-5 bg-cyan-500 text-slate-950 rounded-2xl font-black text-lg transition-all hover:bg-cyan-400 hover:shadow-[0_0_40px_rgba(34,211,238,0.4)] active:scale-95 flex items-center justify-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed group"
                                >
                                    <span>{{ form.processing ? 'SYNCHRONISATION...' : 'TRANSMETTRE LE MESSAGE' }}</span>
                                    <PaperAirplaneIcon v-if="!form.processing" class="h-6 w-6 group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform" />
                                </button>
                                
                                <div v-if="$page.props.flash.success" class="p-4 bg-cyan-500/10 border border-cyan-500/30 rounded-xl text-cyan-400 text-sm font-bold text-center animate-pulse">
                                    {{ $page.props.flash.success }}
                                </div>
                            </form>
                        </div>
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
