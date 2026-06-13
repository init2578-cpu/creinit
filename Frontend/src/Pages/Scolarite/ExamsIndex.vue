<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { 
    AcademicCapIcon, 
    PlusIcon, 
    PencilIcon, 
    TrashIcon, 
    DocumentIcon,
    CalendarIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    EyeIcon,
    ArrowUpTrayIcon,
    DocumentTextIcon,
    ClipboardDocumentCheckIcon,
    QueueListIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    exams: Array,
    modules: Array
});

const isModalOpen = ref(false);
const isGradeModalOpen = ref(false);
const editingExam = ref(null);
const selectedExamForGrades = ref(null);
const isQuestionModalOpen = ref(false);
const editingQuestion = ref(null);
const selectedExamForQuestion = ref(null);

const form = useForm({
    module_id: '',
    titre: '',
    type: 'online',
    description: '',
    duree_minutes: 60,
    total_points: 20,
    scheduled_at: '',
    scheduled_end: '',
    document: null
});

const startDate = ref('');
const startHour = ref('08');
const startMin = ref('00');
const endDate = ref('');
const endHour = ref('10');
const endMin = ref('00');

const startTime = computed(() => `${startHour.value}:${startMin.value}`);
const endTime = computed(() => `${endHour.value}:${endMin.value}`);

const hourOptions = Array.from({ length: 24 }, (_, i) => i.toString().padStart(2, '0'));
const minuteOptions = Array.from({ length: 12 }, (_, i) => (i * 5).toString().padStart(2, '0'));

const gradeForm = useForm({
    grades: [] // Array of { user_id, score }
});

const questionForm = useForm({
    enonce: '',
    points: 5,
    type: 'qcm',
    options: [
        { texte: '', is_correct: false },
        { texte: '', is_correct: false }
    ]
});

