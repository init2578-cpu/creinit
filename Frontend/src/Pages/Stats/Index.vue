<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import {
    ChartBarIcon,
    ArrowUpIcon,
    ArrowDownIcon,
    MinusIcon,
    PresentationChartLineIcon,
    AcademicCapIcon,
    UserGroupIcon,
    ClipboardDocumentCheckIcon,
    ExclamationCircleIcon,
    CheckCircleIcon,
    ClockIcon,
    ShieldCheckIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    growth_data: Array,
    module_performance: Array,
    attendance_stats: Object
})

// --- Growth chart ---
const maxGrowthCount = computed(() => {
    if (!props.growth_data?.length) return 1
    return Math.max(...props.growth_data.map(i => i.count), 1)
})

// --- Module performance ---
const maxCertificates = computed(() => {
    if (!props.module_performance?.length) return 1
    return Math.max(...props.module_performance.map(i => i.certificates), 1)
})

// --- Attendance helpers ---
const stats = computed(() => props.attendance_stats || {})
const weeklyTrends = computed(() => stats.value.weekly_trends || [])
const groupBreakdown = computed(() => stats.value.group_breakdown || [])
const statusSummary = computed(() => stats.value.status_summary || { present: 0, absent: 0, late: 0, justified: 0 })
const overallRate = computed(() => stats.value.overall_rate || 0)
const targetRate = computed(() => stats.value.target_rate || 90)
const trendDirection = computed(() => stats.value.trend_direction || 'stable')

const maxWeeklyRate = computed(() => {
    if (!weeklyTrends.value.length) return 100
    return Math.max(...weeklyTrends.value.map(w => w.rate), 1)
})

const totalAttendances = computed(() => {
    const s = statusSummary.value
    return (s.present || 0) + (s.absent || 0) + (s.late || 0) + (s.justified || 0)
})

const rateColor = (rate) => {
    if (rate >= 90) return 'text-emerald-400'
    if (rate >= 75) return 'text-amber-400'
    return 'text-red-400'
}
const rateBarColor = (rate) => {
    if (rate >= 90) return 'from-emerald-500 to-emerald-400'
    if (rate >= 75) return 'from-amber-500 to-amber-400'
    return 'from-red-500 to-red-400'
}
const weekBarColor = (rate) => {
    if (rate >= 90) return 'from-emerald-500/80 to-emerald-400'
    if (rate >= 75) return 'from-blue-500/80 to-indigo-400'
    return 'from-red-500/70 to-orange-400'
}
</script>

