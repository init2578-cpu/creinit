<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DateInput from '@/Components/DateInput.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { 
    DocumentChartBarIcon, 
    CalendarIcon, 
    UserGroupIcon, 
    ArrowDownTrayIcon,
    InformationCircleIcon,
    CheckCircleIcon,
    TableCellsIcon,
    DocumentTextIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    groups: Array,
    modules: Array,
    trainers: Array,
    report_types: Array
})

const form = useForm({
    type: 'attendance',
    format: 'pdf',
    group_id: '',
    module_id: '',
    trainer_id: '',
    start_date: '',
    end_date: '',
})

const isGenerating = ref(false)
const successMessage = ref('')

const submit = (format = 'pdf') => {
    isGenerating.value = true
    successMessage.value = ''
    form.format = format
    
    const params = new URLSearchParams(form.data()).toString();
    window.open(route('reports.generate') + '?' + params, '_blank');
    
    setTimeout(() => {
        isGenerating.value = false
        successMessage.value = `Le rapport ${format.toUpperCase()} a été généré avec succès.`
    }, 2000)
}
</script>

<template>
    <Head title="Gestion des Rapports" />

    <AuthenticatedLayout>
        <div class="max-w-5xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <header class="mb-12">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-blue-600 rounded-2xl shadow-lg shadow-blue-200">
                        <DocumentChartBarIcon class="h-8 w-8 text-white" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-gray-900 tracking-tight">Gestion des Rapports</h1>
                        <p class="text-gray-500 font-medium">Générez des rapports détaillés au format PDF pour le suivi administratif.</p>
                    </div>
                </div>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Configuration Card -->
                <div class="md:col-span-2 space-y-6">
                    <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-8">
                            <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                                <InformationCircleIcon class="h-5 w-5 text-blue-600" />
                                Paramètres du Rapport
                            </h2>

                            <form @submit.prevent="submit" class="space-y-6">
                                <!-- Report Type -->
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Type de Rapport</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <button 
                                            v-for="type in report_types" 
                                            :key="type.id"
                                            type="button"
                                            @click="form.type = type.id"
                                            class="p-4 rounded-2xl border-2 transition-all text-left flex flex-col gap-2"
                                            :class="form.type === type.id 
                                                ? 'border-blue-600 bg-blue-50/50' 
                                                : 'border-gray-100 hover:border-gray-200'"
                                        >
                                            <span class="text-[10px] font-black uppercase tracking-widest leading-none mb-1" :class="form.type === type.id ? 'text-blue-600' : 'text-gray-400'">{{ type.id.replace('_', ' ') }}</span>
                                            <span class="text-sm font-black leading-tight" :class="form.type === type.id ? 'text-blue-900' : 'text-gray-700'">{{ type.name }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Filters -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-6 border-t border-gray-50">
                                    <!-- Group Selection -->
                                    <div v-if="['attendance', 'academic', 'module_enrollment'].includes(form.type)">
                                        <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-2">
                                            <UserGroupIcon class="h-4 w-4 text-gray-400" />
                                            Groupe (Optionnel)
                                        </label>
                                        <select 
                                            v-model="form.group_id"
                                            class="w-full rounded-xl border-gray-200 shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm font-medium"
                                        >
                                            <option value="">Tous les groupes</option>
                                            <option v-for="group in groups" :key="group.id" :value="group.id">
                                                {{ group.nom_groupe }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Module Selection -->
                                    <div v-if="form.type === 'module_students'">
                                        <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-2">
                                            <DocumentChartBarIcon class="h-4 w-4 text-gray-400" />
                                            Module
                                        </label>
                                        <select 
                                            v-model="form.module_id"
                                            class="w-full rounded-xl border-gray-200 shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm font-medium"
                                        >
                                            <option value="">Tous les modules</option>
                                            <option v-for="module in modules" :key="module.id" :value="module.id">
                                                {{ module.titre }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Trainer Selection -->
                                    <div v-if="form.type === 'trainer_enrollment'">
                                        <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-2">
                                            <UserGroupIcon class="h-4 w-4 text-gray-400" />
                                            Formateur
                                        </label>
                                        <select 
                                            v-model="form.trainer_id"
                                            class="w-full rounded-xl border-gray-200 shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm font-medium"
                                        >
                                            <option value="">Tous les formateurs</option>
                                            <option v-for="trainer in trainers" :key="trainer.id" :value="trainer.id">
                                                {{ trainer.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Date Range -->
                                    <div v-if="['attendance', 'trainer_enrollment', 'module_enrollment'].includes(form.type)">
                                        <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-2">
                                            <CalendarIcon class="h-4 w-4 text-gray-400" />
                                            Période
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <DateInput 
                                                v-model="form.start_date"
                                                class="flex-1 rounded-xl border border-gray-200 shadow-sm text-xs font-semibold focus:ring-blue-500 p-2"
                                            />
                                            <span class="text-gray-400">→</span>
                                            <DateInput 
                                                v-model="form.end_date"
                                                class="flex-1 rounded-xl border border-gray-200 shadow-sm text-xs font-semibold focus:ring-blue-500 p-2"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-8 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <button 
                                        type="button"
                                        @click="submit('pdf')"
                                        :disabled="isGenerating"
                                        class="py-5 bg-gray-900 text-white rounded-[1.5rem] font-black flex items-center justify-center gap-3 hover:bg-black transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 shadow-xl shadow-gray-200"
                                    >
                                        <ArrowDownTrayIcon v-if="!isGenerating" class="h-6 w-6" />
                                        <svg v-else class="animate-spin h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ isGenerating ? 'PDF' : 'Exporter PDF' }}
                                    </button>

                                    <button 
                                        type="button"
                                        @click="submit('excel')"
                                        :disabled="isGenerating"
                                        class="py-5 bg-emerald-600 text-white rounded-[1.5rem] font-black flex items-center justify-center gap-3 hover:bg-emerald-700 transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 shadow-xl shadow-emerald-100"
                                    >
                                        <TableCellsIcon v-if="!isGenerating" class="h-6 w-6" />
                                        <svg v-else class="animate-spin h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ isGenerating ? 'Excel' : 'Exporter Excel' }}
                                    </button>

                                    <button 
                                        type="button"
                                        @click="submit('word')"
                                        :disabled="isGenerating"
                                        class="py-5 bg-blue-600 text-white rounded-[1.5rem] font-black flex items-center justify-center gap-3 hover:bg-blue-700 transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 shadow-xl shadow-blue-100"
                                    >
                                        <DocumentTextIcon v-if="!isGenerating" class="h-6 w-6" />
                                        <svg v-else class="animate-spin h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ isGenerating ? 'Word' : 'Rapport Synthèse' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div v-if="successMessage" class="bg-green-50 border border-green-100 p-4 rounded-2xl flex items-center gap-3">
                        <CheckCircleIcon class="h-6 w-6 text-green-600" />
                        <span class="text-sm font-bold text-green-800">{{ successMessage }}</span>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="space-y-6">
                    <div class="bg-blue-600 rounded-[2.5rem] p-8 text-white shadow-xl shadow-blue-100 relative overflow-hidden group">
                        <div class="absolute -right-10 -bottom-10 opacity-10 group-hover:scale-110 transition-transform duration-700">
                            <DocumentChartBarIcon class="h-48 w-48 text-white" />
                        </div>
                        
                        <h3 class="text-xl font-black mb-4 relative z-10">Aide & Conseils</h3>
                        <ul class="space-y-4 relative z-10">
                            <li class="flex gap-3 items-start">
                                <div class="h-5 w-5 mt-0.5 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0 text-[10px] font-black">1</div>
                                <p class="text-sm font-medium text-blue-50">Les rapports d'assiduité incluent le taux de présence par étudiant sur la période choisie.</p>
                            </li>
                            <li class="flex gap-3 items-start">
                                <div class="h-5 w-5 mt-0.5 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0 text-[10px] font-black">2</div>
                                <p class="text-sm font-medium text-blue-50">Le rapport de performance liste le nombre de certificats obtenus par module pour chaque apprenant.</p>
                            </li>
                            <li class="flex gap-3 items-start">
                                <div class="h-5 w-5 mt-0.5 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0 text-[10px] font-black">3</div>
                                <p class="text-sm font-medium text-blue-50">Les KPIs globaux fournissent une vue d'ensemble de l'écosystème CRE (Parité, Réussite, Matériel).</p>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-gray-50 rounded-[2.5rem] p-8 border border-gray-100">
                        <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-4">Exportation Récente</h3>
                        <div class="text-center py-6">
                            <DocumentChartBarIcon class="h-10 w-10 text-gray-200 mx-auto mb-2" />
                            <p class="text-xs font-bold text-gray-400">Aucun historique d'exportation disponible pour cette session.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
