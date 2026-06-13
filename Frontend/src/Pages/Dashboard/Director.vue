<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import DoughnutChart from '@/Components/Charts/DoughnutChart.vue'
import BarChart from '@/Components/Charts/BarChart.vue'
import AreaChart from '@/Components/Charts/AreaChart.vue'
import RadialGauge from '@/Components/Charts/RadialGauge.vue'
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
    RocketLaunchIcon,
    ClockIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    kpis: Object
})

const dashboardKpis = ref(props.kpis)
const isLoading = ref(false)

const alertsGroupedByTrainer = computed(() => {
    if (!dashboardKpis.value?.alerts?.learners_at_risk) return {}
    
    const alerts = dashboardKpis.value.alerts.learners_at_risk;
    const grouped = {};
    
    alerts.forEach(risk => {
        const trainerName = risk.group?.formateur?.name || 'Non assigné';
        const groupName = risk.group?.nom_groupe || 'Sans groupe';
        
        if (!grouped[trainerName]) {
            grouped[trainerName] = {};
        }
        if (!grouped[trainerName][groupName]) {
            grouped[trainerName][groupName] = [];
        }
        grouped[trainerName][groupName].push(risk);
    });
    
    return grouped;
})

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

const weeklyTrendsLabels = computed(() => {
    if (!dashboardKpis.value || !dashboardKpis.value.weekly_trends) return []
    return dashboardKpis.value.weekly_trends.map(w => w.label)
})

const weeklyTrendsData = computed(() => {
    if (!dashboardKpis.value || !dashboardKpis.value.weekly_trends) return []
    return dashboardKpis.value.weekly_trends.map(w => w.rate)
})

let statsInterval = null

onMounted(() => {
    // Initial fetch to sync possible discrepancies
    fetchStats()

    // Poll stats every 15 seconds to update online users and other metrics in real-time
    statsInterval = setInterval(() => {
        api.get('/stats/director')
            .then(response => {
                dashboardKpis.value = response.data
            })
            .catch(error => {
                console.error('Erreur API lors de la mise à jour automatique:', error)
            })
    }, 15000)
})

onUnmounted(() => {
    if (statsInterval) {
        clearInterval(statsInterval)
    }
})
</script>

