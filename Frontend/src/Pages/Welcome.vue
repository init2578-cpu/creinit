<script setup>
import { onMounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { storageUrl } from '@/utils/format'
import { 
    AcademicCapIcon, 
    ArrowRightIcon, 
    ChartBarIcon, 
    UsersIcon,
    MapPinIcon,
    PhoneIcon,
    EnvelopeIcon,
    CalendarDaysIcon,
    CheckBadgeIcon,
    LightBulbIcon,
    RocketLaunchIcon,
    SparklesIcon,
    CpuChipIcon,
    CommandLineIcon,
    BeakerIcon,
    DocumentTextIcon,
    BuildingOffice2Icon,
    GlobeAltIcon as GlobeIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    canLogin: Boolean,
    settings: Object,
    stats: Object,
    recentPosts: Array,
    partners: Array,
    upcomingEvents: Array,
})

const pillars = [
    {
        title: 'Démocratisation du Savoir',
        description: 'Offrir un accès universel aux connaissances scientifiques et technologiques pour tous les citoyens du Sénégal.',
        icon: LightBulbIcon,
        color: 'cyan'
    },
    {
        title: 'Transfert de Technologie',
        description: 'Transformer les innovations de laboratoire en solutions concrètes pour dynamiser l\'économie locale de Kolda.',
        icon: AcademicCapIcon,
        color: 'blue'
    },
    {
        title: 'Émergence Numérique',
        description: 'Propulser le Fouladou au cœur du Plan Sénégal Émergent par la maîtrise des outils du futur.',
        icon: RocketLaunchIcon,
        color: 'indigo'
    }
]

const modules = [
    { name: 'Maintenance & Réseaux', level: 'Tout public', duration: '3 mois', students: 25, image: '/images/modules/maintenance.png', desc: 'Diagnostic, réparation et configuration de matériel informatique et de réseaux locaux.' },
    { name: 'Transformation Agroalimentaire', level: 'Producteurs', duration: '2 mois', students: 40, image: '/images/modules/agro.png', desc: 'Techniques de conservation et de transformation des fruits, légumes et produits locaux.' },
    { name: 'Énergie Solaire', level: 'Technique', duration: '4 mois', students: 20, image: '/images/modules/solar.png', desc: 'Installation, câblage et maintenance de kits solaires photovoltaïques.' },
    { name: 'Arts & Savonnerie', level: 'Tout public', duration: '1 mois', students: 30, image: '/images/modules/arts.png', desc: 'Fabrication artisanale de savons, eau de javel et cosmétiques naturels.' },
]

const testimonials = [
    {
        name: 'Mariama Diallo',
        role: 'Diplômée en Énergie Solaire (Promo 2026)',
        avatar: 'MD',
        text: 'Grâce à la formation pratique intensive reçue au CRE, j\'ai pu installer mon propre atelier d\'installation et maintenance solaire à Kolda. C\'est une chance inouïe pour les jeunes de la région.',
        rating: 5,
        color: 'cyan'
    },
    {
        name: 'Abdoulaye Sow',
        role: 'Producteur Local & Bénéficiaire Agroalimentaire',
        avatar: 'AS',
        text: 'Les techniques modernes de transformation et de conservation apprises au CRE ont changé notre coopérative. Nos pertes post-récolte ont chuté de près de 80% cette année.',
        rating: 5,
        color: 'indigo'
    },
    {
        name: 'Aliou Konté',
        role: 'Formateur au CRE',
        avatar: 'AK',
        text: 'Le CRE de Kolda offre un plateau technique de premier choix. Transmettre les résultats de nos recherches pour créer des solutions locales concrètes est extrêmement valorisant.',
        rating: 5,
        color: 'blue'
    },
    {
        name: 'Moussa Baldé',
        role: 'Développeur Fullstack Indépendant',
        avatar: 'MB',
        text: 'Le module de développement et initiation à l\'IA m\'a donné les bases. Aujourd\'hui, je travaille en télétravail pour des clients tout en restant basé ici à Kolda.',
        rating: 5,
        color: 'emerald'
    }
]

