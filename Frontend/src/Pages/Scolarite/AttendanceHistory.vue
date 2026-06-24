<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'

const page = usePage()
const isTrainer = computed(() => page.props.auth.user?.is_trainer ?? false)
import { 
    ChevronLeftIcon,
    CalendarIcon,
    ClockIcon,
    MapPinIcon,
    UserIcon,
    UserGroupIcon,
    CheckCircleIcon,
    XCircleIcon,
    PlusIcon,
    ArrowRightIcon
} from '@heroicons/vue/24/outline'
import { formatTime } from '@/utils/format'

const props = defineProps({
    schedule: Object,
    history: Array, // Array of { date, total_students, present, absent, late, justified, trainer_status }
})

const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
const getDayName = (dayOfWeekNum) => {
    // schedules.day_of_week is typically 1 (Lundi) to 6 (Samedi)
    const weekDays = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche']
    return weekDays[dayOfWeekNum - 1] || 'Inconnu'
}

function formatDate(dateString) {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('fr-FR', options);
}

// State for creating new session
const todayStr = new Date().toISOString().slice(0, 10)
const newSessionDate = ref(todayStr)

const startNewSession = () => {
    if (!newSessionDate.value) return
    router.visit(route('attendance.take', { schedule: props.schedule.id, date: newSessionDate.value }))
}
</script>

