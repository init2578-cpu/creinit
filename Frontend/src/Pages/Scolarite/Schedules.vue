<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { router, Head, useForm } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { 
    CalendarIcon, 
    PlusIcon, 
    MapPinIcon, 
    UserIcon,
    TrashIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ChevronDownIcon,
    PencilSquareIcon
} from '@heroicons/vue/24/outline'
import { formatTime } from '@/utils/format'

const props = defineProps({
    schedules: Array,
    rooms: Array,
    groups: Array,
    formateurs: Array
})

const days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
const hours = Array.from({ length: 13 }, (_, i) => i + 8) // 8h to 20h
const hourOptions = Array.from({ length: 13 }, (_, i) => (i + 8).toString().padStart(2, '0'))
const minuteOptions = ['00', '15', '30', '45']

const editingSchedule = ref(null)
const showAddModal = ref(false)

const form = useForm({
    group_id: '',
    room_id: '',
    formateur_id: '',
    day_of_week: 1,
    start_time: '08:00',
    end_time: '10:00',
})

const startTimeHour = ref('08')
const startTimeMinute = ref('00')
const endTimeHour = ref('10')
const endTimeMinute = ref('00')

const openAddModal = () => {
    editingSchedule.value = null
    form.reset()
    startTimeHour.value = '08'
    startTimeMinute.value = '00'
    endTimeHour.value = '10'
    endTimeMinute.value = '00'
    showAddModal.value = true
}

const openEditModal = (schedule) => {
    editingSchedule.value = schedule
    form.group_id = schedule.group_id
    form.room_id = schedule.room_id
    form.formateur_id = schedule.formateur_id
    form.day_of_week = schedule.day_of_week
    
    const [sH, sM] = schedule.start_time.split(':')
    const [eH, eM] = schedule.end_time.split(':')
    
    startTimeHour.value = sH.padStart(2, '0')
    startTimeMinute.value = sM.substring(0, 2)
    endTimeHour.value = eH.padStart(2, '0')
    endTimeMinute.value = eM.substring(0, 2)
    
    form.start_time = `${startTimeHour.value}:${startTimeMinute.value}`
    form.end_time = `${endTimeHour.value}:${endTimeMinute.value}`
    
    showAddModal.value = true
}

const submit = () => {
    form.start_time = `${startTimeHour.value}:${startTimeMinute.value}`
    form.end_time = `${endTimeHour.value}:${endTimeMinute.value}`
    
    if (editingSchedule.value) {
        form.put(route('schedules.update', editingSchedule.value.id), {
            onSuccess: () => {
                showAddModal.value = false
                form.reset()
                editingSchedule.value = null
            }
        })
    } else {
        form.post(route('schedules.store'), {
            onSuccess: () => {
                showAddModal.value = false
                form.reset()
            }
        })
    }
}

const onGroupChange = () => {
    const selectedGroup = props.groups.find(g => g.id === form.group_id)
    if (selectedGroup && selectedGroup.formateur_id) {
        form.formateur_id = selectedGroup.formateur_id
    }
}

const deleteSchedule = (id) => {
    if (confirm('Supprimer ce créneau ?')) {
        router.delete(route('schedules.destroy', id))
    }
}

// Helper to position schedule in grid
const getGridPosition = (day, start) => {
    const dayIndex = days.indexOf(day) + 1
    const hour = parseInt(start.split(':')[0])
    const row = hour - 8 + 1
    return { dayIndex, row }
}

const selectedRoomFilter = ref('all')

const getSchedulesBySlot = (day, hour) => {
    return props.schedules.filter(s => {
        const h = parseInt(s.start_time.split(':')[0])
        const scheduleDayName = days[s.day_of_week - 1]
        const matchesSlot = scheduleDayName === day && h === hour
        if (!matchesSlot) return false
        if (selectedRoomFilter.value !== 'all' && s.room_id !== selectedRoomFilter.value) {
            return false
        }
        return true
    })
}

// Clock and Active Schedule checking logic
const now = ref(new Date())
let clockTimer = null

// Mobile schedule display state
const currentDayIndex = new Date().getDay() // 0 = Sunday, 1 = Monday, ...
const defaultDay = currentDayIndex === 0 || currentDayIndex === 7 ? 'Lundi' : days[currentDayIndex - 1]
const selectedDayMobile = ref(defaultDay)