// Scroll Reveal & Typing Logic
const revealed = ref(new Set())
const typeText = ref('')
const fullTitle = 'L\'Innovation à Portée de Tous. Démocratiser la Science.'
let typeIdx = 0

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
        <section class="relative min-h-screen flex items-center overflow-hidden bg-slate-950 bg-cyber-grid">
            <!-- Digital Overlays -->
            <div class="absolute inset-0 z-[5] pointer-events-none overflow-hidden opacity-30">
                <div class="scanline"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(6,182,212,0.1),transparent_70%)]"></div>
            </div>

            <!-- AI Grid & Background -->
            <div class="absolute inset-0 z-0 overflow-hidden">
                <img src="/images/hero-premium.png" alt="Futuristic background" class="w-full h-full object-cover opacity-90 animate-hero-enhanced">
                
                <!-- Advanced Background Aura -->
                <div class="absolute top-1/4 left-1/4 w-[50vw] h-[50vw] bg-cyan-500/20 rounded-full blur-[120px] animate-pulse-slow"></div>
                <div class="absolute bottom-1/4 right-1/4 w-[40vw] h-[40vw] bg-indigo-500/20 rounded-full blur-[150px] animate-pulse-slow" style="animation-delay: 2s"></div>
                
                <div class="absolute inset-0 bg-gradient-to-b from-slate-950/20 via-transparent to-slate-950"></div>
                <div class="absolute inset-0 bg-cyan-500/5 mix-blend-overlay animate-pulse-slow"></div>
                
                <!-- HUD Decorative Elements -->
                <div class="absolute top-20 right-20 w-64 h-64 border-r border-t border-cyan-500/10 rounded-tr-[4rem] hidden lg:block animate-float"></div>
                <div class="absolute bottom-20 left-20 w-48 h-48 border-l border-b border-indigo-500/10 rounded-bl-[3rem] hidden lg:block animate-float" style="animation-delay: 1.5s"></div>
                
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
                            class="text-4xl sm:text-6xl lg:text-8xl font-black text-white tracking-tighter leading-[1.1] mb-8 font-display"
                            id="hero-title"
                        >
                            <span class="text-glow-cyan">L'Innovation à Portée de Tous.</span><br>
                            <span class="opacity-80 text-shimmer text-2xl sm:text-4xl lg:text-5xl">Démocratiser la Science.</span>
                            <span class="inline-block w-2 h-10 md:h-14 bg-cyan-500 ml-2 animate-blink"></span>
                        </h1>

                        <p 
                            class="text-lg md:text-xl text-slate-300 font-medium leading-relaxed mb-12 max-w-2xl border-l-4 border-cyan-500/50 pl-8 glass-dark py-6 rounded-r-2xl opacity-0 translate-x-[-20px] transition-all duration-1000"
                            id="hero-desc" data-reveal
                        >
                            {{ settings?.site_description || "Outil stratégique du MESRI, le CRE de Kolda réduit la fracture numérique en transformant les résultats de la recherche en leviers concrets de progrès pour le Fouladou." }}
                        </p>
                        
                        <div 
                            class="flex flex-col sm:flex-row items-center gap-6 opacity-0 transition-opacity duration-1000"
                            id="hero-buttons" data-reveal
                        >
                            <Link 
                                :href="route('applications.create')" 
                                class="group relative w-full sm:w-auto px-10 py-5 bg-cyan-500 text-slate-950 rounded-xl font-black text-lg transition-all hover:bg-cyan-400 hover:shadow-[0_0_40px_rgba(6,182,212,0.5)] hover:-translate-y-1 active:scale-95 flex items-center justify-center gap-3 overflow-hidden"
                            >
                                <div class="absolute inset-x-0 bottom-0 h-[2px] bg-white/20 scale-x-0 group-hover:scale-x-100 transition-transform origin-left"></div>
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
                    <div class="lg:col-span-4 grid grid-cols-2 lg:flex lg:flex-col gap-4 md:gap-6 lg:gap-8 justify-center items-center lg:items-end w-full">
                        <div v-for="(val, key) in stats" :key="key" 
                             class="glass-dark p-4 sm:p-6 rounded-2xl border border-white/5 w-full max-w-full lg:max-w-[280px] group hover:border-cyan-500/30 transition-all hover:-translate-y-1 lg:hover:translate-x-[-10px]"
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
        <section class="py-12 sm:py-16 bg-slate-950 relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                    <div v-for="(item, i) in [
                        { title: 'Vision Radicale', desc: 'Transcender les limites de l\'éducation traditionnelle par l\'immersion technologique.', icon: LightBulbIcon, color: 'cyan' },
                        { title: 'Impact Local', desc: 'Propulser la région de Kolda au sommet de la scène numérique sénégalaise.', icon: MapPinIcon, color: 'blue' },
                        { title: 'Excellence IA', desc: 'Maîtriser les outils de demain pour résoudre les problèmes d\'aujourd\'hui.', icon: CpuChipIcon, color: 'indigo' }
                    ]" :key="i" class="glass-dark p-6 sm:p-10 rounded-[2rem] sm:rounded-[2.5rem] border border-white/5 hover:border-cyan-500/20 transition-all group" data-reveal :id="'mission-' + i">
                        <div class="h-14 w-14 rounded-2xl flex items-center justify-center transition-all bg-slate-950 border border-white/5 group-hover:shadow-[0_0_15px_rgba(6,182,212,0.2)] shrink-0 mb-8" :class="`text-${item.color}-400`">
                            <component :is="item.icon" class="h-7 w-7 animate-float" />
                        </div>
                        <h3 class="text-2xl font-black text-white mb-4 uppercase tracking-tighter">{{ item.title }}</h3>
                        <p class="text-slate-400 leading-relaxed">{{ item.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- AI Core Section -->
        <section id="ai-core" class="py-16 sm:py-20 bg-slate-950 relative overflow-hidden">
            <!-- Digital Rain / Grid Pattern -->
            <div class="absolute inset-0 opacity-5 pointer-events-none">
                <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, rgba(6,182,212,0.15) 1px, transparent 0); background-size: 40px 40px;"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 relative z-10">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-24 items-center mb-32">
                    <div data-reveal id="ai-vision" class="relative">
                        <div class="absolute -left-10 -top-10 h-20 w-20 border-l-2 border-t-2 border-cyan-500/20"></div>
                        <h2 class="text-4xl sm:text-6xl lg:text-7xl font-black text-white tracking-tighter leading-tight mb-8 font-display">
                            COGNITION <br> <span class="text-cyan-500 text-glow-cyan italic">AUGMENTÉE</span>
                        </h2>
                        <p class="text-slate-400 text-lg font-medium leading-relaxed mb-10 max-w-xl">
                            Nous ne nous contentons pas d'enseigner la technologie ; nous utilisons l'intelligence artificielle pour décupler les capacités de nos apprenants. De la génération de code au tutorat intelligent, le CRE est le laboratoire du futur à Kolda.
                        </p>
                        <div class="grid grid-cols-2 gap-4 sm:gap-6">
                            <div class="p-4 sm:p-8 glass-dark rounded-3xl relative overflow-hidden group border border-white/5">
                                <div class="absolute inset-0 bg-cyan-500/5 translate-y-[100%] group-hover:translate-y-0 transition-transform duration-500"></div>
                                <p class="text-cyan-400 text-3xl sm:text-4xl font-black mb-1">90%</p>
                                <p class="text-[9px] sm:text-[10px] text-slate-500 font-black uppercase tracking-widest">PRODUCTIVITÉ CODE</p>
                            </div>
                            <div class="p-4 sm:p-8 glass-dark rounded-3xl relative overflow-hidden group border border-white/5">
                                <div class="absolute inset-0 bg-indigo-500/5 translate-y-[100%] group-hover:translate-y-0 transition-transform duration-500"></div>
                                <p class="text-indigo-400 text-3xl sm:text-4xl font-black mb-1">24/7</p>
                                <p class="text-[9px] sm:text-[10px] text-slate-500 font-black uppercase tracking-widest">ASSISTANCE IA</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <div 
                            v-for="pillar in pillars" 
                            :key="pillar.title"
                            class="p-6 sm:p-8 rounded-3xl glass-dark border border-white/5 hover:border-cyan-500/30 transition-all duration-500 group relative overflow-hidden"
                            data-reveal :id="'pillar-' + pillar.color"
                        >
                            <div class="flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-4 sm:gap-6">
                                <div class="h-16 w-16 rounded-2xl flex items-center justify-center transition-all bg-slate-950 border border-white/5 group-hover:shadow-[0_0_20px_rgba(6,182,212,0.2)] shrink-0" :class="`text-${pillar.color}-400`">
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

        <!-- Smart Curriculum Grid -->
        <section class="py-12 sm:py-16 bg-slate-950 relative overflow-hidden">
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
                        class="glass-dark border border-white/5 rounded-[2.5rem] overflow-hidden group hover:border-cyan-500/30 transition-all duration-700 hover:-translate-y-2 flex flex-col h-full"
                        data-reveal :id="'mod-' + mod.name"
                        :style="`transition-delay: ${index * 100}ms`"
                    >
                        <!-- Module Image -->
                        <div class="h-48 w-full overflow-hidden relative">
                            <img :src="mod.image" alt="Module Image" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent"></div>
                        </div>

                        <!-- Card Content -->
                        <div class="p-8 flex-1 flex flex-col relative z-20">
                            <!-- Digital Pulse Circle -->
                            <div class="absolute -right-10 -top-10 h-32 w-32 bg-cyan-500/5 rounded-full blur-3xl group-hover:bg-cyan-500/10 transition-all duration-700 z-10"></div>
                            
                            <div class="relative z-20 flex-1 flex flex-col">
                                <div class="flex justify-between items-start mb-6">
                                    <span class="px-3 py-1 bg-slate-800/50 text-slate-400 text-[8px] font-black rounded-lg border border-white/5 group-hover:bg-cyan-500 group-hover:text-slate-950 transition-all uppercase tracking-[0.2em]">
                                        LEVEL: {{ mod.level }}
                                    </span>
                                    <div class="h-2 w-2 rounded-full bg-cyan-500 shadow-[0_0_10px_cyan] animate-pulse"></div>
                                </div>
                                
                                <h3 class="text-2xl font-black text-white mb-4 tracking-tight leading-tight group-hover:translate-x-1 transition-transform">{{ mod.name }}</h3>
                                <p class="text-slate-400 text-sm font-medium leading-relaxed mb-6 flex-1">
                                    {{ mod.desc }}
                                </p>
                                
                                <div class="mt-auto pt-6 border-t border-white/5 flex items-center justify-between text-[10px] font-black text-slate-500 uppercase tracking-widest">
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
                </div>
            </div>
        </section>

        <!-- Événements (Zone de publication) -->
        <section v-if="upcomingEvents && upcomingEvents.length > 0" class="py-12 sm:py-16 bg-slate-950 relative overflow-hidden border-t border-white/5">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex flex-col md:flex-row justify-between items-end gap-10 mb-16 relative">
                    <div class="absolute -left-12 top-0 h-full w-[2px] bg-gradient-to-b from-pink-500 to-transparent"></div>
                    <div class="max-w-xl" data-reveal id="events-header">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-pink-500/10 border border-pink-500/20 mb-4">
                            <SparklesIcon class="h-4 w-4 text-pink-400" />
                            <span class="text-[9px] font-black text-pink-400 uppercase tracking-[0.3em]">Agenda E-CRE</span>
                        </div>
                        <h2 class="text-4xl md:text-6xl font-black text-white tracking-tighter mb-4 font-display">ÉVÉNEMENTS <span class="bg-gradient-to-r from-pink-400 to-orange-400 bg-clip-text text-transparent">À VENIR</span></h2>
                        <p class="text-slate-500 font-medium tracking-wide">Ne manquez pas nos prochains séminaires, hackathons et ateliers ouverts.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative z-20">
                    <div 
                        v-for="(event, index) in upcomingEvents" 
                        :key="event.id"
                        class="glass-dark border border-white/5 rounded-[2.5rem] overflow-hidden group hover:border-pink-500/30 transition-all duration-500 hover:-translate-y-2 flex flex-col"
                        data-reveal :id="'event-' + event.id"
                        :style="`transition-delay: ${index * 100}ms`"
                    >
                        <div class="h-48 w-full overflow-hidden relative">
                            <img v-if="event.image_path" :src="storageUrl(event.image_path)" :alt="event.titre" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                            <div v-else class="w-full h-full bg-slate-900 flex items-center justify-center">
                                <CalendarDaysIcon class="h-12 w-12 text-slate-700" />
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent"></div>
                            
                            <div class="absolute top-4 left-4">
                                <span class="px-3 py-1 bg-white/10 backdrop-blur-md text-white text-[10px] font-black rounded-lg border border-white/10 uppercase tracking-widest shadow-xl">
                                    {{ event.type_activite }}
                                </span>
                            </div>
                        </div>
                        <div class="p-8 flex-1 flex flex-col">
                            <div class="flex items-center gap-3 text-pink-400 text-[10px] font-black uppercase tracking-[0.2em] mb-4">
                                <CalendarDaysIcon class="h-4 w-4" />
                                {{ new Date(event.date).toLocaleDateString('fr-FR') }}
                            </div>

                            <h3 class="text-xl font-black text-white mb-3 tracking-tight group-hover:text-pink-400 transition-colors line-clamp-2">{{ event.titre }}</h3>
                            
                            <div v-if="event.lieu" class="flex items-center gap-2 mb-4 text-xs font-bold text-slate-400">
                                <MapPinIcon class="h-4 w-4 text-pink-500/70" />
                                {{ event.lieu }}
                            </div>

                            <p class="text-slate-500 text-sm leading-relaxed line-clamp-3 mb-6 flex-1">
                                {{ event.description || 'Détails de l\'événement à venir.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Actualités (Zone de publication) -->
        <section v-if="recentPosts && recentPosts.length > 0" class="py-12 sm:py-16 bg-slate-950 relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex flex-col md:flex-row justify-between items-end gap-10 mb-16 relative">
                    <div class="absolute -left-12 top-0 h-full w-[2px] bg-gradient-to-b from-indigo-500 to-transparent"></div>
                    <div class="max-w-xl" data-reveal id="news-header">
                        <h2 class="text-4xl md:text-6xl font-black text-white tracking-tighter mb-4 font-display">DERNIÈRES <span class="bg-gradient-to-r from-indigo-400 to-cyan-400 bg-clip-text text-transparent">ACTUALITÉS</span></h2>
                        <p class="text-slate-500 font-medium tracking-wide">Restez informés des dernières nouveautés du CRE Kolda.</p>
                    </div>
                    <Link :href="route('public.posts.index')" class="group text-[10px] font-black text-indigo-400 uppercase tracking-[0.3em] hover:text-white transition-colors flex items-center gap-4">
                        VOIR TOUTES LES ACTUALITÉS
                        <div class="h-10 w-10 rounded-full border border-indigo-500/30 flex items-center justify-center group-hover:border-indigo-500 group-hover:bg-indigo-500 group-hover:text-slate-950 transition-all">
                            <ArrowRightIcon class="h-4 w-4" />
                        </div>
                    </Link>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative z-20">
                    <Link 
                        v-for="(post, index) in recentPosts" 
                        :key="post.id"
                        :href="route('public.posts.show', post.slug)"
                        class="glass-dark border border-white/5 rounded-[2.5rem] overflow-hidden group hover:border-indigo-500/30 transition-all duration-500 hover:-translate-y-2 flex flex-col"
                        data-reveal :id="'news-' + post.id"
                        :style="`transition-delay: ${index * 100}ms`"
                    >
                        <div class="h-48 w-full overflow-hidden relative">
                            <img v-if="post.image_path" :src="storageUrl(post.image_path)" :alt="post.title" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                            <div v-else class="w-full h-full bg-slate-900 flex items-center justify-center">
                                <DocumentTextIcon class="h-12 w-12 text-slate-700" />
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent"></div>
                            
                            <div class="absolute bottom-4 left-4">
                                <span class="px-3 py-1 bg-indigo-500/20 backdrop-blur-md text-indigo-300 text-[8px] font-black rounded-lg border border-indigo-500/30 uppercase tracking-widest">
                                    {{ new Date(post.published_at).toLocaleDateString() }}
                                </span>
                            </div>
                        </div>
                        <div class="p-8 flex-1 flex flex-col">
                            <h3 class="text-xl font-black text-white mb-3 tracking-tight group-hover:text-indigo-400 transition-colors line-clamp-2">{{ post.title }}</h3>
                            <p class="text-slate-400 text-sm leading-relaxed line-clamp-3 mb-6 flex-1">
                                {{ post.excerpt || post.content.substring(0, 150) + '...' }}
                            </p>
                            <div class="flex items-center gap-2 text-[10px] font-black text-indigo-400 uppercase tracking-widest group-hover:text-white transition-colors mt-auto">
                                Lire la suite
                                <ArrowRightIcon class="h-3 w-3 group-hover:translate-x-1 transition-transform" />
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Témoignages (Zone Vitrine) -->
        <section class="py-12 sm:py-16 bg-slate-950 relative overflow-hidden border-t border-white/5">
            <!-- Background effects -->
            <div class="absolute inset-0 pointer-events-none opacity-20">
                <div class="absolute top-[20%] right-[10%] w-[350px] h-[350px] bg-emerald-500/10 rounded-full blur-[100px] animate-pulse-slow"></div>
                <div class="absolute bottom-[20%] left-[10%] w-[300px] h-[300px] bg-cyan-500/10 rounded-full blur-[100px] animate-pulse-slow" style="animation-delay: 1.5s"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 relative z-10">
                <div class="flex flex-col md:flex-row justify-between items-end gap-10 mb-16 relative">
                    <div class="absolute -left-12 top-0 h-full w-[2px] bg-gradient-to-b from-emerald-500 to-transparent"></div>
                    <div class="max-w-xl" data-reveal id="testimonials-header">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 mb-4">
                            <SparklesIcon class="h-4 w-4 text-emerald-400" />
                            <span class="text-[9px] font-black text-emerald-400 uppercase tracking-[0.3em]">Impact & Success Stories</span>
                        </div>
                        <h2 class="text-4xl md:text-6xl font-black text-white tracking-tighter mb-4 font-display">
                            TEMOIGNAGES <span class="bg-gradient-to-r from-emerald-400 to-cyan-400 bg-clip-text text-transparent">LOCAUX</span>
                        </h2>
                        <p class="text-slate-500 font-medium">Découvrez comment le CRE Kolda contribue activement au développement et à l'autonomie technologique de notre communauté.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative z-20">
                    <div 
                        v-for="(t, index) in testimonials" 
                        :key="t.name"
                        class="glass-dark border border-white/5 rounded-[2.5rem] p-6 sm:p-8 lg:p-10 hover:border-emerald-500/30 transition-all duration-500 hover:-translate-y-1.5 flex flex-col justify-between group"
                        data-reveal :id="'testimonial-' + index"
                        :style="`transition-delay: ${index * 100}ms`"
                    >
                        <div>
                            <!-- Star ratings -->
                            <div class="flex gap-1 mb-6">
                                <span v-for="star in t.rating" :key="star" class="text-emerald-400 text-lg">★</span>
                            </div>

                            <p class="text-slate-300 text-base font-medium leading-relaxed italic mb-8 relative">
                                <span class="absolute -top-6 -left-3 text-6xl text-slate-800 pointer-events-none select-none font-serif">“</span>
                                {{ t.text }}
                            </p>
                        </div>

                        <div class="flex items-center gap-4 pt-6 border-t border-white/5 mt-auto">
                            <!-- Avatar placeholder with initials and cyber-theme gradient -->
                            <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 border border-white/10 flex items-center justify-center text-sm font-black text-white group-hover:shadow-[0_0_15px_rgba(16,185,129,0.3)] transition-all">
                                {{ t.avatar }}
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-white uppercase tracking-wider group-hover:text-emerald-400 transition-colors">{{ t.name }}</h4>
                                <span class="text-[11px] text-slate-500 font-bold block mt-0.5">{{ t.role }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Partenaires (Zone Vitrine) -->
        <section v-if="partners && partners.length > 0" class="py-12 sm:py-16 bg-slate-950 relative overflow-hidden border-t border-white/5">
            <div class="absolute inset-0 pointer-events-none" style="background-image: radial-gradient(circle at 2px 2px, rgba(6,182,212,0.04) 1px, transparent 0); background-size: 40px 40px;"></div>
            
            <div class="max-w-7xl mx-auto px-4 relative z-10">
                <div class="flex flex-col md:flex-row justify-between items-end gap-10 mb-16">
                    <div class="max-w-xl" data-reveal id="partners-header">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-cyan-500/10 border border-cyan-500/20 mb-4">
                            <span class="text-[9px] font-black text-cyan-400 uppercase tracking-[0.3em]">Réseau Partenaires</span>
                        </div>
                        <h2 class="text-4xl md:text-6xl font-black text-white tracking-tighter mb-4 font-display">
                            NOS <span class="text-cyan-500 text-glow-cyan">PARTENAIRES</span>
                        </h2>
                        <p class="text-slate-500 font-medium">Institutions, entreprises et organisations qui soutiennent la mission du CRE Kolda.</p>
                    </div>
                    <Link :href="route('public.partenaires')" class="group text-[10px] font-black text-cyan-400 uppercase tracking-[0.3em] hover:text-white transition-colors flex items-center gap-4">
                        VOIR TOUS LES PARTENAIRES
                        <div class="h-10 w-10 rounded-full border border-cyan-500/30 flex items-center justify-center group-hover:border-cyan-500 group-hover:bg-cyan-500 group-hover:text-slate-950 transition-all">
                            <ArrowRightIcon class="h-4 w-4" />
                        </div>
                    </Link>
                </div>

                <!-- Partners scrolling strip -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                    <div
                        v-for="(partner, i) in partners"
                        :key="partner.id"
                        class="glass-dark border border-white/5 rounded-[1.5rem] p-6 flex flex-col items-center justify-center gap-4 group hover:border-cyan-500/30 transition-all duration-500 hover:-translate-y-1 min-h-[140px]"
                        data-reveal :id="'partner-' + partner.id"
                        :style="`transition-delay: ${i * 60}ms`"
                    >
                        <div class="h-16 w-full flex items-center justify-center">
                            <img 
                                v-if="partner.logo_path" 
                                :src="storageUrl(partner.logo_path)" 
                                :alt="partner.nom"
                                class="max-h-14 max-w-full object-contain filter grayscale group-hover:grayscale-0 opacity-60 group-hover:opacity-100 transition-all duration-500"
                            >
                            <div v-else class="h-14 w-14 rounded-2xl bg-slate-800 flex items-center justify-center border border-white/5">
                                <BuildingOffice2Icon class="h-7 w-7 text-slate-600" />
                            </div>
                        </div>
                        <div class="text-center">
                            <p class="text-xs font-black text-white group-hover:text-cyan-400 transition-colors line-clamp-2 text-center leading-tight">{{ partner.nom }}</p>
                            <span class="inline-block mt-1 px-2 py-0.5 bg-slate-800/50 text-slate-500 text-[8px] font-black rounded uppercase tracking-wider">{{ partner.type }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Feature Section (Futuristic Command) -->
        <section class="max-w-7xl mx-auto px-4 py-12 sm:py-16 relative z-20">
            <div class="glass-dark border border-cyan-500/20 rounded-[2rem] sm:rounded-[3rem] lg:rounded-[4rem] p-6 sm:p-12 lg:p-24 relative overflow-hidden flex flex-col items-center text-center shadow-2xl">
                <!-- Abstract decorations -->
                <div class="absolute top-0 left-0 w-full h-full opacity-20 pointer-events-none">
                    <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-cyan-500 rounded-full blur-[150px] animate-pulse-slow"></div>
                    <div class="absolute bottom-[-20%] right-[-10%] w-[60%] h-[60%] bg-indigo-600 rounded-full blur-[200px] animate-pulse-slow" style="animation-delay: 2s"></div>
                </div>

                <div class="relative z-10 max-w-3xl">
                    <div class="mb-8 inline-block px-4 py-2 bg-cyan-500/10 border border-cyan-500/30 rounded-xl text-[10px] text-cyan-400 font-black tracking-[0.5em]">PRIORITY PROTOCOL: ACTIVE</div>
                    <h2 class="text-4xl sm:text-6xl lg:text-8xl font-black text-white tracking-tighter leading-none mb-10 font-display">
                        FORCEZ LE <span class="text-cyan-500 skew-x-[-12deg] inline-block">DESTIN</span>.
                    </h2>
                    <p class="text-slate-400 text-base sm:text-lg md:text-xl font-medium mb-16 max-w-2xl mx-auto border-l-4 border-cyan-500 pl-4 sm:pl-8 text-left glass-dark py-6 rounded-r-2xl">
                        Le futur n'attend pas. Les cohortes se saturent. Assurez votre place dans l'élite numérique du Sénégal.
                    </p>
                    <Link 
                        :href="route('applications.create')"
                        class="group relative inline-flex items-center justify-center gap-4 px-6 sm:px-12 py-4 sm:py-6 bg-white text-slate-950 rounded-2xl font-black text-base sm:text-xl transition-all hover:bg-cyan-500 hover:shadow-[0_0_60px_rgba(34,211,238,0.5)] active:scale-95 shadow-2xl w-full sm:w-auto"
                    >
                        INITIALISER CANDIDATURE
                        <ArrowRightIcon class="h-5 w-5 sm:h-6 sm:w-6 group-hover:rotate-[-45deg] transition-transform" />
                    </Link>
                </div>
            </div>
        </section>
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

/* Cyber Grid Background */
.bg-cyber-grid {
    background-size: 50px 50px;
    background-image: 
        linear-gradient(to right, rgba(6, 182, 212, 0.05) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(6, 182, 212, 0.05) 1px, transparent 1px);
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
