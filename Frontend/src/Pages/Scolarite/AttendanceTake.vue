<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import { 
    ChevronLeftIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    CalendarIcon,
    InformationCircleIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'
import { formatTime } from '@/utils/format'

const props = defineProps({
    schedule: Object,
    date: String,
    students: Array,
    settings: Object,
})

const form = useForm({
    schedule_id: props.schedule.id,
    date: props.date,
    latitude: null,
    longitude: null,
    students: props.students.map(s => ({
        id: s.id,
        name: s.name,
        status: s.status || 'present'
    }))
})

// GPS check is done server-side via EnsureWithinPremises middleware

const locationError = ref('')
const isCheckingLocation = ref(false)


const checkLocation = () => {
    return new Promise((resolve) => {
        if (!navigator.geolocation) {
            locationError.value = "Géolocalisation non supportée."
            resolve(false); return
        }
        isCheckingLocation.value = true
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                // Store GPS coordinates in form for backend validation and storage
                form.latitude = pos.coords.latitude
                form.longitude = pos.coords.longitude

                isCheckingLocation.value = false
                locationError.value = ''
                resolve(true)
            },
            () => {
                isCheckingLocation.value = false
                locationError.value = "Accès à la position refusé. La géolocalisation est obligatoire pour émarger."
                resolve(false)
            },
            { timeout: 10000, enableHighAccuracy: true }
        )
    })
}

async function handleSave() {
    const locOk = await checkLocation()
    if (!locOk) return

    form.post(route('attendance.store'), {
        preserveScroll: true
    })
}

function setStatusAll(status) {
    form.students.forEach(s => s.status = status)
}

function formatDate(dateString) {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('fr-FR', options);
}

const statusConfig = {
    present: { label: 'Présent', color: 'text-green-600', bg: 'bg-green-50', border: 'border-green-100', icon: CheckCircleIcon },
    absent: { label: 'Absent', color: 'text-red-600', bg: 'bg-red-50', border: 'border-red-100', icon: XCircleIcon },
    late: { label: 'Retard', color: 'text-amber-600', bg: 'bg-amber-50', border: 'border-amber-100', icon: ClockIcon },
    justifie: { label: 'Justifié', color: 'text-blue-600', bg: 'bg-blue-50', border: 'border-blue-100', icon: InformationCircleIcon },
}

</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-5xl mx-auto py-8 px-4">
            <!-- Back Navigation -->
            <Link :href="route('attendance.index', { date: date })" class="inline-flex items-center gap-2 text-gray-500 hover:text-blue-600 font-bold text-sm mb-8 transition group">
                <div class="p-2 bg-white rounded-xl shadow-sm border border-gray-100 group-hover:bg-blue-50 group-hover:border-blue-100 transition">
                    <ChevronLeftIcon class="h-4 w-4" />
                </div>
                Retour aux sessions
            </Link>

            <div class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="p-10 border-b border-gray-50 flex flex-col md:flex-row md:items-center justify-between gap-6 bg-gradient-to-br from-white to-gray-50/50">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[10px] font-black uppercase tracking-widest border border-blue-100">
                                {{ schedule.group.nom_groupe }}
                            </span>
                            <span class="px-3 py-1 bg-gray-50 text-gray-500 rounded-full text-[10px] font-black uppercase tracking-widest border border-gray-100">
                                {{ schedule.room.nom }}
                            </span>
                        </div>
                        <h1 class="text-3xl font-black text-gray-900 tracking-tight">{{ schedule.group.module.titre }}</h1>
                        <p class="text-gray-500 mt-1 font-bold flex items-center gap-2">
                            <CalendarIcon class="h-4 w-4 text-blue-500" />
                            Session du {{ formatDate(date) }}
                            <span class="mx-2 text-gray-300">•</span>
                            <ClockIcon class="h-4 w-4 text-blue-500" />
                            {{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <button @click="setStatusAll('present')" class="px-4 py-2 bg-green-50 text-green-700 rounded-xl text-xs font-black uppercase tracking-widest border border-green-100 hover:bg-green-100 transition">
                            Tous Présents
                        </button>
                        <button @click="setStatusAll('absent')" class="px-4 py-2 bg-red-50 text-red-700 rounded-xl text-xs font-black uppercase tracking-widest border border-red-100 hover:bg-red-100 transition">
                            Tous Absents
                        </button>
                    </div>
                </div>

                <div class="p-0 overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100">
                                <th class="px-10 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Apprenant</th>
                                <th class="px-10 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Status de présence</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="student in form.students" :key="student.id" 
                                class="group transition"
                                :class="student.is_trainer ? 'bg-indigo-50/50 hover:bg-indigo-50' : 'hover:bg-gray-50/30'"
                            >
                                <td class="px-10 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center font-black text-sm"
                                            :class="student.is_trainer ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-400'"
                                        >
                                            {{ student.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <div class="font-black text-gray-900 leading-none">{{ student.name }}</div>
                                                <span v-if="student.is_trainer" class="px-2 py-0.5 bg-indigo-600 text-white text-[8px] font-black rounded uppercase tracking-widest">Formateur</span>
                                            </div>
                                            <div class="text-[10px] font-bold text-gray-400 mt-1 uppercase tracking-tighter">{{ props.students.find(s => s.id === student.id).email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-10 py-6">
                                    <div class="flex justify-center items-center gap-2 p-1.5 bg-gray-50 rounded-2xl w-fit mx-auto border border-gray-100 shadow-inner">
                                        <button 
                                            v-for="(config, key) in statusConfig" 
                                            :key="key"
                                            type="button"
                                            @click="student.status = key"
                                            class="px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2 whitespace-nowrap border border-transparent shadow-none"
                                            :class="student.status === key 
                                                ? `${config.bg} ${config.color} ${config.border} shadow-sm scale-105 ring-4 ring-white` 
                                                : 'text-gray-400 hover:bg-white hover:text-gray-600'"
                                        >
                                            <component :is="config.icon" class="h-3.5 w-3.5" />
                                            {{ config.label }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-10 bg-gray-50/50 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-6">
                    <p class="text-xs font-bold text-gray-400 italic text-center sm:text-left">
                        <InformationCircleIcon class="h-4 w-4 inline mr-1" />
                        L'enregistrement écrasera les données précédentes pour cette session.
                    </p>
                    <div v-if="locationError || form.hasErrors" class="w-full sm:w-auto p-4 bg-red-50 border border-red-100 rounded-[1.5rem] mb-4 sm:mb-0">
                        <div v-if="locationError" class="flex items-center gap-2 text-red-600 text-[10px] font-black uppercase tracking-widest">
                            <ExclamationTriangleIcon class="h-4 w-4" />
                            {{ locationError }}
                        </div>
                        <div v-for="(error, key) in form.errors" :key="key" class="flex items-center gap-2 text-red-600 text-[10px] font-black uppercase tracking-widest">
                            <ExclamationTriangleIcon class="h-4 w-4" />
                            {{ error }}
                        </div>
                    </div>

                    <button 
                        @click="handleSave" 
                        :disabled="form.processing || isCheckingLocation"
                        class="w-full sm:w-auto px-10 py-5 bg-blue-600 text-white rounded-[1.5rem] font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition shadow-xl shadow-blue-100 disabled:opacity-50 flex items-center justify-center gap-3"
                    >
                        <CheckCircleIcon v-if="!form.processing && !isCheckingLocation" class="h-5 w-5" />
                        {{ form.processing ? 'Enregistrement...' : (isCheckingLocation ? 'Vérification position...' : 'Valider la liste de présence') }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