<template>
    <Head :title="`Historique Émargements - ${schedule.group.nom_groupe}`" />

    <AuthenticatedLayout>
        <div class="max-w-6xl mx-auto py-8 px-4 font-sans">
            <!-- Back Button -->
            <Link :href="route('schedules.index')" class="inline-flex items-center gap-2 text-gray-500 hover:text-blue-600 font-bold text-sm mb-8 transition group">
                <div class="p-2 bg-white rounded-xl shadow-sm border border-gray-100 group-hover:bg-blue-50 group-hover:border-blue-100 transition">
                    <ChevronLeftIcon class="h-4 w-4" />
                </div>
                Retour à l'emploi du temps
            </Link>

            <!-- Schedule Header Info Card -->
            <div class="bg-white rounded-[2.5rem] border border-gray-100 p-8 sm:p-10 shadow-sm mb-8 relative overflow-hidden">
                <div class="absolute -right-20 -top-20 w-64 h-64 bg-blue-500/5 rounded-full blur-[100px]"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-3.5 py-1.5 bg-blue-50 text-blue-600 rounded-full text-[10px] font-black uppercase tracking-widest border border-blue-100">
                                {{ schedule.group.nom_groupe }}
                            </span>
                            <span class="px-3.5 py-1.5 bg-gray-50 text-gray-500 rounded-full text-[10px] font-black uppercase tracking-widest border border-gray-100">
                                {{ schedule.room.nom }}
                            </span>
                            <span class="px-3.5 py-1.5 bg-indigo-50 text-indigo-600 rounded-full text-[10px] font-black uppercase tracking-widest border border-indigo-100">
                                {{ getDayName(schedule.day_of_week) }}
                            </span>
                        </div>
                        
                        <h1 class="text-3xl font-black text-gray-900 tracking-tight leading-tight">
                            {{ schedule.group.module.titre }}
                        </h1>

                        <div class="flex flex-wrap gap-4 text-sm font-bold text-gray-500">
                            <div class="flex items-center gap-2">
                                <ClockIcon class="h-5 w-5 text-gray-400" />
                                <span>{{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <UserIcon class="h-5 w-5 text-gray-400" />
                                <span>Formateur : {{ schedule.formateur.name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Panel: Start a new session — visible uniquement pour les administratifs -->
                    <div v-if="$page.props.auth.user.roles.some(r => ['Directeur', 'Secrétaire'].includes(r))" class="w-full md:w-auto bg-gray-50 p-6 rounded-3xl border border-gray-100/50 flex flex-col sm:flex-row items-center gap-4">
                        <div class="w-full sm:w-auto">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Nouvelle session le</label>
                            <input 
                                v-model="newSessionDate" 
                                type="date" 
                                class="w-full sm:w-44 bg-white border border-gray-200 rounded-2xl font-bold py-3 px-4 focus:ring-2 focus:ring-blue-600 focus:border-blue-600 text-gray-800 text-sm cursor-pointer"
                            />
                        </div>
                        <button 
                            @click="startNewSession"
                            class="w-full sm:w-auto mt-5 sm:mt-0 px-6 py-4 bg-blue-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition shadow-lg shadow-blue-100 flex items-center justify-center gap-2 whitespace-nowrap"
                        >
                            <PlusIcon class="h-4 w-4" />
                            Démarrer l'appel
                        </button>
                    </div>
                </div>
            </div>

            <!-- History Section -->
            <div class="space-y-6">
                <h2 class="text-lg font-black text-gray-900 uppercase tracking-widest flex items-center gap-2">
                    <UserGroupIcon class="h-5 w-5 text-gray-400" />
                    Feuilles d'émargement enregistrées
                </h2>

                <div v-if="history.length === 0" class="bg-white rounded-[2.5rem] border border-gray-100 p-16 text-center shadow-sm">
                    <CalendarIcon class="h-12 w-12 text-gray-300 mx-auto mb-4" />
                    <h3 class="text-lg font-black text-gray-900">Aucun historique disponible</h3>
                    <p class="text-gray-500 mt-2 font-medium max-w-sm mx-auto">
                        Aucune feuille de présence n'a encore été enregistrée pour ce créneau horaire. Utilisez le panneau ci-dessus pour lancer un appel.
                    </p>
                </div>

                <div v-else class="grid grid-cols-1 gap-4">
                    <div 
                        v-for="item in history" 
                        :key="item.date"
                        class="bg-white rounded-3xl border border-gray-100 p-6 sm:p-8 hover:shadow-xl hover:shadow-gray-100/50 transition duration-300 flex flex-col lg:flex-row lg:items-center justify-between gap-6"
                    >
                        <div class="space-y-2">
                            <h3 class="text-lg font-black text-gray-900 capitalize">
                                {{ formatDate(item.date) }}
                            </h3>
                            <div class="flex flex-wrap items-center gap-3 text-xs font-bold text-gray-500">
                                <span>Statut Formateur :</span>
                                <span 
                                    class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-widest"
                                    :class="item.trainer_status === 'present' 
                                        ? 'bg-green-50 text-green-600 border border-green-100' 
                                        : (item.trainer_status === 'Non émargé' ? 'bg-gray-100 text-gray-400' : 'bg-red-50 text-red-600 border border-red-100')"
                                >
                                    {{ item.trainer_status === 'present' ? 'Présent' : item.trainer_status }}
                                </span>
                            </div>
                        </div>

                        <!-- Statistics Grid -->
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="bg-green-50 border border-green-100 text-green-700 px-4 py-2.5 rounded-2xl text-center min-w-[70px]">
                                <div class="text-sm font-black">{{ item.present }}</div>
                                <div class="text-[8px] font-black uppercase tracking-widest opacity-75">Présents</div>
                            </div>
                            <div class="bg-red-50 border border-red-100 text-red-700 px-4 py-2.5 rounded-2xl text-center min-w-[70px]">
                                <div class="text-sm font-black">{{ item.absent }}</div>
                                <div class="text-[8px] font-black uppercase tracking-widest opacity-75">Absents</div>
                            </div>
                            <div v-if="!isTrainer" class="bg-amber-50 border border-amber-100 text-amber-700 px-4 py-2.5 rounded-2xl text-center min-w-[70px]">
                                <div class="text-sm font-black">{{ item.late }}</div>
                                <div class="text-[8px] font-black uppercase tracking-widest opacity-75">Retards</div>
                            </div>
                            <div v-if="!isTrainer" class="bg-blue-50 border border-blue-100 text-blue-700 px-4 py-2.5 rounded-2xl text-center min-w-[70px]">
                                <div class="text-sm font-black">{{ item.justified }}</div>
                                <div class="text-[8px] font-black uppercase tracking-widest opacity-75">Justifiés</div>
                            </div>
                            <div class="bg-gray-50 border border-gray-100 text-gray-500 px-4 py-2.5 rounded-2xl text-center min-w-[70px]">
                                <div class="text-sm font-black">{{ item.total_students }}</div>
                                <div class="text-[8px] font-black uppercase tracking-widest opacity-75">Total</div>
                            </div>
                        </div>


                        <!-- Actions — visible uniquement pour les administratifs -->
                        <div v-if="$page.props.auth.user.roles.some(r => ['Directeur', 'Secrétaire'].includes(r))" class="flex items-center gap-3 lg:self-center">
                            <Link 
                                :href="route('attendance.take', { schedule: schedule.id, date: item.date })"
                                class="w-full lg:w-auto px-6 py-4 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-2xl font-black text-xs uppercase tracking-widest transition flex items-center justify-center gap-2"
                            >
                                Modifier la liste
                                <ArrowRightIcon class="h-4 w-4" />
                            </Link>
                        </div>
                        <div v-else class="flex items-center gap-3 lg:self-center">
                            <Link 
                                :href="route('attendance.take', { schedule: schedule.id, date: item.date })"
                                class="w-full lg:w-auto px-6 py-4 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-2xl font-black text-xs uppercase tracking-widest transition flex items-center justify-center gap-2"
                            >
                                Voir la liste
                                <ArrowRightIcon class="h-4 w-4" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