<template>
    <Head title="Statistiques & Rapports" />
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-10">

            <!-- Header -->
            <header class="relative overflow-hidden bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm">
                <div class="absolute top-0 right-0 -mt-8 -mr-8 w-64 h-64 bg-indigo-50/50 rounded-full blur-3xl"></div>
                <div class="relative z-10">
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-50 text-indigo-700 text-[10px] font-black uppercase tracking-[0.2em] rounded-full border border-indigo-100 mb-4">
                        Analytiques
                    </span>
                    <h1 class="text-4xl font-black text-gray-900 tracking-tight mb-2">Statistiques & Rapports</h1>
                    <p class="text-lg text-gray-500 font-medium max-w-2xl">Rapports détaillés sur la croissance, la performance et l'engagement des apprenants.</p>
                </div>
            </header>

            <!-- Growth + Module -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- User Growth -->
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-xl font-black text-gray-900 flex items-center gap-3">
                            <PresentationChartLineIcon class="h-6 w-6 text-indigo-600" />
                            Croissance Utilisateurs
                        </h2>
                        <span class="px-3 py-1 bg-green-50 text-green-600 rounded-lg text-xs font-black flex items-center gap-1 border border-green-100">
                            <ArrowUpIcon class="h-3 w-3" /> 12% ce mois
                        </span>
                    </div>
                    <div class="h-64 flex items-end gap-4 px-4 pb-2 border-b border-gray-100">
                        <div v-for="item in growth_data" :key="item.month" class="flex-1 flex flex-col items-center justify-end h-full group relative">
                            <div class="absolute -top-10 opacity-0 group-hover:opacity-100 transition-all bg-gray-900 text-white text-[10px] font-black px-2.5 py-1 rounded-lg shadow-xl z-20 pointer-events-none">
                                {{ item.count }} utilisateur{{ item.count > 1 ? 's' : '' }}
                            </div>
                            <div class="w-full bg-gradient-to-t from-indigo-500/80 to-indigo-600 rounded-2xl transition-all duration-500 hover:from-indigo-600 hover:to-indigo-700 shadow-sm hover:shadow-md cursor-pointer"
                                :style="{ height: ((item.count / maxGrowthCount) * 100) + '%' }">
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-4 px-4 mt-3">
                        <span v-for="item in growth_data" :key="item.month" class="flex-1 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ item.month }}</span>
                    </div>
                </div>

                <!-- Module Performance -->
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100">
                    <h2 class="text-xl font-black text-gray-900 flex items-center gap-3 mb-8">
                        <ChartBarIcon class="h-6 w-6 text-orange-500" />
                        Certifications par Module
                    </h2>
                    <div class="space-y-6">
                        <div v-for="module in module_performance" :key="module.name" class="space-y-2">
                            <div class="flex justify-between items-center text-sm">
                                <span class="font-bold text-gray-700 truncate max-w-[80%]" :title="module.name">{{ module.name }}</span>
                                <span class="font-black text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-md text-xs">{{ module.certificates }}</span>
                            </div>
                            <div class="h-3 bg-gray-50 rounded-full overflow-hidden border border-gray-100/50">
                                <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-full transition-all duration-1000"
                                    :style="{ width: ((module.certificates / maxCertificates) * 100) + '%' }"></div>
                            </div>
                        </div>
                        <div v-if="!module_performance?.length" class="text-center py-12 text-gray-400 flex flex-col items-center gap-2">
                            <AcademicCapIcon class="h-8 w-8 text-gray-300" />
                            <span>Aucune donnée disponible.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════ -->
            <!-- TAUX D'ÉMARGEMENT — Section principale                     -->
            <!-- ═══════════════════════════════════════════════════════════ -->
            <div class="bg-gray-950 rounded-[3rem] overflow-hidden shadow-2xl">

                <!-- Header du bloc -->
                <div class="px-10 pt-10 pb-0">
                    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-6">
                        <div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/10 text-indigo-300 text-[10px] font-black uppercase tracking-[0.2em] rounded-full border border-white/10 mb-3">
                                Présence Physique
                            </span>
                            <h2 class="text-3xl font-black text-white tracking-tight mb-1">Taux d'Émargement</h2>
                            <p class="text-gray-500 font-medium text-sm">Analyse des présences sur les 8 dernières semaines</p>
                        </div>

                        <!-- KPI Cards -->
                        <div class="flex flex-wrap gap-3">
                            <!-- Overall Rate -->
                            <div class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-center min-w-[110px]">
                                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Taux Global</p>
                                <p class="text-3xl font-black" :class="rateColor(overallRate)">{{ overallRate }}%</p>
                                <div class="flex items-center justify-center gap-1 mt-1">
                                    <ArrowUpIcon v-if="trendDirection === 'up'" class="h-3 w-3 text-emerald-400" />
                                    <ArrowDownIcon v-else-if="trendDirection === 'down'" class="h-3 w-3 text-red-400" />
                                    <MinusIcon v-else class="h-3 w-3 text-gray-500" />
                                    <span class="text-[10px] font-bold" :class="trendDirection === 'up' ? 'text-emerald-400' : trendDirection === 'down' ? 'text-red-400' : 'text-gray-500'">
                                        {{ trendDirection === 'up' ? 'En hausse' : trendDirection === 'down' ? 'En baisse' : 'Stable' }}
                                    </span>
                                </div>
                            </div>
                            <!-- Target -->
                            <div class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-center min-w-[110px]">
                                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Objectif</p>
                                <p class="text-3xl font-black text-gray-400">{{ targetRate }}%</p>
                                <p class="text-[10px] text-gray-600 mt-1 font-bold">
                                    {{ overallRate >= targetRate ? '✓ Atteint' : (targetRate - overallRate).toFixed(1) + '% manquant' }}
                                </p>
                            </div>
                            <!-- Sessions -->
                            <div class="bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-center min-w-[110px]">
                                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Séances</p>
                                <p class="text-3xl font-black text-white">{{ stats.total_sessions || 0 }}</p>
                                <p class="text-[10px] text-gray-600 mt-1 font-bold">{{ stats.total_students || 0 }} apprenants</p>
                            </div>
                        </div>
                    </div>

                    <!-- Status summary pills -->
                    <div class="flex flex-wrap gap-3 mt-6 pb-8 border-b border-white/5">
                        <div class="flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 rounded-xl px-4 py-2">
                            <CheckCircleIcon class="h-4 w-4 text-emerald-400" />
                            <span class="text-xs font-black text-emerald-300">{{ statusSummary.present }} Présents</span>
                        </div>
                        <div class="flex items-center gap-2 bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-2">
                            <ExclamationCircleIcon class="h-4 w-4 text-red-400" />
                            <span class="text-xs font-black text-red-300">{{ statusSummary.absent }} Absents</span>
                        </div>
                        <div class="flex items-center gap-2 bg-amber-500/10 border border-amber-500/20 rounded-xl px-4 py-2">
                            <ClockIcon class="h-4 w-4 text-amber-400" />
                            <span class="text-xs font-black text-amber-300">{{ statusSummary.late }} Retards</span>
                        </div>
                        <div class="flex items-center gap-2 bg-blue-500/10 border border-blue-500/20 rounded-xl px-4 py-2">
                            <ShieldCheckIcon class="h-4 w-4 text-blue-400" />
                            <span class="text-xs font-black text-blue-300">{{ statusSummary.justified }} Justifiés</span>
                        </div>
                        <div v-if="totalAttendances > 0" class="flex items-center gap-2 bg-white/5 border border-white/10 rounded-xl px-4 py-2 ml-auto">
                            <span class="text-[10px] font-black text-gray-400">{{ totalAttendances }} enregistrements au total</span>
                        </div>
                    </div>
                </div>

                <!-- Graphique hebdomadaire -->
                <div class="px-10 py-8">
                    <div v-if="weeklyTrends.length > 0">
                        <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-6">Évolution semaine par semaine</p>

                        <!-- Bars -->
                        <div class="flex items-end gap-3 h-52 pb-0 border-b border-white/5 relative">
                            <!-- Gridlines -->
                            <div class="absolute inset-0 flex flex-col justify-between pb-0 pointer-events-none">
                                <div v-for="line in [100, 75, 50, 25]" :key="line" class="flex items-center gap-2">
                                    <span class="text-[9px] text-gray-700 font-bold w-7 shrink-0">{{ line }}%</span>
                                    <div class="flex-1 border-t border-white/5"></div>
                                </div>
                                <div></div>
                            </div>

                            <!-- Actual bars -->
                            <div class="flex-1 flex items-end gap-3 h-full pl-9">
                                <div v-for="(trend, i) in weeklyTrends" :key="i"
                                    class="flex-1 flex flex-col items-center justify-end h-full group relative">

                                    <!-- Tooltip -->
                                    <div class="absolute bottom-full mb-3 left-1/2 -translate-x-1/2 bg-white rounded-2xl shadow-2xl p-3 opacity-0 group-hover:opacity-100 transition-all pointer-events-none z-30 w-44">
                                        <p class="text-xs font-black text-gray-900 mb-2">{{ trend.week }} — {{ trend.label }}</p>
                                        <div class="space-y-1">
                                            <div class="flex justify-between text-[10px]">
                                                <span class="text-gray-500">Présents</span>
                                                <span class="font-black text-emerald-600">{{ trend.present }}</span>
                                            </div>
                                            <div class="flex justify-between text-[10px]">
                                                <span class="text-gray-500">Absents</span>
                                                <span class="font-black text-red-600">{{ trend.absent }}</span>
                                            </div>
                                            <div class="flex justify-between text-[10px]">
                                                <span class="text-gray-500">Retards</span>
                                                <span class="font-black text-amber-600">{{ trend.late }}</span>
                                            </div>
                                            <div class="flex justify-between text-[10px]">
                                                <span class="text-gray-500">Justifiés</span>
                                                <span class="font-black text-blue-600">{{ trend.justified }}</span>
                                            </div>
                                            <div class="border-t border-gray-100 pt-1 mt-1 flex justify-between text-[10px]">
                                                <span class="text-gray-500 font-bold">Taux</span>
                                                <span class="font-black" :class="trend.rate >= 90 ? 'text-emerald-600' : trend.rate >= 75 ? 'text-amber-600' : 'text-red-600'">{{ trend.rate }}%</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Rate label -->
                                    <span class="text-[10px] font-black mb-1.5 transition-opacity"
                                        :class="trend.rate >= 90 ? 'text-emerald-400' : trend.rate >= 75 ? 'text-amber-400' : 'text-red-400'">
                                        {{ trend.rate }}%
                                    </span>

                                    <!-- Bar -->
                                    <div class="w-full rounded-t-xl transition-all duration-700 bg-gradient-to-t cursor-pointer relative overflow-hidden"
                                        :class="weekBarColor(trend.rate)"
                                        :style="{ height: ((trend.rate / 100) * 100) + '%', minHeight: '8px' }">
                                        <!-- Target line indicator -->
                                        <div v-if="trend.rate < 90 && trend.rate >= 75"
                                            class="absolute inset-x-0 top-0 h-0.5 bg-white/20"></div>
                                        <div class="absolute inset-0 bg-white/0 group-hover:bg-white/10 transition-colors"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Week labels -->
                        <div class="flex gap-3 mt-3 pl-9">
                            <div v-for="trend in weeklyTrends" :key="trend.week" class="flex-1 text-center">
                                <p class="text-[10px] font-black text-gray-600 uppercase tracking-widest">{{ trend.week }}</p>
                                <p class="text-[9px] text-gray-700 font-medium">{{ trend.label }}</p>
                            </div>
                        </div>

                        <!-- Target reference line legend -->
                        <div class="flex items-center gap-6 mt-5 px-1">
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-2 rounded bg-emerald-500/60"></div>
                                <span class="text-[10px] text-gray-500 font-bold">≥ 90% (Objectif atteint)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-2 rounded bg-blue-500/60"></div>
                                <span class="text-[10px] text-gray-500 font-bold">75–90% (Acceptable)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-2 rounded bg-red-500/60"></div>
                                <span class="text-[10px] text-gray-500 font-bold">&lt; 75% (Insuffisant)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-else class="text-center py-16 text-gray-600">
                        <ClipboardDocumentCheckIcon class="h-12 w-12 mx-auto mb-4 text-gray-700" />
                        <p class="font-bold">Aucun émargement enregistré sur les 8 dernières semaines.</p>
                    </div>
                </div>

                <!-- Breakdown par groupe -->
                <div v-if="groupBreakdown.length > 0" class="px-10 pb-10 border-t border-white/5 pt-8">
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-5">Détail par groupe</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div v-for="group in groupBreakdown" :key="group.group_id"
                            class="bg-white/5 border border-white/10 rounded-2xl p-5 hover:bg-white/8 transition-colors">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <p class="font-black text-white text-sm">{{ group.group_name }}</p>
                                    <p class="text-[10px] text-gray-500 font-medium mt-0.5">{{ group.module }}</p>
                                    <p class="text-[10px] text-gray-600 font-medium">Formateur : {{ group.formateur }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-black" :class="rateColor(group.rate)">{{ group.rate }}%</p>
                                    <p class="text-[9px] text-gray-600 font-bold">{{ group.students }} apprenants</p>
                                </div>
                            </div>

                            <!-- Progress bar -->
                            <div class="h-2 bg-white/5 rounded-full overflow-hidden mb-3">
                                <div class="h-full rounded-full bg-gradient-to-r transition-all duration-700"
                                    :class="rateBarColor(group.rate)"
                                    :style="{ width: group.rate + '%' }"></div>
                            </div>

                            <!-- Mini stats -->
                            <div class="grid grid-cols-4 gap-1 text-center">
                                <div>
                                    <p class="text-[9px] text-gray-600 uppercase font-black">Prés.</p>
                                    <p class="text-xs font-black text-emerald-400">{{ group.present }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] text-gray-600 uppercase font-black">Abs.</p>
                                    <p class="text-xs font-black text-red-400">{{ group.absent }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] text-gray-600 uppercase font-black">Ret.</p>
                                    <p class="text-xs font-black text-amber-400">{{ group.late }}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] text-gray-600 uppercase font-black">Just.</p>
                                    <p class="text-xs font-black text-blue-400">{{ group.justified }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
