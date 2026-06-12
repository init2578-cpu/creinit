<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { 
    UserPlusIcon, 
    UserMinusIcon, 
    ArrowLeftIcon,
    MagnifyingGlassIcon,
    AcademicCapIcon,
    CheckCircleIcon,
    LockClosedIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    group: Object,
    currentStudents: Array,
    availableStudents: Array
})

const search = ref('')
const selectedUsers = ref([])

const filteredAvailable = computed(() => {
    return props.availableStudents.filter(s => 
        s.name.toLowerCase().includes(search.value.toLowerCase()) ||
        s.email?.toLowerCase().includes(search.value.toLowerCase()) ||
        s.telephone.includes(search.value)
    )
})

const form = useForm({
    user_ids: []
})

const toggleSelection = (userId) => {
    const index = selectedUsers.value.indexOf(userId)
    if (index > -1) {
        selectedUsers.value.splice(index, 1)
    } else {
        selectedUsers.value.push(userId)
    }
}

const addStudents = () => {
    form.user_ids = selectedUsers.value
    form.post(route('groups.students.store', props.group.id), {
        onSuccess: () => {
            selectedUsers.value = []
        }
    })
}

const removeStudent = (studentId) => {
    if (confirm('Voulez-vous retirer cet apprenant du groupe ?')) {
        form.delete(route('groups.students.destroy', [props.group.id, studentId]))
    }
}

const nominate = (userId, role) => {
    const data = {
        module_id: props.group.module_id,
        formateur_id: props.group.formateur_id,
        annee_academique: props.group.annee_academique,
        responsable_groupe_id: props.group.responsable_groupe_id,
        adjoint_groupe_id: props.group.adjoint_groupe_id
    }

    if (role === 'responsable') {
        data.responsable_groupe_id = data.responsable_groupe_id === userId ? null : userId
        // If they were assistant, they can't be anymore
        if (data.adjoint_groupe_id === userId) data.adjoint_groupe_id = null
    } else {
        data.adjoint_groupe_id = data.adjoint_groupe_id === userId ? null : userId
        // If they were leader, they can't be anymore
        if (data.responsable_groupe_id === userId) data.responsable_groupe_id = null
    }

    useForm(data).put(route('groups.update', props.group.id), {
        preserveScroll: true
    })
}
</script>