// Get all schedules for the selected mobile day, sorted by start_time
const mobileSchedules = computed(() => {
    const dayNum = days.indexOf(selectedDayMobile.value) + 1
    return props.schedules
        .filter(s => {
            if (parseInt(s.day_of_week) !== dayNum) return false
            if (selectedRoomFilter.value !== 'all' && s.room_id !== selectedRoomFilter.value) return false
            return true
        })
        .sort((a, b) => a.start_time.localeCompare(b.start_time))
})

onMounted(() => {
    clockTimer = setInterval(() => {
        now.value = new Date()
    }, 30000) // update every 30 seconds
})

onUnmounted(() => {
    if (clockTimer) clearInterval(clockTimer)
})

const timeToMinutes = (timeStr) => {
    if (!timeStr) return 0
    const [h, m] = timeStr.split(':')
    return parseInt(h) * 60 + parseInt(m)
}

const isScheduleToday = (schedule) => {
    if (!schedule) return false
    
    let currentDay = now.value.getDay() // 0 = Sunday, 1 = Monday, ...
    if (currentDay === 0) currentDay = 7 // Map to ISO day where 7 = Sunday
    
    return parseInt(schedule.day_of_week) === currentDay
}

const isScheduleCurrent = (schedule) => {
    if (!isScheduleToday(schedule)) return false
    
    const nowMinutes = now.value.getHours() * 60 + now.value.getMinutes()
    const startMinutes = timeToMinutes(schedule.start_time)
    const endMinutes = timeToMinutes(schedule.end_time)
    
    return nowMinutes >= startMinutes && nowMinutes <= endMinutes
}

const getNextDateForDay = (dayOfWeek) => {
    // dayOfWeek: 1=Monday, ..., 6=Saturday
    const today = new Date()
    const todayDay = today.getDay() === 0 ? 7 : today.getDay()
    let diff = dayOfWeek - todayDay
    if (diff < 0) diff += 7
    const target = new Date(today)
    target.setDate(today.getDate() + diff)
    return target.toISOString().slice(0, 10)
}

const navigateToAttendance = (schedule) => {
    router.visit(route('attendance.history', { schedule: schedule.id }))
}
</script>

