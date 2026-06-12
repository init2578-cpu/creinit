<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import { 
    ChartBarIcon, 
    ArrowUpIcon, 
    ArrowDownIcon,
    CalendarDaysIcon,
    PresentationChartLineIcon,
    AcademicCapIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    growth_data: Array,
    module_performance: Array,
    attendance_trends: Array
})

// Normalize User Growth chart bars
const maxGrowthCount = computed(() => {
    if (!props.growth_data || props.growth_data.length === 0) return 1
    return Math.max(...props.growth_data.map(item => item.count), 1)
})

// Normalize Module Performance progress bars
const maxCertificatesCount = computed(() => {
    if (!props.module_performance || props.module_performance.length === 0) return 1
    return Math.max(...props.module_performance.map(item => item.certificates), 1)
})
</script>

<template>
    <Head title="Statistiques & Rapports" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <header class="mb-12 relative overflow-hidden bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm">
                <div class="absolute top-0 right-0 -mt-8 -mr-8 w-64 h-64 bg-indigo-50/50 rounded-full blur-3xl"></div>
                <div class="relative z-10">
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-50 text-indigo-700 text-[10px] font-black uppercase tracking-[0.2em] rounded-full border border-indigo-100 mb-4">
                        Analytiques
                    </span>
                    <h1 class="text-4xl font-black text-gray-900 tracking-tight mb-2">Statistiques & Rapports</h1>
                    <p class="text-lg text-gray-500 font-medium tracking-tight max-w-2xl">
                        Rapports détaillés sur la croissance de la plateforme, la performance des modules et l'engagement hebdomadaire des apprenants.
                    </p>
                </div>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
                <!-- User Growth -->
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-8">
                            <h2 class="text-xl font-black text-gray-900 tracking-tight flex items-center gap-3">
                                <PresentationChartLineIcon class="h-6 w-6 text-indigo-600" />
                                Croissance Utilisateurs
                            </h2>
                            <span class="px-3 py-1 bg-green-50 text-green-600 rounded-lg text-xs font-black flex items-center gap-1 border border-green-100">
                                <ArrowUpIcon class="h-3 w-3" /> 12% ce mois
                            </span>
                        </div>
                        
                        <div class="h-64 flex items-end gap-4 px-4 pb-2 border-b border-gray-100">
                            <div v-for="item in growth_data" :key="item.month" class="flex-1 flex flex-col items-center justify-end h-full group relative">
                                <!-- Hover count tooltip -->
                                <div class="absolute -top-10 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-2 group-hover:translate-y-0 bg-gray-900 text-white text-[10px] font-black px-2.5 py-1 rounded-lg shadow-xl z-20 pointer-events-none">
                                    {{ item.count }} {{ item.count > 1 ? 'utilisateurs' : 'utilisateur' }}
                                </div>

                                <!-- Bar -->
                                <div 
                                    class="w-full bg-gradient-to-t from-indigo-500/80 to-indigo-600 rounded-2xl relative overflow-hidden transition-all duration-500 hover:from-indigo-600 hover:to-indigo-700 shadow-sm hover:shadow-md cursor-pointer" 
                                    :style="{ height: ((item.count / maxGrowthCount) * 100) + '%' }"
                                >
                                    <div class="absolute inset-0 bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Labels -->
                        <div class="flex gap-4 px-4 mt-3">
                            <span v-for="item in growth_data" :key="item.month" class="flex-1 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                {{ item.month }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Module Performance -->
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100 flex flex-col justify-between">
                    <div>
                        <h2 class="text-xl font-black text-gray-900 tracking-tight flex items-center gap-3 mb-8">
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
                                    <div 
                                        class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-full transition-all duration-1000" 
                                        :style="{ width: ((module.certificates / maxCertificatesCount) * 100) + '%' }"
                                    ></div>
                                </div>
                            </div>
                            
                            <div v-if="module_performance.length === 0" class="text-center py-12 text-gray-400 italic flex flex-col items-center gap-2">
                                <AcademicCapIcon class="h-8 w-8 text-gray-300" />
                                <span>Aucune donnée de performance disponible.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Trend -->
            <div class="bg-gray-900 p-10 rounded-[3rem] text-white shadow-2xl relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-[400px] h-[400px] bg-indigo-500/10 rounded-full blur-[100px] opacity-60"></div>
                <div class="relative z-10">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
                        <div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/10 text-indigo-300 text-[10px] font-black uppercase tracking-[0.2em] rounded-full border border-white/5 mb-3">
                                Présence Physique
                            </span>
                            <h2 class="text-3xl font-black tracking-tight mb-2">Taux d'Émargement</h2>
                            <p class="text-gray-400 font-medium">Evolution hebdomadaire globale des présences sur la plateforme.</p>
                        </div>
                        <div class="flex items-center gap-6 bg-white/5 px-6 py-4 rounded-3xl border border-white/5 backdrop-blur-xl">
                             <div class="text-center">
                                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Moyenne</p>
                                <p class="text-3xl font-black text-white">84%</p>
                             </div>
                             <div class="h-10 w-px bg-white/10"></div>
                             <div class="text-center">
                                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Cible</p>
                                <p class="text-3xl font-black text-gray-400">90%</p>
                             </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-4 gap-6 h-56 items-end px-4 border-b border-white/10 pb-4">
                        <div v-for="trend in attendance_trends" :key="trend.week" class="relative group h-full flex flex-col justify-end">
                            <!-- Rate label on hover -->
                            <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity bg-white text-gray-950 text-xs font-black px-2 py-0.5 rounded shadow-lg pointer-events-none">
                                {{ trend.rate }}%
                            </div>
                            
                            <!-- Column bar -->
                            <div 
                                class="w-full bg-white/5 hover:bg-white/15 border border-white/5 rounded-2xl transition-all cursor-pointer flex flex-col items-center justify-end p-4 gap-2" 
                                :style="{ height: trend.rate + '%' }"
                            >
                                <div class="w-3 h-3 rounded-full bg-indigo-400 shadow-[0_0_15px_rgba(99,102,241,0.8)] animate-pulse"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Week Labels -->
                    <div class="grid grid-cols-4 gap-6 mt-4 px-4">
                        <p v-for="trend in attendance_trends" :key="trend.week" class="text-center text-[10px] font-black text-gray-500 uppercase tracking-widest">
                            {{ trend.week }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
