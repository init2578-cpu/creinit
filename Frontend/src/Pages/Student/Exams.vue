<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import {
    PencilSquareIcon,
    ClockIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
    AcademicCapIcon,
    ArrowDownTrayIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    exams: Array,
})

const isLocating = ref(false)
const loadingExamId = ref(null)

const handleExamAction = (exam, action) => {
    if (exam.is_practice) {
        proceedWithAction(exam, action, null, null)
        return
    }

    isLocating.value = true
    loadingExamId.value = exam.id

    if (!navigator.geolocation) {
        window.platformAlert("La géolocalisation n'est pas supportée par votre navigateur.", 'error')
        isLocating.value = false
        loadingExamId.value = null
        return
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            proceedWithAction(exam, action, position.coords.latitude, position.coords.longitude)
        },
        (error) => {
            window.platformAlert("Impossible de récupérer votre position. Veuillez autoriser l'accès au GPS pour passer l'examen.", 'error')
            isLocating.value = false
            loadingExamId.value = null
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 }
    )
}

const proceedWithAction = (exam, action, latitude, longitude) => {
    isLocating.value = false
    loadingExamId.value = null
    
    if (action === 'start') {
        router.get(route('student.exams.show', exam.id), { latitude, longitude })
    } else if (action === 'download') {
        let url = route('exams.download', exam.id)
        if (latitude && longitude) {
            url += `?latitude=${latitude}&longitude=${longitude}`
        }
        window.location.href = url
    }
}

const statusLabel = (exam) => {
    if (!exam.my_result) return 'À faire'
    if (exam.my_result.status === 'blocked' || exam.my_result.status === 'started') return 'Bloqué'
    
    if (!exam.is_practice && !exam.are_grades_published) return 'En attente de validation'
    
    const scoreVal = parseFloat(exam.my_result.score) || 0
    const bonusVal = parseFloat(exam.my_result.bonus) || 0
    const total = scoreVal + bonusVal
    if (bonusVal > 0) {
        return `${total.toFixed(1)} / 20 (+${bonusVal.toFixed(1)} bonus)`
    }
    return `${total.toFixed(1)} / 20`
}

const statusClass = (exam) => {
    if (!exam.my_result) return 'bg-amber-50 text-amber-600 border-amber-100'
    if (exam.my_result.status === 'blocked' || exam.my_result.status === 'started') return 'bg-red-100 text-red-600 border-red-200 animate-pulse'
    
    if (!exam.is_practice && !exam.are_grades_published) return 'bg-gray-100 text-gray-500 border-gray-200'

    const scoreVal = parseFloat(exam.my_result.score) || 0
    const bonusVal = parseFloat(exam.my_result.bonus) || 0
    const score = scoreVal + bonusVal
    if (score >= 14) return 'bg-green-50 text-green-600 border-green-100'
    if (score >= 10) return 'bg-blue-50 text-blue-600 border-blue-100'
    return 'bg-red-50 text-red-600 border-red-100'
}

