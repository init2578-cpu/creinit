<script setup>
import { ref, onMounted, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import DoughnutChart from '@/Components/Charts/DoughnutChart.vue'
import BarChart from '@/Components/Charts/BarChart.vue'
import AreaChart from '@/Components/Charts/AreaChart.vue'
import api from '@/services/api'
import { 
    UsersIcon, 
    AcademicCapIcon, 
    ComputerDesktopIcon, 
    ScaleIcon,
    ExclamationCircleIcon,
    WrenchIcon,
    ArrowTrendingUpIcon,
    ArrowPathIcon,
    ChartBarIcon,
    BoltIcon,
    BriefcaseIcon,
    ClipboardDocumentCheckIcon,
    BuildingLibraryIcon,
    CalendarIcon,
    RocketLaunchIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    kpis: Object
})

const dashboardKpis = ref(props.kpis)
const isLoading = ref(false)

const fetchStats = async () => {
    isLoading.value = true
    try {
        const response = await api.get('/stats/director')
        // The API response matches the structure we need
        dashboardKpis.value = response.data
    } catch (error) {
        console.error('Erreur API:', error)
    } finally {
        isLoading.value = false
    }
}

const moduleLabels = computed(() => {
    if (!dashboardKpis.value || !dashboardKpis.value.module_validation_rates) return []
    return Object.keys(dashboardKpis.value.module_validation_rates)
})

const moduleData = computed(() => {
    if (!dashboardKpis.value || !dashboardKpis.value.module_validation_rates) return []
    return Object.values(dashboardKpis.value.module_validation_rates)
})

onMounted(() => {
    // Initial fetch to sync possible discrepancies
    fetchStats()
})
</script>

