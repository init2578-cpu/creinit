<script setup>
import { ref, onMounted } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { 
    MapPinIcon, 
    CheckCircleIcon, 
    XCircleIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    group: Object,
    students: Array
})

const isLocating = ref(false)
const locationError = ref(null)
const locationValidated = ref(props.group.gps_check_required === 0 || props.group.gps_check_required === false || !props.group.gps_check_required)

const form = useForm({
    group_id: props.group.id,
    date: new Date().toISOString().split('T')[0],
    latitude: (props.group.gps_check_required === 0 || props.group.gps_check_required === false || !props.group.gps_check_required) ? 0 : null,
    longitude: (props.group.gps_check_required === 0 || props.group.gps_check_required === false || !props.group.gps_check_required) ? 0 : null,
    attendances: props.students.map(s => ({
        user_id: s.id,
        status: 'present'
    }))
})

function verifyPosition() {
    isLocating.value = true
    locationError.value = null

    if (!navigator.geolocation) {
        locationError.value = "La géolocalisation n'est pas supportée par votre navigateur."
        isLocating.value = false
        return
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.latitude = position.coords.latitude
            form.longitude = position.coords.longitude
            locationValidated.value = true
            isLocating.value = false
        },
        (error) => {
            locationError.value = "Impossible de récupérer votre position. Veuillez autoriser l'accès au GPS."
            isLocating.value = false
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 }
    )
}

function submitAttendance() {
    form.post(route('attendances.store-batch'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success handling via flash messages
        }
    })
}
</script>

<template>
    <Head :title="'Appel - ' + group.nom_groupe" />

    <AuthenticatedLayout>
        <div class="max-w-2xl mx-auto py-6 px-4 pb-32">
            <header class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">{{ group.nom_groupe }}</h1>
                <p class="text-gray-500 italic">Feuille d'émargement du {{ form.date }}</p>
            </header>

            <!-- GPS Verification Card (PROMPT FRONTEND 2) -->
            <div v-if="group.gps_check_required" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8 text-center">
                <div v-if="!locationValidated" class="space-y-4">
                    <MapPinIcon class="h-12 w-12 text-blue-500 mx-auto" />
                    <h2 class="text-lg font-semibold">Validation de présence requise</h2>
                    <p class="text-sm text-gray-500">Vous devez être présent au CRE pour valider l'appel.</p>
                    
                    <button 
                        @click="verifyPosition"
                        :disabled="isLocating"
                        class="w-full py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition flex items-center justify-center gap-2"
                    >
                        <ArrowPathIcon v-if="isLocating" class="h-5 w-5 animate-spin" />
                        {{ isLocating ? 'Vérification GPS...' : 'Vérifier ma position' }}
                    </button>
                    
                    <p v-if="locationError" class="text-sm text-red-600 font-medium">⚠️ {{ locationError }}</p>
                </div>

                <div v-else class="flex items-center justify-center gap-3 text-green-600 bg-green-50 py-3 rounded-xl border border-green-100">
                    <CheckCircleIcon class="h-6 w-6" />
                    <span class="font-bold">Position validée</span>
                </div>
            </div>

            <!-- Attendance List -->
            <div v-if="locationValidated" class="space-y-4">
                <div v-for="(student, index) in students" :key="student.id" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                    <span class="font-medium text-gray-900">{{ student.name }}</span>
                    
                    <div class="flex gap-2">
                        <label class="cursor-pointer">
                            <input 
                                type="radio" 
                                :name="'student-' + student.id" 
                                v-model="form.attendances[index].status" 
                                value="present"
                                class="hidden peer"
                            >
                            <span class="px-4 py-1.5 rounded-lg border text-sm font-medium transition peer-checked:bg-green-600 peer-checked:text-white peer-checked:border-green-600 text-gray-400 border-gray-200">
                                Présent
                            </span>
                        </label>
                        <label class="cursor-pointer">
                            <input 
                                type="radio" 
                                :name="'student-' + student.id" 
                                v-model="form.attendances[index].status" 
                                value="absent_non_justifie"
                                class="hidden peer"
                            >
                            <span class="px-4 py-1.5 rounded-lg border text-sm font-medium transition peer-checked:bg-red-600 peer-checked:text-white peer-checked:border-red-600 text-gray-400 border-gray-200">
                                Absent
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Floating Validation Button -->
            <div v-if="locationValidated" class="fixed bottom-6 left-1/2 -translate-x-1/2 w-full max-w-md px-6 z-30">
                <button 
                    @click="submitAttendance"
                    :disabled="form.processing"
                    class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold hover:bg-indigo-700 transition shadow-2xl flex items-center justify-center gap-3"
                >
                    <CheckCircleIcon class="h-6 w-6" />
                    Valider l'appel
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
