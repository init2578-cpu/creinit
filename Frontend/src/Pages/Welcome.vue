<script setup>
import { onMounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import AssaneChat from '@/Components/AssaneChat.vue'
import { 
    AcademicCapIcon, 
    ArrowRightIcon, 
    ChartBarIcon, 
    UsersIcon,
    MapPinIcon,
    PhoneIcon,
    EnvelopeIcon,
    CheckBadgeIcon,
    LightBulbIcon,
    RocketLaunchIcon,
    SparklesIcon,
    CpuChipIcon,
    CommandLineIcon,
    BeakerIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    canLogin: Boolean,
    settings: Object,
    stats: Object,
    events: Array,
})

const pillars = [
    {
        title: 'IA Générative',
        description: 'Nous intégrons les derniers modèles de langage pour accélérer l\'apprentissage du code et de la conception.',
        icon: CpuChipIcon,
        color: 'cyan'
    },
    {
        title: 'Apprentissage Adaptatif',
        description: 'Des parcours personnalisés boostés par l\'IA pour s\'adapter au rythme de chaque apprenant du Fouladou.',
        icon: CommandLineIcon,
        color: 'blue'
    },
    {
        title: 'Labs d\'Innovation',
        description: 'Expérimentez avec le Machine Learning et la Robotique dans nos nouveaux espaces dédiés.',
        icon: BeakerIcon,
        color: 'indigo'
    }
]

const modules = [
    { name: 'Développement Web & IA', level: 'Avancé', duration: '6 mois', students: 45 },
    { name: 'Data Analytics', level: 'Intermédiaire', duration: '4 mois', students: 32 },
    { name: 'Design Systèmes', level: 'Intermédiaire', duration: '3 mois', students: 28 },
    { name: 'Entrepreneuriat Digital', level: 'Tout public', duration: '2 mois', students: 50 },
]

// Scroll Reveal & Typing Logic
const revealed = ref(new Set())
const typeText = ref('')
const fullTitle = 'Penser Demain. Innover Maintenant.'
let typeIdx = 0

// Modal Logic
const selectedEvent = ref(null)
const isModalOpen = ref(false)

const openEvent = (event) => {
    selectedEvent.value = event
    isModalOpen.value = true
    document.body.style.overflow = 'hidden'
}

const closeEvent = () => {
    isModalOpen.value = false
    selectedEvent.value = null
    document.body.style.overflow = 'auto'
}

function resolveImagePath(path) {
    if (!path) return '/images/default-event.png'
    if (path.startsWith('/') || path.startsWith('http')) return path
    return '/storage/' + path
}

onMounted(() => {
    // Intersection Observer
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed')
                revealed.value.add(entry.target.id)
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('[data-reveal]').forEach((el) => observer.observe(el));

    // Typing Effect
    const typeInterval = setInterval(() => {
        if (typeIdx < fullTitle.length) {
            typeText.value += fullTitle[typeIdx]
            typeIdx++
        } else {
            clearInterval(typeInterval)
        }
    }, 50);
})
</script>