<template>
    <Head title="Tableau de Bord Stratégique" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#fcfdfe] pb-24">
            <!-- Hero Header with Premium Gradient -->
            <div class="relative overflow-hidden bg-white border-b border-gray-100 px-4 sm:px-6 lg:px-8 py-6 mb-6 -mt-4 sm:-mt-6 lg:-mt-8">
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
                <section class="animate-in">
                    <div class="flex items-center gap-2 mb-6">
                        <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">Indicateurs Stratégiques</h2>
                        <div class="h-px flex-1 bg-gray-100"></div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Total Apprenants -->
                        <div class="group relative bg-white p-6 rounded-3xl border border-gray-100 shadow-sm transition-all hover:shadow-xl hover:-translate-y-1 overflow-hidden">
                            <div class="absolute top-0 right-0 w-24 h-24 -mr-8 -mt-8 bg-blue-50/50 rounded-full blur-2xl group-hover:bg-blue-100/50 transition-colors"></div>
                            <div class="relative z-10 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Effectif Actif</p>
                                    <h3 class="text-3xl font-black text-gray-900 tracking-tighter">{{ dashboardKpis.total_learners }}</h3>
                                    <span class="text-[10px] font-bold text-slate-400 mt-2 block">Apprenants inscrits</span>
                                </div>
                                <div class="h-12 w-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center border border-blue-100/50 group-hover:bg-blue-600 group-hover:text-white transition-all transform group-hover:rotate-6">
                                    <UsersIcon class="h-6 w-6" />
                                </div>
                            </div>
                        </div>

                        <!-- Utilisateurs en Ligne -->
                        <div class="group relative bg-white p-6 rounded-3xl border border-gray-100 shadow-sm transition-all hover:shadow-xl hover:-translate-y-1 overflow-hidden">
                            <div class="absolute top-0 right-0 w-24 h-24 -mr-8 -mt-8 bg-emerald-50/50 rounded-full blur-2xl group-hover:bg-emerald-100/50 transition-colors"></div>
                            <div class="relative z-10 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Membres Actifs</p>
                                    <h3 class="text-3xl font-black text-gray-900 tracking-tighter flex items-center gap-2">
                                        {{ dashboardKpis.online_users_count || 0 }}
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                        </span>
                                    </h3>
                                    <span class="text-[10px] font-bold text-slate-400 mt-2 block">Connectés en temps réel</span>
                                </div>
                                <div class="h-12 w-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center border border-emerald-100/50 group-hover:bg-emerald-600 group-hover:text-white transition-all transform group-hover:scale-110">
                                    <ComputerDesktopIcon class="h-6 w-6" />
                                </div>
                            </div>
                        </div>

                        <!-- Pedagogical Performance -->
                        <div class="group relative bg-white p-6 rounded-3xl border border-gray-100 shadow-sm transition-all hover:shadow-xl hover:-translate-y-1 overflow-hidden">
                            <div class="absolute top-0 right-0 w-24 h-24 -mr-8 -mt-8 bg-indigo-50/50 rounded-full blur-2xl group-hover:bg-indigo-100/50 transition-colors"></div>
                            <div class="relative z-10 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Moyenne Examens</p>
                                    <h3 class="text-3xl font-black text-gray-900 tracking-tighter">{{ dashboardKpis.pedagogical?.avg_exam_score || 0 }}<span class="text-sm text-gray-400 ml-1">/20</span></h3>
                                    <span class="text-[10px] font-bold text-slate-400 mt-2 block">Validation: {{ dashboardKpis.pedagogical?.chapters_validated_rate || 0 }}% chapitres</span>
                                </div>
                                <div class="h-12 w-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center border border-indigo-100/50 group-hover:bg-indigo-600 group-hover:text-white transition-all transform group-hover:-rotate-6">
                                    <AcademicCapIcon class="h-6 w-6" />
                                </div>
                            </div>
                        </div>

                        <!-- Admission Pipe -->
                        <div class="group relative bg-white p-6 rounded-3xl border border-gray-100 shadow-sm transition-all hover:shadow-xl hover:-translate-y-1 overflow-hidden">
                            <div class="absolute top-0 right-0 w-24 h-24 -mr-8 -mt-8 bg-amber-50/50 rounded-full blur-2xl group-hover:bg-amber-100/50 transition-colors"></div>
                            <div class="relative z-10 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Dossiers Admission</p>
                                    <h3 class="text-3xl font-black text-gray-900 tracking-tighter">{{ dashboardKpis.admissions?.pending || 0 }}</h3>
                                    <span class="text-[10px] font-bold text-slate-400 mt-2 block">Dossiers en attente de validation</span>
                                </div>
                                <div class="h-12 w-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center border border-amber-100/50 group-hover:bg-amber-600 group-hover:text-white transition-all transform group-hover:scale-110">
                                    <BriefcaseIcon class="h-6 w-6" />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section: Assiduity and Attendance analysis -->
                <section class="grid grid-cols-1 lg:grid-cols-5 gap-8 animate-in">
                    <!-- Column 1: Absence Rates RadialGauges (col-span-2) -->
                    <div class="lg:col-span-2 bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h3 class="text-xl font-black text-gray-900 tracking-tight">Taux d'Absences</h3>
                                <p class="text-xs text-gray-500 font-medium">Analyses de fréquentation par profil</p>
                            </div>
                            <div class="h-10 w-10 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center border border-rose-100">
                                <ClockIcon class="h-5 w-5" />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4 py-4 justify-items-center">
                            <!-- Eleves -->
                            <div class="flex flex-col items-center">
                                <RadialGauge 
                                    :value="dashboardKpis.attendance_stats?.learners_absence_rate || 0" 
                                    color="#f43f5e" 
                                    trackColor="#ffe4e6" 
                                    :size="90" 
                                    :strokeWidth="8"
                                />
                                <span class="text-[10px] font-black text-slate-500 uppercase mt-3 text-center">Élèves</span>
                                <span class="text-[9px] font-bold text-rose-500 mt-0.5">{{ dashboardKpis.attendance_stats?.learners_absence_hours || 0 }}h</span>
                            </div>
                            
                            <!-- Stagiaires -->
                            <div class="flex flex-col items-center">
                                <RadialGauge 
                                    :value="dashboardKpis.attendance_stats?.trainees_absence_rate || 0" 
                                    color="#ec4899" 
                                    trackColor="#fce7f3" 
                                    :size="90" 
                                    :strokeWidth="8"
                                />
                                <span class="text-[10px] font-black text-slate-500 uppercase mt-3 text-center">Stagiaires</span>
                                <span class="text-[9px] font-bold text-pink-500 mt-0.5">{{ dashboardKpis.attendance_stats?.trainees_absence_hours || 0 }}h</span>
                            </div>

                            <!-- Formateurs -->
                            <div class="flex flex-col items-center">
                                <RadialGauge 
                                    :value="dashboardKpis.attendance_stats?.trainers_absence_rate || 0" 
                                    color="#f97316" 
                                    trackColor="#ffedd5" 
                                    :size="90" 
                                    :strokeWidth="8"
                                />
                                <span class="text-[10px] font-black text-slate-500 uppercase mt-3 text-center">Formateurs</span>
                                <span class="text-[9px] font-bold text-orange-500 mt-0.5">{{ dashboardKpis.attendance_stats?.trainers_absence_hours || 0 }}h</span>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-gray-50 flex justify-between text-[9px] font-bold text-slate-400">
                            <span>Mise à jour: Temps réel</span>
                            <span class="flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Présences normales</span>
                        </div>
                    </div>

                    <!-- Column 2: Weekly Trends Line Chart (col-span-3) -->
                    <div class="lg:col-span-3 bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-xl font-black text-gray-900 tracking-tight">Tendance de Présentation</h3>
                                <p class="text-xs text-gray-500 font-medium">Évolution du taux global sur les 8 dernières semaines</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-black text-blue-600 bg-blue-50 border border-blue-100 px-3 py-1 rounded-full uppercase tracking-wider">
                                    {{ dashboardKpis.attendance_rate || 0 }}% global
                                </span>
                            </div>
                        </div>

                        <div class="flex-1 min-h-[200px] mt-2">
                            <AreaChart 
                                :labels="weeklyTrendsLabels" 
                                :data="weeklyTrendsData" 
                                label="Taux global (%)" 
                                color="#2563eb"
                            />
                        </div>
                    </div>
                </section>

                <!-- Section 2: Advanced Visualizations -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Academic Success Distribution -->
                    <div class="bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm flex flex-col justify-between transition-all hover:shadow-xl">
                        <div>
                            <!-- Header -->
                            <div class="flex items-start justify-between mb-8">
                                <div>
                                    <h2 class="text-2xl font-black text-gray-900 tracking-tight">Performance Académique</h2>
                                    <p class="text-xs text-gray-500 font-medium mt-1">Taux de validation par filière</p>
                                </div>
                                <div class="h-12 w-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center border border-indigo-100 shrink-0">
                                    <ChartBarIcon class="h-6 w-6" />
                                </div>
                            </div>

                            <!-- Quick stats strip -->
                            <div class="grid grid-cols-3 gap-3 mb-8" v-if="moduleData && moduleData.length > 0">
                                <div class="p-4 bg-emerald-50/60 rounded-2xl border border-emerald-100/60 text-center">
                                    <p class="text-[9px] font-black text-emerald-600 uppercase tracking-widest mb-1">Meilleur</p>
                                    <p class="text-xl font-black text-emerald-700">
                                        {{ Math.max(...moduleData.map(v => v <= 1 ? Math.round(v * 100) : Math.round(v))) }}%
                                    </p>
                                </div>
                                <div class="p-4 bg-indigo-50/60 rounded-2xl border border-indigo-100/60 text-center">
                                    <p class="text-[9px] font-black text-indigo-600 uppercase tracking-widest mb-1">Moyenne</p>
                                    <p class="text-xl font-black text-indigo-700">
                                        {{ Math.round(moduleData.reduce((a, v) => a + (v <= 1 ? v * 100 : v), 0) / moduleData.length) }}%
                                    </p>
                                </div>
                                <div class="p-4 bg-slate-50/80 rounded-2xl border border-slate-100 text-center">
                                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Filières</p>
                                    <p class="text-xl font-black text-slate-700">{{ moduleData.length }}</p>
                                </div>
                            </div>

                            <!-- Bar Chart -->
                            <div class="flex-1 min-h-[260px]">
                                <BarChart :labels="moduleLabels" :data="moduleData" />
                            </div>
                        </div>

                        <!-- Status legend -->
                        <div class="flex items-center gap-4 mt-6 pt-4 border-t border-gray-50">
                            <div class="flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-emerald-500 shrink-0"></span>
                                <span class="text-[10px] font-bold text-gray-400">≥ 75% Excellent</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-amber-400 shrink-0"></span>
                                <span class="text-[10px] font-bold text-gray-400">≥ 50% Satisfaisant</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-red-400 shrink-0"></span>
                                <span class="text-[10px] font-bold text-gray-400">&lt; 50% À surveiller</span>
                            </div>
                        </div>
                    </div>

                    <!-- Gender & Demographics -->
                    <div class="bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm flex flex-col justify-between lg:flex-row gap-8 transition-all hover:shadow-xl">
                        <div class="flex-1 flex flex-col justify-between">
                            <div>
                                <h2 class="text-2xl font-black text-gray-900 tracking-tight mb-1">Démographie</h2>
                                <p class="text-xs text-gray-500 font-medium mb-8">Distribution de genre et inclusion au sein du centre</p>
                                
                                <div class="space-y-4">
                                    <div class="p-5 bg-pink-50/50 rounded-2xl border border-pink-100/30">
                                        <div class="flex justify-between items-center mb-2">
                                            <p class="text-[10px] font-black text-pink-700 uppercase tracking-widest">Femmes</p>
                                            <span class="text-lg font-black text-pink-600">{{ dashboardKpis.gender_parity?.female || 0 }}</span>
                                        </div>
                                        <div class="w-full bg-pink-100/50 h-2 rounded-full">
                                            <div class="bg-pink-500 h-full rounded-full transition-all duration-1000" :style="{ width: (dashboardKpis.gender_parity?.ratio || 0) + '%' }"></div>
                                        </div>
                                    </div>

                                    <div class="p-5 bg-blue-50/50 rounded-2xl border border-blue-100/30">
                                        <div class="flex justify-between items-center mb-2">
                                            <p class="text-[10px] font-black text-blue-700 uppercase tracking-widest">Hommes</p>
                                            <span class="text-lg font-black text-blue-600">{{ dashboardKpis.gender_parity?.male || 0 }}</span>
                                        </div>
                                        <div class="w-full bg-blue-100/50 h-2 rounded-full">
                                            <div class="bg-blue-500 h-full rounded-full transition-all duration-1000" :style="{ width: (100 - (dashboardKpis.gender_parity?.ratio || 0)) + '%' }"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-50 text-[10px] font-bold text-gray-400">
                                Parité cible: 50% / 50%
                            </div>
                        </div>
                        <div class="lg:w-1/2 flex items-center justify-center">
                            <div class="w-full max-w-[200px]">
                                <DoughnutChart :male="dashboardKpis.gender_parity?.male || 0" :female="dashboardKpis.gender_parity?.female || 0" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Operational Control & Ecosystem -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Logistics & Inventory -->
                    <div class="lg:col-span-2 bg-slate-900 p-8 rounded-[2.5rem] border border-slate-800 shadow-2xl relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 blur-[80px] -mr-32 -mt-32"></div>
                        
                        <div class="flex items-center justify-between mb-8 relative z-10">
                            <div>
                                <h2 class="text-2xl font-black text-white tracking-tight">Gestion du Parc</h2>
                                <p class="text-xs text-slate-400 font-medium">Indicateurs de maintenance et logistique</p>
                            </div>
                            <div class="h-12 w-12 bg-white/5 border border-white/10 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <ComputerDesktopIcon class="h-6 w-6 text-indigo-400" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8 relative z-10">
                            <div class="p-5 bg-white/[0.02] border border-white/5 rounded-2xl backdrop-blur-xl">
                                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Actifs Totaux</p>
                                <p class="text-2xl font-black text-white">{{ dashboardKpis.logistics?.total_assets || 0 }}</p>
                                <div class="mt-3 h-1 w-full bg-white/10 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-500" style="width: 100%"></div>
                                </div>
                            </div>
                            <div class="p-5 bg-white/[0.02] border border-white/5 rounded-2xl backdrop-blur-xl">
                                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Indice de Santé</p>
                                <p class="text-2xl font-black text-white">{{ dashboardKpis.operational_hardware || 0 }}%</p>
                                <div class="mt-3 h-1 w-full bg-white/10 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500" :style="{ width: (dashboardKpis.operational_hardware || 0) + '%' }"></div>
                                </div>
                            </div>
                            <div class="p-5 bg-white/[0.02] border border-white/5 rounded-2xl backdrop-blur-xl">
                                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Prêts Actifs</p>
                                <p class="text-2xl font-black text-white">{{ dashboardKpis.logistics?.active_loans || 0 }}</p>
                                <div class="mt-3 h-1 w-full bg-white/10 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500" :style="{ width: (dashboardKpis.logistics?.total_assets ? ((dashboardKpis.logistics.active_loans / dashboardKpis.logistics.total_assets) * 100) : 0) + '%' }"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Defective Assets List -->
                        <div class="bg-red-950/20 border border-red-900/30 rounded-2xl p-5 relative z-10">
                            <div class="flex items-center gap-3 mb-3">
                                <ExclamationCircleIcon class="h-4 w-4 text-red-500" />
                                <h3 class="text-xs font-black text-red-200 uppercase tracking-widest">Alertes Maintenance ({{ dashboardKpis.alerts?.broken_assets?.length || 0 }})</h3>
                            </div>
                            <div v-if="dashboardKpis.alerts?.broken_assets?.length > 0" class="flex flex-wrap gap-2">
                                <div v-for="asset in dashboardKpis.alerts.broken_assets.slice(0, 4)" :key="asset.id" class="px-3 py-1.5 bg-red-900/30 text-red-100 rounded-lg text-xs font-bold border border-red-800/30">
                                    {{ asset.nom }}
                                </div>
                                <span v-if="dashboardKpis.alerts.broken_assets.length > 4" class="text-red-400 text-xs font-bold flex items-center">+{{ dashboardKpis.alerts.broken_assets.length - 4 }} autres</span>
                            </div>
                            <p v-else class="text-xs text-emerald-400 font-bold">Zéro défaut technique détecté.</p>
                        </div>
                    </div>

                    <!-- Ecosystem & Partnerships -->
                    <div class="bg-gradient-to-br from-emerald-600 to-teal-800 p-8 rounded-[2.5rem] shadow-2xl relative overflow-hidden group flex flex-col justify-between">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 blur-[80px] -mr-32 -mt-32"></div>
                        
                        <div class="relative z-10">
                            <div class="flex items-center justify-between mb-8">
                                <div>
                                    <h2 class="text-2xl font-black text-white tracking-tight">Écosystème</h2>
                                    <p class="text-xs text-emerald-100 font-medium opacity-80">Réseau et influence du CRE</p>
                                </div>
                                <div class="h-12 w-12 bg-white/10 border border-white/20 rounded-2xl flex items-center justify-center group-hover:rotate-12 transition-transform">
                                    <BuildingLibraryIcon class="h-6 w-6 text-emerald-100" />
                                </div>
                            </div>

                            <div class="space-y-5">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 bg-white/10 rounded-xl flex items-center justify-center text-white border border-white/5">
                                        <RocketLaunchIcon class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p class="text-2xl font-black text-white leading-none mb-0.5">{{ dashboardKpis.ecosystem?.total_partners || 0 }}</p>
                                        <p class="text-[9px] font-black text-emerald-100/80 uppercase tracking-widest leading-none">Partenaires Actifs</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 bg-white/10 rounded-xl flex items-center justify-center text-white border border-white/5">
                                        <CalendarIcon class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p class="text-2xl font-black text-white leading-none mb-0.5">{{ dashboardKpis.ecosystem?.upcoming_events || 0 }}</p>
                                        <p class="text-[9px] font-black text-emerald-100/80 uppercase tracking-widest leading-none">Événements Prévus</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="relative z-10 mt-8">
                            <button class="w-full py-3 bg-white text-emerald-800 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-emerald-50 transition-colors shadow-lg">
                                Gérer les Partenariats
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Focus Alerts (Student Risks) -->
                <section v-if="dashboardKpis.alerts?.learners_at_risk?.length > 0" class="animate-in">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-4">
                            <div class="h-9 w-9 bg-red-50 text-red-600 rounded-xl flex items-center justify-center border border-red-100">
                                <ExclamationCircleIcon class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-xl font-black text-gray-900 tracking-tight leading-none">Alertes de Vigilance Apprenants</h2>
                                <p class="text-xs text-gray-500 font-medium mt-1">Élèves sous vigilance d'absence prolongée</p>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full bg-red-50 text-red-600 text-xs font-black border border-red-100/50">{{ dashboardKpis.alerts.learners_at_risk.length }}</span>
                        </div>
                        
                        <div class="flex-1 hidden sm:block h-px bg-red-50 mx-4"></div>
                    </div>

                    <div class="space-y-6">
                        <div v-for="(groups, trainerName) in alertsGroupedByTrainer" :key="trainerName" class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm transition-all hover:shadow-md">
                            <div class="flex items-center gap-3 mb-6 border-b border-slate-50 pb-4">
                                <div class="h-10 w-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center border border-indigo-100/50">
                                    <AcademicCapIcon class="h-5 w-5" />
                                </div>
                                <div>
                                    <h3 class="text-base font-black text-gray-900">{{ trainerName }}</h3>
                                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Formateur Principal / Tuteur</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div v-for="(students, groupName) in groups" :key="groupName">
                                    <div class="flex items-center gap-2 mb-4 pl-2 border-l-2 border-indigo-500">
                                        <UsersIcon class="h-4 w-4 text-slate-400" />
                                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">{{ groupName }}</h4>
                                        <span class="px-2 py-0.5 bg-red-50 text-red-600 text-[9px] font-black rounded-full border border-red-100/50">{{ students.length }} alertes</span>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div v-for="risk in students" :key="risk.user_id + '_' + risk.group_id" class="relative group bg-slate-50 border border-slate-100/50 p-4 rounded-2xl hover:shadow-lg hover:border-red-200 hover:bg-white transition-all flex items-center gap-4">
                                            <div class="h-10 w-10 bg-gradient-to-br from-red-50 to-orange-50 rounded-xl flex items-center justify-center text-red-600 border border-red-100 shrink-0 font-black">
                                                {{ risk.user?.name?.charAt(0) || '?' }}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h3 class="font-bold text-sm text-gray-900 truncate" :title="risk.user?.name">{{ risk.user?.name }}</h3>
                                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-2 truncate" :title="risk.user?.email">{{ risk.user?.email }}</p>
                                                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-red-50 text-red-600 rounded-full border border-red-100">
                                                    <span class="relative flex h-1.5 w-1.5">
                                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-red-500"></span>
                                                    </span>
                                                    <span class="text-[9px] font-black uppercase tracking-wider">{{ risk.total_absences }} Absences</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section 5: Elite & Performance (Leaderboards) -->
                <section class="animate-in">
                    <div class="flex items-center gap-2 mb-6">
                        <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">Élite & Performance</h2>
                        <div class="h-px flex-1 bg-gray-100"></div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Top Learners Leaderboard -->
                        <div class="bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm flex flex-col justify-between transition-all hover:shadow-xl">
                            <div>
                                <div class="flex items-center justify-between mb-8">
                                    <div>
                                        <h3 class="text-xl font-black text-gray-900 tracking-tight leading-none mb-1">Major de Promotion</h3>
                                        <p class="text-xs text-gray-500 font-medium">Top 5 des meilleurs stagiaires par examens</p>
                                    </div>
                                    <div class="h-10 w-10 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center border border-amber-100">
                                        <AcademicCapIcon class="h-5 w-5" />
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div v-for="(learner, index) in dashboardKpis.top_learners" :key="index" class="flex items-center justify-between p-3.5 bg-slate-50/50 rounded-2xl border border-slate-100/50 hover:bg-white hover:border-slate-200 hover:shadow-md transition-all group">
                                        <div class="flex items-center gap-3">
                                            <div class="h-9 w-9 flex items-center justify-center font-black rounded-lg text-xs" :class="index === 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500'">
                                                #{{ index + 1 }}
                                            </div>
                                            <div>
                                                <p class="text-xs font-black text-gray-900">{{ learner.name }}</p>
                                                <p class="text-[8px] text-gray-400 font-bold uppercase tracking-widest">{{ learner.email }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-base font-black text-indigo-600 leading-none">{{ learner.score }}</p>
                                            <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mt-1">Pointage Moy.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Trainers Productivity -->
                        <div class="bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm flex flex-col justify-between transition-all hover:shadow-xl">
                            <div>
                                <div class="flex items-center justify-between mb-8">
                                    <div>
                                        <h3 class="text-xl font-black text-gray-900 tracking-tight leading-none mb-1">Activités Formateurs</h3>
                                        <p class="text-xs text-gray-500 font-medium">Validations de modules par formateur</p>
                                    </div>
                                    <div class="h-10 w-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center border border-blue-100">
                                        <ClipboardDocumentCheckIcon class="h-5 w-5" />
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div v-for="(trainer, index) in dashboardKpis.trainers_performance" :key="index" class="flex items-center justify-between p-3.5 bg-slate-50/50 border border-slate-100/50 rounded-2xl hover:bg-white hover:border-slate-200 hover:shadow-md transition-all">
                                        <div class="flex items-center gap-3">
                                            <div class="h-9 w-9 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center font-black text-xs border border-indigo-100/50">
                                                {{ trainer.name.charAt(0) }}
                                            </div>
                                            <p class="text-xs font-black text-gray-900">{{ trainer.name }}</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xl font-black text-slate-800">{{ trainer.count }}</span>
                                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Validations</span>
                                        </div>
                                    </div>
                                    <div v-if="!dashboardKpis.trainers_performance || dashboardKpis.trainers_performance.length === 0" class="py-8 flex flex-col items-center text-gray-400 gap-2">
                                        <p class="text-xs font-bold italic">Aucune donnée de performance enregistrée.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Footer Summary View -->
                <footer class="pt-16 border-t border-gray-100 flex flex-col md:flex-row items-center justify-between gap-6 opacity-60 hover:opacity-100 transition-opacity">
                    <div class="flex items-center gap-4">
                        <div class="h-10 w-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-500 border border-slate-200">
                           <RocketLaunchIcon class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-sm font-black text-gray-900">E-CRE Platform Engine</p>
                            <p class="text-[10px] font-bold text-gray-500">Version 2.5 Strategic Intelligence</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-8 text-[9px] font-black text-gray-400 uppercase tracking-widest">
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
