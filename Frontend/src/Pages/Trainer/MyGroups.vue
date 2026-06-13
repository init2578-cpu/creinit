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
        <div class="max-w-5xl mx-auto py-4 px-4 -mt-6 sm:-mt-8 lg:-mt-10">
            <header class="mb-6">
                <div class="flex items-center gap-3 mb-2">
                    <UserGroupIcon class="h-8 w-8 text-blue-600" />
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight">Mes Groupes</h1>
                </div>
                <p class="text-gray-500">Consultez la liste de vos groupes de formation et le détail de vos apprenants.</p>
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
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300"
                    :class="openGroups.includes(group.id) ? 'ring-2 ring-blue-500/20 shadow-md' : 'hover:border-blue-200 hover:shadow-md'"
                >
                    <!-- Accordion Header -->
                    <button 
                        @click="toggleGroup(group.id)"
                        class="w-full flex items-center justify-between p-6 text-left focus:outline-none"
                    >
                        <div class="flex items-center gap-4">
                            <div class="h-12 w-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center shrink-0">
                                <AcademicCapIcon class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors">
                                    {{ group.nom_groupe }} - <span class="text-gray-500 font-medium">{{ group.module?.titre || group.module?.nom_module }}</span>
                                </h2>
                                <div class="flex items-center gap-3 mt-1">
                                    <span class="inline-flex items-center text-xs font-bold px-2 py-0.5 rounded-md bg-gray-50 text-gray-600 border border-gray-200">
                                        Année académique : {{ group.annee_academique }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-6">
                            <div class="text-right hidden sm:block">
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Effectif</p>
                                <p class="text-lg font-black text-blue-600">{{ group.students?.length || 0 }} <span class="text-sm font-medium text-gray-400">apprenants</span></p>
                            </div>
                            <div class="h-8 w-8 rounded-full bg-gray-50 flex items-center justify-center transition-transform duration-300" :class="openGroups.includes(group.id) ? 'rotate-180 bg-blue-50 text-blue-600' : 'text-gray-400'">
                                <ChevronDownIcon class="h-5 w-5" />
                            </div>
                        </div>
                    </button>

                    <!-- Accordion Body (Students List) -->
                    <div 
                        v-show="openGroups.includes(group.id)"
                        class="border-t border-gray-100 bg-gray-50/50 p-6"
                    >
                        <div v-if="!group.students || group.students.length === 0" class="text-center py-8">
                            <p class="text-gray-500 italic text-sm">Ce groupe ne contient aucun apprenant pour le moment.</p>
                        </div>
                        
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div 
                                v-for="student in group.students" 
                                :key="student.id"
                                class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex items-center gap-4 hover:border-blue-200 transition-colors"
                            >
                                <!-- Avatar Placeholder -->
                                <div class="h-10 w-10 bg-gradient-to-br from-blue-500 to-indigo-600 text-white rounded-full flex items-center justify-center overflow-hidden font-bold text-sm shrink-0 shadow-inner border border-white">
                                    <img v-if="student.profile_photo_url" :src="student.profile_photo_url" class="h-full w-full object-cover">
                                    <template v-else>{{ student.name.charAt(0).toUpperCase() }}</template>
                                </div>
                                
                                <div class="min-w-0 w-full">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-sm font-bold text-gray-900 whitespace-normal" :title="student.name">
                                                {{ student.name }}
                                            </h3>
                                            <!-- Discriminants -->
                                            <span v-if="student.id === group.responsable_groupe_id" class="text-[9px] font-black uppercase tracking-tighter px-1.5 py-0.5 rounded bg-blue-600 text-white shadow-sm shrink-0">
                                                Chef de groupe
                                            </span>
                                            <span v-if="student.id === group.adjoint_groupe_id" class="text-[9px] font-black uppercase tracking-tighter px-1.5 py-0.5 rounded bg-amber-500 text-white shadow-sm shrink-0">
                                                Adjoint
                                            </span>

                                            <!-- Nomination Status Indicators -->
                                            <div v-if="student.active_nomination" class="flex items-center gap-1 shrink-0">
                                                <span 
                                                    v-if="student.active_nomination.status === 'pending'"
                                                    class="text-[8px] font-bold uppercase px-1.5 py-0.5 rounded-full bg-blue-50 text-blue-600 border border-blue-100 flex items-center gap-1 animate-pulse"
                                                >
                                                    <span class="h-1 w-1 rounded-full bg-blue-600"></span>
                                                    {{ student.active_nomination.role === 'responsable' ? 'Chef' : 'Adjoint' }} : En attente
                                                </span>
                                                <span 
                                                    v-if="student.active_nomination.status === 'rejected'"
                                                    class="text-[8px] font-bold uppercase px-1.5 py-0.5 rounded-full bg-red-50 text-red-600 border border-red-100"
                                                >
                                                    {{ student.active_nomination.role === 'responsable' ? 'Chef' : 'Adjoint' }} : Rejeté
                                                </span>
                                                <span 
                                                    v-if="student.active_nomination.status === 'approved'"
                                                    class="text-[8px] font-bold uppercase px-1.5 py-0.5 rounded-full bg-green-50 text-green-600 border border-green-100 flex items-center gap-1"
                                                >
                                                    <CheckCircleIcon class="h-2 w-2" />
                                                    {{ student.active_nomination.role === 'responsable' ? 'Chef' : 'Adjoint' }} : Validé
                                                </span>
                                            </div>

                                            <!-- Nomination Actions for Trainer -->
                                            <div v-if="(!group.responsable_groupe_id || !group.adjoint_groupe_id) && student.id != group.responsable_groupe_id && student.id != group.adjoint_groupe_id && student.active_nomination?.status !== 'approved'" class="flex items-center gap-1.5 px-2 py-1 bg-gray-50/50 rounded-lg border border-gray-100 ml-auto">
                                                <button 
                                                    v-if="!group.responsable_groupe_id"
                                                    @click="proposeNomination(group.id, student.id, 'responsable')"
                                                    title="Proposer comme Chef de groupe"
                                                    class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-white rounded-md transition-all active:scale-90"
                                                >
                                                    <AcademicCapIcon class="h-4 w-4" />
                                                </button>
                                                <button 
                                                    v-if="!group.adjoint_groupe_id"
                                                    @click="proposeNomination(group.id, student.id, 'adjoint')"
                                                    title="Proposer comme Adjoint"
                                                    class="p-1.5 text-gray-400 hover:text-amber-500 hover:bg-white rounded-md transition-all active:scale-90"
                                                >
                                                    <CheckCircleIcon class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </div>

                                        <span class="text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded-md border shrink-0" 
                                            :class="student.absences_count > 0 ? 'bg-red-50 text-red-600 border-red-100' : 'bg-green-50 text-green-600 border-green-100'">
                                            {{ student.absences_count }} absence{{ student.absences_count !== 1 ? 's' : '' }}
                                        </span>
                                    </div>
                                    
                                        <!-- Progression Level -->
                                        <div class="mt-3 space-y-1">
                                            <div class="flex items-center justify-between text-[10px] font-bold text-gray-500 uppercase tracking-tighter">
                                                <span>Progression</span>
                                                <span class="text-blue-600">{{ student.progression_percentage }}%</span>
                                            </div>
                                            <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                                                <div 
                                                    class="h-full bg-blue-600 rounded-full transition-all duration-1000"
                                                    :style="{ width: `${student.progression_percentage}%` }"
                                                ></div>
                                            </div>
                                        </div>

                                        <div class="pt-2 space-y-1.5 text-xs text-gray-500">
                                        <div class="flex items-center gap-1.5 truncate" :title="student.application?.niveau_etude">
                                            <AcademicCapIcon class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                                            <span class="truncate">
                                                {{ student.application?.niveau_etude || 'Niveau NC' }} 
                                                <span v-if="student.application?.etablissement" class="text-gray-400 font-medium">({{ student.application.etablissement }})</span>
                                            </span>
                                        </div>
                                        <div v-if="student.application?.date_naissance" class="flex items-center gap-1.5 truncate">
                                            <CakeIcon class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                                            <span class="truncate">
                                                Né(e) le {{ new Date(student.application.date_naissance).toLocaleDateString('fr-FR') }}
                                                <span v-if="student.application.lieu_naissance" class="text-gray-400 font-medium"> à {{ student.application.lieu_naissance }}</span>
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1.5 truncate" :title="student.email">
                                            <EnvelopeIcon class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                                            <span class="truncate">{{ student.email }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 truncate" :title="student.adresse">
                                            <MapPinIcon class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                                            <span class="truncate">{{ student.adresse || 'Adresse non renseignée' }}</span>
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
            @confirm="handleConfirmNomination"
            @cancel="confirmNominationModal.isOpen = false"
        />
    </AuthenticatedLayout>
</template>