const openModal = (exam = null) => {
    editingExam.value = exam;
    if (exam) {
        form.module_id = exam.module_id;
        form.titre = exam.titre;
        form.type = exam.type;
        form.description = exam.description;
        form.duree_minutes = exam.duree_minutes;
        form.total_points = exam.total_points;
        form.scheduled_at = exam.scheduled_at ? new Date(exam.scheduled_at).toISOString().slice(0, 16) : '';
        if (form.scheduled_at) {
            const dateObj = new Date(exam.scheduled_at);
            startDate.value = dateObj.toISOString().slice(0, 10);
            startHour.value = dateObj.getHours().toString().padStart(2, '0');
            startMin.value = (Math.round(dateObj.getMinutes() / 5) * 5).toString().padStart(2, '0');
            if (parseInt(startMin.value) > 55) startMin.value = '55';
            
            const endObj = new Date(dateObj.getTime() + exam.duree_minutes * 60000);
            endDate.value = endObj.toISOString().slice(0, 10);
            endHour.value = endObj.getHours().toString().padStart(2, '0');
            endMin.value = (Math.round(endObj.getMinutes() / 5) * 5).toString().padStart(2, '0');
            if (parseInt(endMin.value) > 55) endMin.value = '55';
            
            form.scheduled_at = `${startDate.value}T${startTime.value}`;
            form.scheduled_end = `${endDate.value}T${endTime.value}`;
        } else {
            startDate.value = '';
            startHour.value = '08';
            startMin.value = '00';
            endDate.value = '';
            endHour.value = '10';
            endMin.value = '00';
            form.scheduled_at = '';
            form.scheduled_end = '';
        }
    } else {
        form.reset();
        startDate.value = '';
        startHour.value = '08';
        startMin.value = '00';
        endDate.value = '';
        endHour.value = '10';
        endMin.value = '00';
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    editingExam.value = null;
};

const submit = () => {
    if (editingExam.value) {
        const currentQuestionsPoints = editingExam.value.questions?.reduce((sum, q) => sum + (parseFloat(q.points) || 0), 0) || 0;
        if (form.total_points < currentQuestionsPoints) {
            window.platformAlert(`Impossible de réduire le barème : le total des points des questions existantes (${currentQuestionsPoints}) dépasse le nouveau barème (${form.total_points}).`, 'error');
            return;
        }
        form.post(route('exams.update', { exam: editingExam.value.id, _method: 'PUT' }), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('exams.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteExam = (id) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet examen ?')) {
        form.delete(route('exams.destroy', id));
    }
};

const openGradeModal = async (exam) => {
    selectedExamForGrades.value = exam;
    try {
        const response = await axios.get(route('exams.results', exam.id));
        gradeForm.grades = response.data;
        isGradeModalOpen.value = true;
    } catch (error) {
        console.error(error);
        window.platformAlert("Erreur lors du chargement des étudiants.", 'error');
    }
};

const submitGrades = () => {
    gradeForm.post(route('exams.enter-grades', selectedExamForGrades.value.id), {
        onSuccess: () => {
            isGradeModalOpen.value = false;
        },
    });
};

const openQuestionModal = (exam, question = null) => {
    selectedExamForQuestion.value = exam;
    editingQuestion.value = question;
    if (question) {
        questionForm.enonce = question.enonce;
        questionForm.points = question.points;
        questionForm.type = question.type;
        questionForm.options = question.options.length > 0 ? [...question.options] : [{ texte: '', is_correct: false }, { texte: '', is_correct: false }];
    } else {
        questionForm.reset();
        questionForm.options = [{ texte: '', is_correct: false }, { texte: '', is_correct: false }];
    }
    isQuestionModalOpen.value = true;
};

const currentExamQuestionsTotalPoints = computed(() => {
    if (!selectedExamForQuestion.value || !selectedExamForQuestion.value.questions) return 0;
    return selectedExamForQuestion.value.questions.reduce((sum, q) => sum + (parseFloat(q.points) || 0), 0);
});

const isQuestionLimitExceeded = computed(() => {
    if (!selectedExamForQuestion.value) return false;
    let currentPoints = currentExamQuestionsTotalPoints.value;
    // If we're editing a question in the list, we don't handle it through questionForm yet
    // but the inline edit uses updateQuestionPoints.
    // For the "Add" form (questionForm):
    const newPoints = parseFloat(questionForm.points) || 0;
    return (currentPoints + newPoints) > selectedExamForQuestion.value.total_points;
});

const submitQuestionForm = () => {
    if (isQuestionLimitExceeded.value) {
        window.platformAlert(`Dépassement du barème : le total (${currentExamQuestionsTotalPoints.value + parseFloat(questionForm.points)}) ne peut pas dépasser ${selectedExamForQuestion.value.total_points} pts.`, 'error');
        return;
    }
    questionForm.post(route('exams.questions.store', selectedExamForQuestion.value.id), {
        onSuccess: () => {
            isQuestionModalOpen.value = false;
        }
    });
};

const deleteQuestion = (id) => {
    if (confirm('Supprimer cette question ?')) {
        useForm({}).delete(route('questions.destroy', id));
    }
};

const isModalTotalPointsInvalid = computed(() => {
    if (!editingExam.value) return false;
    const currentQuestionsPoints = editingExam.value.questions?.reduce((sum, q) => sum + (parseFloat(q.points) || 0), 0) || 0;
    return form.total_points < currentQuestionsPoints;
});

const updateQuestionPoints = (question) => {
    useForm({ points: question.points }).patch(route('questions.update', question.id), {
        onSuccess: () => {
            // Updated successfully
        },
        onError: (errors) => {
            // Errors are handled by flash messages in backend, but we can also handle them here if needed.
            // Actually, the backend returns a redirect back with('error', ...).
            // Inertia handles this by putting the message in $page.props.flash.error.
        }
    });
};

const getTypeLabel = (type) => {
    return type === 'online' ? 'Session en ligne' : 'Épreuve sur table';
};

const getTypeClass = (type) => {
    return type === 'online' 
        ? 'bg-blue-50 text-blue-700 border-blue-100' 
        : 'bg-purple-50 text-purple-700 border-purple-100';
};

const isExamEnded = (exam) => {
    if (!exam.scheduled_at) return false;
    const end = new Date(new Date(exam.scheduled_at).getTime() + exam.duree_minutes * 60000);
    return new Date() > end;
};

// Removed handleTimeInput since we now use dropdowns

// Reactive duration calculation
watch(() => [startDate.value, startTime.value, endDate.value, endTime.value], () => {
    if (startDate.value && startTime.value) {
        form.scheduled_at = `${startDate.value}T${startTime.value}`;
    }
    
    if (endDate.value && endTime.value) {
        form.scheduled_end = `${endDate.value}T${endTime.value}`;
    }

    if (form.scheduled_at && form.scheduled_end) {
        const start = new Date(form.scheduled_at);
        const end = new Date(form.scheduled_end);
        const diffMs = end.getTime() - start.getTime();
        if (diffMs > 0) {
            form.duree_minutes = Math.round(diffMs / 60000);
        }
    }
});

</script>

<template>
    <Head title="Gestion des Examens" />

    <AuthenticatedLayout>
        <div class="py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10">
                <div>
                    <h2 class="text-4xl font-black text-gray-900 tracking-tight flex items-center gap-3">
                        <AcademicCapIcon class="h-10 w-10 text-blue-600" />
                        Administration des Examens
                    </h2>
                    <p class="mt-2 text-gray-500 font-medium">Gérez vos sessions d'examens en ligne et sur table.</p>
                </div>
                <button 
                    @click="openModal()"
                    class="flex items-center justify-center gap-2 px-6 py-3.5 bg-gray-900 text-white rounded-2xl font-black text-sm hover:bg-blue-600 transition-all shadow-xl shadow-gray-200 hover:shadow-blue-100"
                >
                    <PlusIcon class="h-5 w-5" />
                    Nouvel Examen
                </button>
            </header>

            <!-- Stats Bar -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-gray-100 flex items-center gap-5">
                    <div class="h-14 w-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                        <DocumentTextIcon class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Examens</p>
                        <p class="text-2xl font-black text-gray-900">{{ exams.length }}</p>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-gray-100 flex items-center gap-5">
                    <div class="h-14 w-14 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center">
                        <CheckCircleIcon class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Sessions en Ligne</p>
                        <p class="text-2xl font-black text-gray-900">{{ exams.filter(e => e.type === 'online').length }}</p>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-gray-100 flex items-center gap-5">
                    <div class="h-14 w-14 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center">
                        <PencilIcon class="h-7 w-7" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Épreuves sur Table</p>
                        <p class="text-2xl font-black text-gray-900">{{ exams.filter(e => e.type === 'paper').length }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-black tracking-widest border-b border-gray-100">
                                <th class="px-6 py-4">Examen</th>
                                <th class="px-6 py-4">Type</th>
                                <th class="px-6 py-4 text-center">Planification</th>
                                <th class="px-6 py-4 text-center">Score Max</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="exam in exams" :key="exam.id" class="hover:bg-gray-50/50 transition group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="h-12 w-12 rounded-xl bg-gray-100 text-gray-500 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition">
                                            <AcademicCapIcon class="h-6 w-6" />
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <p class="font-bold text-gray-900">{{ exam.titre }}</p>
                                                <div v-if="isExamEnded(exam) && (exam.exam_results?.length < exam.expected_results_count)" 
                                                      class="px-2 py-0.5 bg-amber-500 text-white text-[8px] font-black uppercase rounded shadow-lg shadow-amber-100 ring-2 ring-amber-50 flex items-center gap-1.5">
                                                    <span class="h-1 w-1 rounded-full bg-white animate-ping"></span>
                                                    MANQUE {{ exam.expected_results_count - exam.exam_results?.length }} NOTES
                                                </div>
                                            </div>
                                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-wider italic">{{ exam.module?.titre }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border" :class="getTypeClass(exam.type)">
                                        {{ getTypeLabel(exam.type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col items-center gap-1">
                                        <div class="flex flex-col items-center">
                                            <span class="text-xs font-black text-gray-900 flex items-center gap-1.5 uppercase tracking-tight">
                                                <CalendarIcon class="h-3.5 w-3.5 text-gray-400" />
                                                {{ exam.scheduled_at ? new Date(exam.scheduled_at).toLocaleDateString('fr-FR') : 'Non planifié' }}
                                            </span>
                                            <span v-if="exam.scheduled_at" class="text-[10px] font-bold text-blue-600 mt-0.5">
                                                {{ new Date(exam.scheduled_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', hour12: false }) }}
                                                -
                                                {{ new Date(new Date(exam.scheduled_at).getTime() + exam.duree_minutes * 60000).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', hour12: false }) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] text-gray-400 font-bold flex items-center gap-1">
                                                <ClockIcon class="h-3 w-3" />
                                                {{ exam.duree_minutes }} min
                                            </span>
                                            <span v-if="isExamEnded(exam)" class="text-[9px] font-black text-red-500 bg-red-50 px-1.5 py-0.5 rounded border border-red-100 uppercase tracking-widest">
                                                Terminé
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-sm font-black text-gray-900">{{ parseFloat(exam.total_points).toFixed(0) }}</span>
                                    <span class="text-[10px] text-gray-400 font-bold ml-1">pts</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button v-if="exam.type === 'online'" @click="openQuestionModal(exam)" class="p-2 text-blue-500 hover:bg-blue-50 rounded-xl transition" title="Gérer les questions">
                                            <QueueListIcon class="h-6 w-6" />
                                        </button>
                                        <button @click="openGradeModal(exam)" class="p-2 text-green-600 hover:bg-green-50 rounded-xl transition" title="Consulter les notes / Saisie">
                                            <ClipboardDocumentCheckIcon class="h-6 w-6" />
                                        </button>
                                        <button @click="openModal(exam)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-xl transition" title="Modifier">
                                            <PencilIcon class="h-6 w-6" />
                                        </button>
                                        <button @click="deleteExam(exam.id)" class="p-2 text-red-600 hover:bg-red-50 rounded-xl transition" title="Supprimer">
                                            <TrashIcon class="h-6 w-6" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-md transition-all duration-300">
            <div class="bg-white w-full max-w-2xl rounded-[3rem] overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.1)] border border-gray-100 flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="p-8 border-b border-gray-50 flex items-center justify-between bg-white">
                    <div>
                        <h3 class="text-3xl font-black text-gray-900 tracking-tight flex items-center gap-3">
                            <span class="p-2 bg-blue-50 text-blue-600 rounded-2xl">
                                <PlusIcon v-if="!editingExam" class="h-6 w-6" />
                                <PencilIcon v-else class="h-6 w-6" />
                            </span>
                            {{ editingExam ? 'Modifier l\'examen' : 'Nouvel Examen' }}
                        </h3>
                        <p class="text-xs text-gray-400 font-bold mt-1 uppercase tracking-widest italic ml-12">Session d'évaluation e-CRE</p>
                    </div>
                    <button @click="closeModal" class="p-3 hover:bg-gray-100 rounded-2xl transition-all duration-300 transform hover:rotate-90">
                        <XMarkIcon class="h-6 w-6 text-gray-400" />
                    </button>
                </div>

                <!-- Modal Body -->
                <form @submit.prevent="submit" class="p-0 overflow-y-auto custom-scrollbar flex-1 bg-gray-50/30">
                    <div class="p-8 space-y-8">
                        <!-- Section: Informations Générales -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] flex items-center gap-2 mb-6">
                                <span class="h-px w-8 bg-blue-200"></span>
                                Informations Générales
                            </h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">
                                        <QueueListIcon class="h-3.5 w-3.5" />
                                        Module
                                    </label>
                                    <select v-model="form.module_id" required class="w-full bg-white border-gray-100 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-4 appearance-none shadow-sm transition-all focus:border-blue-500">
                                        <option value="">Sélectionner un module</option>
                                        <option v-for="m in modules" :key="m.id" :value="m.id">{{ m.titre }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">
                                        <AcademicCapIcon class="h-3.5 w-3.5" />
                                        Titre de l'examen
                                    </label>
                                    <input v-model="form.titre" type="text" required placeholder="Ex: Examen Final Module 1" class="w-full bg-white border-gray-100 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-4 shadow-sm transition-all focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Section: Type & Contenu -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-black text-purple-600 uppercase tracking-[0.2em] flex items-center gap-2 mb-6">
                                <span class="h-px w-8 bg-purple-200"></span>
                                Format & Contenu
                            </h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">
                                        <EyeIcon class="h-3.5 w-3.5" />
                                        Type d'examen
                                    </label>
                                    <div class="grid grid-cols-2 gap-2 p-1.5 bg-gray-100 rounded-[1.5rem]">
                                        <button 
                                            type="button"
                                            @click="form.type = 'online'"
                                            :class="form.type === 'online' ? 'bg-white text-blue-600 shadow-md' : 'text-gray-500 hover:text-gray-700'"
                                            class="py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all duration-300"
                                        >
                                            En Ligne
                                        </button>
                                        <button 
                                            type="button"
                                            @click="form.type = 'paper'"
                                            :class="form.type === 'paper' ? 'bg-white text-purple-600 shadow-md' : 'text-gray-500 hover:text-gray-700'"
                                            class="py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all duration-300"
                                        >
                                            Sur Table
                                        </button>
                                    </div>
                                </div>

                                <div v-if="form.type === 'paper'" class="animate-in fade-in slide-in-from-top-2 duration-300">
                                    <label class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">
                                        <DocumentIcon class="h-3.5 w-3.5" />
                                        Énoncé (PDF)
                                    </label>
                                    <div class="relative group h-[52px]">
                                        <input 
                                            type="file" 
                                            @input="form.document = $event.target.files[0]"
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                            accept=".pdf,.doc,.docx"
                                        />
                                        <div class="w-full h-full bg-white border border-gray-100 rounded-2xl px-4 flex items-center justify-between group-hover:border-purple-400 transition-all shadow-sm">
                                            <span class="text-[10px] font-bold text-gray-500 truncate max-w-[150px]">
                                                {{ form.document ? form.document.name : 'Veuillez joindre l\'énoncé' }}
                                            </span>
                                            <ArrowUpTrayIcon class="h-4 w-4 text-purple-400 group-hover:scale-110 transition-transform" />
                                        </div>
                                    </div>
                                </div>

                                <div v-else>
                                    <label class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">
                                        <CheckCircleIcon class="h-3.5 w-3.5" />
                                        Notation
                                    </label>
                                    <div class="flex items-center gap-3 bg-blue-50/50 p-4 rounded-2xl border border-blue-100/50" :class="{'border-red-200 bg-red-50/50': isModalTotalPointsInvalid}">
                                        <div class="h-10 w-10 bg-white rounded-xl flex items-center justify-center text-blue-600 shadow-sm" :class="{'text-red-600': isModalTotalPointsInvalid}">
                                            <input v-model="form.total_points" type="number" step="0.5" required class="w-full bg-transparent border-0 text-center font-black p-0 focus:ring-0">
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-[10px] font-black text-blue-600 uppercase tracking-widest" :class="{'text-red-600': isModalTotalPointsInvalid}">Points Totaux</span>
                                            <span v-if="isModalTotalPointsInvalid" class="text-[8px] font-bold text-red-500 uppercase tracking-tight animate-pulse">Attention : inférieur au cumul des questions</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Calendrier & Planning -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-black text-orange-600 uppercase tracking-[0.2em] flex items-center gap-2 mb-6">
                                <span class="h-px w-8 bg-orange-200"></span>
                                Planning & Durée
                            </h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-3">
                                    <label class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 ml-1">
                                        <CalendarIcon class="h-3.5 w-3.5" />
                                        Début de l'épreuve (jj/mm/aaaa)
                                    </label>
                                    <div class="flex gap-2">
                                        <input v-model="startDate" type="date" required class="flex-1 bg-white border-gray-100 rounded-2xl focus:ring-2 focus:ring-orange-500 font-bold px-4 py-3.5 text-xs shadow-sm focus:border-orange-500">
                                        <div class="flex items-center bg-white border border-gray-100 rounded-2xl px-3 shadow-sm focus-within:ring-2 focus-within:ring-orange-500 transition-all">
                                            <select v-model="startHour" class="bg-transparent border-0 font-black text-xs p-2 focus:ring-0 cursor-pointer">
                                                <option v-for="h in hourOptions" :key="h" :value="h">{{ h }}h</option>
                                            </select>
                                            <span class="text-gray-300 font-black text-xs">:</span>
                                            <select v-model="startMin" class="bg-transparent border-0 font-black text-xs p-2 focus:ring-0 cursor-pointer">
                                                <option v-for="m in minuteOptions" :key="m" :value="m">{{ m }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <label class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 ml-1">
                                        <CalendarIcon class="h-3.5 w-3.5 text-orange-400" />
                                        Fin de l'épreuve (jj/mm/aaaa)
                                    </label>
                                    <div class="flex gap-2">
                                        <input v-model="endDate" type="date" required class="flex-1 bg-white border-gray-100 rounded-2xl focus:ring-2 focus:ring-orange-500 font-bold px-4 py-3.5 text-xs shadow-sm focus:border-orange-500">
                                        <div class="flex items-center bg-white border border-gray-100 rounded-2xl px-3 shadow-sm focus-within:ring-2 focus-within:ring-orange-500 transition-all">
                                            <select v-model="endHour" class="bg-transparent border-0 font-black text-xs p-2 focus:ring-0 cursor-pointer">
                                                <option v-for="h in hourOptions" :key="h" :value="h">{{ h }}h</option>
                                            </select>
                                            <span class="text-gray-300 font-black text-xs">:</span>
                                            <select v-model="endMin" class="bg-transparent border-0 font-black text-xs p-2 focus:ring-0 cursor-pointer">
                                                <option v-for="m in minuteOptions" :key="m" :value="m">{{ m }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="form.duree_minutes" class="flex items-center gap-4 bg-orange-50/50 p-4 rounded-2xl border border-orange-100/50 animate-in zoom-in duration-300">
                                <div class="h-12 w-12 bg-white rounded-xl flex items-center justify-center text-orange-600 shadow-sm">
                                    <ClockIcon class="h-6 w-6" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-orange-400 uppercase tracking-widest">Durée de la session</p>
                                    <p class="text-xl font-black text-orange-600 tracking-tight">{{ form.duree_minutes }} <span class="text-sm">minutes</span></p>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="space-y-3">
                            <label class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">
                                <DocumentTextIcon class="h-3.5 w-3.5" />
                                Description / Consignes
                            </label>
                            <textarea v-model="form.description" rows="3" placeholder="Écrivez ici les consignes pour les apprenants..." class="w-full bg-white border-gray-100 rounded-[2rem] focus:ring-2 focus:ring-blue-500 font-bold px-6 py-4 shadow-sm transition-all focus:border-blue-500"></textarea>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="p-8 bg-gray-50 border-t border-gray-100 flex gap-4 sticky bottom-0">
                        <button type="button" @click="closeModal" class="flex-1 py-4.5 bg-white text-gray-500 rounded-[1.5rem] font-black text-xs uppercase tracking-widest hover:bg-gray-100 transition-all shadow-sm border border-gray-200">Annuler</button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="flex-1 py-4.5 bg-gray-900 text-white rounded-[1.5rem] font-black text-xs uppercase tracking-widest hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-xl shadow-gray-200 flex items-center justify-center gap-2"
                        >
                            <div v-if="form.processing" class="h-4 w-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                            {{ editingExam ? 'Mettre à jour' : 'Confirmer la création' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>


        <!-- Grading Modal (Batch) -->
        <div v-if="isGradeModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-md transition-all duration-300">
            <div class="bg-white w-full max-w-3xl rounded-[3rem] overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.15)] border border-gray-100 flex flex-col max-h-[85vh]">
                <!-- Header -->
                <div class="p-8 border-b border-gray-50 flex items-center justify-between bg-white">
                    <div class="flex items-center gap-4">
                        <div class="h-14 w-14 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center shadow-inner">
                            <ClipboardDocumentCheckIcon class="h-8 w-8" />
                        </div>
                        <div>
                            <h3 class="text-2xl font-black text-gray-900 tracking-tight italic">Saisie des Notes</h3>
                            <div class="flex items-center gap-2 mt-1">
                                <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] truncate max-w-[200px]">{{ selectedExamForGrades?.titre }}</p>
                                <span class="h-1 w-1 rounded-full bg-gray-300"></span>
                                <p class="text-[10px] text-blue-500 font-black uppercase tracking-[0.2em]">{{ selectedExamForGrades?.module?.titre }}</p>
                            </div>
                        </div>
                    </div>
                    <button @click="isGradeModalOpen = false" class="p-3 hover:bg-gray-100 rounded-2xl transition-all duration-300 transform hover:rotate-90">
                        <XMarkIcon class="h-6 w-6 text-gray-400" />
                    </button>
                </div>
                
                <form @submit.prevent="submitGrades" class="flex-1 flex flex-col overflow-hidden bg-gray-50/20">
                    <div class="flex-1 overflow-y-auto custom-scrollbar p-8">
                        <div class="space-y-3">
                            <div v-for="(student, index) in gradeForm.grades" :key="student.user_id" 
                                 class="group flex items-center justify-between p-4 bg-white rounded-3xl border border-gray-100 shadow-sm transition-all duration-300 hover:border-green-200 hover:shadow-md animate-in slide-in-from-bottom-2 duration-300"
                                 :style="`animation-delay: ${index * 30}ms`">
                                
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-tr from-gray-50 to-gray-100 text-gray-400 flex items-center justify-center font-black text-sm border border-gray-200 shadow-inner group-hover:from-green-50 group-hover:to-green-100 group-hover:text-green-600 group-hover:border-green-200 transition-colors">
                                        {{ student.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-black text-gray-800 text-sm tracking-tight">{{ student.name }}</p>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Apprenant e-CRE</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4">
                                    <div class="relative w-32">
                                        <input 
                                            v-model="student.score" 
                                            type="number" 
                                            step="0.25" 
                                            min="0" 
                                            :max="selectedExamForGrades?.total_points"
                                            class="w-full bg-gray-50 border-gray-100 rounded-2xl focus:ring-2 focus:ring-green-500 font-black text-center px-4 py-3.5 text-sm transition-all focus:bg-white"
                                            placeholder="0.00"
                                        >
                                        <div class="absolute -top-2.5 -right-1">
                                            <span class="px-2 py-0.5 bg-gray-900 text-white text-[8px] font-black rounded-lg shadow-lg">/ {{ selectedExamForGrades?.total_points }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="gradeForm.grades.length === 0" class="flex flex-col items-center justify-center py-20 text-center space-y-4">
                                <div class="h-20 w-20 bg-white rounded-full flex items-center justify-center text-gray-200 shadow-inner">
                                    <XMarkIcon class="h-10 w-10" />
                                </div>
                                <p class="text-sm font-bold text-gray-400 italic">Aucun apprenant inscrit à ce module.</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-8 bg-white border-t border-gray-50 flex gap-4 sticky bottom-0">
                        <button type="button" @click="isGradeModalOpen = false" class="flex-1 py-4.5 bg-gray-50 text-gray-500 rounded-[1.5rem] font-black text-xs uppercase tracking-widest hover:bg-gray-100 transition-all shadow-sm">Annuler</button>
                        <button 
                            type="submit" 
                            :disabled="gradeForm.processing"
                            class="flex-1 py-4.5 bg-green-600 text-white rounded-[1.5rem] font-black text-xs uppercase tracking-widest hover:bg-green-700 disabled:opacity-50 transition-all shadow-xl shadow-green-100 flex items-center justify-center gap-3"
                        >
                            <CheckCircleIcon class="h-5 w-5" />
                            {{ gradeForm.processing ? 'Enregistrement...' : 'Enregistrer les notes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>


        <!-- Question Management Modal -->
        <div v-if="isQuestionModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-md transition-all duration-300">
            <div class="bg-white w-full max-w-5xl rounded-[3rem] overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.15)] border border-gray-100 flex flex-col max-h-[85vh]">
                <!-- Header -->
                <div class="p-8 border-b border-gray-50 flex items-center justify-between bg-white">
                    <div class="flex items-center gap-4">
                        <div class="h-14 w-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center shadow-inner">
                            <QueueListIcon class="h-8 w-8" />
                        </div>
                        <div>
                            <h3 class="text-2xl font-black text-gray-900 tracking-tight italic">Banque de Questions</h3>
                            <p class="text-[10px] text-gray-400 font-black mt-1 uppercase tracking-[0.2em] flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                {{ selectedExamForQuestion?.titre }}
                            </p>
                        </div>
                    </div>
                    <button @click="isQuestionModalOpen = false" class="p-3 hover:bg-gray-100 rounded-2xl transition-all duration-300 transform hover:rotate-90">
                        <XMarkIcon class="h-6 w-6 text-gray-400" />
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-5 flex-1 overflow-hidden divide-x divide-gray-50">
                    <!-- Questions List (Left) -->
                    <div class="lg:col-span-2 p-8 space-y-6 overflow-y-auto custom-scrollbar bg-gray-50/20">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest flex items-center gap-2">
                                Questions existantes
                                <span class="bg-blue-100 text-blue-600 px-1.5 py-0.5 rounded-md text-[8px]">{{ selectedExamForQuestion.questions?.length || 0 }}</span>
                            </h4>
                            <div class="px-3 py-1 bg-gray-100 rounded-lg border border-gray-200">
                                <span :class="currentExamQuestionsTotalPoints > selectedExamForQuestion.total_points ? 'text-red-600' : 'text-gray-600'" class="text-[10px] font-black">
                                    {{ currentExamQuestionsTotalPoints }} / {{ selectedExamForQuestion.total_points }} pts
                                </span>
                            </div>
                        </div>

                        <div v-if="currentExamQuestionsTotalPoints > selectedExamForQuestion.total_points" class="p-4 bg-red-50 border border-red-100 rounded-2xl mb-6 flex items-center gap-3 text-red-600 animate-in shake duration-500">
                            <XCircleIcon class="h-5 w-5 shrink-0" />
                            <p class="text-[9px] font-black uppercase tracking-widest leading-relaxed">Alerte : Le total des points dépasse le barème de l'examen. Veuillez ajuster les points des questions.</p>
                        </div>
                        
                        <div v-for="(q, idx) in selectedExamForQuestion.questions" :key="q.id" class="p-5 bg-white rounded-3xl border border-gray-100 shadow-sm relative group hover:border-blue-200 transition-all duration-300 hover:shadow-md animate-in slide-in-from-left duration-300 text-left" :style="`animation-delay: ${idx * 50}ms` ">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 space-y-3">
                                    <div class="flex items-center gap-3">
                                        <span class="px-3 py-1 bg-blue-50 text-blue-600 text-[9px] font-black uppercase tracking-widest rounded-lg border border-blue-100/50">
                                            {{ q.type === 'qcm' ? 'QCM' : 'Ouverte' }}
                                        </span>
                                        <div class="flex items-center gap-1.5 bg-gray-50 px-2 py-0.5 rounded-lg border border-gray-100">
                                            <input
                                                type="number"
                                                v-model.number="q.points"
                                                @change="updateQuestionPoints(q)"
                                                min="0"
                                                step="0.5"
                                                class="w-8 text-center bg-transparent border-0 font-black text-[10px] p-0 focus:ring-0 text-gray-900"
                                            />
                                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-tighter">pts</span>
                                        </div>
                                    </div>
                                    <p class="text-sm font-bold text-gray-800 leading-relaxed">{{ q.enonce }}</p>
                                    
                                    <div v-if="q.type === 'qcm'" class="flex flex-wrap gap-2 pt-1 border-t border-gray-50 mt-3">
                                        <span v-for="opt in q.options" :key="opt.id" class="px-2 py-1 rounded-md text-[8px] font-bold flex items-center gap-1" :class="opt.is_correct ? 'bg-green-50 text-green-600 border border-green-100' : 'bg-gray-50 text-gray-400 border border-gray-100'">
                                            <CheckCircleIcon v-if="opt.is_correct" class="h-2.5 w-2.5" />
                                            {{ opt.texte }}
                                        </span>
                                    </div>
                                </div>
                                <button @click="deleteQuestion(q.id)" class="opacity-0 group-hover:opacity-100 p-2 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all duration-300">
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </div>
                        </div>

                        <div v-if="!selectedExamForQuestion.questions?.length" class="flex flex-col items-center justify-center py-20 text-center space-y-4">
                            <div class="h-20 w-20 bg-gray-50 rounded-full flex items-center justify-center text-gray-200">
                                <DocumentTextIcon class="h-10 w-10" />
                            </div>
                            <p class="text-sm font-bold text-gray-400 italic">Aucune question n'a été ajoutée <br> pour le moment.</p>
                        </div>
                    </div>

                    <!-- Add Question Form (Right) -->
                    <div class="lg:col-span-3 p-10 space-y-8 overflow-y-auto custom-scrollbar bg-white">
                        <div>
                            <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] flex items-center gap-2 mb-6">
                                <PlusIcon class="h-4 w-4" />
                                Nouvelle Question
                            </h4>
                            
                            <form @submit.prevent="submitQuestionForm" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="md:col-span-2">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2.5 ml-1">Type de question</label>
                                        <div class="grid grid-cols-2 gap-3 p-1.5 bg-gray-50 rounded-2xl">
                                            <button 
                                                type="button"
                                                @click="questionForm.type = 'qcm'"
                                                :class="questionForm.type === 'qcm' ? 'bg-white text-blue-600 shadow-sm border-blue-50' : 'text-gray-400 hover:text-gray-600'"
                                                class="py-3 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all duration-300 border border-transparent"
                                            >
                                                QCM (Quiz)
                                            </button>
                                            <button 
                                                type="button"
                                                @click="questionForm.type = 'open'"
                                                :class="questionForm.type === 'open' ? 'bg-white text-blue-600 shadow-sm border-blue-50' : 'text-gray-400 hover:text-gray-600'"
                                                class="py-3 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all duration-300 border border-transparent"
                                            >
                                                Question Ouverte
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2.5 ml-1">Coefficient/Points</label>
                                        <div class="relative group">
                                            <input v-model="questionForm.points" type="number" step="0.5" class="w-full bg-gray-50 border-gray-100 rounded-2xl focus:ring-2 focus:ring-blue-500 font-black px-5 py-4 text-center">
                                            <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none">
                                                <span class="text-[10px] font-black text-gray-400">PTS</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Énoncé de la question</label>
                                    <textarea v-model="questionForm.enonce" rows="3" required placeholder="Tapez ici votre question..." class="w-full bg-gray-50 border-gray-100 rounded-[2rem] focus:ring-2 focus:ring-blue-500 font-bold px-6 py-5 shadow-inner transition-all focus:border-blue-500"></textarea>
                                </div>

                                <!-- Options for QCM -->
                                <div v-if="questionForm.type === 'qcm'" class="space-y-4 pt-4 border-t border-gray-50 animate-in slide-in-from-bottom-5 duration-500">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="p-1.5 bg-blue-50 text-blue-500 rounded-lg">
                                                <QueueListIcon class="h-4 w-4" />
                                            </span>
                                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Options de réponse</label>
                                        </div>
                                        <button type="button" @click="addOption" class="group flex items-center gap-1.5 text-blue-600 hover:text-blue-700 text-[10px] font-black uppercase tracking-widest px-3 py-1.5 bg-blue-50 rounded-lg transition-all active:scale-95">
                                            <PlusIcon class="h-3.5 w-3.5 group-hover:rotate-90 transition-transform" />
                                            Ajouter une option
                                        </button>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 gap-3">
                                        <div v-for="(opt, idx) in questionForm.options" :key="idx" class="flex items-center gap-3 p-3 bg-gray-50/50 rounded-2xl border border-gray-100 transition-all hover:bg-white hover:border-blue-200 group">
                                            <input type="checkbox" v-model="opt.is_correct" class="h-5 w-5 rounded-lg text-green-500 focus:ring-green-500 border-gray-200 cursor-pointer shadow-sm transition-all hover:scale-110" title="Marquer comme correcte">
                                            <input v-model="opt.texte" placeholder="Saisissez l'option..." class="flex-1 bg-transparent border-0 font-bold text-sm px-2 focus:ring-0 text-gray-700 p-0">
                                            <button v-if="questionForm.options.length > 2" type="button" @click="removeOption(idx)" class="opacity-0 group-hover:opacity-100 p-2 text-gray-300 hover:text-red-500 transition-all hover:bg-red-50 rounded-xl">
                                                <XMarkIcon class="h-5 w-5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="isQuestionLimitExceeded" class="p-4 bg-red-50 border border-red-100 rounded-2xl flex items-center gap-3 text-red-600 animate-pulse">
                                    <XMarkIcon class="h-5 w-5 shrink-0" />
                                    <p class="text-[9px] font-black uppercase tracking-widest">Le cumul avec cette question ({{ currentExamQuestionsTotalPoints + parseFloat(questionForm.points) }}) dépasse le total autorisé.</p>
                                </div>

                                <button 
                                    type="submit" 
                                    :disabled="questionForm.processing || isQuestionLimitExceeded"
                                    class="w-full py-5 bg-blue-600 text-white rounded-[2rem] font-black text-xs uppercase tracking-[0.3em] hover:bg-blue-700 active:scale-[0.98] transition-all shadow-xl shadow-blue-100 flex items-center justify-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <CheckCircleIcon class="h-5 w-5" />
                                    {{ questionForm.processing ? 'Enregistrement...' : 'Valider la question' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}
</style>
