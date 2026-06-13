<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { 
    CalendarIcon, 
    UserGroupIcon, 
    ClockIcon,
    MapPinIcon,
    ChevronRightIcon,
    CheckCircleIcon,
    XCircleIcon
} from '@heroicons/vue/24/outline'
import { formatTime } from '@/utils/format'

const props = defineProps({
    schedules: Array,
    selectedDate: String,
})

const date = ref(props.selectedDate)

watch(date, (newDate) => {
    router.get(route('attendance.index'), { date: newDate }, {
        preserveState: true,
        preserveScroll: true,
    })
})

function formatDate(dateString) {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('fr-FR', options);
}

</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-8 px-4">
            <header class="mb-10 flex flex-col sm:flex-row sm:items-end justify-between gap-6">
                <div>
                    <h1 class="text-4xl font-black text-gray-900 tracking-tight">Listes de Présence</h1>
                    <p class="text-gray-500 mt-2 font-medium">Suivi des présences par session de formation.</p>
                </div>
                <div class="flex items-center gap-4 bg-white p-2 rounded-2xl border border-gray-100 shadow-sm">
                    <CalendarIcon class="h-6 w-6 text-blue-600 ml-2" />
                    <input 
                        v-model="date" 
                        type="date" 
                        title="jj/mm/aaaa"
                        class="border-0 focus:ring-0 font-black text-gray-900 cursor-pointer"
                    >
                </div>
            </header>

            <div class="mb-8">
                <h2 class="text-lg font-black text-gray-900 uppercase tracking-widest mb-4 flex items-center gap-2">
                    Sessions du {{ formatDate(date) }}
                </h2>

                <div v-if="schedules.length === 0" class="bg-white rounded-[2rem] p-12 text-center border border-gray-100 flex flex-col items-center">
                    <div class="w-20 h-20 bg-gray-50 text-gray-300 rounded-3xl flex items-center justify-center mb-6">
                        <CalendarIcon class="h-10 w-10" />
                    </div>
                    <h3 class="text-xl font-black text-gray-900">Aucune session prévue</h3>
                    <p class="text-gray-500 mt-2 font-medium max-w-xs mx-auto">Il n'y a pas de cours programmés pour cette date dans l'emploi du temps.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="schedule in schedules" :key="schedule.id" 
                        class="bg-white rounded-[2.5rem] border border-gray-100 p-8 hover:shadow-2xl hover:shadow-gray-200/50 transition duration-500 flex flex-col relative overflow-hidden group">
                        
                        <!-- Status Badge -->
                        <div class="absolute top-6 right-6 flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest"
                            :class="schedule.attendance_taken ? 'bg-green-50 text-green-600' : 'bg-amber-50 text-amber-600'">
                            <CheckCircleIcon v-if="schedule.attendance_taken" class="h-3 w-3" />
                            <ClockIcon v-else class="h-3 w-3" />
                            {{ schedule.attendance_taken ? 'Saisie effectuée' : 'En attente' }}
                        </div>

                        <div class="mb-6 flex items-start gap-4">
                            <div class="p-4 bg-blue-50 text-blue-600 rounded-2xl group-hover:bg-blue-600 group-hover:text-white transition duration-300">
                                <UserGroupIcon class="h-8 w-8" />
                            </div>
                            <div>
                                <span class="text-[10px] font-black text-blue-500 uppercase tracking-widest">{{ schedule.group.nom_groupe }}</span>
                                <h3 class="text-xl font-black text-gray-900 leading-tight mt-1">{{ schedule.group.module.titre }}</h3>
                            </div>
                        </div>

                        <div class="space-y-3 mb-8">
                            <div class="flex items-center gap-3 text-gray-500">
                                <ClockIcon class="h-5 w-5 text-gray-400" />
                                <span class="text-sm font-bold">{{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-gray-500">
                                <MapPinIcon class="h-5 w-5 text-gray-400" />
                                <span class="text-sm font-bold">{{ schedule.room.nom }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-gray-500">
                                <div class="w-5 h-5 bg-gray-100 rounded-full flex items-center justify-center text-[10px] font-black text-gray-400 text-center leading-[20px]">F</div>
                                <span class="text-sm font-bold">{{ schedule.formateur.name }}</span>
                            </div>
                        </div>

                        <Link 
                            :href="route('attendance.history', { schedule: schedule.id })"
                            class="mt-auto w-full py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all flex items-center justify-center gap-2 shadow-lg bg-blue-600 text-white hover:bg-blue-700 shadow-blue-100"
                        >
                            Voir l'historique d'émargement
                            <ChevronRightIcon class="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