<template>
    <Head title="Tableau de Bord Stratégique" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#fcfdfe] pb-24">
            <!-- Hero Header with Premium Gradient -->
            <div class="relative overflow-hidden bg-white border-b border-gray-100 px-4 sm:px-6 lg:px-8 py-12 mb-10">
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-[600px] h-[600px] bg-blue-50/40 rounded-full blur-[100px] opacity-60"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-96 h-96 bg-emerald-50/40 rounded-full blur-[80px] opacity-40"></div>
                
                <div class="max-w-7xl mx-auto relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-8">
                    <div class="max-w-3xl">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-100/50 text-blue-700 text-[10px] font-black uppercase tracking-[0.2em] rounded-full border border-blue-200/50">
                                <span class="relative flex h-2 w-2">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                                </span>
                                Temps Réel
                            </span>
                            <span class="px-3 py-1 bg-gray-100 text-gray-400 text-[10px] font-black uppercase tracking-[0.2em] rounded-full border border-gray-200/50">Vue Stratégique</span>
                        </div>
                        <h1 class="text-5xl font-black text-gray-900 tracking-tight leading-[1.1]">
                            Command <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 via-indigo-600 to-emerald-600">Center</span>
                        </h1>
                        <p class="text-lg text-gray-500 mt-4 font-medium leading-relaxed">
                            Analytiques haute précision pour le pilotage opérationnel et stratégique du CRE Kolda.
                        </p>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-4">
                        <button 
                            @click="fetchStats"
                            :disabled="isLoading"
                            class="group flex items-center gap-2 px-6 py-4 bg-white border border-gray-200 text-gray-700 rounded-[1.25rem] font-bold shadow-sm hover:shadow-xl hover:border-blue-200 hover:-translate-y-0.5 transition-all active:scale-95 disabled:opacity-50"
                        >
                            <ArrowPathIcon class="h-5 w-5 text-blue-500" :class="{ 'animate-spin': isLoading }" />
                            <span>Synchroniser</span>
                        </button>
                        <a 
                            :href="route('dashboard.director.export-pdf')"
                            class="flex items-center gap-2 px-8 py-4 bg-gray-900 text-white rounded-[1.25rem] font-bold shadow-2xl shadow-gray-200 hover:bg-black hover:shadow-gray-300 hover:-translate-y-1 transition-all active:scale-95"
                        >
                            <ArrowTrendingUpIcon class="h-5 w-5 text-emerald-400" />
                            <span>Audit Mensuel</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                
                <!-- KPI Section 1: Main Channels -->
                <section>
                    <div class="flex items-center gap-2 mb-6">
                        <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">Indicateurs Clés</h2>
                        <div class="h-px flex-1 bg-gray-100"></div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Total Apprenants -->
                        <div class="group relative bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm transition-all hover:shadow-2xl hover:border-blue-100 overflow-hidden">
                            <div class="absolute top-0 right-0 w-24 h-24 -mr-8 -mt-8 bg-blue-50 rounded-full blur-2xl group-hover:bg-blue-100 transition-colors"></div>
                            <div class="relative z-10">
                                <div class="h-12 w-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 mb-6 group-hover:bg-blue-600 group-hover:text-white transition-all transform group-hover:rotate-6 shadow-sm">
                                    <UsersIcon class="h-6 w-6" />
                                </div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Capacité Totale</p>
                                <h3 class="text-4xl font-black text-gray-900 tracking-tighter">{{ dashboardKpis.total_learners }}</h3>
                                <div class="mt-4 flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-500">Apprenants actifs</span>
                                    <span class="px-2 py-0.5 bg-blue-50 text-blue-600 text-[8px] font-black rounded-lg">LIVE</span>
                                </div>
                            </div>
                        </div>

                        <!-- Assiduité Realtime -->
                        <div class="group relative bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm transition-all hover:shadow-2xl hover:border-emerald-100">
                             <div class="absolute top-0 right-0 w-24 h-24 -mr-8 -mt-8 bg-emerald-50 rounded-full blur-2xl group-hover:bg-emerald-100 transition-colors"></div>
                            <div class="relative z-10">
                                <div class="h-12 w-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 mb-6 group-hover:bg-emerald-600 group-hover:text-white transition-all transform group-hover:-rotate-6 shadow-sm">
                                    <BoltIcon class="h-6 w-6" />
                                </div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Taux d'Engagement</p>
                                <h3 class="text-4xl font-black text-gray-900 tracking-tighter">{{ dashboardKpis.attendance_rate }}%</h3>
                                <div class="mt-6 w-full bg-gray-100 h-2 rounded-full overflow-hidden p-0.5 shadow-inner">
                                    <div class="bg-gradient-to-r from-emerald-400 to-emerald-600 h-full rounded-full transition-all duration-1000 shadow-sm" :style="{ width: dashboardKpis.attendance_rate + '%' }"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Pedagogical Performance -->
                        <div class="group relative bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm transition-all hover:shadow-2xl hover:border-indigo-100">
                            <div class="absolute top-0 right-0 w-24 h-24 -mr-8 -mt-8 bg-indigo-50 rounded-full blur-2xl group-hover:bg-indigo-100 transition-colors"></div>
                            <div class="relative z-10">
                                <div class="h-12 w-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 mb-6 group-hover:bg-indigo-600 group-hover:text-white transition-all transform group-hover:scale-110 shadow-sm">
                                    <AcademicCapIcon class="h-6 w-6" />
                                </div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Moyenne Examens</p>
                                <h3 class="text-4xl font-black text-gray-900 tracking-tighter">{{ dashboardKpis.pedagogical?.avg_exam_score || 0 }}<span class="text-xs text-gray-400 ml-1">/20</span></h3>
                                <p class="mt-4 text-xs font-bold text-gray-500">Validation: {{ dashboardKpis.pedagogical?.chapters_validated_rate || 0 }}% des chapitres</p>
                            </div>
                        </div>

                        <!-- Admission Pipe -->
                        <div class="group relative bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm transition-all hover:shadow-2xl hover:border-orange-100">
                            <div class="absolute top-0 right-0 w-24 h-24 -mr-8 -mt-8 bg-orange-50 rounded-full blur-2xl group-hover:bg-orange-100 transition-colors"></div>
                            <div class="relative z-10">
                                <div class="h-12 w-12 bg-orange-50 rounded-2xl flex items-center justify-center text-orange-600 mb-6 group-hover:bg-orange-600 group-hover:text-white transition-all transform group-hover:-scale-y-110 shadow-sm">
                                    <BriefcaseIcon class="h-6 w-6" />
                                </div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Lead Validation</p>
                                <h3 class="text-4xl font-black text-gray-900 tracking-tighter">{{ dashboardKpis.admissions?.pending || 0 }}</h3>
                                <div class="mt-4 flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-500">Dossiers en attente</span>
                                    <div class="h-2 w-2 bg-orange-500 rounded-full animate-pulse"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section 2: Advanced Visualizations -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Academic Success Distribution -->
                    <div class="bg-white p-10 rounded-[3rem] border border-gray-100 shadow-sm flex flex-col">
                        <div class="flex items-center justify-between mb-10">
                            <div>
                                <h2 class="text-2xl font-black text-gray-900 tracking-tight">Performance Académique</h2>
                                <p class="text-sm text-gray-500 font-medium">Réussite et certifications par filière</p>
                            </div>
                            <div class="h-12 w-12 bg-gray-50 text-indigo-500 rounded-2xl flex items-center justify-center border border-gray-100">
                                <ChartBarIcon class="h-6 w-6" />
                            </div>
                        </div>
                        <div class="flex-1 min-h-[300px]">
                            <BarChart :labels="moduleLabels" :data="moduleData" />
                        </div>
                    </div>

                    <!-- Gender & Demographics -->
                    <div class="bg-white p-10 rounded-[3rem] border border-gray-100 shadow-sm flex flex-col lg:flex-row gap-10">
                        <div class="flex-1">
                            <h2 class="text-2xl font-black text-gray-900 tracking-tight mb-2">Démographie</h2>
                            <p class="text-sm text-gray-500 font-medium mb-10">Distribution de genre et inclusion</p>
                            
                            <div class="space-y-6">
                                <div class="p-6 bg-pink-50/50 rounded-3xl border border-pink-100/50">
                                    <div class="flex justify-between items-center mb-2">
                                        <p class="text-xs font-black text-pink-700 uppercase tracking-widest">Femmes</p>
                                        <span class="text-xl font-black text-pink-600">{{ dashboardKpis.gender_parity?.female || 0 }}</span>
                                    </div>
                                    <div class="w-full bg-pink-100/50 h-2 rounded-full">
                                        <div class="bg-pink-500 h-full rounded-full transition-all duration-1000" :style="{ width: (dashboardKpis.gender_parity?.ratio || 0) + '%' }"></div>
                                    </div>
                                </div>

                                <div class="p-6 bg-blue-50/50 rounded-3xl border border-blue-100/50">
                                    <div class="flex justify-between items-center mb-2">
                                        <p class="text-xs font-black text-blue-700 uppercase tracking-widest">Hommes</p>
                                        <span class="text-xl font-black text-blue-600">{{ dashboardKpis.gender_parity?.male || 0 }}</span>
                                    </div>
                                    <div class="w-full bg-blue-100/50 h-2 rounded-full">
                                        <div class="bg-blue-500 h-full rounded-full transition-all duration-1000" :style="{ width: (100 - (dashboardKpis.gender_parity?.ratio || 0)) + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="lg:w-1/2 flex items-center justify-center">
                            <div class="w-full max-w-[240px]">
                                <DoughnutChart :male="dashboardKpis.gender_parity?.male || 0" :female="dashboardKpis.gender_parity?.female || 0" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Operational Control & Ecosystem -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Logistics & Inventory -->
                    <div class="lg:col-span-2 bg-gray-900 p-10 rounded-[3rem] shadow-2xl relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 blur-[80px] -mr-32 -mt-32"></div>
                        
                        <div class="flex items-center justify-between mb-10 relative z-10">
                            <div>
                                <h2 class="text-2xl font-black text-white tracking-tight">Gestion du Parc</h2>
                                <p class="text-sm text-gray-400 font-medium">Indicateurs de maintenance et logistique</p>
                            </div>
                            <div class="h-14 w-14 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/10 group-hover:scale-110 transition-transform">
                                <ComputerDesktopIcon class="h-7 w-7 text-indigo-400" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-10 relative z-10">
                            <div class="p-6 bg-white/5 border border-white/5 rounded-3xl backdrop-blur-xl">
                                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Actifs Totaux</p>
                                <p class="text-3xl font-black text-white">{{ dashboardKpis.logistics?.total_assets || 0 }}</p>
                                <div class="mt-4 h-1 w-full bg-white/10 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-500" style="width: 100%"></div>
                                </div>
                            </div>
                            <div class="p-6 bg-white/5 border border-white/5 rounded-3xl backdrop-blur-xl">
                                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Indice Santé</p>
                                <p class="text-3xl font-black text-white">{{ dashboardKpis.operational_hardware }}%</p>
                                <div class="mt-4 h-1 w-full bg-white/10 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500" :style="{ width: dashboardKpis.operational_hardware + '%' }"></div>
                                </div>
                            </div>
                            <div class="p-6 bg-white/5 border border-white/5 rounded-3xl backdrop-blur-xl">
                                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Prêts Actifs</p>
                                <p class="text-3xl font-black text-white">{{ dashboardKpis.logistics?.active_loans || 0 }}</p>
                                <div class="mt-4 h-1 w-full bg-white/10 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500" :style="{ width: ((dashboardKpis.logistics?.active_loans / dashboardKpis.logistics?.total_assets) * 100) + '%' }"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Defective Assets List -->
                        <div class="bg-red-950/30 border border-red-900/30 rounded-3xl p-6 relative z-10">
                            <div class="flex items-center gap-3 mb-4">
                                <ExclamationCircleIcon class="h-5 w-5 text-red-500" />
                                <h3 class="text-sm font-black text-red-200 uppercase tracking-widest">Alertes Maintenance ({{ dashboardKpis.alerts?.broken_assets?.length || 0 }})</h3>
                            </div>
                            <div v-if="dashboardKpis.alerts?.broken_assets?.length > 0" class="flex flex-wrap gap-3">
                                <div v-for="asset in dashboardKpis.alerts.broken_assets.slice(0, 4)" :key="asset.id" class="px-4 py-2 bg-red-900/40 text-red-100 rounded-xl text-xs font-bold border border-red-800/40">
                                    {{ asset.nom }}
                                </div>
                                <span v-if="dashboardKpis.alerts.broken_assets.length > 4" class="text-red-400 text-xs font-bold flex items-center">+{{ dashboardKpis.alerts.broken_assets.length - 4 }} autres</span>
                            </div>
                            <p v-else class="text-xs text-emerald-400 font-bold">Zéro défaut technique détecté.</p>
                        </div>
                    </div>

                    <!-- Ecosystem & Partnerships -->
                    <div class="bg-emerald-600 p-10 rounded-[3rem] shadow-2xl relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 blur-[80px] -mr-32 -mt-32"></div>
                        
                        <div class="relative z-10 flex flex-col h-full">
                            <div class="flex items-center justify-between mb-10">
                                <div>
                                    <h2 class="text-2xl font-black text-white tracking-tight">Écosystème</h2>
                                    <p class="text-sm text-emerald-100 font-medium opacity-80">Réseau et influence du CRE</p>
                                </div>
                                <div class="h-14 w-14 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/10 group-hover:rotate-12 transition-transform">
                                    <BuildingLibraryIcon class="h-7 w-7 text-emerald-100" />
                                </div>
                            </div>

                            <div class="space-y-6 flex-1">
                                <div class="flex items-center gap-4 group/item">
                                    <div class="h-12 w-12 bg-white/10 rounded-2xl flex items-center justify-center text-white border border-white/5">
                                        <RocketLaunchIcon class="h-6 w-6" />
                                    </div>
                                    <div>
                                        <p class="text-3xl font-black text-white">{{ dashboardKpis.ecosystem?.total_partners || 0 }}</p>
                                        <p class="text-[10px] font-black text-emerald-100 uppercase tracking-widest leading-none">Partenaires Actifs</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 bg-white/10 rounded-2xl flex items-center justify-center text-white border border-white/5">
                                        <CalendarIcon class="h-6 w-6" />
                                    </div>
                                    <div>
                                        <p class="text-3xl font-black text-white">{{ dashboardKpis.ecosystem?.upcoming_events || 0 }}</p>
                                        <p class="text-[10px] font-black text-emerald-100 uppercase tracking-widest leading-none">Événements Prévus</p>
                                    </div>
                                </div>
                            </div>

                            <button class="mt-10 w-full py-4 bg-white text-emerald-700 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-emerald-50 transition-colors shadow-lg">
                                Gérer les Partenariats
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Focus Alerts (Student Risks) -->
                <section v-if="dashboardKpis.alerts?.learners_at_risk?.length > 0">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="h-8 w-8 bg-red-100 text-red-600 rounded-full flex items-center justify-center">
                            <ExclamationCircleIcon class="h-5 w-5" />
                        </div>
                        <h2 class="text-xl font-black text-gray-900 tracking-tight">Alertes de Vigilance Apprenants</h2>
                        <div class="h-px flex-1 bg-red-50"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="risk in dashboardKpis.alerts.learners_at_risk" :key="risk.user_id" class="relative group bg-white border border-red-50 p-6 rounded-[2rem] hover:shadow-xl hover:border-red-200 transition-all flex items-center gap-6">
                            <div class="h-16 w-16 bg-gradient-to-br from-red-50 to-orange-50 rounded-2xl flex items-center justify-center text-red-600 border border-red-100">
                                <span class="text-2xl font-black">{{ risk.user.name.charAt(0) }}</span>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-black text-gray-900">{{ risk.user.name }}</h3>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">{{ risk.user.email }}</p>
                                <div class="inline-flex items-center gap-2 px-3 py-1 bg-red-50 text-red-600 rounded-full">
                                    <span class="text-xs font-black">{{ risk.total_absences }} Absences</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section 5: Elite & Performance (Leaderboards) -->
                <section>
                    <div class="flex items-center gap-2 mb-6">
                        <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">Élite & Performance</h2>
                        <div class="h-px flex-1 bg-gray-100"></div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Top Learners Leaderboard -->
                        <div class="bg-white p-8 rounded-[3rem] border border-gray-100 shadow-sm">
                            <div class="flex items-center justify-between mb-8">
                                <div>
                                    <h3 class="text-2xl font-black text-gray-900 tracking-tight leading-none mb-2">Major de Promotion</h3>
                                    <p class="text-sm text-gray-500 font-medium">Top 5 des meilleurs stagiaires par examens</p>
                                </div>
                                <div class="h-12 w-12 bg-yellow-50 text-yellow-600 rounded-2xl flex items-center justify-center border border-yellow-100">
                                    <AcademicCapIcon class="h-6 w-6" />
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div v-for="(learner, index) in dashboardKpis.top_learners" :key="index" class="flex items-center justify-between p-4 bg-gray-50/50 rounded-2xl border border-gray-100/50 hover:bg-white hover:border-gray-200 hover:shadow-lg transition-all group">
                                    <div class="flex items-center gap-4">
                                        <div class="h-10 w-10 flex items-center justify-center font-black rounded-xl" :class="index === 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500'">
                                            #{{ index + 1 }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-black text-gray-900">{{ learner.name }}</p>
                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ learner.email }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-black text-indigo-600 leading-none">{{ learner.score }}</p>
                                        <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mt-1">Pointage Moy.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Trainers Productivity -->
                        <div class="bg-white p-8 rounded-[3rem] border border-gray-100 shadow-sm">
                            <div class="flex items-center justify-between mb-8">
                                <div>
                                    <h3 class="text-2xl font-black text-gray-900 tracking-tight leading-none mb-2">Activités Formateurs</h3>
                                    <p class="text-sm text-gray-500 font-medium">Validations de modules par formateur</p>
                                </div>
                                <div class="h-12 w-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center border border-blue-100">
                                    <ClipboardDocumentCheckIcon class="h-6 w-6" />
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div v-for="(trainer, index) in dashboardKpis.trainers_performance" :key="index" class="flex items-center justify-between p-4 bg-white border border-gray-100 rounded-2xl hover:shadow-xl transition-all">
                                    <div class="flex items-center gap-4">
                                        <div class="h-10 w-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center font-black">
                                            {{ trainer.name.charAt(0) }}
                                        </div>
                                        <p class="text-sm font-black text-gray-900">{{ trainer.name }}</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-2xl font-black text-gray-900">{{ trainer.count }}</span>
                                        <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Validations</span>
                                    </div>
                                </div>
                                <div v-if="!dashboardKpis.trainers_performance || dashboardKpis.trainers_performance.length === 0" class="py-12 flex flex-col items-center text-gray-400 gap-3">
                                    <p class="text-xs font-bold italic">Aucune donnée de performance enregistrée.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Footer Summary View -->
                <footer class="pt-20 border-t border-gray-100 flex flex-col md:flex-row items-center justify-between gap-6 opacity-60 grayscale hover:grayscale-0 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="h-10 w-10 bg-gray-100 rounded-xl flex items-center justify-center text-gray-500">
                           <RocketLaunchIcon class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-sm font-black text-gray-900">E-CRE Platform Engine</p>
                            <p class="text-xs font-bold text-gray-500">Version 2.5 Strategic Intelligence</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-8 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        <span>Sécurisé TLS 1.3</span>
                        <span>API Health: OK</span>
                        <span>Dernier Audit: {{ new Date().toLocaleDateString() }}</span>
                    </div>
                </footer>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-in {
    animation: fadeIn 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
