<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import {
    ChartBarIcon, ArrowUpIcon, ArrowDownIcon, MinusIcon,
    PresentationChartLineIcon, AcademicCapIcon, UserGroupIcon,
    ClipboardDocumentCheckIcon, ExclamationCircleIcon, CheckCircleIcon,
    ClockIcon, ShieldCheckIcon, TrophyIcon, FireIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    growth_data: Array,
    module_performance: Array,
    attendance_stats: Object
})

const months    = computed(() => props.growth_data?.months || [])
const growthPct = computed(() => props.growth_data?.growth_pct ?? null)
const maxGrowth = computed(() => Math.max(...months.value.map(i => i.count), 1))
const maxCerts  = computed(() => Math.max(...(props.module_performance || []).map(i => i.certificates), 1))

const stats         = computed(() => props.attendance_stats || {})
const weekly        = computed(() => stats.value.weekly_trends || [])
const groups        = computed(() => stats.value.group_breakdown || [])
const summary       = computed(() => stats.value.status_summary || { present: 0, absent_non_justifie: 0, late: 0, justifie: 0 })
const overallRate   = computed(() => stats.value.overall_rate || 0)
const targetRate    = computed(() => stats.value.target_rate || 90)
const trend         = computed(() => stats.value.trend_direction || 'stable')
const totalRecords  = computed(() => Object.values(summary.value).reduce((a,b) => a+b, 0))

const rateGrade = (r) => r >= 90 ? 'A' : r >= 80 ? 'B' : r >= 70 ? 'C' : 'D'
const rateColor = (r) => r >= 90 ? 'text-emerald-400' : r >= 75 ? 'text-amber-400' : 'text-red-400'
const barGrad   = (r) => r >= 90 ? 'from-emerald-500 to-teal-400' : r >= 75 ? 'from-amber-500 to-yellow-400' : 'from-red-500 to-orange-400'
const bgCard    = (r) => r >= 90 ? 'bg-emerald-500/10 border-emerald-500/20' : r >= 75 ? 'bg-amber-500/10 border-amber-500/20' : 'bg-red-500/10 border-red-500/20'

const hoveredWeek = ref(null)
</script>

