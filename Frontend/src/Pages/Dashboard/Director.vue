<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import DoughnutChart from '@/Components/Charts/DoughnutChart.vue'
import ModuleInfographic from '@/Components/Charts/ModuleInfographic.vue'
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

const selectedPeriod = ref('15J')

const filteredDailyTrends = computed(() => {
    if (!dashboardKpis.value || !dashboardKpis.value.daily_trends) return []
    const trends = dashboardKpis.value.daily_trends
    if (selectedPeriod.value === '7J') {
        return trends.slice(-7)
    } else if (selectedPeriod.value === '15J') {
        return trends.slice(-15)
    } else if (selectedPeriod.value === '30J') {
        return trends.slice(-30)
    }
    return trends
})

const dailyTrendsLabels = computed(() => {
    return filteredDailyTrends.value.map(w => w.label)
})

const dailyTrendsData = computed(() => {
    return filteredDailyTrends.value.map(w => w.rate)
})

const periodStats = computed(() => {
    const trends = filteredDailyTrends.value
    if (trends.length === 0) {
        return {
            totalAbsences: 0,
            averageRate: '0.0',
            initialRate: '0.0',
            finalRate: '0.0',
            highestRate: '0.0',
            lowestRate: '0.0',
            variation: '0.0%'
        }
    }

    const rates = trends.map(t => t.rate)
    const totalAbs = trends.reduce((sum, t) => sum + (t.absences_count || 0), 0)
    const avgRate = (rates.reduce((sum, r) => sum + r, 0) / rates.length).toFixed(1)
    const initRate = trends[0].rate.toFixed(1)
    const finRate = trends[trends.length - 1].rate.toFixed(1)
    const highRate = Math.max(...rates).toFixed(1)
    const lowRate = Math.min(...rates).toFixed(1)
    const varValue = (trends[trends.length - 1].rate - trends[0].rate).toFixed(1)
    const varFormatted = (parseFloat(varValue) >= 0 ? '+' : '') + varValue + '%'

    return {
        totalAbsences: totalAbs,
        averageRate: avgRate,
        initialRate: initRate,
        finalRate: finRate,
        highestRate: highRate,
        lowestRate: lowRate,
        variation: varFormatted
    }
})

const showAbsenceModal = ref(false)
const selectedLearner = ref(null)
const learnerAbsences = ref([])
const isLoadingAbsences = ref(false)

const getFrenchDayName = (dayNumber) => {
    const days = {
        1: 'Lundi',
        2: 'Mardi',
        3: 'Mercredi',
        4: 'Jeudi',
        5: 'Vendredi',
        6: 'Samedi',
        7: 'Dimanche'
    }
    return days[dayNumber] || 'Inconnu'
}

const formatTime = (timeString) => {
    if (!timeString) return ''
    const parts = timeString.split(':')
    if (parts.length >= 2) {
        return `${parts[0]}:${parts[1]}`
    }
    return timeString
}

const formatDateFrench = (dateString) => {
    if (!dateString) return ''
    try {
        const date = new Date(dateString)
        const formatted = date.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
        return formatted.charAt(0).toUpperCase() + formatted.slice(1)
    } catch (e) {
        return dateString
    }
}

