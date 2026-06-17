<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { ref } from 'vue'
import { 
    UserGroupIcon, 
    AcademicCapIcon,
    ChevronDownIcon,
    MapPinIcon,
    CakeIcon,
    EnvelopeIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    groups: Array
})

const nominationForm = useForm({
    group_id: null,
    user_id: null,
    role: ''
})

const confirmNominationModal = ref({
    isOpen: false,
    roleTitle: '',
    data: null
})

const proposeNomination = (groupId, userId, role) => {
    confirmNominationModal.value = {
        isOpen: true,
        roleTitle: role === 'responsable' ? 'Chef de groupe' : 'Adjoint',
        data: { groupId, userId, role }
    }
}

const handleConfirmNomination = () => {
    const { groupId, userId, role } = confirmNominationModal.value.data
    nominationForm.group_id = groupId
    nominationForm.user_id = userId
    nominationForm.role = role
    nominationForm.post(route('nominations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            confirmNominationModal.value.isOpen = false
        }
    })
}

// Track which group accordion is open
const openGroups = ref([])

function toggleGroup(groupId) {
    if (openGroups.value.includes(groupId)) {
        openGroups.value = openGroups.value.filter(id => id !== groupId)
    } else {
        openGroups.value.push(groupId)
    }
}
</script>