<template>
    <Head :title="'Apprenants - ' + group.nom_groupe" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div class="flex items-center gap-4">
                    <Link 
                        :href="route('groups.index')"
                        class="h-12 w-12 bg-white border border-gray-100 rounded-2xl flex items-center justify-center text-gray-400 hover:text-indigo-600 hover:border-indigo-100 transition shadow-sm"
                    >
                        <ArrowLeftIcon class="h-6 w-6" />
                    </Link>
                    <div>
                        <h2 class="text-3xl font-black text-gray-900 tracking-tight">{{ group.nom_groupe }}</h2>
                        <p class="text-gray-500 font-medium">
                            {{ group.module.titre }} <span class="mx-2 text-gray-300">•</span> {{ group.formateur?.name || 'Sans formateur' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Closed Group Banner -->
            <div v-if="group.status === 'closed'" class="mb-6 flex items-center gap-4 p-5 bg-gray-100 border border-gray-200 rounded-3xl text-gray-500">
                <LockClosedIcon class="h-6 w-6 shrink-0 text-gray-400" />
                <div>
                    <p class="font-black text-gray-700 text-sm">Formation terminée &mdash; Groupe clôturé</p>
                    <p class="text-xs font-medium">L’effectif de ce groupe est figé. Aucun apprenant ne peut être ajouté ou retiré.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Current Roster -->
                <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm overflow-hidden flex flex-col h-[700px]">
                    <div class="p-8 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-black text-gray-900 tracking-tight">Liste du Groupe</h3>
                            <p class="text-indigo-600 text-xs font-black uppercase tracking-widest">{{ currentStudents.length }} Apprenants inscrits</p>
                        </div>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
                        <div v-for="student in currentStudents" :key="student.id" class="p-4 rounded-3xl border border-gray-50 bg-white hover:border-indigo-100 transition group">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 rounded-2xl bg-indigo-50 flex items-center justify-center overflow-hidden text-indigo-600 font-black text-lg">
                                        <img v-if="student.profile_photo_url" :src="student.profile_photo_url" class="h-full w-full object-cover">
                                        <template v-else>{{ student.name.charAt(0) }}</template>
                                    </div>
                                    <div>
                                        <p class="font-black text-gray-900 leading-tight">{{ student.name }}</p>
                                        <p class="text-xs text-gray-400 font-medium">{{ student.email || student.telephone }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <!-- Action buttons hidden for closed groups -->
                                    <template v-if="group.status !== 'closed'">
                                        <!-- Nomination Buttons -->
                                        <button 
                                            @click="nominate(student.id, 'responsable')"
                                            class="p-2.5 rounded-xl transition-all border"
                                            :class="group.responsable_groupe_id === student.id 
                                                ? 'bg-blue-600 border-blue-600 text-white shadow-lg shadow-blue-100' 
                                                : 'bg-white border-gray-100 text-gray-400 hover:text-blue-600 hover:border-blue-100'"
                                            title="Nommer Chef de Groupe"
                                        >
                                            <AcademicCapIcon class="h-5 w-5" />
                                        </button>
                                        <button 
                                            @click="nominate(student.id, 'adjoint')"
                                            class="p-2.5 rounded-xl transition-all border"
                                            :class="group.adjoint_groupe_id === student.id 
                                                ? 'bg-amber-500 border-amber-500 text-white shadow-lg shadow-amber-100' 
                                                : 'bg-white border-gray-100 text-gray-400 hover:text-amber-500 hover:border-amber-100'"
                                            title="Nommer Adjoint"
                                        >
                                            <CheckCircleIcon class="h-5 w-5" />
                                        </button>
                                        <button 
                                            @click="removeStudent(student.id)"
                                            class="p-2.5 text-gray-300 hover:text-red-600 hover:bg-red-50 rounded-xl transition"
                                            title="Retirer du groupe"
                                        >
                                            <UserMinusIcon class="h-5 w-5" />
                                        </button>
                                    </template>
                                    <span v-else class="text-[10px] font-black text-gray-300 uppercase tracking-widest">Figé</span>
                                </div>
                            </div>
                        </div>
                        <div v-if="currentStudents.length === 0" class="h-full flex flex-col items-center justify-center text-gray-300">
                            <AcademicCapIcon class="h-16 w-16 mb-4 opacity-20" />
                            <p class="font-bold">Aucun apprenant dans ce groupe.</p>
                        </div>
                    </div>
                </div>

                <!-- Available Students panel: hidden when group is closed -->
                <div v-if="group.status !== 'closed'" class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm overflow-hidden flex flex-col h-[700px]">
                    <div class="p-8 border-b border-gray-100 bg-gray-50/50 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-black text-gray-900 tracking-tight">Apprenants Disponibles</h3>
                            <span class="px-3 py-1 bg-green-100 text-green-700 text-[10px] font-black uppercase tracking-widest rounded-full">Admis au module</span>
                        </div>
                        
                        <div class="relative">
                            <input 
                                v-model="search"
                                type="text" 
                                placeholder="Rechercher un apprenant..."
                                class="w-full bg-white border-gray-200 rounded-2xl pl-12 pr-4 py-4 text-sm font-bold focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm"
                            >
                            <MagnifyingGlassIcon class="h-5 w-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" />
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar">
                        <label 
                            v-for="student in filteredAvailable" 
                            :key="student.id" 
                            class="flex items-center justify-between p-4 rounded-3xl border border-gray-50 cursor-pointer hover:border-indigo-200 transition-all select-none"
                            :class="{'bg-indigo-50/30 border-indigo-200': selectedUsers.includes(student.id)}"
                        >
                            <div class="flex items-center gap-4">
                                <div class="relative">
                                    <div class="h-12 w-12 rounded-2xl bg-gray-100 flex items-center justify-center text-gray-400 font-black text-lg overflow-hidden transition-colors"
                                        :class="{'bg-indigo-600 text-white': selectedUsers.includes(student.id)}">
                                        <template v-if="!selectedUsers.includes(student.id)">
                                            <img v-if="student.profile_photo_url" :src="student.profile_photo_url" class="h-full w-full object-cover">
                                            <span v-else>{{ student.name.charAt(0) }}</span>
                                        </template>
                                        <CheckCircleIcon v-else class="h-6 w-6" />
                                    </div>
                                    <input 
                                        type="checkbox" 
                                        :value="student.id" 
                                        v-model="selectedUsers"
                                        class="hidden"
                                    >
                                </div>
                                <div>
                                    <p class="font-black text-gray-900 leading-tight">{{ student.name }}</p>
                                    <p class="text-xs text-gray-400 font-medium">{{ student.telephone }}</p>
                                </div>
                            </div>
                            <div class="text-[10px] font-black uppercase tracking-widest text-indigo-400">
                                {{ student.sexe === 'M' ? 'Homme' : 'Femme' }}
                            </div>
                        </label>
                        <div v-if="filteredAvailable.length === 0" class="h-full flex flex-col items-center justify-center text-gray-300">
                            <MagnifyingGlassIcon class="h-16 w-16 mb-4 opacity-20" />
                            <p class="font-bold">Aucun apprenant disponible.</p>
                        </div>
                    </div>

                    <div class="p-8 border-t border-gray-100 bg-gray-50/50">
                        <button 
                            @click="addStudents"
                            :disabled="selectedUsers.length === 0 || form.processing"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-5 rounded-[1.5rem] font-black transition-all shadow-xl shadow-indigo-100 active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-3"
                        >
                            <UserPlusIcon class="h-5 w-5" />
                            Ajouter {{ selectedUsers.length }} sélectionné(s)
                        </button>
                    </div>
                </div>

                <!-- Closed group placeholder -->
                <div v-else class="bg-gray-50 rounded-[2.5rem] border-2 border-dashed border-gray-200 flex flex-col items-center justify-center h-[700px] gap-4 text-center p-12">
                    <div class="h-20 w-20 bg-gray-100 rounded-3xl flex items-center justify-center">
                        <LockClosedIcon class="h-10 w-10 text-gray-300" />
                    </div>
                    <h3 class="text-xl font-black text-gray-400">Groupe Clôturé</h3>
                    <p class="text-gray-400 text-sm font-medium max-w-xs">L’effectif est figé. Pour modifier ce groupe, réouvrez-le depuis la page des groupes.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
</style>