const formatDateTime = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    return date.toLocaleString('fr-FR', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatTime = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    return date.toLocaleString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>

<template>
    <Head title="Mes Examens" />
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
            <header class="mb-10">
                <h1 class="text-4xl font-black text-gray-900 tracking-tight flex items-center gap-3">
                    <PencilSquareIcon class="h-10 w-10 text-red-500" />
                    Mes Examens
                </h1>
                <p class="mt-2 text-gray-500 font-medium">Consultez vos examens et résultats.</p>
            </header>

            <div class="space-y-4">
                <div
                    v-for="exam in exams"
                    :key="exam.id"
                    class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-gray-100 flex items-center justify-between hover:border-blue-200 transition"
                >
                    <div class="flex items-center gap-5">
                        <div class="h-14 w-14 rounded-2xl flex items-center justify-center shadow-sm border border-gray-100"
                            :class="exam.my_result ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-500'"
                        >
                            <CheckCircleIcon v-if="exam.my_result" class="h-7 w-7" />
                            <ClockIcon v-else class="h-7 w-7" />
                        </div>
                        <div>
                            <h3 class="font-black text-gray-900 text-lg tracking-tight">{{ exam.titre }}</h3>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                    {{ exam.module?.titre }} •
                                    {{ exam.is_practice ? 'Entraînement' : 'Examen' }} •
                                    {{ exam.total_points }} pts
                                </p>
                                <div v-if="exam.scheduled_at" class="flex items-center gap-1.5 px-2 py-0.5 bg-blue-50 text-blue-500 rounded-md border border-blue-100 text-[10px] font-black uppercase tracking-tight">
                                    <ClockIcon class="h-3 w-3" />
                                    {{ formatDateTime(exam.scheduled_at) }} - {{ formatTime(exam.end_at) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- Énoncé pour examen sur table (accessible uniquement si débuté) -->
                        <template v-if="!exam.is_online && exam.document_path">
                            <button 
                                v-if="exam.can_start"
                                @click="handleExamAction(exam, 'download')"
                                :disabled="isLocating && loadingExamId === exam.id"
                                class="px-4 py-2 bg-purple-100 text-purple-700 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-purple-200 transition flex items-center gap-2 border border-purple-200 disabled:opacity-50"
                            >
                                <ArrowPathIcon v-if="isLocating && loadingExamId === exam.id" class="h-3.5 w-3.5 animate-spin" />
                                <ArrowDownTrayIcon v-else class="h-3.5 w-3.5" />
                                {{ isLocating && loadingExamId === exam.id ? 'Vérification GPS...' : 'Énoncé' }}
                            </button>
                            <span 
                                v-else
                                class="px-4 py-2 bg-gray-50 text-gray-400 rounded-xl text-[9px] font-black uppercase tracking-widest border border-gray-200 italic"
                                title="L'énoncé sera téléchargeable à l'heure prévue"
                            >
                                En attente
                            </span>
                        </template>

                        <span v-if="!exam.is_online" class="px-3 py-1 bg-gray-100 text-gray-500 text-[9px] font-black uppercase tracking-tighter rounded-md border border-gray-200">
                            Sur Table
                        </span>

                        <!-- État de disponibilité -->
                        <span v-if="exam.has_ended && !exam.my_result" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border bg-red-50 text-red-400 border-red-100 italic">
                            Terminé
                        </span>
                        <span v-else-if="!exam.can_start && !exam.my_result" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border bg-blue-50 text-blue-500 border-blue-100 italic animate-pulse">
                            Bientôt
                        </span>

                        <span v-if="exam.my_result" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border" :class="statusClass(exam)">
                            {{ statusLabel(exam) }}
                        </span>

                        <button
                            v-if="exam.is_online && exam.can_start && (!exam.my_result || exam.is_practice) && exam.my_result?.status !== 'blocked' && exam.my_result?.status !== 'started'"
                            @click="handleExamAction(exam, 'start')"
                            :disabled="isLocating && loadingExamId === exam.id"
                            class="px-5 py-2.5 bg-gray-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-red-600 transition shadow-lg shadow-gray-200 flex items-center justify-center gap-2 disabled:opacity-50"
                        >
                            <ArrowPathIcon v-if="isLocating && loadingExamId === exam.id" class="h-3.5 w-3.5 animate-spin" />
                            {{ isLocating && loadingExamId === exam.id ? 'GPS...' : (exam.my_result ? 'Refaire' : 'Commencer') }}
                        </button>
                    </div>
                </div>

                <div v-if="exams.length === 0" class="py-20 text-center text-gray-400 font-bold italic bg-white rounded-[2.5rem] border border-dashed border-gray-200">
                    <AcademicCapIcon class="h-12 w-12 mx-auto mb-4 text-gray-200" />
                    Aucun examen disponible pour le moment.
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
