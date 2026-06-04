<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { 
    AcademicCapIcon, 
    CheckBadgeIcon, 
    ArrowDownTrayIcon,
    TrashIcon,
    FunnelIcon,
    MagnifyingGlassIcon,
    ClockIcon,
    ChevronRightIcon,
    PrinterIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    students: Array,
    modules: Array
})

const searchQuery = ref('')
const selectedModule = ref(null)

const filteredStudents = computed(() => {
    if (!props.students) return []
    return props.students.filter(student => {
        const name = String(student.name || '').toLowerCase()
        const email = String(student.email || '').toLowerCase()
        const query = searchQuery.value.toLowerCase()
        const matchesSearch = name.includes(query) || email.includes(query)
        
        if (!selectedModule.value) return matchesSearch
        
        return matchesSearch && student.progress.some(p => p.module_id === selectedModule.value)
    })
})

function getProgressForModule(student, moduleId) {
    return student.progress.find(p => p.module_id === moduleId)
}

function generateCertificate(studentId, moduleId) {
    router.post(route('certificates.generate', { student: studentId, module: moduleId }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Success notification could be added here
        }
    })
}

function deleteCertificate(certificateId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette attestation ?')) {
        router.delete(route('certificates.destroy', certificateId), {
            preserveScroll: true
        })
    }
}
</script>

<template>
    <Head title="Gestion des Attestations" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-black text-gray-900 tracking-tight">Gestion des Attestations</h2>
                    <p class="text-sm text-gray-500 mt-1 font-medium">Générez et suivez les certifications de vos apprenants</p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Stats / Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-100 flex items-center gap-6">
                        <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600">
                            <AcademicCapIcon class="h-8 w-8" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-500 uppercase tracking-widest">Total Apprenants</p>
                            <p class="text-3xl font-black text-gray-900">{{ students.length }}</p>
                        </div>
                    </div>
                    
                    <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-100 flex items-center gap-6">
                        <div class="w-16 h-16 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600">
                            <CheckBadgeIcon class="h-8 w-8" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-500 uppercase tracking-widest">Attestations Délivrées</p>
                            <p class="text-3xl font-black text-gray-900">
                                {{ students.reduce((acc, s) => acc + s.certificates.length, 0) }}
                            </p>
                        </div>
                    </div>

                    <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-100 flex items-center gap-6">
                        <div class="w-16 h-16 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600">
                            <ClockIcon class="h-8 w-8" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-500 uppercase tracking-widest">En attente</p>
                            <p class="text-3xl font-black text-gray-900">
                                {{ students.reduce((acc, s) => acc + s.progress.filter(p => p.completed && !p.has_certificate).length, 0) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-gray-100 flex flex-col md:flex-row gap-4 items-center">
                    <div class="relative flex-1 w-full">
                        <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Rechercher un apprenant par nom ou email..."
                            class="w-full pl-12 pr-4 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-blue-500/20 transition-all font-medium text-sm"
                        >
                    </div>
                    
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <FunnelIcon class="h-5 w-5 text-gray-400 hidden md:block" />
                        <select 
                            v-model="selectedModule"
                            class="flex-1 md:w-64 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-blue-500/20 transition-all font-bold text-xs uppercase tracking-widest"
                        >
                            <option :value="null">Tous les Modules</option>
                            <option v-for="module in modules" :key="module.id" :value="module.id">
                                {{ module.titre }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Content Grid -->
                <div class="grid grid-cols-1 gap-6">
                    <div v-for="student in filteredStudents" :key="student.id" class="group bg-white rounded-[2.5rem] border border-gray-100 overflow-hidden hover:shadow-xl hover:shadow-blue-900/5 transition-all duration-500">
                        <div class="p-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-8">
                            <div class="flex items-center gap-6">
                                <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center overflow-hidden text-white font-black text-xl shadow-lg shadow-blue-200">
                                    <img v-if="student.profile_photo_url" :src="student.profile_photo_url" class="h-full w-full object-cover">
                                    <template v-else>{{ student.name.charAt(0) }}</template>
                                </div>
                                <div>
                                    <h4 class="text-xl font-black text-gray-900">{{ student.name }}</h4>
                                    <p class="text-sm text-gray-500 font-medium">{{ student.email }}</p>
                                </div>
                            </div>

                            <div class="flex-1 w-full grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div 
                                    v-for="prog in (selectedModule ? student.progress.filter(p => p.module_id === selectedModule) : student.progress)" 
                                    :key="prog.module_id"
                                    class="p-5 rounded-3xl bg-gray-50/50 border border-gray-100 flex flex-col gap-3"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 line-clamp-1 flex-1">{{ prog.module_title }}</span>
                                        <span v-if="prog.completed" class="flex items-center gap-1 text-[10px] font-black text-emerald-600 uppercase tracking-tighter">
                                            <CheckBadgeIcon class="h-3 w-3" />
                                            Complété
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between group/row">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-gray-900">{{ prog.progress_pct }}% Progression</span>
                                            <div class="flex items-center gap-2 mt-1">
                                                <div class="w-16 h-1 bg-gray-200 rounded-full overflow-hidden">
                                                    <div class="h-full bg-blue-500 transition-all duration-500" :style="{ width: prog.progress_pct + '%' }"></div>
                                                </div>
                                                <span class="text-[9px] font-black text-gray-400">{{ prog.completed_count }}/{{ prog.total_chapters }}</span>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <template v-if="prog.has_certificate">
                                                <a 
                                                    :href="route('certificates.download', prog.certificate.id)"
                                                    class="p-3 bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-100 transition shadow-sm"
                                                    title="Télécharger l'attestation"
                                                >
                                                    <ArrowDownTrayIcon class="h-4 w-4" />
                                                </a>
                                                <button 
                                                    @click="deleteCertificate(prog.certificate.id)"
                                                    class="p-3 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition shadow-sm"
                                                    title="Supprimer"
                                                >
                                                    <TrashIcon class="h-4 w-4" />
                                                </button>
                                            </template>
                                            <button 
                                                v-else-if="prog.completed"
                                                @click="generateCertificate(student.id, prog.module_id)"
                                                class="flex items-center gap-2 px-4 py-3 bg-blue-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-500 transition shadow-lg shadow-blue-200"
                                            >
                                                <PrinterIcon class="h-4 w-4" />
                                                Générer
                                            </button>
                                            <span v-else class="text-[10px] font-black text-gray-400 uppercase tracking-widest">En cours</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredStudents.length === 0" class="py-20 text-center bg-white rounded-[3rem] border-2 border-dashed border-gray-100">
                        <AcademicCapIcon class="h-16 w-16 text-gray-200 mx-auto mb-4" />
                        <h3 class="text-xl font-black text-gray-900">Aucun apprenant trouvé</h3>
                        <p class="text-gray-500 mt-2">Ajustez vos filtres ou effectuez une nouvelle recherche.</p>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