<template>
    <Head title="Statistiques & Rapports" />
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- ── PAGE HEADER ── -->
            <div class="relative bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-[2.5rem] overflow-hidden p-10 border border-indigo-900/40 shadow-2xl">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_rgba(99,102,241,0.25),transparent_60%)]"></div>
                <div class="absolute bottom-0 left-0 w-72 h-72 bg-indigo-600/10 rounded-full blur-3xl -mb-20 -ml-20"></div>
                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div>
                        <span class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-500/20 text-indigo-300 text-[10px] font-black uppercase tracking-[0.2em] rounded-full border border-indigo-500/30 mb-4">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
                            Tableau de Bord Analytique
                        </span>
                        <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tight mb-2">Statistiques<br><span class="text-indigo-400">&amp; Rapports</span></h1>
                        <p class="text-slate-400 font-medium max-w-lg">Vue d'ensemble de la plateforme — croissance, performance pédagogique et assiduité des apprenants.</p>
                    </div>
                    <!-- Quick KPIs -->
                    <div class="flex gap-4">
                        <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl px-6 py-4 text-center">
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Apprenants</p>
                            <p class="text-3xl font-black text-white mt-1">{{ stats.total_students || 0 }}</p>
                        </div>
                        <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl px-6 py-4 text-center">
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Émargement</p>
                            <p class="text-3xl font-black mt-1" :class="rateColor(overallRate)">{{ overallRate }}%</p>
                        </div>
                        <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl px-6 py-4 text-center">
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Séances</p>
                            <p class="text-3xl font-black text-white mt-1">{{ stats.total_sessions || 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── ROW 1: Growth + Modules ── -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

                <!-- User Growth (3 cols) -->
                <div class="lg:col-span-3 bg-white rounded-[2rem] shadow-sm border border-gray-100 p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-black text-gray-900 flex items-center gap-2">
                                <PresentationChartLineIcon class="h-5 w-5 text-indigo-600" /> Croissance Utilisateurs
                            </h2>
                            <p class="text-xs text-gray-400 font-medium mt-0.5">Nouveaux inscrits par mois</p>
                        </div>
                    <!-- Growth badge -->
                        <span v-if="growthPct !== null" class="flex items-center gap-1 px-3 py-1.5 text-xs font-black rounded-xl border"
                            :class="growthPct >= 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-red-50 text-red-700 border-red-100'">
                            <component :is="growthPct >= 0 ? ArrowUpIcon : ArrowDownIcon" class="h-3 w-3" />
                            {{ growthPct >= 0 ? '+' : '' }}{{ growthPct }}% ce mois
                        </span>
                        <span v-else class="flex items-center gap-1 px-3 py-1.5 bg-gray-50 text-gray-400 text-xs font-black rounded-xl border border-gray-100">
                            <MinusIcon class="h-3 w-3" /> Nouveau mois
                        </span>
                    </div>
                    <div class="h-48 flex items-end gap-3 border-b border-gray-100 pb-2">
                        <div v-for="item in months" :key="item.month" class="flex-1 flex flex-col items-center justify-end h-full group relative">
                            <div class="absolute -top-8 opacity-0 group-hover:opacity-100 transition-all bg-gray-900 text-white text-[10px] font-black px-2 py-1 rounded-lg pointer-events-none whitespace-nowrap z-10">
                                {{ item.count }} utilisateurs
                            </div>
                            <div class="w-full rounded-t-xl bg-gradient-to-t from-indigo-600 to-indigo-400 group-hover:from-indigo-700 group-hover:to-indigo-500 transition-all duration-300 shadow-sm"
                                :style="{ height: ((item.count / maxGrowth) * 100) + '%', minHeight: '6px' }">
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-3">
                        <span v-for="item in months" :key="item.month" class="flex-1 text-center text-[10px] font-black text-gray-400 uppercase">{{ item.month }}</span>
                    </div>
                </div>

                <!-- Module Performance (2 cols) -->
                <div class="lg:col-span-2 bg-white rounded-[2rem] shadow-sm border border-gray-100 p-8">
                    <h2 class="text-lg font-black text-gray-900 flex items-center gap-2 mb-6">
                        <TrophyIcon class="h-5 w-5 text-amber-500" /> Certifications
                    </h2>
                    <div class="space-y-5">
                        <div v-for="(m, i) in module_performance" :key="m.name">
                            <div class="flex justify-between text-xs mb-1.5">
                                <span class="font-bold text-gray-700 truncate max-w-[75%]">{{ m.name }}</span>
                                <span class="font-black text-indigo-600">{{ m.certificates }}</span>
                            </div>
                            <div class="h-2.5 bg-gray-50 rounded-full overflow-hidden border border-gray-100">
                                <div class="h-full rounded-full transition-all duration-700"
                                    :class="i % 2 === 0 ? 'bg-gradient-to-r from-indigo-500 to-purple-500' : 'bg-gradient-to-r from-amber-500 to-orange-400'"
                                    :style="{ width: ((m.certificates / maxCerts) * 100) + '%' }"></div>
                            </div>
                        </div>
                        <div v-if="!module_performance?.length" class="text-center py-8 text-gray-300">
                            <AcademicCapIcon class="h-8 w-8 mx-auto mb-2" />
                            <p class="text-xs font-bold">Aucune donnée</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── TAUX D'ÉMARGEMENT ── -->
            <div class="bg-[#0a0e1a] rounded-[3rem] overflow-hidden border border-white/5 shadow-2xl">

                <!-- Section Header -->
                <div class="p-10 border-b border-white/5">
                    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-8">
                        <div>
                            <span class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-500/15 text-indigo-400 text-[10px] font-black uppercase tracking-[0.2em] rounded-full border border-indigo-500/20 mb-3">
                                <ClipboardDocumentCheckIcon class="h-3 w-3" /> Présence Physique
                            </span>
                            <h2 class="text-3xl font-black text-white mb-1">Taux d'Émargement</h2>
                            <p class="text-slate-500 text-sm font-medium">Analyse détaillée sur les 8 dernières semaines</p>
                        </div>

                        <!-- KPI Strip -->
                        <div class="flex flex-wrap gap-3">
                            <!-- Global Rate -->
                            <div class="relative bg-white/5 border border-white/10 rounded-2xl p-5 text-center min-w-[120px]">
                                <p class="text-[9px] font-black text-slate-600 uppercase tracking-widest mb-2">Taux Global</p>
                                <p class="text-4xl font-black" :class="rateColor(overallRate)">{{ overallRate }}<span class="text-2xl">%</span></p>
                                <div class="flex items-center justify-center gap-1 mt-2">
                                    <component :is="trend === 'up' ? ArrowUpIcon : trend === 'down' ? ArrowDownIcon : MinusIcon"
                                        class="h-3 w-3" :class="trend === 'up' ? 'text-emerald-400' : trend === 'down' ? 'text-red-400' : 'text-slate-500'" />
                                    <span class="text-[10px] font-bold" :class="trend === 'up' ? 'text-emerald-400' : trend === 'down' ? 'text-red-400' : 'text-slate-500'">
                                        {{ trend === 'up' ? 'En hausse' : trend === 'down' ? 'En baisse' : 'Stable' }}
                                    </span>
                                </div>
                                <!-- Grade badge -->
                                <div class="absolute -top-2 -right-2 h-7 w-7 rounded-full flex items-center justify-center text-[10px] font-black shadow-lg"
                                    :class="overallRate >= 90 ? 'bg-emerald-500 text-white' : overallRate >= 75 ? 'bg-amber-500 text-white' : 'bg-red-500 text-white'">
                                    {{ rateGrade(overallRate) }}
                                </div>
                            </div>
                            <!-- Target -->
                            <div class="bg-white/5 border border-white/10 rounded-2xl p-5 text-center min-w-[100px]">
                                <p class="text-[9px] font-black text-slate-600 uppercase tracking-widest mb-2">Objectif</p>
                                <p class="text-4xl font-black text-slate-500">{{ targetRate }}<span class="text-2xl">%</span></p>
                                <p class="text-[10px] font-bold mt-2" :class="overallRate >= targetRate ? 'text-emerald-400' : 'text-amber-400'">
                                    {{ overallRate >= targetRate ? '✓ Atteint' : '-' + (targetRate - overallRate).toFixed(1) + '%' }}
                                </p>
                            </div>
                            <!-- Total -->
                            <div class="bg-white/5 border border-white/10 rounded-2xl p-5 text-center min-w-[100px]">
                                <p class="text-[9px] font-black text-slate-600 uppercase tracking-widest mb-2">Pointages</p>
                                <p class="text-4xl font-black text-white">{{ totalRecords }}</p>
                                <p class="text-[10px] font-bold text-slate-600 mt-2">{{ stats.total_students || 0 }} apprenants</p>
                            </div>
                        </div>
                    </div>

                    <!-- Status Pills -->
                    <div class="flex flex-wrap gap-3 mt-7">
                        <div class="flex items-center gap-2.5 bg-emerald-500/10 border border-emerald-500/20 rounded-xl px-4 py-2.5">
                            <CheckCircleIcon class="h-4 w-4 text-emerald-400 shrink-0" />
                            <div>
                                <p class="text-xs font-black text-emerald-300">{{ summary.present }}</p>
                                <p class="text-[9px] text-emerald-600 font-bold uppercase">Présents</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2.5 bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-2.5">
                            <ExclamationCircleIcon class="h-4 w-4 text-red-400 shrink-0" />
                            <div>
                                <p class="text-xs font-black text-red-300">{{ summary.absent_non_justifie }}</p>
                                <p class="text-[9px] text-red-600 font-bold uppercase">Absents</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2.5 bg-amber-500/10 border border-amber-500/20 rounded-xl px-4 py-2.5">
                            <ClockIcon class="h-4 w-4 text-amber-400 shrink-0" />
                            <div>
                                <p class="text-xs font-black text-amber-300">{{ summary.late }}</p>
                                <p class="text-[9px] text-amber-600 font-bold uppercase">Retards</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2.5 bg-blue-500/10 border border-blue-500/20 rounded-xl px-4 py-2.5">
                            <ShieldCheckIcon class="h-4 w-4 text-blue-400 shrink-0" />
                            <div>
                                <p class="text-xs font-black text-blue-300">{{ summary.justifie }}</p>
                                <p class="text-[9px] text-blue-600 font-bold uppercase">Justifiés</p>
                            </div>
                        </div>

                        <!-- Distribution bar -->
                        <div v-if="totalRecords > 0" class="flex-1 min-w-[200px] flex flex-col justify-center gap-1.5 ml-2">
                            <p class="text-[9px] text-slate-600 font-black uppercase tracking-widest">Répartition</p>
                            <div class="h-3 flex rounded-full overflow-hidden gap-0.5">
                                <div class="bg-emerald-500 transition-all" :style="{ width: ((summary.present / totalRecords) * 100) + '%' }"></div>
                                <div class="bg-amber-500 transition-all" :style="{ width: ((summary.late / totalRecords) * 100) + '%' }"></div>
                                <div class="bg-blue-500 transition-all" :style="{ width: ((summary.justifie / totalRecords) * 100) + '%' }"></div>
                                <div class="bg-red-500 transition-all" :style="{ width: ((summary.absent_non_justifie / totalRecords) * 100) + '%' }"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Weekly Chart -->
                <div class="p-10 border-b border-white/5">
                    <div v-if="weekly.length > 0">
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <p class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Évolution hebdomadaire</p>
                                <p class="text-xs text-slate-500 mt-0.5">Taux de présence par semaine (8 semaines)</p>
                            </div>
                            <div class="flex items-center gap-4 text-[10px] font-bold text-slate-600">
                                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500"></span> ≥ 90% Excellent</span>
                                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-500"></span> 75–89% Correct</span>
                                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500"></span> &lt;75% Insuffisant</span>
                            </div>
                        </div>

                        <!-- Target line + bars -->
                        <div class="relative">
                            <!-- Target line at 90% -->
                            <div class="absolute left-0 right-0 border-t border-dashed border-indigo-500/40 z-10 flex items-center gap-2" :style="{ bottom: '90%' }">
                                <span class="text-[9px] text-indigo-400 font-black bg-[#0a0e1a] px-1 ml-2">OBJECTIF 90%</span>
                            </div>

                            <!-- Bars container -->
                            <div class="h-56 flex items-end gap-4 relative">
                                <div v-for="(w, i) in weekly" :key="i"
                                    class="flex-1 flex flex-col items-center justify-end h-full group cursor-pointer"
                                    @mouseenter="hoveredWeek = i" @mouseleave="hoveredWeek = null">

                                    <!-- Tooltip -->
                                    <Transition enter-active-class="transition-all duration-150" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100">
                                        <div v-if="hoveredWeek === i"
                                            class="absolute bottom-full mb-3 left-1/2 -translate-x-1/2 bg-white rounded-2xl shadow-2xl p-4 z-30 w-44 border border-gray-100">
                                            <p class="text-xs font-black text-gray-900 mb-3 flex items-center justify-between">
                                                <span>{{ w.week }}</span>
                                                <span :class="w.rate >= 90 ? 'text-emerald-600' : w.rate >= 75 ? 'text-amber-600' : 'text-red-600'" class="text-base">{{ w.rate }}%</span>
                                            </p>
                                            <div class="space-y-1.5">
                                                <div class="flex justify-between text-[11px]"><span class="text-gray-400 flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span>Présents</span><span class="font-black text-gray-700">{{ w.present }}</span></div>
                                                <div class="flex justify-between text-[11px]"><span class="text-gray-400 flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-400 inline-block"></span>Absents</span><span class="font-black text-gray-700">{{ w.absent }}</span></div>
                                                <div class="flex justify-between text-[11px]"><span class="text-gray-400 flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400 inline-block"></span>Retards</span><span class="font-black text-gray-700">{{ w.late }}</span></div>
                                                <div class="flex justify-between text-[11px]"><span class="text-gray-400 flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-400 inline-block"></span>Justifiés</span><span class="font-black text-gray-700">{{ w.justified }}</span></div>
                                                <div class="pt-2 border-t border-gray-100 flex justify-between text-[11px]"><span class="text-gray-400">Total</span><span class="font-black text-gray-700">{{ w.total }}</span></div>
                                            </div>
                                            <p class="text-[9px] text-gray-400 mt-2">{{ w.label }}</p>
                                        </div>
                                    </Transition>

                                    <!-- Rate label -->
                                    <span class="text-[11px] font-black mb-1.5" :class="rateColor(w.rate)">{{ w.rate }}%</span>

                                    <!-- Bar -->
                                    <div class="w-full rounded-t-xl bg-gradient-to-t transition-all duration-700 relative overflow-hidden"
                                        :class="[barGrad(w.rate), hoveredWeek === i ? 'opacity-100 shadow-lg' : 'opacity-80']"
                                        :style="{ height: (w.rate) + '%', minHeight: '8px' }">
                                        <div class="absolute inset-0 bg-white/0 group-hover:bg-white/10 transition-colors"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- X-axis labels -->
                        <div class="flex gap-4 mt-3">
                            <div v-for="w in weekly" :key="w.week" class="flex-1 text-center">
                                <p class="text-[10px] font-black text-slate-600 uppercase">{{ w.week }}</p>
                                <p class="text-[9px] text-slate-700">{{ w.label }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-20 text-slate-700">
                        <ClipboardDocumentCheckIcon class="h-14 w-14 mx-auto mb-4 text-slate-800" />
                        <p class="font-bold text-slate-600">Aucun émargement enregistré récemment.</p>
                    </div>
                </div>

                <!-- Group Breakdown -->
                <div v-if="groups.length > 0" class="p-10">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <p class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Détail par groupe</p>
                            <p class="text-xs text-slate-600 mt-0.5">Performance individuelle de chaque groupe de formation</p>
                        </div>
                        <FireIcon class="h-5 w-5 text-orange-500" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div v-for="g in groups" :key="g.group_id"
                            class="rounded-2xl border p-5 transition-all hover:scale-[1.01] cursor-default"
                            :class="bgCard(g.rate)">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1 min-w-0 pr-3">
                                    <p class="font-black text-white text-sm truncate">{{ g.group_name }}</p>
                                    <p class="text-[10px] text-slate-500 truncate mt-0.5">{{ g.module }}</p>
                                    <p class="text-[10px] text-slate-600 mt-0.5">{{ g.formateur }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-2xl font-black" :class="rateColor(g.rate)">{{ g.rate }}%</p>
                                    <p class="text-[9px] text-slate-600 font-bold">{{ g.students }} élèves</p>
                                </div>
                            </div>
                            <div class="h-1.5 bg-white/5 rounded-full overflow-hidden mb-4">
                                <div class="h-full rounded-full bg-gradient-to-r transition-all duration-700"
                                    :class="barGrad(g.rate)"
                                    :style="{ width: g.rate + '%' }"></div>
                            </div>
                            <div class="grid grid-cols-4 gap-1 text-center">
                                <div class="bg-emerald-500/10 rounded-lg py-1.5">
                                    <p class="text-xs font-black text-emerald-400">{{ g.present }}</p>
                                    <p class="text-[8px] text-emerald-700 font-black uppercase">Prés.</p>
                                </div>
                                <div class="bg-red-500/10 rounded-lg py-1.5">
                                    <p class="text-xs font-black text-red-400">{{ g.absent }}</p>
                                    <p class="text-[8px] text-red-700 font-black uppercase">Abs.</p>
                                </div>
                                <div class="bg-amber-500/10 rounded-lg py-1.5">
                                    <p class="text-xs font-black text-amber-400">{{ g.late }}</p>
                                    <p class="text-[8px] text-amber-700 font-black uppercase">Ret.</p>
                                </div>
                                <div class="bg-blue-500/10 rounded-lg py-1.5">
                                    <p class="text-xs font-black text-blue-400">{{ g.justified }}</p>
                                    <p class="text-[8px] text-blue-700 font-black uppercase">Just.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