<template>
    <Head title="Plannings & Emplois du Temps" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-8 px-4 font-sans">
            <header class="mb-8 flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight">Emploi du Temps</h1>
                    <p class="text-gray-500">Organisation hebdomadaire des salles et formateurs.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative flex items-center bg-white px-4 py-3 rounded-2xl border border-gray-100 shadow-sm text-xs font-bold hover:border-gray-200 transition">
                        <MapPinIcon class="h-4 w-4 text-gray-400 shrink-0 mr-2" />
                        <select 
                            v-model="selectedRoomFilter" 
                            class="bg-transparent border-none outline-none appearance-none font-extrabold text-gray-700 text-xs focus:ring-0 focus:outline-none p-0 pr-7 cursor-pointer"
                            style="border: none !important; outline: none !important; box-shadow: none !important;"
                        >
                            <option value="all">Toutes les salles</option>
                            <option v-for="room in rooms" :key="room.id" :value="room.id">
                                {{ room.nom }}
                            </option>
                        </select>
                        <ChevronDownIcon class="h-4 w-4 text-gray-400 pointer-events-none absolute right-3" />
                    </div>

                    <button 
                        v-if="$page.props.auth.user.roles.some(r => ['Directeur', 'Secrétaire'].includes(r))"
                        @click="openAddModal"
                        class="px-5 py-3 bg-blue-600 text-white rounded-2xl font-black flex items-center gap-2 hover:bg-blue-700 transition shadow-lg shadow-blue-100"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Ajouter un Créneau
                    </button>
                </div>
            </header>

            <!-- Calendar Grid -->
            <div class="hidden md:block bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <div class="grid grid-cols-7 border-b border-gray-50">
                    <div class="p-4 bg-gray-50/50"></div>
                    <div v-for="day in days" :key="day" class="p-4 bg-gray-50/50 text-center text-xs font-black text-gray-400 uppercase tracking-widest">
                        {{ day }}
                    </div>
                </div>

                <div class="grid grid-cols-7 relative">
                    <!-- Left Hour markers -->
                    <div class="flex flex-col">
                        <div v-for="hour in hours" :key="hour" class="min-h-28 p-4 text-[10px] font-bold text-gray-300 border-r border-gray-50 font-mono">
                            {{ formatTime(hour) }}
                        </div>
                    </div>

                    <!-- Column per day -->
                    <div v-for="day in days" :key="day" class="relative group">
                        <div v-for="hour in hours" :key="hour" class="min-h-28 border-r border-b border-gray-50 group-last:border-r-0 p-1.5 flex flex-col gap-1.5">
                            <!-- Cell Content: Render ALL matching schedules -->
                            <div 
                                v-for="schedule in getSchedulesBySlot(day, hour)"
                                :key="schedule.id"
                                @click="navigateToAttendance(schedule)"
                                class="relative p-2.5 rounded-2xl border shadow-sm transition cursor-pointer hover:shadow-md hover:scale-[1.01] group/card"
                                :class="isScheduleCurrent(schedule) 
                                    ? 'bg-indigo-50/90 border-indigo-300 ring-2 ring-indigo-100 text-indigo-800 font-medium' 
                                    : 'bg-indigo-50/60 border-indigo-100 text-indigo-700 hover:border-indigo-300 hover:bg-indigo-50'"
                                :title="`Voir la liste de présence — ${schedule.group.nom_groupe} (${schedule.room?.nom})`"
                            >
                                <!-- Indicator dot / clignotant -->
                                <div v-if="isScheduleToday(schedule)" class="absolute top-2.5 right-2.5 flex h-3.5 w-3.5" :title="schedule.attendance_taken_today ? 'Émargement validé' : 'Émargement en attente'">
                                    <span 
                                        v-if="isScheduleCurrent(schedule)"
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                                        :class="schedule.attendance_taken_today ? 'bg-emerald-400' : 'bg-rose-400'"
                                    ></span>
                                    <span 
                                        class="relative inline-flex rounded-full h-3.5 w-3.5"
                                        :class="schedule.attendance_taken_today ? 'bg-emerald-500' : 'bg-rose-500'"
                                    ></span>
                                </div>

                                <div class="flex flex-col">
                                    <div class="flex items-center justify-between gap-1 mb-1 pr-4">
                                        <span class="text-[9px] font-black uppercase leading-none opacity-60 font-mono">
                                            {{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}
                                        </span>
                                    </div>
                                    
                                    <div class="mb-1">
                                        <span class="inline-block text-[9px] font-black uppercase px-2 py-0.5 rounded-md bg-blue-100/80 text-blue-900 border border-blue-200/60">
                                            📍 {{ schedule.room?.nom }}
                                        </span>
                                    </div>

                                    <p class="text-xs font-black leading-tight text-gray-900 mb-1">
                                        {{ schedule.group.nom_groupe }}
                                    </p>

                                    <div class="flex items-center gap-1 mt-1 pt-1 border-t border-indigo-100/50">
                                        <div class="flex-1 flex items-center gap-1 overflow-hidden">
                                            <UserIcon class="h-3 w-3 flex-shrink-0 opacity-60" />
                                            <span class="text-[9px] font-bold truncate opacity-80">{{ schedule.formateur.name }}</span>
                                        </div>
                                        <div v-if="$page.props.auth.user.roles.some(r => ['Directeur', 'Secrétaire'].includes(r))" class="flex items-center gap-1 opacity-0 group-hover/card:opacity-100 transition-opacity">
                                            <button @click.stop="openEditModal(schedule)" class="text-indigo-600 hover:text-indigo-900 transition p-0.5" title="Modifier">
                                                <PencilSquareIcon class="h-3.5 w-3.5" />
                                            </button>
                                            <button @click.stop="deleteSchedule(schedule.id)" class="text-red-500 hover:text-red-700 transition p-0.5" title="Supprimer">
                                                <TrashIcon class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile and Tablet view (< md) -->
            <div class="block md:hidden">
                <!-- Day selector tabs -->
                <div class="flex space-x-2 overflow-x-auto pb-4 mb-6 scrollbar-none snap-x">
                    <button 
                        v-for="day in days" 
                        :key="day"
                        @click="selectedDayMobile = day"
                        class="snap-center px-4 py-3 rounded-2xl font-black text-xs uppercase tracking-wider transition-all flex-shrink-0"
                        :class="selectedDayMobile === day 
                            ? 'bg-blue-600 text-white shadow-lg shadow-blue-100 scale-105' 
                            : 'bg-gray-50 text-gray-500 hover:bg-gray-100 border border-gray-100'"
                    >
                        {{ day }}
                    </button>
                </div>

                <!-- Schedules List for selected day -->
                <div v-if="mobileSchedules.length > 0" class="space-y-4">
                    <div 
                        v-for="schedule in mobileSchedules" 
                        :key="schedule.id"
                        @click="navigateToAttendance(schedule)"
                        class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 transition relative overflow-hidden cursor-pointer hover:shadow-md hover:border-indigo-200 active:scale-[0.99]"
                        :class="isScheduleCurrent(schedule) ? 'ring-2 ring-indigo-500/10 border-indigo-200' : ''"
                        :title="`Voir la liste de présence — ${schedule.group.nom_groupe}`"
                    >
                        <!-- Active Schedule Glow indicator bar on the left -->
                        <div v-if="isScheduleCurrent(schedule)" class="absolute left-0 top-0 bottom-0 w-1.5 bg-indigo-500"></div>

                        <div class="flex flex-col gap-4">
                            <!-- Time and Badges row -->
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <span class="text-xs font-black text-indigo-600 bg-indigo-50 px-3.5 py-1.5 rounded-full font-mono uppercase">
                                    {{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}
                                </span>
                                
                                <!-- Indicator Badge -->
                                <div class="flex items-center gap-2">
                                    <div v-if="isScheduleToday(schedule)" class="flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                                        :class="schedule.attendance_taken_today ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
                                    >
                                        <span class="relative flex h-2 w-2">
                                            <span v-if="isScheduleCurrent(schedule)" class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" :class="schedule.attendance_taken_today ? 'bg-emerald-400' : 'bg-rose-400'"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2" :class="schedule.attendance_taken_today ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                        </span>
                                        {{ schedule.attendance_taken_today ? (isScheduleCurrent(schedule) ? 'En cours - Émargé' : 'Émargé') : (isScheduleCurrent(schedule) ? 'En cours - En attente' : 'En attente d\'émargement') }}
                                    </div>
                                    <div v-else class="text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-full"
                                        :class="schedule.attendance_taken_today ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-50 text-gray-400'"
                                    >
                                        {{ schedule.attendance_taken_today ? 'Émargé' : 'Non émargé' }}
                                    </div>
                                </div>
                            </div>

                            <!-- Title & Room -->
                            <div>
                                <h3 class="text-lg font-black text-gray-900 leading-tight mb-1">{{ schedule.group.nom_groupe }}</h3>
                                <div class="flex items-center gap-1.5 text-gray-500 text-xs font-semibold">
                                    <MapPinIcon class="h-4 w-4 text-gray-400" />
                                    <span>{{ schedule.room.nom }}</span>
                                </div>
                            </div>

                            <!-- Footer row with Formateur name and action buttons -->
                            <div class="flex items-center justify-between border-t border-gray-50 pt-4 mt-1">
                                <div class="flex items-center gap-2">
                                    <div class="h-8 w-8 rounded-full bg-gray-50 flex items-center justify-center border border-gray-100">
                                        <UserIcon class="h-4 w-4 text-gray-400" />
                                    </div>
                                    <span class="text-xs font-bold text-gray-700">{{ schedule.formateur.name }}</span>
                                </div>

                                <!-- Actions for Directeur/Secretaire -->
                                <div v-if="$page.props.auth.user.roles.some(r => ['Directeur', 'Secrétaire'].includes(r))" class="flex items-center gap-2">
                                    <button 
                                        @click.stop="openEditModal(schedule)" 
                                        class="p-2.5 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-xl transition"
                                    >
                                        <PencilSquareIcon class="h-4 w-4" />
                                    </button>
                                    <button 
                                        @click.stop="deleteSchedule(schedule.id)" 
                                        class="p-2.5 bg-red-50 text-red-600 hover:bg-red-100 rounded-xl transition"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-gray-50/50 rounded-3xl p-12 text-center border border-dashed border-gray-200">
                    <CalendarIcon class="h-10 w-10 text-gray-300 mx-auto mb-3" />
                    <p class="text-xs font-black text-gray-400 uppercase tracking-widest">Aucun cours programmé</p>
                    <p class="text-gray-400 text-xs mt-1">Il n'y a pas de créneau horaire prévu pour ce jour.</p>
                </div>
            </div>

            <!-- Add Schedule Modal -->
            <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
                <div class="bg-white w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl">
                    <h2 class="text-2xl font-black text-gray-900 mb-6 tracking-tight">
                        {{ editingSchedule ? 'Modifier le Créneau' : 'Nouveau Créneau' }}
                    </h2>
                    
                    <div v-if="form.hasErrors" class="mb-6 p-4 bg-red-50 rounded-2xl border border-red-100 flex items-center gap-3 animate-head-shake">
                        <div class="h-8 w-8 bg-red-600/10 rounded-full flex items-center justify-center text-red-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </div>
                        <p class="text-[10px] font-black text-red-600 uppercase tracking-widest leading-tight">Attention : Veuillez corriger les erreurs ci-dessous pour continuer.</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Groupe</label>
                                <select v-model="form.group_id" @change="onGroupChange" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4 focus:ring-2 focus:ring-blue-600">
                                    <option value="">Choisir un groupe</option>
                                    <option v-for="g in groups" :key="g.id" :value="g.id">
                                        {{ g.nom_groupe }} ({{ g.formateur?.name || 'N/A' }})
                                    </option>
                                </select>
                                <p v-if="form.errors.group_id" class="text-red-500 text-[10px] mt-1 font-bold">{{ form.errors.group_id }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Salle</label>
                                <select v-model="form.room_id" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4 focus:ring-2 focus:ring-blue-600">
                                    <option value="">Choisir une salle</option>
                                    <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.nom }}</option>
                                </select>
                                <p v-if="form.errors.room_id" class="text-red-500 text-[10px] mt-1 font-bold">{{ form.errors.room_id }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Jour</label>
                            <select v-model="form.day_of_week" class="w-full bg-gray-50 border-0 rounded-xl font-bold py-3 px-4 focus:ring-2 focus:ring-blue-600">
                                <option v-for="(d, index) in days" :key="d" :value="index + 1">{{ d }}</option>
                            </select>
                            <p v-if="form.errors.day_of_week" class="text-red-500 text-[10px] mt-1 font-bold">{{ form.errors.day_of_week }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Début (24h)</label>
                                <div class="flex items-center gap-2">
                                    <select v-model="startTimeHour" class="flex-1 bg-gray-50 border-0 rounded-xl font-bold py-3 px-2 focus:ring-2 focus:ring-blue-600 appearance-none text-center">
                                        <option v-for="h in hourOptions" :key="h" :value="h">{{ h }}</option>
                                    </select>
                                    <span class="font-black text-gray-300">:</span>
                                    <select v-model="startTimeMinute" class="flex-1 bg-gray-50 border-0 rounded-xl font-bold py-3 px-2 focus:ring-2 focus:ring-blue-600 appearance-none text-center">
                                        <option v-for="m in minuteOptions" :key="m" :value="m">{{ m }}</option>
                                    </select>
                                </div>
                                <p v-if="form.errors.start_time" class="text-red-500 text-[10px] mt-1 font-bold">{{ form.errors.start_time }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Fin (24h)</label>
                                <div class="flex items-center gap-2">
                                    <select v-model="endTimeHour" class="flex-1 bg-gray-50 border-0 rounded-xl font-bold py-3 px-2 focus:ring-2 focus:ring-blue-600 appearance-none text-center">
                                        <option v-for="h in hourOptions" :key="h" :value="h">{{ h }}</option>
                                    </select>
                                    <span class="font-black text-gray-300">:</span>
                                    <select v-model="endTimeMinute" class="flex-1 bg-gray-50 border-0 rounded-xl font-bold py-3 px-2 focus:ring-2 focus:ring-blue-600 appearance-none text-center">
                                        <option v-for="m in minuteOptions" :key="m" :value="m">{{ m }}</option>
                                    </select>
                                </div>
                                <p v-if="form.errors.end_time" class="text-red-500 text-[10px] mt-1 font-bold">{{ form.errors.end_time }}</p>
                            </div>
                        </div>

                        <div class="flex gap-4 mt-8">
                            <button @click="showAddModal = false" type="button" class="flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black">Annuler</button>
                            <button :disabled="form.processing" type="submit" class="flex-[2] py-4 bg-blue-600 text-white rounded-2xl font-black shadow-lg shadow-blue-100">
                                {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