const fetchLearnerAbsences = async (userId, groupId) => {
    isLoadingAbsences.value = true
    showAbsenceModal.value = true
    selectedLearner.value = null
    learnerAbsences.value = []
    try {
        const response = await api.get(`/stats/director/learner-absences/${userId}/${groupId}`)
        selectedLearner.value = response.data.user
        selectedLearner.value.group = response.data.group
        learnerAbsences.value = response.data.absences
    } catch (error) {
        console.error("Erreur lors de la récupération des absences :", error)
    } finally {
        isLoadingAbsences.value = false
    }
}

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
            <div class="relative overflow-hidden bg-white border-b border-gray-100 px-4 sm:px-6 lg:px-8 py-3 mb-4 -mt-6 sm:-mt-8 lg:-mt-10">
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-[600px] h-[600px] bg-blue-50/40 rounded-full blur-[100px] opacity-60"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-96 h-96 bg-emerald-50/40 rounded-full blur-[80px] opacity-40"></div>
                
                <div class="max-w-7xl mx-auto relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-8">
                    <div class="max-w-3xl">
                        <div class="flex items-center gap-3 mb-2">
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
                        <p class="text-lg text-gray-500 mt-2 font-medium leading-relaxed">
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
                                <p class="text-xs text-gray-500 font-medium">
                                    Évolution du taux global 
                                    <span v-if="selectedPeriod === '7J'">sur les 7 derniers jours d'activité</span>
                                    <span v-else-if="selectedPeriod === '15J'">sur les 15 derniers jours d'activité</span>
                                    <span v-else-if="selectedPeriod === '30J'">sur les 30 derniers jours d'activité</span>
                                    <span v-else>sur l'ensemble des jours d'activité</span>
                                </p>
                            </div>
                            <div class="flex items-center gap-4">
                                <!-- Period Selector (Stock Chart Style) -->
                                <div class="flex items-center bg-slate-50 p-1 rounded-xl border border-slate-100/80">
                                    <button 
                                        v-for="period in ['7J', '15J', '30J', 'Tout']" 
                                        :key="period"
                                        @click="selectedPeriod = period"
                                        class="px-2.5 py-1 text-[10px] font-black rounded-lg transition-all duration-200"
                                        :class="selectedPeriod === period 
                                            ? 'bg-white text-blue-600 shadow-sm border border-slate-100/50' 
                                            : 'text-slate-400 hover:text-slate-600'"
                                    >
                                        {{ period }}
                                    </button>
                                </div>
                                <span class="text-xs font-black text-blue-600 bg-blue-50 border border-blue-100 px-3 py-1 rounded-full uppercase tracking-wider">
                                    {{ dashboardKpis.attendance_rate || 0 }}% global
                                </span>
                            </div>
                        </div>

                        <!-- Inner Grid splitting Chart and Statistics table (Stock Style) -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-2 flex-1 items-stretch">
                            <!-- Left: The Line Chart -->
                            <div class="lg:col-span-2 flex flex-col justify-center min-h-[220px]">
                                <AreaChart 
                                    :labels="dailyTrendsLabels" 
                                    :data="dailyTrendsData" 
                                    label="Taux global (%)" 
                                    color="#2563eb"
                                />
                            </div>

                            <!-- Right: The Stats Panel (Stock Table Style) -->
                            <div class="lg:col-span-1 border-t lg:border-t-0 lg:border-l border-slate-100 lg:pl-6 pt-6 lg:pt-0 flex flex-col justify-between">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between py-1.5 border-b border-slate-50">
                                        <span class="text-xs font-bold text-slate-400">Total Absences</span>
                                        <span class="text-xs font-extrabold text-slate-800">{{ periodStats.totalAbsences }}</span>
                                    </div>
                                    <div class="flex items-center justify-between py-1.5 border-b border-slate-50">
                                        <span class="text-xs font-bold text-slate-400">Taux Moyen</span>
                                        <span class="text-xs font-extrabold text-blue-600 bg-blue-50 px-2 py-0.5 rounded">{{ periodStats.averageRate }}%</span>
                                    </div>
                                    <div class="flex items-center justify-between py-1.5 border-b border-slate-50">
                                        <span class="text-xs font-bold text-slate-400">Plus Haut</span>
                                        <span class="text-xs font-extrabold text-emerald-600">{{ periodStats.highestRate }}%</span>
                                    </div>
                                    <div class="flex items-center justify-between py-1.5 border-b border-slate-50">
                                        <span class="text-xs font-bold text-slate-400">Plus Bas</span>
                                        <span class="text-xs font-extrabold text-rose-600">{{ periodStats.lowestRate }}%</span>
                                    </div>
                                    <div class="flex items-center justify-between py-1.5 border-b border-slate-50">
                                        <span class="text-xs font-bold text-slate-400">Dernier Taux</span>
                                        <span class="text-xs font-extrabold text-slate-800">{{ periodStats.finalRate }}%</span>
                                    </div>
                                    <div class="flex items-center justify-between py-1.5">
                                        <span class="text-xs font-bold text-slate-400">Variation</span>
                                        <span class="text-xs font-black" :class="parseFloat(periodStats.variation) >= 0 ? 'text-emerald-500' : 'text-rose-500'">
                                            {{ periodStats.variation }}
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-4 bg-slate-50 p-3 rounded-2xl border border-slate-100/50">
                                    <p class="text-[9px] font-bold text-slate-400 leading-normal font-sans">
                                        * Les calculs sont effectués dynamiquement sur la base des données de présence pour la période sélectionnée.
                                    </p>
                                </div>
                            </div>
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
                    <div class="relative bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950 p-8 rounded-[2.5rem] border border-white/10 shadow-2xl overflow-hidden group">
                        <!-- Background glow effects -->
                        <div class="absolute -top-16 -right-16 w-48 h-48 bg-blue-500/20 rounded-full blur-[60px] pointer-events-none"></div>
                        <div class="absolute -bottom-12 -left-12 w-40 h-40 bg-pink-500/20 rounded-full blur-[60px] pointer-events-none"></div>
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_2px_2px,rgba(255,255,255,0.03)_1px,transparent_0)] bg-[size:32px_32px] pointer-events-none"></div>

                        <div class="relative z-10 flex flex-col lg:flex-row gap-8 items-center">
                            <!-- Left: Stats -->
                            <div class="flex-1 flex flex-col justify-between w-full">
                                <!-- Header -->
                                <div class="mb-7">
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/5 border border-white/10 mb-4">
                                        <span class="h-1.5 w-1.5 rounded-full bg-pink-400 animate-pulse"></span>
                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-[0.25em]">Parité Cible: 50 / 50</span>
                                    </div>
                                    <h2 class="text-2xl font-black text-white tracking-tight mb-1">Démographie</h2>
                                    <p class="text-xs text-slate-500 font-medium">Distribution de genre au sein du centre</p>
                                </div>

                                <!-- Female stat -->
                                <div class="group/card p-5 rounded-2xl bg-white/[0.04] border border-white/10 hover:border-pink-500/30 transition-all duration-300 hover:bg-pink-500/5 mb-3">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="h-8 w-8 rounded-xl bg-gradient-to-br from-pink-500/30 to-pink-600/10 border border-pink-500/20 flex items-center justify-center">
                                            <span class="text-[10px] font-black text-pink-400">F</span>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-[9px] font-black text-pink-400 uppercase tracking-widest">Femmes</p>
                                        </div>
                                        <span class="text-2xl font-black text-white tabular-nums">{{ dashboardKpis.gender_parity?.female || 0 }}</span>
                                    </div>
                                    <div class="w-full bg-white/10 h-1.5 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r from-pink-400 to-pink-600 transition-all duration-1000" :style="{ width: (dashboardKpis.gender_parity?.ratio || 0) + '%' }"></div>
                                    </div>
                                    <p class="text-[9px] font-bold text-slate-500 mt-1.5">{{ Math.round(dashboardKpis.gender_parity?.ratio || 0) }}% des apprenants</p>
                                </div>

                                <!-- Male stat -->
                                <div class="group/card p-5 rounded-2xl bg-white/[0.04] border border-white/10 hover:border-blue-500/30 transition-all duration-300 hover:bg-blue-500/5">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="h-8 w-8 rounded-xl bg-gradient-to-br from-blue-500/30 to-blue-600/10 border border-blue-500/20 flex items-center justify-center">
                                            <span class="text-[10px] font-black text-blue-400">H</span>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-[9px] font-black text-blue-400 uppercase tracking-widest">Hommes</p>
                                        </div>
                                        <span class="text-2xl font-black text-white tabular-nums">{{ dashboardKpis.gender_parity?.male || 0 }}</span>
                                    </div>
                                    <div class="w-full bg-white/10 h-1.5 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r from-blue-400 to-blue-600 transition-all duration-1000" :style="{ width: (100 - (dashboardKpis.gender_parity?.ratio || 0)) + '%' }"></div>
                                    </div>
                                    <p class="text-[9px] font-bold text-slate-500 mt-1.5">{{ Math.round(100 - (dashboardKpis.gender_parity?.ratio || 0)) }}% des apprenants</p>
                                </div>
                            </div>

                            <!-- Right: Donut -->
                            <div class="shrink-0 flex flex-col items-center justify-center">
                                <DoughnutChart :male="dashboardKpis.gender_parity?.male || 0" :female="dashboardKpis.gender_parity?.female || 0" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Répartition par Module -->
                <div>
                    <div class="flex items-center gap-2 mb-6">
                        <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">Répartition par Module</h2>
                        <div class="h-px flex-1 bg-gray-100"></div>
                        <span class="text-[9px] font-black text-gray-300 uppercase tracking-widest">{{ dashboardKpis.module_distribution?.length || 0 }} modules</span>
                    </div>

                    <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm p-8 overflow-hidden">
                        <div class="mb-6">
                            <h2 class="text-2xl font-black text-gray-900 tracking-tight">Distribution des Apprenants</h2>
                            <p class="text-xs text-gray-500 font-medium mt-1">Effectif actif réparti par module de formation</p>
                        </div>
                        <ModuleInfographic :modules="dashboardKpis.module_distribution || []" />
                    </div>
                </div>


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

                <!-- Section 5: Elite & Performance (Leaderboards) -->
                <section class="animate-in">
                    <div class="flex items-center gap-2 mb-6">
                        <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">Élite & Performance</h2>
                        <div class="h-px flex-1 bg-gray-100"></div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
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
                        
                        <!-- Trainers Availability -->
                        <div class="bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm flex flex-col justify-between transition-all hover:shadow-xl">
                            <div>
                                <div class="flex items-center justify-between mb-8">
                                    <div>
                                        <h3 class="text-xl font-black text-gray-900 tracking-tight leading-none mb-1">Disponibilité</h3>
                                        <p class="text-xs text-gray-500 font-medium">Charges horaires des formateurs</p>
                                    </div>
                                    <div class="h-10 w-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center border border-emerald-100">
                                        <ClockIcon class="h-5 w-5" />
                                    </div>
                                </div>

                                <div class="space-y-3 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                                    <div v-for="(trainer, index) in dashboardKpis.trainers_availability" :key="index" class="flex items-center justify-between p-3.5 bg-slate-50/50 border border-slate-100/50 rounded-2xl hover:bg-white hover:border-slate-200 hover:shadow-md transition-all group">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <div class="h-9 w-9 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center font-black text-xs border border-emerald-100/50 shrink-0">
                                                {{ trainer.name.charAt(0) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-black text-gray-900 truncate" :title="trainer.name">{{ trainer.name }}</p>
                                                <div v-if="trainer.active_groups && trainer.active_groups.length > 0" class="flex flex-wrap gap-1 mt-1">
                                                    <span v-for="group in trainer.active_groups.slice(0, 2)" :key="group" class="text-[8px] font-black text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-100/50 truncate max-w-[80px]" :title="group">{{ group }}</span>
                                                    <span v-if="trainer.active_groups.length > 2" class="text-[8px] font-black text-gray-400 mt-0.5">+{{ trainer.active_groups.length - 2 }}</span>
                                                </div>
                                                <span v-else class="text-[8px] font-black text-emerald-500 uppercase tracking-widest mt-1 inline-block">100% Disponible</span>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0 ml-2">
                                            <span class="text-sm font-black" :class="trainer.total_minutes > 0 ? 'text-slate-800' : 'text-emerald-500'">{{ trainer.total_hours }}</span>
                                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">/ sem</p>
                                        </div>
                                    </div>
                                    <div v-if="!dashboardKpis.trainers_availability || dashboardKpis.trainers_availability.length === 0" class="py-8 flex flex-col items-center text-gray-400 gap-2">
                                        <p class="text-xs font-bold italic">Aucune donnée de disponibilité.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

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
                                        <div v-for="risk in students" :key="risk.user_id + '_' + risk.group_id" @click="fetchLearnerAbsences(risk.user_id, risk.group_id)" class="relative group bg-slate-50 border border-slate-100/50 p-4 rounded-2xl hover:shadow-lg hover:border-red-200 hover:bg-white transition-all flex items-center gap-4 cursor-pointer hover:scale-[1.02]">
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

                <!-- Modal pour les Détails d'Absence -->
                <transition name="fade">
                    <div v-if="showAbsenceModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300">
                        <div class="relative w-full max-w-2xl bg-white rounded-[2rem] border border-slate-100 shadow-2xl p-8 overflow-hidden transform transition-all max-h-[90vh] flex flex-col animate-in">
                            <!-- Décorations d'arrière-plan -->
                            <div class="absolute top-0 right-0 w-48 h-48 -mr-16 -mt-16 bg-red-50/50 rounded-full blur-2xl pointer-events-none"></div>
                            <div class="absolute bottom-0 left-0 w-48 h-48 -ml-16 -mb-16 bg-indigo-50/30 rounded-full blur-2xl pointer-events-none"></div>

                            <!-- Header du Modal -->
                            <div class="flex items-start justify-between pb-6 border-b border-slate-100 relative z-10 shrink-0">
                                <div class="flex items-center gap-4">
                                    <div class="h-14 w-14 bg-gradient-to-br from-red-50 to-orange-50 rounded-2xl flex items-center justify-center text-red-600 border border-red-100 shrink-0 font-black text-xl">
                                        {{ selectedLearner?.name?.charAt(0) || '?' }}
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-black text-gray-900 tracking-tight">Détails des Absences</h3>
                                        <p class="text-xs font-bold text-gray-500 mt-0.5">{{ selectedLearner?.name }}</p>
                                        <p class="text-[9px] font-black text-indigo-600 uppercase tracking-wider mt-1 bg-indigo-50 border border-indigo-100/50 px-2 py-0.5 rounded-full inline-block">
                                            Groupe: {{ selectedLearner?.group?.nom_groupe || '...' }}
                                        </p>
                                    </div>
                                </div>
                                <button @click="showAbsenceModal = false" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-xl transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Corps du Modal -->
                            <div class="flex-1 overflow-y-auto py-6 pr-1 custom-scrollbar relative z-10">
                                <!-- Spinner de Chargement -->
                                <div v-if="isLoadingAbsences" class="flex flex-col items-center justify-center py-12 gap-3">
                                    <div class="animate-spin rounded-full h-10 w-10 border-4 border-slate-200 border-t-indigo-600"></div>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest animate-pulse">Chargement des données...</p>
                                </div>

                                <!-- Contenu des Absences -->
                                <div v-else>
                                    <div v-if="learnerAbsences.length === 0" class="flex flex-col items-center justify-center py-12 text-slate-400 gap-2">
                                        <p class="text-sm font-bold italic">Aucune absence enregistrée pour cet apprenant.</p>
                                    </div>
                                    <div v-else class="space-y-4">
                                        <div v-for="absence in learnerAbsences" :key="absence.id" class="flex items-center justify-between p-4 bg-slate-50/50 hover:bg-slate-50 border border-slate-100 hover:border-slate-200 rounded-2xl transition-all group">
                                            <div class="flex items-center gap-3.5">
                                                <div class="h-10 w-10 rounded-xl flex items-center justify-center border shrink-0"
                                                    :class="absence.status === 'absent_non_justifie' 
                                                        ? 'bg-red-50 text-red-600 border-red-100/50' 
                                                        : 'bg-amber-50 text-amber-600 border-amber-100/50'">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-black text-gray-900 leading-tight">
                                                        {{ formatDateFrench(absence.date) }}
                                                    </p>
                                                    <div class="flex items-center gap-1.5 mt-1 text-[10px] font-bold text-gray-400">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                        </svg>
                                                        <span>{{ getFrenchDayName(absence.day_of_week) }} ({{ formatTime(absence.start_time) }} - {{ formatTime(absence.end_time) }})</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border"
                                                    :class="absence.status === 'absent_non_justifie' 
                                                        ? 'bg-red-50 text-red-700 border-red-100' 
                                                        : 'bg-amber-50 text-amber-700 border-amber-100'">
                                                    {{ absence.status === 'absent_non_justifie' ? 'Non Justifié' : 'Justifié' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer du Modal -->
                            <div class="pt-6 border-t border-slate-100 flex justify-end shrink-0 relative z-10">
                                <button @click="showAbsenceModal = false" class="px-6 py-3 bg-slate-900 text-white hover:bg-black rounded-xl font-bold text-xs uppercase tracking-wider transition-all active:scale-95">
                                    Fermer
                                </button>
                            </div>
                        </div>
                    </div>
                </transition>
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

/* Custom scrollbar for small areas */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