<template>
    <Head :title="settings?.site_name || 'E-CRE Kolda - Le Futur Numérique'" />

    <GuestLayout>
        <!-- Futuristic Hero Section -->
        <section class="relative min-h-screen flex items-center overflow-hidden bg-slate-950">
            <!-- Digital Overlays -->
            <div class="absolute inset-0 z-[5] pointer-events-none overflow-hidden opacity-30">
                <div class="scanline"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(6,182,212,0.1),transparent_70%)]"></div>
            </div>

            <!-- AI Grid & Background -->
            <div class="absolute inset-0 z-0 overflow-hidden">
                <img src="/images/hero-premium.png" alt="Futuristic background" class="w-full h-full object-cover opacity-90 animate-hero-enhanced">
                <div class="absolute inset-0 bg-gradient-to-b from-slate-950/20 via-transparent to-slate-950"></div>
                <div class="absolute inset-0 bg-cyan-500/5 mix-blend-overlay animate-pulse-slow"></div>
                <!-- Glitch/Flash Overlays -->
                <div class="absolute inset-0 opacity-0 bg-cyan-400/10 mix-blend-screen animate-glitch-flash pointer-events-none"></div>
                <div class="absolute inset-0 opacity-0 bg-white/5 mix-blend-overlay animate-glitch-flash-fast pointer-events-none"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 py-20 lg:py-32 w-full">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-8">
                        <div 
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-cyan-500/10 border border-cyan-500/20 mb-8 animate-pulse-slow"
                            data-reveal id="hero-badge"
                        >
                            <SparklesIcon class="h-4 w-4 text-cyan-400" />
                            <span class="text-[10px] font-black text-cyan-400 uppercase tracking-[0.3em]">IA-Ready Ecosystem — Kolda 2.0</span>
                        </div>

                        <h1 
                            class="text-5xl md:text-8xl font-black text-white tracking-tighter leading-[1.1] mb-8 font-display"
                            id="hero-title"
                        >
                            <span class="text-glow-cyan">{{ typeText.split('.')[0] }}</span><br>
                            <span class="opacity-80 text-shimmer text-3xl md:text-5xl">{{ typeText.split('.')[1] }}</span>
                            <span class="inline-block w-2 h-10 md:h-14 bg-cyan-500 ml-2 animate-blink"></span>
                        </h1>

                        <p 
                            class="text-lg md:text-xl text-slate-300 font-medium leading-relaxed mb-12 max-w-2xl border-l-4 border-cyan-500/50 pl-8 glass-dark py-6 rounded-r-2xl opacity-0 translate-x-[-20px] transition-all duration-1000"
                            id="hero-desc" data-reveal
                        >
                            {{ settings?.site_description || "Le CRE de Kolda fusionne l'expertise humaine et l'intelligence artificielle pour forger les leaders de la nouvelle économie numérique." }}
                        </p>
                        
                        <div 
                            class="flex flex-col sm:flex-row items-center gap-6 opacity-0 transition-opacity duration-1000"
                            id="hero-buttons" data-reveal
                        >
                            <Link 
                                :href="route('applications.create')" 
                                class="group relative w-full sm:w-auto px-10 py-5 bg-cyan-500 text-slate-950 rounded-xl font-black text-lg transition-all hover:bg-cyan-400 hover:shadow-[0_0_40px_rgba(6,182,212,0.5)] active:scale-95 flex items-center justify-center gap-3 overflow-hidden"
                            >
                                Déposer ma Candidature
                                <ArrowRightIcon class="h-6 w-6 group-hover:translate-x-1 transition-transform" />
                            </Link>
                            
                            <a 
                                href="#ai-core" 
                                class="w-full sm:w-auto px-10 py-5 glass-dark text-white rounded-xl font-bold text-center border border-white/10 hover:border-cyan-500/50 transition-all flex items-center justify-center gap-3 group"
                            >
                                Explorez l'IA
                                <div class="h-px w-6 bg-cyan-500/50 group-hover:w-10 transition-all"></div>
                            </a>
                        </div>
                    </div>

                    <!-- Circular Stats (Futuristic Style) -->
                    <div class="lg:col-span-4 flex flex-col gap-8 justify-center items-end">
                        <div v-for="(val, key) in stats" :key="key" 
                             class="glass-dark p-6 rounded-2xl border border-white/5 w-full max-w-[280px] group hover:border-cyan-500/30 transition-all hover:translate-x-[-10px]"
                             data-reveal :id="'stat-' + key">
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest group-hover:text-cyan-400 transition-colors">
                                {{ key === 'students' ? 'Hub Apprenants' : key === 'modules' ? 'Nexus Formations' : key === 'trainers' ? 'Core Experts' : 'Taux Success' }}
                            </p>
                            <div class="flex items-baseline gap-2">
                                <p class="text-4xl md:text-5xl font-black text-white tracking-tighter">{{ key === 'success_rate' ? val + '%' : val }}</p>
                                <div class="h-2 w-2 rounded-full bg-cyan-500 animate-pulse"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bottom Fade -->
            <div class="absolute bottom-0 left-0 w-full h-32 bg-gradient-to-t from-slate-950 to-transparent z-10"></div>
        </section>

        <!-- Mission & Innovation Section -->
        <section class="py-24 bg-slate-950 relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                    <div v-for="(item, i) in [
                        { title: 'Vision Radicale', desc: 'Transcender les limites de l\'éducation traditionnelle par l\'immersion technologique.', icon: LightBulbIcon },
                        { title: 'Impact Local', desc: 'Propulser la région de Kolda au sommet de la scène numérique sénégalaise.', icon: MapPinIcon },
                        { title: 'Excellence IA', desc: 'Maîtriser les outils de demain pour résoudre les problèmes d\'aujourd\'hui.', icon: CpuChipIcon }
                    ]" :key="i" class="glass-dark p-10 rounded-[2.5rem] border border-white/5 hover:border-cyan-500/20 transition-all group" data-reveal :id="'mission-' + i">
                        <div class="h-14 w-14 rounded-2xl bg-cyan-500/10 flex items-center justify-center mb-8 group-hover:bg-cyan-500 group-hover:text-slate-950 transition-all">
                            <component :is="item.icon" class="h-8 w-8" />
                        </div>
                        <h3 class="text-2xl font-black text-white mb-4 uppercase tracking-tighter">{{ item.title }}</h3>
                        <p class="text-slate-400 leading-relaxed">{{ item.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- AI Core Section -->
        <section id="ai-core" class="pt-24 pb-8 bg-slate-950 relative overflow-hidden">
            <!-- Digital Rain / Grid Pattern -->
            <div class="absolute inset-0 opacity-5 pointer-events-none">
                <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, rgba(6,182,212,0.15) 1px, transparent 0); background-size: 40px 40px;"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 relative z-10">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-24 items-center mb-32">
                    <div data-reveal id="ai-vision" class="relative">
                        <div class="absolute -left-10 -top-10 h-20 w-20 border-l-2 border-t-2 border-cyan-500/20"></div>
                        <h2 class="text-4xl md:text-7xl font-black text-white tracking-tighter leading-tight mb-8 font-display">
                            COGNITION <br> <span class="text-cyan-500 text-glow-cyan italic">AUGMENTÉE</span>
                        </h2>
                        <p class="text-slate-400 text-lg font-medium leading-relaxed mb-10 max-w-xl">
                            Nous ne nous contentons pas d'enseigner la technologie ; nous utilisons l'intelligence artificielle pour décupler les capacités de nos apprenants. De la génération de code au tutorat intelligent, le CRE est le laboratoire du futur à Kolda.
                        </p>
                        <div class="grid grid-cols-2 gap-6">
                            <div class="p-8 glass-dark rounded-3xl relative overflow-hidden group border border-white/5">
                                <div class="absolute inset-0 bg-cyan-500/5 translate-y-[100%] group-hover:translate-y-0 transition-transform duration-500"></div>
                                <p class="text-cyan-400 text-4xl font-black mb-1">90%</p>
                                <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest">PRODUCTIVITÉ CODE</p>
                            </div>
                            <div class="p-8 glass-dark rounded-3xl relative overflow-hidden group border border-white/5">
                                <div class="absolute inset-0 bg-indigo-500/5 translate-y-[100%] group-hover:translate-y-0 transition-transform duration-500"></div>
                                <p class="text-indigo-400 text-4xl font-black mb-1">24/7</p>
                                <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest">ASSISTANCE IA</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <div 
                            v-for="pillar in pillars" 
                            :key="pillar.title"
                            class="p-8 rounded-3xl glass-dark border border-white/5 hover:border-cyan-500/30 transition-all duration-500 group relative overflow-hidden"
                            data-reveal :id="'pillar-' + pillar.color"
                        >
                            <div class="flex items-center gap-6">
                                <div class="h-16 w-16 rounded-2xl flex items-center justify-center transition-all bg-slate-950 border border-white/5 group-hover:shadow-[0_0_20px_rgba(6,182,212,0.2)]" :class="`text-${pillar.color}-400`">
                                    <component :is="pillar.icon" class="h-8 w-8 animate-float" />
                                </div>
                                <div>
                                    <h3 class="text-xl font-black text-white mb-1 group-hover:text-cyan-400 transition-colors uppercase tracking-tight">{{ pillar.title }}</h3>
                                    <p class="text-slate-500 text-sm leading-relaxed">
                                        {{ pillar.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Subtle Divider -->
        <div class="max-w-7xl mx-auto px-4">
            <div class="h-px w-full bg-gradient-to-r from-transparent via-cyan-500/20 to-transparent"></div>
        </div>

        <!-- Latest Activities Section -->
        <section v-if="events && events.length > 0" class="pt-8 pb-16 bg-slate-950 relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4">
                <div class="mb-16 text-center" data-reveal id="events-header">
                    <h2 class="text-4xl md:text-6xl font-black text-white tracking-tighter mb-4 font-display uppercase">Flux d'activités <span class="text-cyan-500 text-glow-cyan">CRE</span></h2>
                    <p class="text-slate-500 font-medium tracking-wide">Découvrez les dernières innovations et événements communautaires au cœur de Kolda.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div 
                        v-for="(event, index) in events" 
                        :key="event.id"
                        class="group relative h-[450px] rounded-[2.5rem] overflow-hidden border border-white/5 hover:border-cyan-500/50 transition-all duration-700 shadow-2xl cursor-pointer"
                        data-reveal :id="'event-' + event.id"
                        :style="`transition-delay: ${index * 150}ms`"
                        @click="openEvent(event)"
                    >
                        <!-- Background Image with Overlay -->
                        <div class="absolute inset-0">
                            <img :src="resolveImagePath(event.image_path)" 
                                 class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110" 
                                 :alt="event.titre">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></div>
                        </div>

                        <!-- Content -->
                        <div class="relative h-full flex flex-col justify-end p-8 z-10">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="px-3 py-1 bg-cyan-500/20 text-cyan-400 text-[10px] font-black rounded-lg border border-cyan-500/30 uppercase tracking-widest backdrop-blur-md">
                                    {{ event.type_activite }}
                                </span>
                                <span class="text-slate-400 text-[10px] font-bold uppercase tracking-widest">
                                    {{ new Date(event.date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                </span>
                            </div>

                            <h3 class="text-2xl font-black text-white mb-4 leading-tight group-hover:text-cyan-400 transition-colors uppercase tracking-tighter">
                                {{ event.titre }}
                            </h3>
                            
                            <p class="text-slate-300 text-sm line-clamp-3 mb-6 opacity-80 group-hover:opacity-100 transition-opacity">
                                {{ event.description }}
                            </p>

                            <div class="flex items-center justify-between pt-6 border-t border-white/10">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                    <MapPinIcon class="h-3 w-3 text-cyan-500" />
                                    {{ event.lieu }}
                                </span>
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center gap-1.5 px-3 py-1 bg-white/5 rounded-full border border-white/10">
                                        <UserGroupIcon class="h-3 w-3 text-cyan-400" />
                                        <span class="text-[10px] font-black text-white leading-none">{{ event.audience_estimee }}</span>
                                    </div>
                                    <div class="h-8 w-8 rounded-full bg-white/5 border border-white/10 flex items-center justify-center group-hover:bg-cyan-500 group-hover:text-slate-950 transition-all">
                                        <ArrowRightIcon class="h-4 w-4" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Glow effect -->
                        <div class="absolute -inset-2 bg-cyan-500/20 blur-2xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                    </div>
                </div>
            </div>
        </section>
        <section class="py-24 bg-slate-950 relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex flex-col md:flex-row justify-between items-end gap-10 mb-24 relative">
                    <div class="absolute -left-12 top-0 h-full w-[2px] bg-gradient-to-b from-cyan-500 to-transparent"></div>
                    <div class="max-w-xl" data-reveal id="curr-header">
                        <h2 class="text-4xl md:text-6xl font-black text-white tracking-tighter mb-4 font-display">NEXUS <span class="bg-gradient-to-r from-cyan-400 to-indigo-400 bg-clip-text text-transparent">LEARNING</span></h2>
                        <p class="text-slate-500 font-medium tracking-wide">Interface de navigation des modules de formation avancés.</p>
                    </div>
                    <Link :href="route('applications.create')" class="group text-[10px] font-black text-cyan-400 uppercase tracking-[0.3em] hover:text-white transition-colors flex items-center gap-4">
                        ACCÉDER AU CATALOGUE COMPLET
                        <div class="h-10 w-10 rounded-full border border-cyan-500/30 flex items-center justify-center group-hover:border-cyan-500 group-hover:bg-cyan-500 group-hover:text-slate-950 transition-all">
                            <ArrowRightIcon class="h-4 w-4" />
                        </div>
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div 
                        v-for="(mod, index) in modules" 
                        :key="mod.name"
                        class="p-8 rounded-[2.5rem] glass-dark border border-white/5 hover:border-cyan-500/30 transition-all duration-700 group relative overflow-hidden flex flex-col h-full min-h-[400px]"
                        data-reveal :id="'mod-' + mod.name"
                        :style="`transition-delay: ${index * 100}ms`"
                    >
                        <!-- Digital Pulse Circle -->
                        <div class="absolute -right-10 -top-10 h-32 w-32 bg-cyan-500/5 rounded-full blur-3xl group-hover:bg-cyan-500/10 transition-all duration-700"></div>
                        
                        <div>
                            <div class="flex justify-between items-start mb-10">
                                <span class="px-3 py-1 bg-slate-800/50 text-slate-400 text-[8px] font-black rounded-lg border border-white/5 group-hover:bg-cyan-500 group-hover:text-slate-950 transition-all uppercase tracking-[0.2em]">
                                    LEVEL: {{ mod.level }}
                                </span>
                                <div class="h-2 w-2 rounded-full bg-cyan-500 shadow-[0_0_10px_cyan] animate-pulse"></div>
                            </div>
                            
                            <h3 class="text-2xl font-black text-white mb-4 tracking-tight leading-tight group-hover:translate-x-1 transition-transform">{{ mod.name }}</h3>
                            <p class="text-slate-500 text-xs font-medium leading-relaxed opacity-60 group-hover:opacity-100 transition-opacity">
                                Architecture orientée vers le futur, combinant ingénierie logicielle et intelligence machine.
                            </p>
                        </div>
                        
                        <div class="mt-auto pt-8 border-t border-white/5 flex items-center justify-between text-[10px] font-black text-slate-500 uppercase tracking-widest">
                            <span class="flex items-center gap-2">
                                <ChartBarIcon class="h-3 w-3 text-cyan-500" />
                                {{ mod.duration }}
                            </span>
                            <span class="text-white bg-slate-800 px-3 py-1 rounded-lg">
                                {{ mod.students }} SLOTS
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Feature Section (Futuristic Command) -->
        <section class="max-w-7xl mx-auto px-4 py-24 relative z-20">
            <div class="glass-dark border border-cyan-500/20 rounded-[4rem] p-12 lg:p-24 relative overflow-hidden flex flex-col items-center text-center shadow-2xl">
                <!-- Abstract decorations -->
                <div class="absolute top-0 left-0 w-full h-full opacity-20 pointer-events-none">
                    <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-cyan-500 rounded-full blur-[150px] animate-pulse-slow"></div>
                    <div class="absolute bottom-[-20%] right-[-10%] w-[60%] h-[60%] bg-indigo-600 rounded-full blur-[200px] animate-pulse-slow" style="animation-delay: 2s"></div>
                </div>

                <div class="relative z-10 max-w-3xl">
                    <div class="mb-8 inline-block px-4 py-2 bg-cyan-500/10 border border-cyan-500/30 rounded-xl text-[10px] text-cyan-400 font-black tracking-[0.5em]">PRIORITY PROTOCOL: ACTIVE</div>
                    <h2 class="text-5xl md:text-8xl font-black text-white tracking-tighter leading-none mb-10 font-display">
                        FORCEZ LE <span class="text-cyan-500 skew-x-[-12deg] inline-block">DESTIN</span>.
                    </h2>
                    <p class="text-slate-400 text-lg md:text-xl font-medium mb-16 max-w-2xl mx-auto border-l-4 border-cyan-500 pl-8 text-left glass-dark py-6 rounded-r-2xl">
                        Le futur n'attend pas. Les cohortes se saturent. Assurez votre place dans l'élite numérique du Sénégal.
                    </p>
                    <Link 
                        :href="route('applications.create')"
                        class="group relative inline-flex items-center gap-4 px-12 py-6 bg-white text-slate-950 rounded-2xl font-black text-xl transition-all hover:bg-cyan-500 hover:shadow-[0_0_60px_rgba(34,211,238,0.5)] active:scale-95 shadow-2xl"
                    >
                        INITIALISER CANDIDATURE
                        <ArrowRightIcon class="h-6 w-6 group-hover:rotate-[-45deg] transition-transform" />
                    </Link>
                </div>
            </div>
        </section>

        <!-- Event Details Modal -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="isModalOpen && selectedEvent" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-xl" @click="closeEvent"></div>

                <!-- Modal Content -->
                <div class="relative w-full max-w-4xl bg-slate-900 border border-white/10 rounded-[3rem] overflow-hidden shadow-2xl flex flex-col md:flex-row max-h-[90vh]">
                    <!-- Image Half -->
                    <div class="w-full md:w-1/2 h-64 md:h-auto relative">
                        <img :src="resolveImagePath(selectedEvent.image_path)" 
                             class="w-full h-full object-cover" 
                             :alt="selectedEvent.titre">
                        <div class="absolute inset-0 bg-gradient-to-t md:bg-gradient-to-r from-slate-900 via-transparent to-transparent"></div>
                    </div>

                    <!-- Info Half -->
                    <div class="w-full md:w-1/2 p-8 md:p-12 overflow-y-auto">
                        <button @click="closeEvent" class="absolute top-6 right-6 text-slate-400 hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <div class="flex items-center gap-3 mb-6">
                            <span class="px-4 py-1.5 bg-cyan-500/10 text-cyan-400 text-xs font-black rounded-xl border border-cyan-500/20 uppercase tracking-widest">
                                {{ selectedEvent.type_activite }}
                            </span>
                            <span class="text-slate-500 text-xs font-bold uppercase tracking-widest">
                                {{ new Date(selectedEvent.date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                            </span>
                        </div>

                        <h2 class="text-3xl md:text-5xl font-black text-white mb-8 tracking-tighter leading-tight uppercase font-display">
                            {{ selectedEvent.titre }}
                        </h2>

                        <div class="space-y-6 text-slate-400 leading-relaxed text-lg">
                            <p v-if="selectedEvent.description">{{ selectedEvent.description }}</p>
                            
                            <div class="pt-8 border-t border-white/5 space-y-4">
                                <div class="flex items-center gap-4 text-sm font-medium">
                                    <div class="h-10 w-10 rounded-full bg-slate-800 flex items-center justify-center text-cyan-400">
                                        <MapPinIcon class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-500 uppercase tracking-widest font-black">Localisation</p>
                                        <p class="text-white">{{ selectedEvent.lieu }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 text-sm font-medium">
                                    <div class="h-10 w-10 rounded-full bg-slate-800 flex items-center justify-center text-indigo-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-500 uppercase tracking-widest font-black">Horaire</p>
                                        <p class="text-white">{{ selectedEvent.heure_debut ? selectedEvent.heure_debut.substring(0, 5) : '09:00' }} - {{ selectedEvent.heure_fin ? selectedEvent.heure_fin.substring(0, 5) : '17:00' }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 text-sm font-medium">
                                    <div class="h-10 w-10 rounded-full bg-slate-800 flex items-center justify-center text-emerald-400">
                                        <UserGroupIcon class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-500 uppercase tracking-widest font-black">Impact (Audience)</p>
                                        <p class="text-white">{{ selectedEvent.audience_estimee }} participants estimés</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-12 flex flex-col sm:flex-row gap-4">
                            <button @click="closeEvent" class="px-8 py-4 bg-white text-slate-950 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-cyan-500 transition-all active:scale-95 shadow-xl">
                                Fermer
                            </button>
                            <Link :href="route('contact.index')" class="px-8 py-4 glass-dark border border-white/10 text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:border-cyan-500 transition-all text-center">
                                En savoir plus
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </GuestLayout>
</template>

<style>
/* Global overrides for futuristic feel */
body {
    background: #020617; /* Slate 950 */
}

.text-glow {
    text-shadow: 0 0 20px rgba(6, 182, 212, 0.4), 0 0 40px rgba(6, 182, 212, 0.2);
}

.text-shimmer {
    background: linear-gradient(90deg, #94a3b8, #fff, #94a3b8);
    background-size: 200% auto;
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: shimmer 5s linear infinite;
}

@keyframes shimmer {
    0% { background-position: 200% center; }
    100% { background-position: 0% center; }
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0; }
}
.animate-blink { animation: blink 1s step-end infinite; }

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
.animate-float { animation: float 4s ease-in-out infinite; }

@keyframes hero-enhanced {
    0% { transform: scale(1) translate(0, 0); filter: brightness(1) contrast(1); }
    33% { transform: scale(1.05) translate(-1%, -1%); filter: brightness(1.1) contrast(1.05); }
    66% { transform: scale(1.03) translate(1%, -0.5%); filter: brightness(0.95) contrast(1.1); }
    100% { transform: scale(1) translate(0, 0); filter: brightness(1) contrast(1); }
}
.animate-hero-enhanced { 
    animation: hero-enhanced 20s ease-in-out infinite; 
}

@keyframes pulse-slow {
    0%, 100% { opacity: 0.3; }
    50% { opacity: 0.6; }
}
.animate-pulse-slow { animation: pulse-slow 4s ease-in-out infinite; }

@keyframes glitch-flash {
    0%, 90%, 100% { opacity: 0; transform: scale(1); }
    92% { opacity: 0.8; transform: scale(1.02); filter: hue-rotate(90deg) brightness(2); }
    94% { opacity: 0.4; transform: scale(0.98); filter: hue-rotate(0deg); }
    96% { opacity: 1; transform: scale(1.01); filter: brightness(1.5); }
}
.animate-glitch-flash { animation: glitch-flash 8s infinite alternate-reverse; }

@keyframes glitch-flash-fast {
    0%, 95%, 100% { opacity: 0; }
    96% { opacity: 0.5; transform: translateX(5px); }
    97% { opacity: 0; }
    98% { opacity: 0.3; transform: translateX(-5px); }
}
.animate-glitch-flash-fast { animation: glitch-flash-fast 12s infinite; }

@keyframes scanline {
    0% { transform: translateY(-100%); }
    100% { transform: translateY(100%); }
}
.scanline {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 2px;
    background: linear-gradient(to right, transparent, rgba(6, 182, 212, 0.3), transparent);
    animation: scanline 8s linear infinite;
    z-index: 10;
}

.stream-line {
    position: absolute;
    top: 0; width: 1px; height: 100%;
    background: linear-gradient(to bottom, transparent, rgba(6, 182, 212, 0.5), transparent);
    animation: stream 12s linear infinite;
}
@keyframes stream {
    0% { transform: translateY(-100%); }
    100% { transform: translateY(100%); }
}

/* Border Animation Utility */
.border-animation::before {
    content: '';
    position: absolute;
    inset: -1px;
    background: linear-gradient(90deg, transparent, #22d3ee, transparent);
    background-size: 200% 100%;
    opacity: 0;
    transition: opacity 0.3s;
    pointer-events: none;
    border-radius: inherit;
    mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
}
.border-animation:hover::before {
    opacity: 1;
    animation: border-flow 2s linear infinite;
}
@keyframes border-flow {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* Scroll Reveal Initial States */
[data-reveal] {
    opacity: 0;
    transform: translateY(20px);
    transition: all 1s cubic-bezier(0.22, 1, 0.36, 1);
}

[data-reveal].revealed {
    opacity: 1 !important;
    transform: translateY(0) !important;
}

/* Hide scrollbar for clean look */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #1e293b;
    border-radius: 10px;
}
</style>