<template>
    <Head title="Mes Groupes d'Apprenants" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-4 px-4 -mt-6 sm:-mt-8 lg:-mt-10 font-sans">
            <!-- Hero Header with Premium Gradient & Glassmorphism -->
            <header class="relative overflow-hidden bg-white p-8 sm:p-10 rounded-[2.5rem] border border-gray-100 shadow-sm flex flex-col md:flex-row items-center gap-8 mb-8">
                <div class="absolute top-0 right-0 -mt-16 -mr-16 w-80 h-80 bg-blue-50/50 rounded-full blur-3xl opacity-60"></div>
                <div class="absolute bottom-0 left-0 -mb-16 -ml-16 w-64 h-64 bg-emerald-50/30 rounded-full blur-2xl opacity-40"></div>
                
                <div class="relative z-10 h-20 w-20 rounded-[1.5rem] bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100 shrink-0 shadow-sm">
                    <UserGroupIcon class="h-10 w-10" />
                </div>
                <div class="relative z-10 text-center md:text-left flex-1">
                    <div class="flex items-center justify-center md:justify-start gap-2 mb-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-100/50 text-blue-700 text-[10px] font-black uppercase tracking-[0.2em] rounded-full border border-blue-200/50">
                            Espace Formateur
                        </span>
                        <span class="px-3 py-1 bg-slate-100 text-slate-500 text-[10px] font-black uppercase tracking-[0.2em] rounded-full border border-slate-200/50">Gestion Pédagogique</span>
                    </div>
                    <h1 class="text-4xl sm:text-5xl font-black text-gray-900 tracking-tight leading-none mb-3">
                        Mes <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 via-indigo-600 to-emerald-600">Groupes</span>
                    </h1>
                    <p class="text-gray-500 text-sm font-medium">
                        Consultez la liste de vos groupes de formation et gérez le détail de vos apprenants.
                    </p>
                </div>
            </header>

            <div v-if="groups.length === 0" class="bg-white rounded-3xl p-12 text-center shadow-sm border border-gray-100">
                <div class="h-16 w-16 bg-gray-50 text-gray-400 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <AcademicCapIcon class="h-8 w-8" />
                </div>
                <p class="text-gray-600 font-bold text-lg">Aucun groupe</p>
                <p class="text-gray-400 text-sm">Vous n'avez aucun groupe assigné pour le moment.</p>
            </div>

            <div v-else class="space-y-4">
                <div 
                    v-for="group in groups" 
                    :key="group.id"
                    class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300"
                    :class="openGroups.includes(group.id) ? 'ring-2 ring-blue-500/20 shadow-md' : 'hover:border-blue-200 hover:shadow-md'"
                >
                    <!-- Accordion Header -->
                    <button 
                        @click="toggleGroup(group.id)"
                        class="w-full flex items-center justify-between p-6 text-left focus:outline-none bg-white transition-all hover:bg-slate-50/30"
                    >
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center border border-blue-100 shrink-0">
                                <AcademicCapIcon class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-lg font-black text-gray-900 group-hover:text-blue-600 transition-colors">
                                    {{ group.nom_groupe }} <span class="text-gray-400 font-bold mx-2">•</span> <span class="text-gray-500 font-medium text-sm sm:text-base">{{ group.module?.titre || group.module?.nom_module }}</span>
                                </h2>
                                <div class="flex items-center gap-3 mt-1.5">
                                    <span class="inline-flex items-center text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md bg-slate-50 text-slate-500 border border-slate-200/50">
                                        Année : {{ group.annee_academique }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-6">
                            <div class="text-right hidden sm:block">
                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Effectif</p>
                                <p class="text-base font-black text-blue-600">{{ group.students?.length || 0 }} <span class="text-xs font-medium text-gray-400">apprenants</span></p>
                            </div>
                            <div class="h-8 w-8 rounded-full bg-slate-50 flex items-center justify-center border border-slate-100/50 transition-transform duration-300" :class="openGroups.includes(group.id) ? 'rotate-180 bg-blue-50 text-blue-600 border-blue-100' : 'text-gray-400'">
                                <ChevronDownIcon class="h-4 w-4" />
                            </div>
                        </div>
                    </button>

                    <!-- Accordion Body (Students List) -->
                    <div 
                        v-show="openGroups.includes(group.id)"
                        class="border-t border-gray-100 bg-slate-50/30 p-6"
                    >
                        <div v-if="!group.students || group.students.length === 0" class="text-center py-8">
                            <p class="text-gray-500 italic text-sm">Ce groupe ne contient aucun apprenant pour le moment.</p>
                        </div>
                        
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            <div 
                                v-for="student in group.students" 
                                :key="student.id"
                                class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex flex-col justify-between hover:border-blue-200 hover:shadow-lg transition-all duration-300"
                            >
                                <div class="flex items-start gap-4 mb-4">
                                    <!-- Avatar Placeholder -->
                                    <div class="h-14 w-14 bg-gradient-to-br from-blue-500 via-indigo-500 to-indigo-600 text-white rounded-2xl flex items-center justify-center overflow-hidden font-black text-lg shrink-0 shadow-sm border-2 border-white">
                                        <img v-if="student.profile_photo_url" :src="student.profile_photo_url" class="h-full w-full object-cover">
                                        <template v-else>{{ student.name.charAt(0).toUpperCase() }}</template>
                                    </div>
                                    
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                            <h3 class="text-base font-black text-gray-900 leading-tight truncate w-full" :title="student.name">
                                                {{ student.name }}
                                            </h3>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span v-if="student.id === group.responsable_groupe_id" class="text-[8px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-md bg-blue-600 text-white shadow-sm shrink-0">
                                                Chef de groupe
                                            </span>
                                            <span v-if="student.id === group.adjoint_groupe_id" class="text-[8px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-md bg-amber-500 text-white shadow-sm shrink-0">
                                                Adjoint
                                            </span>

                                            <!-- Nomination Status Indicators -->
                                            <div v-if="student.active_nomination" class="flex items-center gap-1 shrink-0">
                                                <span 
                                                    v-if="student.active_nomination.status === 'pending'"
                                                    class="text-[8px] font-bold uppercase px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 border border-blue-100 flex items-center gap-1 animate-pulse"
                                                >
                                                    <span class="h-1 w-1 rounded-full bg-blue-600"></span>
                                                    {{ student.active_nomination.role === 'responsable' ? 'Chef' : 'Adjoint' }}
                                                </span>
                                                <span 
                                                    v-if="student.active_nomination.status === 'rejected'"
                                                    class="text-[8px] font-bold uppercase px-1.5 py-0.5 rounded bg-red-50 text-red-600 border border-red-100"
                                                >
                                                    {{ student.active_nomination.role === 'responsable' ? 'Chef' : 'Adjoint' }} : Rejeté
                                                </span>
                                                <span 
                                                    v-if="student.active_nomination.status === 'approved'"
                                                    class="text-[8px] font-bold uppercase px-1.5 py-0.5 rounded bg-green-50 text-green-600 border border-green-100 flex items-center gap-1"
                                                >
                                                    <CheckCircleIcon class="h-2 w-2" />
                                                    {{ student.active_nomination.role === 'responsable' ? 'Chef' : 'Adjoint' }}
                                                </span>
                                            </div>

                                            <!-- Nomination Actions for Trainer -->
                                            <div v-if="(!group.responsable_groupe_id || !group.adjoint_groupe_id) && student.id != group.responsable_groupe_id && student.id != group.adjoint_groupe_id && student.active_nomination?.status !== 'approved'" class="flex items-center gap-1 px-1.5 py-0.5 bg-slate-50 rounded-md border border-slate-100 ml-auto">
                                                <button 
                                                    v-if="!group.responsable_groupe_id"
                                                    @click="proposeNomination(group.id, student.id, 'responsable')"
                                                    title="Proposer comme Chef de groupe"
                                                    class="p-1 text-slate-400 hover:text-blue-600 hover:bg-white rounded-md transition-all active:scale-90"
                                                >
                                                    <AcademicCapIcon class="h-3.5 w-3.5" />
                                                </button>
                                                <button 
                                                    v-if="!group.adjoint_groupe_id"
                                                    @click="proposeNomination(group.id, student.id, 'adjoint')"
                                                    title="Proposer comme Adjoint"
                                                    class="p-1 text-slate-400 hover:text-amber-500 hover:bg-white rounded-md transition-all active:scale-90"
                                                >
                                                    <CheckCircleIcon class="h-3.5 w-3.5" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="space-y-3 pt-3 border-t border-slate-100">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Absences</span>
                                        <span class="text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded-md border shrink-0" 
                                            :class="student.absences_count > 0 ? 'bg-red-50 text-red-600 border-red-100' : 'bg-green-50 text-green-600 border-green-100'">
                                            {{ student.absences_count }} absence{{ student.absences_count !== 1 ? 's' : '' }}
                                        </span>
                                    </div>

                                    <!-- Progression Levels -->
                                    <div class="space-y-2">
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between text-[9px] font-bold text-gray-500 uppercase tracking-tighter">
                                                <span>Prog. Individuelle</span>
                                                <span class="text-indigo-600 font-black">{{ student.progression_percentage }}%</span>
                                            </div>
                                            <div class="h-1 w-full bg-slate-100 rounded-full overflow-hidden">
                                                <div class="h-full bg-gradient-to-r from-indigo-400 to-indigo-600 rounded-full transition-all duration-1000" :style="{ width: `${student.progression_percentage}%` }"></div>
                                            </div>
                                        </div>
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between text-[9px] font-bold text-gray-500 uppercase tracking-tighter">
                                                <span>Prog. Classe</span>
                                                <span class="text-blue-600 font-black">{{ student.group_progression }}%</span>
                                            </div>
                                            <div class="h-1 w-full bg-slate-100 rounded-full overflow-hidden">
                                                <div class="h-full bg-gradient-to-r from-blue-400 to-blue-600 rounded-full transition-all duration-1000" :style="{ width: `${student.group_progression}%` }"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-1.5 text-xs text-slate-500 pt-1">
                                        <div class="flex items-center gap-2 truncate" :title="student.application?.niveau_etude">
                                            <div class="h-5 w-5 bg-slate-50 rounded-md flex items-center justify-center text-slate-400 border border-slate-100 shrink-0">
                                                <AcademicCapIcon class="h-3.5 w-3.5" />
                                            </div>
                                            <span class="truncate text-[11px] font-medium">
                                                {{ student.application?.niveau_etude || 'Niveau NC' }} 
                                                <span v-if="student.application?.etablissement" class="text-slate-400 font-normal">({{ student.application.etablissement }})</span>
                                            </span>
                                        </div>
                                        <div v-if="student.application?.date_naissance" class="flex items-center gap-2 truncate">
                                            <div class="h-5 w-5 bg-slate-50 rounded-md flex items-center justify-center text-slate-400 border border-slate-100 shrink-0">
                                                <CakeIcon class="h-3.5 w-3.5" />
                                            </div>
                                            <span class="truncate text-[11px] font-medium">
                                                Né(e) le {{ new Date(student.application.date_naissance).toLocaleDateString('fr-FR') }}
                                                <span v-if="student.application.lieu_naissance" class="text-slate-400 font-normal"> à {{ student.application.lieu_naissance }}</span>
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 truncate" :title="student.email">
                                            <div class="h-5 w-5 bg-slate-50 rounded-md flex items-center justify-center text-slate-400 border border-slate-100 shrink-0">
                                                <EnvelopeIcon class="h-3.5 w-3.5" />
                                            </div>
                                            <span class="truncate text-[11px] font-medium">{{ student.email }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 truncate" :title="student.adresse">
                                            <div class="h-5 w-5 bg-slate-50 rounded-md flex items-center justify-center text-slate-400 border border-slate-100 shrink-0">
                                                <MapPinIcon class="h-3.5 w-3.5" />
                                            </div>
                                            <span class="truncate text-[11px] font-medium">{{ student.adresse || 'Adresse non renseignée' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <ConfirmModal 
            :is-open="confirmNominationModal.isOpen"
            :title="'Confirmation de Nomination'"
            :message="`Voulez-vous vraiment proposer cet apprenant pour le poste de ${confirmNominationModal.roleTitle} ? Cette proposition sera soumise à la validation du secrétariat.`"
            type="info"
            confirm-text="Oui, proposer"
            cancel-text="Annuler"
            :is-loading="nominationForm.processing"
            @confirm="handleConfirmNomination"
            @cancel="confirmNominationModal.isOpen = false"
        />
    </AuthenticatedLayout>
</template>
