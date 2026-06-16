<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { 
    BookOpenIcon,
    CheckCircleIcon,
    XCircleIcon,
    DocumentIcon,
    ChatBubbleLeftRightIcon,
    EyeIcon,
    EyeSlashIcon,
    AcademicCapIcon,
    ChevronRightIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    UserCircleIcon,
    XMarkIcon,
    PencilIcon,
    PencilSquareIcon,
    TrashIcon,
    PlusIcon,
    Squares2X2Icon,
    ListBulletIcon,
    QueueListIcon
} from '@heroicons/vue/24/outline';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    exercises: Array,
    modules: Array
});

const page = usePage();
const roles = computed(() => page.props.auth.user.roles);
const isDirecteur = computed(() => roles.value.includes('Directeur'));
const isSecretaire = computed(() => roles.value.includes('Secrétaire'));
const isTrainer = computed(() => page.props.auth.user.is_trainer);

const viewMode = ref('correction'); // 'correction' or 'admin'
const isEditModalOpen = ref(false);
const isCreatingExercise = ref(false);
const editingExercise = ref(null);
const isQuestionModalOpen = ref(false);
const editingQuestion = ref(null);
const selectedChapterForQuestionId = ref(null);
const selectedChapterForQuestion = computed(() => {
    if (!selectedChapterForQuestionId.value) return null;
    for (const module of props.modules) {
        if (!module.chapters) continue;
        const chapter = module.chapters.find(c => c.id === selectedChapterForQuestionId.value);
        if (chapter) return chapter;
    }
    return null;
});

const isFileModalOpen = ref(false);
const selectedChapterForFileId = ref(null);
const selectedChapterForFile = computed(() => {
    if (!selectedChapterForFileId.value) return null;
    for (const module of props.modules) {
        if (!module.chapters) continue;
        const chapter = module.chapters.find(c => c.id === selectedChapterForFileId.value);
        if (chapter) return chapter;
    }
    return null;
});

// Correction mode state
const isGradeModalOpen = ref(false);
const selectedSubmission = ref(null);
const selectedExercise = ref(null);

const gradeForm = useForm({
    grade: '',
    trainer_feedback: '',
    status: 'graded'
});

const adminForm = useForm({
    module_id: '',
    exercise_title: '',
    exercise_type: 'online',
    exercise_instructions: '',
    exercise_points: 20
});

const questionForm = useForm({
    enonce: '',
    expected_answer: '',
    points: 5,
    type: 'qcm',
    options: [
        { texte: '', is_correct: false },
        { texte: '', is_correct: false }
    ]
});

const fileForm = useForm({
    attachment: null
});

const openGradeModal = (submission, exercise) => {
    selectedSubmission.value = submission;
    selectedExercise.value = exercise;
    gradeForm.grade = submission.grade || '';
    gradeForm.trainer_feedback = submission.trainer_feedback || '';
    gradeForm.status = submission.status === 'pending' ? 'graded' : submission.status;
    isGradeModalOpen.value = true;
};

const submitGrade = () => {
    gradeForm.post(route('exercises.grade-submission', selectedSubmission.value.id), {
        onSuccess: () => {
            isGradeModalOpen.value = false;
        }
    });
};

watch(() => gradeForm.grade, (newGrade) => {
    if (newGrade !== '' && newGrade !== null && selectedExercise.value) {
        const maxPoints = selectedExercise.value.exercise_points || 20;
        const ratio = Number(newGrade) / maxPoints;
        
        if (ratio >= 0.8) {
            gradeForm.status = 'excellent';
        } else if (ratio >= 0.7) {
            gradeForm.status = 'very_good';
        } else if (ratio >= 0.6) {
            gradeForm.status = 'good';
        } else if (ratio >= 0.5) {
            gradeForm.status = 'satisfactory';
        } else if (ratio >= 0.25) {
            gradeForm.status = 'rejected';
        } else {
            gradeForm.status = 'weak';
        }
    }
});

const openCreateModal = () => {
    isCreatingExercise.value = true;
    editingExercise.value = null;
    adminForm.reset();
    adminForm.module_id = props.modules.length > 0 ? props.modules[0].id : '';
    adminForm.exercise_type = 'online';
    adminForm.exercise_points = 20;
    isEditModalOpen.value = true;
};

const openEditModal = (exercise) => {
    isCreatingExercise.value = false;
    editingExercise.value = exercise;
    adminForm.exercise_title = exercise.exercise_title || exercise.titre;
    adminForm.exercise_type = exercise.exercise_type;
    adminForm.exercise_instructions = exercise.exercise_instructions || '';
    adminForm.exercise_points = exercise.exercise_points;
    isEditModalOpen.value = true;
};

const isModalTotalPointsInvalid = computed(() => {
    if (!editingExercise.value) return false;
    const currentQuestionsPoints = editingExercise.value.questions?.reduce((sum, q) => sum + (parseFloat(q.points) || 0), 0) || 0;
    return adminForm.exercise_points < currentQuestionsPoints;
});

const togglePublish = (chapter) => {
    const action = chapter.is_published ? 'masquer cet exercice' : 'publier cet exercice et notifier les apprenants';
    if (confirm(`Voulez-vous vraiment ${action} ?`)) {
        router.patch(route('exercises.publish', chapter.id), {}, {
            preserveScroll: true
        });
    }
};

const submitAdminForm = () => {
    if (isCreatingExercise.value) {
        adminForm.post(route('exercises.store'), {
            onSuccess: () => {
                isEditModalOpen.value = false;
            }
        });
    } else {
        if (editingExercise.value) {
            const currentQuestionsPoints = editingExercise.value.questions?.reduce((sum, q) => sum + (parseFloat(q.points) || 0), 0) || 0;
            if (adminForm.exercise_points < currentQuestionsPoints) {
                window.platformAlert(`Impossible de réduire le barème : le total des points des questions existantes (${currentQuestionsPoints}) dépasse le nouveau barème (${adminForm.exercise_points}).`, 'error');
                return;
            }
        }
        adminForm.put(route('exercises.update', editingExercise.value.id), {
            onSuccess: () => {
                isEditModalOpen.value = false;
            }
        });
    }
};

const openQuestionModal = (chapter, question = null) => {
    selectedChapterForQuestionId.value = chapter.id;
    editingQuestion.value = question;
    if (question) {
        questionForm.enonce = question.enonce;
        questionForm.expected_answer = question.expected_answer || '';
        questionForm.points = question.points;
        questionForm.type = question.type;
        questionForm.options = question.options.length > 0 ? [...question.options] : [{ texte: '', is_correct: false }, { texte: '', is_correct: false }];
    } else {
        questionForm.reset();
        questionForm.options = [{ texte: '', is_correct: false }, { texte: '', is_correct: false }];
    }
    isQuestionModalOpen.value = true;
};

const addOption = () => {
    questionForm.options.push({ texte: '', is_correct: false });
};

const removeOption = (index) => {
    questionForm.options.splice(index, 1);
};

const currentChapterQuestionsTotalPoints = computed(() => {
    if (!selectedChapterForQuestion.value || !selectedChapterForQuestion.value.questions) return 0;
    return selectedChapterForQuestion.value.questions.reduce((sum, q) => sum + (parseFloat(q.points) || 0), 0);
});

const isQuestionLimitExceeded = computed(() => {
    if (!selectedChapterForQuestion.value) return false;
    let currentPoints = currentChapterQuestionsTotalPoints.value;
    const newPoints = parseFloat(questionForm.points) || 0;
    
    if (editingQuestion.value) {
        currentPoints -= parseFloat(editingQuestion.value.points) || 0;
    }
    
    return (currentPoints + newPoints) > selectedChapterForQuestion.value.exercise_points;
});

const submitQuestionForm = () => {
    if (isQuestionLimitExceeded.value) {
        window.platformAlert(`Dépassement du barème : le total (${currentChapterQuestionsTotalPoints.value + parseFloat(questionForm.points)}) ne peut pas dépasser ${selectedChapterForQuestion.value.exercise_points} pts.`, 'error');
        return;
    }
    const transformData = (data) => ({
        ...data,
        options: data.type === 'open' ? [] : data.options
    });

    if (editingQuestion.value) {
        questionForm
            .transform(transformData)
            .patch(route('questions.update', editingQuestion.value.id), {
                onSuccess: () => {
                    questionForm.reset();
                    questionForm.clearErrors();
                    editingQuestion.value = null;
                }
            });
    } else {
        questionForm
            .transform(transformData)
            .post(route('exercises.questions.store', selectedChapterForQuestion.value.id), {
                onSuccess: () => {
                    questionForm.reset();
                    questionForm.clearErrors();
                }
            });
    }
};

const deleteQuestion = (id) => {
    if (confirm('Supprimer cette question ?')) {
        useForm({}).delete(route('questions.destroy', id));
    }
};

const openFileModal = (chapter) => {
    selectedChapterForFileId.value = chapter.id;
    fileForm.reset();
    fileForm.clearErrors();
    isFileModalOpen.value = true;
};

const deleteExercise = (chapter) => {
    if (confirm('Attention : Cette action est irréversible. Êtes-vous sûr de vouloir supprimer cet exercice et toutes ses données (fichiers, questions, rendus des apprenants) ?')) {
        useForm({}).delete(route('exercises.destroy', chapter.id), {
            preserveScroll: true,
            onSuccess: () => {
                if (selectedChapterForQuestionId.value === chapter.id) {
                    isQuestionModalOpen.value = false;
                }
                if (selectedChapterForFileId.value === chapter.id) {
                    isFileModalOpen.value = false;
                }
            }
        });
    }
};

const submitFileForm = () => {
    if (!fileForm.attachment) return;
    fileForm.post(route('exercises.attachments.store', selectedChapterForFile.value.id), {
        onSuccess: () => {
            fileForm.reset();
            // Optional: close modal or leave it open
        }
    });
};

const deleteAttachment = (chapterId, index) => {
    if (confirm('Supprimer ce fichier ?')) {
        useForm({}).delete(route('exercises.attachments.destroy', { chapter: chapterId, index }));
    }
};

const updateQuestionPoints = (question) => {
    useForm({ points: question.points }).patch(route('questions.update', question.id), {
        onSuccess: () => {},
        onError: () => {}
    });
};

const getStatusLabel = (status) => {
    const labels = {
        'pending': 'À corriger',
        'excellent': 'Excellent',
        'very_good': 'Très Bien',
        'good': 'Bien',
        'satisfactory': 'Passable',
        'graded': 'Validé',
        'rejected': 'À retravailler',
        'weak': 'Faible'
    };
    return labels[status] || status;
};

const getStatusClass = (status) => {
    const classes = {
        'pending': 'bg-amber-50 text-amber-600 border-amber-100',
        'excellent': 'bg-emerald-50 text-emerald-600 border-emerald-100',
        'very_good': 'bg-green-50 text-green-600 border-green-100',
        'good': 'bg-lime-50 text-lime-600 border-lime-100',
        'satisfactory': 'bg-blue-50 text-blue-600 border-blue-100',
        'graded': 'bg-green-50 text-green-600 border-green-100',
        'rejected': 'bg-orange-50 text-orange-600 border-orange-100',
        'weak': 'bg-red-50 text-red-600 border-red-100'
    };
    return classes[status] || 'bg-gray-50 text-gray-600 border-gray-100';
};

const getSubmissionAnswerText = (question, submission) => {
    const answers = submission.answers || {};
    const answer = answers[question.id];
    
    if (!answer) return '(Pas de réponse)';
    
    if (question.type === 'qcm') {
        const option = question.options.find(o => o.id === Number(answer));
        return option ? option.texte : answer;
    }
    
    return answer;
};

const isSubmissionCorrect = (question, submission) => {
    if (question.type !== 'qcm') return false;
    const answers = submission.answers || {};
    const answer = answers[question.id];
    const correctOption = question.options.find(o => o.is_correct);
    return answer && correctOption && Number(answer) === correctOption.id;
};

const collapsedModules = ref({});

const toggleModule = (moduleId) => {
    collapsedModules.value[moduleId] = !collapsedModules.value[moduleId];
};

</script>

<template>
    <Head title="Correction des Exercices" />

    <AuthenticatedLayout>
        <div class="py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <header class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h2 class="text-4xl font-black text-gray-900 tracking-tight flex items-center gap-3">
                        <BookOpenIcon class="h-10 w-10 text-blue-600" />
                        Gestion des Exercices
                    </h2>
                    <p class="mt-2 text-gray-500 font-medium tracking-tight">Administrez et corrigez les travaux pratiques.</p>
                </div>

                <!-- Mode Switcher -->
                <div class="inline-flex p-1.5 bg-gray-100 rounded-2xl">
                    <button 
                        @click="viewMode = 'correction'"
                        :class="viewMode === 'correction' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                        class="px-6 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest transition-all"
                    >
                        Correction
                    </button>
                    <button 
                        @click="viewMode = 'admin'"
                        :class="viewMode === 'admin' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                        class="px-6 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest transition-all"
                    >
                        Administration
                    </button>
                </div>
            </header>

            <div v-if="viewMode === 'correction'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Exercises List Grouped by Module -->
                <div class="lg:col-span-1 space-y-8">
                    <div v-for="moduleName in [...new Set(exercises.map(ex => ex.module?.titre))]" :key="moduleName" class="space-y-3">
                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest px-4">{{ moduleName }}</h4>
                        
                        <div class="space-y-3">
                            <div 
                                v-for="ex in exercises.filter(e => e.module?.titre === moduleName)" 
                                :key="ex.id"
                                @click="selectedExercise = ex"
                                :class="selectedExercise?.id === ex.id ? 'border-blue-600 ring-2 ring-blue-50 bg-blue-50/10' : 'border-gray-100 hover:border-blue-200 bg-white'"
                                class="p-6 rounded-[2.5rem] border shadow-sm transition-all cursor-pointer group"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] text-gray-400 font-bold italic">{{ ex.exercise_submissions?.length || 0 }} rendu(s)</span>
                                        <div v-if="ex.exercise_submissions?.some(s => s.status === 'pending')" class="flex items-center gap-1.5 px-2 py-0.5 bg-red-600 text-white text-[8px] font-black uppercase rounded shadow-lg shadow-red-100 animate-pulse ring-2 ring-red-100">
                                            <span class="h-1 w-1 rounded-full bg-white"></span>
                                            À CORRIGER
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 bg-blue-50 text-[9px] font-black text-blue-500 uppercase rounded-lg border border-blue-100">
                                        {{ ex.exercise_type }}
                                    </span>
                                </div>
                                <h3 class="font-black text-gray-900 leading-tight group-hover:text-blue-600 mb-2 truncate" :title="ex.exercise_title || ex.titre">
                                    {{ ex.exercise_title || ex.titre }}
                                </h3>
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest flex items-center gap-1.5">
                                    <AcademicCapIcon class="h-3.5 w-3.5" />
                                    Ch. {{ ex.ordre }} • {{ ex.exercise_points }} pts
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-if="exercises.length === 0" class="p-12 text-center bg-gray-50 rounded-[2.5rem] border border-dashed border-gray-200">
                        <DocumentIcon class="h-12 w-12 text-gray-300 mx-auto mb-3" />
                        <p class="text-sm font-bold text-gray-400 italic">Aucun exercice actif pour le moment.</p>
                    </div>
                </div>

                <!-- Submissions Detail -->
                <div class="lg:col-span-2">
                    <div v-if="selectedExercise" class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-8 border-b border-gray-50 bg-gray-50/30 flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-black text-gray-900 tracking-tight">{{ selectedExercise.exercise_title || selectedExercise.titre }}</h3>
                                <p class="text-xs text-gray-400 font-bold italic mt-1">{{ selectedExercise.module?.titre }}</p>
                            </div>
                            <div class="h-12 w-12 bg-white text-blue-600 rounded-2xl flex items-center justify-center shadow-sm border border-gray-100">
                                <BookOpenIcon class="h-6 w-6" />
                            </div>
                        </div>

                        <div class="p-4 overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                        <th class="px-6 py-4">Apprenant</th>
                                        <th class="px-6 py-4">Statut</th>
                                        <th class="px-6 py-4 text-center">Note</th>
                                        <th class="px-6 py-4 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr v-for="sub in selectedExercise.exercise_submissions" :key="sub.id" class="hover:bg-gray-50/50 transition">
                                        <td class="px-6 py-5">
                                            <div class="flex items-center gap-3">
                                                <div class="h-9 w-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-black text-sm">
                                                    {{ sub.user.name.charAt(0) }}
                                                </div>
                                                <div>
                                                    <span class="font-bold text-gray-900 block">{{ sub.user.name }}</span>
                                                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest leading-none">
                                                        {{ sub.user.student_groups?.[0]?.nom_groupe || 'Individuel' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5">
                                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border" :class="getStatusClass(sub.status)">
                                                {{ getStatusLabel(sub.status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-5 text-center">
                                            <span class="text-sm font-black text-gray-900">{{ sub.grade !== null ? sub.grade : '—' }}</span>
                                            <span class="text-[10px] text-gray-400 ml-0.5 font-bold">/{{ selectedExercise.exercise_points }}</span>
                                        </td>
                                        <td class="px-6 py-5 text-right">
                                            <button 
                                                @click="openGradeModal(sub, selectedExercise)"
                                                class="px-4 py-2 bg-gray-900 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-600 transition"
                                            >
                                                {{ isSecretaire ? 'Voir' : 'Corriger' }}
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="selectedExercise.exercise_submissions?.length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center italic text-gray-400 font-bold text-sm">
                                            Aucun rendu pour cet exercice.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-else class="h-full min-h-[400px] flex flex-col items-center justify-center bg-gray-50/30 border-2 border-dashed border-gray-100 rounded-[3rem] p-12 text-center text-gray-400">
                        <div class="h-20 w-20 bg-white rounded-3xl flex items-center justify-center shadow-lg shadow-gray-100 mb-6">
                            <BookOpenIcon class="h-10 w-10 text-gray-200" />
                        </div>
                        <h4 class="text-lg font-black text-gray-900 tracking-tight mb-2 italic">Sélectionnez un exercice</h4>
                        <p class="max-w-xs font-bold text-sm">Cliquez sur un exercice dans la liste de gauche pour voir les rendus à corriger.</p>
                    </div>
                </div>
            </div>

            <!-- Administration View -->
            <div v-else class="space-y-8">
                <div class="flex justify-end">
                    <button v-if="!isSecretaire" @click="openCreateModal" class="px-6 py-3 bg-blue-600 text-white rounded-[1.5rem] font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition shadow-xl shadow-blue-100 flex items-center gap-2">
                        <PlusIcon class="h-5 w-5" />
                        Nouvel Exercice
                    </button>
                </div>
                <div v-for="module in modules" :key="module.id" class="bg-white rounded-[3rem] shadow-sm border border-gray-100 overflow-hidden">
                    <div @click="toggleModule(module.id)" class="p-8 border-b border-gray-50 bg-gray-50/30 flex items-center justify-between cursor-pointer hover:bg-gray-50/50 transition">
                        <div>
                            <span class="text-[10px] font-black text-blue-600 uppercase tracking-widest px-3 py-1 bg-blue-50 rounded-full mb-2 inline-block">{{ module.code_module }}</span>
                            <h3 class="text-xl font-black text-gray-900 tracking-tight">{{ module.titre }}</h3>
                        </div>
                        <button class="h-12 w-12 bg-white rounded-2xl flex items-center justify-center shadow-sm border border-gray-100 text-gray-400 hover:text-blue-600 hover:border-blue-100 transition">
                            <ChevronDownIcon v-if="collapsedModules[module.id]" class="h-6 w-6" />
                            <ChevronUpIcon v-else class="h-6 w-6" />
                        </button>
                    </div>

                    <div v-show="!collapsedModules[module.id]" class="p-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div v-for="chapter in module.chapters" :key="chapter.id" class="p-6 rounded-[2.5rem] border border-gray-100 hover:border-blue-200 transition bg-gray-50/30">
                            <div class="flex items-start justify-between mb-4">
                                <div class="h-10 w-10 bg-white rounded-xl flex items-center justify-center shadow-sm border border-gray-100 text-blue-600">
                                    <span class="font-black text-xs">{{ chapter.ordre }}</span>
                                </div>
                                <div v-if="!isSecretaire" class="flex gap-2">
                                    <button @click="togglePublish(chapter)" class="p-2 bg-white rounded-xl shadow-sm border border-gray-100 transition" :class="chapter.is_published ? 'text-green-600 hover:bg-green-50 hover:border-green-100' : 'text-amber-500 hover:bg-amber-50 hover:border-amber-100'" :title="chapter.is_published ? 'Publié (Cliquez pour masquer)' : 'Masqué (Cliquez pour publier)'">
                                        <EyeIcon class="h-5 w-5" v-if="chapter.is_published" />
                                        <EyeSlashIcon class="h-5 w-5" v-else />
                                    </button>
                                    <button @click="openEditModal(chapter)" class="p-2 bg-white text-gray-400 hover:text-blue-600 rounded-xl shadow-sm border border-gray-100 transition" title="Modifier l'exercice">
                                        <PencilIcon class="h-5 w-5" />
                                    </button>
                                    <button v-if="isDirecteur" @click="deleteExercise(chapter)" class="p-2 bg-white text-red-400 hover:text-red-600 hover:bg-red-50 rounded-xl shadow-sm border border-gray-100 hover:border-red-100 transition" title="Supprimer l'exercice">
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>
                            <h4 class="font-bold text-gray-900 mb-2 truncate">{{ chapter.titre }}</h4>
                            
                            <div v-if="chapter.exercise_type !== 'none'" class="space-y-3">
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-1 bg-green-50 text-green-600 text-[9px] font-black uppercase tracking-widest rounded-lg border border-green-100">
                                        {{ chapter.exercise_type }}
                                    </span>
                                    <span class="text-[10px] font-bold text-gray-400 italic">{{ chapter.exercise_points }} pts</span>
                                </div>
                                <div v-if="chapter.exercise_type === 'online'" class="pt-2 border-t border-gray-100 flex items-center justify-between">
                                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">{{ chapter.questions?.length || 0 }} Questions</span>
                                    <button @click="openQuestionModal(chapter)" class="text-blue-600 hover:text-blue-700 text-[10px] font-black uppercase tracking-widest flex items-center gap-1">
                                        <PlusIcon v-if="!isSecretaire" class="h-3.5 w-3.5" />
                                        {{ isSecretaire ? 'Voir' : 'Gérer' }}
                                    </button>
                                </div>
                                <div v-else-if="chapter.exercise_type === 'file'" class="pt-2 border-t border-gray-100 flex items-center justify-between">
                                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">{{ chapter.attachments?.length || 0 }} Fichier(s)</span>
                                    <button @click="openFileModal(chapter)" class="text-blue-600 hover:text-blue-700 text-[10px] font-black uppercase tracking-widest flex items-center gap-1">
                                        <PlusIcon v-if="!isSecretaire" class="h-3.5 w-3.5" />
                                        {{ isSecretaire ? 'Voir' : 'Gérer le fichier' }}
                                    </button>
                                </div>
                            </div>
                            <div v-else class="text-[10px] font-bold text-gray-300 italic">Aucun exercice configuré</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exercise Edit Modal -->
        <div v-if="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm">
            <div class="bg-white w-full max-w-lg rounded-[2.5rem] overflow-hidden shadow-2xl">
                <div class="p-8 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-2xl font-black text-gray-900 tracking-tight italic">{{ isCreatingExercise ? 'Créer un exercice' : 'Configurer l\'exercice' }}</h3>
                    <button @click="isEditModalOpen = false" class="p-2 hover:bg-gray-100 rounded-xl transition">
                        <XMarkIcon class="h-6 w-6 text-gray-400" />
                    </button>
                </div>
                <form @submit.prevent="submitAdminForm" class="p-8 space-y-5">
                    <div v-if="isCreatingExercise">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Module</label>
                        <select v-model="adminForm.module_id" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5 appearance-none">
                            <option value="" disabled>Sélectionner un module</option>
                            <option v-for="mod in modules" :key="mod.id" :value="mod.id">{{ mod.titre }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Titre de l'exercice</label>
                        <input v-model="adminForm.exercise_title" type="text" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Type d'exercice</label>
                        <select v-model="adminForm.exercise_type" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5 appearance-none">
                            <option value="none">Aucun</option>
                            <option value="online">En Ligne (QCM / Questions)</option>
                            <option value="file">Dépôt de fichier</option>
                        </select>
                    </div>
                    <div :class="{'p-4 bg-red-50 rounded-2xl border border-red-100': isModalTotalPointsInvalid}">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5" :class="{'text-red-600': isModalTotalPointsInvalid}">
                            Total Points
                        </label>
                        <input v-model="adminForm.exercise_points" type="number" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5" :class="{'bg-white border border-red-200 text-red-600': isModalTotalPointsInvalid}">
                        <p v-if="isModalTotalPointsInvalid" class="text-[9px] font-bold text-red-500 uppercase tracking-tight mt-1.5 animate-pulse">Attention : inférieur au cumul des questions</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Instructions</label>
                        <textarea v-model="adminForm.exercise_instructions" rows="3" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5"></textarea>
                    </div>
                    <div class="pt-4 flex gap-3">
                        <button type="button" @click="isEditModalOpen = false" class="flex-1 py-4 bg-gray-100 text-gray-500 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-gray-200 transition">Annuler</button>
                        <button type="submit" class="flex-1 py-4 bg-gray-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-600 transition shadow-xl shadow-gray-200">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Question Management Modal -->
        <div v-if="isQuestionModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm">
            <div class="bg-white w-full max-w-2xl rounded-[2.5rem] overflow-hidden shadow-2xl">
                <div class="p-8 border-b border-gray-100 flex items-center justify-between bg-gray-50/30">
                    <div>
                        <h3 class="text-2xl font-black text-gray-900 tracking-tight italic">{{ isSecretaire ? 'Banque de Questions' : 'Gestion des Questions' }}</h3>
                        <p class="text-xs text-gray-400 font-bold mt-1 uppercase tracking-widest truncate max-w-[300px]">{{ selectedChapterForQuestion?.titre }}</p>
                    </div>
                    <button @click="isQuestionModalOpen = false" class="p-2 hover:bg-gray-100 rounded-xl transition">
                        <XMarkIcon class="h-6 w-6 text-gray-400" />
                    </button>
                </div>

                <div :class="isSecretaire ? 'md:grid-cols-1' : 'md:grid-cols-2'" class="grid grid-cols-1 divide-x divide-gray-100">
                    <!-- Questions List -->
                    <div class="p-8 space-y-4 max-h-[60vh] overflow-y-auto custom-scrollbar">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Questions Existantes</h4>
                            <div class="px-2 py-0.5 bg-gray-100 rounded text-[9px] font-black" :class="currentChapterQuestionsTotalPoints > selectedChapterForQuestion.exercise_points ? 'text-red-600' : 'text-gray-500'">
                                {{ currentChapterQuestionsTotalPoints }} / {{ selectedChapterForQuestion.exercise_points }} pts
                            </div>
                        </div>

                        <div v-if="currentChapterQuestionsTotalPoints > selectedChapterForQuestion.exercise_points" class="p-3 bg-red-50 border border-red-100 rounded-xl text-red-600 flex items-center gap-2 mb-4 animate-pulse">
                            <XCircleIcon class="h-4 w-4 shrink-0" />
                            <p class="text-[9px] font-black uppercase tracking-widest leading-tight">Total dépassé ! Ajustez les points des questions.</p>
                        </div>

                        <div v-for="q in selectedChapterForQuestion.questions" :key="q.id" class="p-4 bg-gray-50 rounded-2xl border border-gray-100 relative group">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <label class="text-[9px] font-black text-gray-400 uppercase tracking-widest whitespace-nowrap">Barème</label>
                                    <input
                                        v-if="!isSecretaire"
                                        type="number"
                                        v-model.number="q.points"
                                        @change="updateQuestionPoints(q)"
                                        min="0"
                                        step="0.5"
                                        class="w-16 text-center bg-blue-50 text-blue-700 border-0 rounded-lg font-black text-[11px] py-1 focus:ring-2 focus:ring-blue-400"
                                        title="Barème (points)"
                                    />
                                    <span v-else class="px-3 py-1 bg-blue-50 text-blue-700 text-[11px] font-black rounded-lg">{{ q.points }}</span>
                                    <span class="text-[9px] font-bold text-gray-400">pts</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button v-if="!isSecretaire" @click="openQuestionModal(selectedChapterForQuestion, q)" class="opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-blue-600 transition" title="Modifier">
                                        <PencilSquareIcon class="h-4 w-4" />
                                    </button>
                                    <button v-if="!isSecretaire" @click="deleteQuestion(q.id)" class="opacity-0 group-hover:opacity-100 p-1 text-red-400 hover:text-red-600 transition" title="Supprimer">
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                            <p class="text-xs font-bold text-gray-900 mt-1">{{ q.enonce }}</p>
                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">{{ q.type }}</span>
                        </div>
                        <div v-if="!selectedChapterForQuestion.questions?.length" class="text-center py-8 italic text-gray-300 font-bold text-sm">
                            Aucune question.
                        </div>
                    </div>

                    <!-- Add/Edit Question Form -->
                    <div v-if="!isSecretaire" class="p-8 bg-gray-50/30">
                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-6">Ajouter une question</h4>
                        
                        <div v-if="Object.keys(questionForm.errors).length > 0" class="p-4 bg-red-50 border border-red-100 rounded-2xl text-red-600 mb-6 space-y-1">
                            <p class="text-[10px] font-black uppercase tracking-widest flex items-center gap-2 mb-1 text-red-700">
                                <XMarkIcon class="h-4 w-4" /> Erreurs de validation
                            </p>
                            <ul class="list-disc pl-4 text-xs font-bold space-y-0.5">
                                <li v-for="(err, key) in questionForm.errors" :key="key">{{ err }}</li>
                            </ul>
                        </div>

                        <form @submit.prevent="submitQuestionForm" class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Énoncé</label>
                                <textarea v-model="questionForm.enonce" rows="2" required class="w-full bg-white border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-4 py-3 text-sm"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Type</label>
                                    <select v-model="questionForm.type" class="w-full bg-white border-0 rounded-xl focus:ring-2 focus:ring-blue-500 font-black text-[10px] px-3 py-2.5 appearance-none">
                                        <option value="qcm">QCM</option>
                                        <option value="open">Question ouverte</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Points</label>
                                    <input v-model="questionForm.points" type="number" class="w-full bg-white border-0 rounded-xl focus:ring-2 focus:ring-blue-500 font-black text-[10px] px-3 py-2.5">
                                </div>
                            </div>

                            <!-- Options for QCM -->
                            <div v-if="questionForm.type === 'qcm'" class="space-y-3 pt-2">
                                <div class="flex items-center justify-between">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Options</label>
                                    <button type="button" @click="addOption" class="text-blue-600 hover:text-blue-700 text-[10px] font-black uppercase tracking-widest">+ Ajouter</button>
                                </div>
                                <div v-for="(opt, idx) in questionForm.options" :key="idx" class="flex items-center gap-2">
                                    <input type="checkbox" v-model="opt.is_correct" class="rounded text-blue-600 focus:ring-blue-500">
                                    <input v-model="opt.texte" placeholder="Option..." class="flex-1 bg-white border-0 rounded-xl focus:ring-2 focus:ring-blue-500 font-bold text-[10px] px-3 py-2">
                                    <button type="button" @click="removeOption(idx)" class="text-gray-300 hover:text-red-500 transition">
                                        <XMarkIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <!-- Expected Answer for Open Question -->
                            <div v-if="questionForm.type === 'open'" class="pt-2">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Corrigé / Réponse attendue (optionnel)</label>
                                <textarea v-model="questionForm.expected_answer" rows="3" class="w-full bg-white border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-4 py-3 text-sm" placeholder="Saisissez les éléments de réponse attendus..."></textarea>
                                <p class="text-[9px] font-bold text-gray-400 mt-1">Sert de référence lors de la correction.</p>
                            </div>

                            <div v-if="isQuestionLimitExceeded" class="p-3 bg-red-50 border border-red-100 rounded-xl text-red-600 flex items-center gap-2 animate-pulse mt-4">
                                <XCircleIcon class="h-4 w-4 shrink-0" />
                                <p class="text-[9px] font-black uppercase tracking-widest leading-tight">Cumul ({{ currentChapterQuestionsTotalPoints + parseFloat(questionForm.points) }}) dépasse le total autorisé.</p>
                            </div>

                            <button 
                                type="submit" 
                                :disabled="questionForm.processing || isQuestionLimitExceeded"
                                class="w-full py-4 bg-blue-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition shadow-lg shadow-blue-100 mt-6 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Ajouter la question
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- File Management Modal -->
        <div v-if="isFileModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm">
            <div class="bg-white w-full max-w-lg rounded-[2.5rem] overflow-hidden shadow-2xl">
                <div class="p-8 border-b border-gray-100 flex items-center justify-between bg-gray-50/30">
                    <div>
                        <h3 class="text-2xl font-black text-gray-900 tracking-tight italic">Gérer les Fichiers</h3>
                        <p class="text-xs text-gray-400 font-bold mt-1 uppercase tracking-widest truncate max-w-[300px]">{{ selectedChapterForFile?.titre }}</p>
                    </div>
                    <button @click="isFileModalOpen = false" class="p-2 hover:bg-gray-100 rounded-xl transition">
                        <XMarkIcon class="h-6 w-6 text-gray-400" />
                    </button>
                </div>
                <div class="p-8 space-y-6">
                    <div v-if="selectedChapterForFile?.attachments && selectedChapterForFile.attachments.length > 0" class="space-y-3">
                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Fichiers Existants</h4>
                        <div v-for="(file, index) in selectedChapterForFile.attachments" :key="index" class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl border border-gray-100 group">
                            <div class="flex items-center gap-3 overflow-hidden">
                                <div class="h-10 w-10 shrink-0 bg-white rounded-xl flex items-center justify-center shadow-sm border border-blue-50 text-blue-600">
                                    <DocumentIcon class="h-5 w-5" />
                                </div>
                                <span class="text-xs font-bold text-gray-700 truncate">{{ file.name }}</span>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a :href="'/chapters/' + selectedChapterForFile.id + '/attachments/' + index" target="_blank" class="p-2 bg-white text-blue-600 rounded-xl shadow-sm border border-blue-50 hover:bg-blue-600 hover:text-white transition" title="Télécharger">
                                    <EyeIcon class="h-4 w-4" />
                                </a>
                                <button v-if="!isSecretaire" @click="deleteAttachment(selectedChapterForFile.id, index)" class="p-2 bg-white text-red-500 rounded-xl shadow-sm border border-red-50 hover:bg-red-500 hover:text-white transition" title="Supprimer">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-6 italic text-gray-400 font-bold text-sm">
                        Aucun fichier attaché.
                    </div>

                    <div v-if="!isSecretaire" class="pt-6 border-t border-gray-100">
                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Ajouter un fichier</h4>
                        <form @submit.prevent="submitFileForm" class="space-y-4">
                            <div>
                                <input type="file" @input="fileForm.attachment = $event.target.files[0]" class="w-full text-sm text-gray-500 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-xs file:font-black file:uppercase file:tracking-widest file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition" required>
                                <div v-if="fileForm.errors.attachment" class="mt-2 text-xs font-bold text-red-500">{{ fileForm.errors.attachment }}</div>
                            </div>
                            <button type="submit" :disabled="fileForm.processing" class="w-full py-4 bg-gray-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-600 transition shadow-xl shadow-gray-200 disabled:opacity-50">
                                Uploader
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grading Modal -->
        <div v-if="isGradeModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm">
            <div class="bg-white w-full max-w-lg rounded-[2.5rem] overflow-hidden shadow-2xl">
                <div class="p-8 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-black text-gray-900 tracking-tight italic">{{ isSecretaire ? 'Détails du rendu' : 'Correction' }}</h3>
                        <p class="text-xs text-gray-400 font-bold mt-1 uppercase tracking-widest">{{ selectedSubmission.user.name }}</p>
                    </div>
                    <button @click="isGradeModalOpen = false" class="p-2 hover:bg-gray-100 rounded-xl transition">
                        <XMarkIcon class="h-6 w-6 text-gray-400" />
                    </button>
                </div>
                
                <div class="p-8 space-y-6">
                     <!-- Submission View -->
                    <div class="p-6 bg-blue-50/30 rounded-3xl border border-blue-100/50">
                        <h4 class="text-[9px] font-black text-blue-400 uppercase tracking-[0.2em] mb-3">Travail Rendu</h4>
                        
                        <!-- File Upload -->
                        <div v-if="selectedSubmission.file_path" class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 bg-white rounded-xl flex items-center justify-center shadow-sm border border-blue-50 text-blue-600">
                                    <DocumentIcon class="h-6 w-6" />
                                </div>
                                <span class="text-xs font-black text-blue-900 truncate max-w-[200px] italic">{{ selectedSubmission.file_path.split('/').pop() }}</span>
                            </div>
                            <a :href="'/storage/' + selectedSubmission.file_path" target="_blank" class="p-2 bg-white text-blue-600 rounded-xl shadow-sm border border-blue-50 hover:bg-blue-600 hover:text-white transition">
                                <EyeIcon class="h-5 w-5" />
                            </a>
                        </div>

                        <!-- Online Answers -->
                        <div v-if="selectedExercise.exercise_type === 'online' && selectedSubmission.answers" class="space-y-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[10px] font-black text-blue-600 uppercase tracking-widest">Réponses détaillées</span>
                                <span class="text-[9px] font-bold text-blue-400 bg-white px-2 py-0.5 rounded border border-blue-100">Auto-correction active</span>
                            </div>
                            <div v-for="(q, idx) in selectedExercise.questions" :key="q.id" class="p-3 bg-white/60 rounded-xl border border-blue-50/50 text-xs">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="font-black text-gray-900">{{ idx + 1 }}. {{ q.enonce }}</span>
                                    <span v-if="q.type === 'qcm'" 
                                        :class="isSubmissionCorrect(q, selectedSubmission) ? 'text-green-600' : 'text-red-500'"
                                        class="font-black text-[9px] uppercase tracking-widest"
                                    >
                                        {{ isSubmissionCorrect(q, selectedSubmission) ? '+' + q.points : '+0' }} pts
                                    </span>
                                </div>
                                <div class="font-medium text-blue-800 italic">
                                    "{{ getSubmissionAnswerText(q, selectedSubmission) }}"
                                </div>
                            </div>
                        </div>

                        <p v-if="selectedSubmission.student_comment" class="mt-4 p-4 bg-white/60 rounded-2xl text-[11px] font-medium text-blue-800 border border-blue-50/50 italic">
                            <ChatBubbleLeftRightIcon class="h-4 w-4 inline mr-1 opacity-50" />
                            "{{ selectedSubmission.student_comment }}"
                        </p>
                    </div>

                    <!-- Grading Form -->
                    <form @submit.prevent="submitGrade" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Note ({{ selectedExercise.exercise_points }} max)</label>
                                <input :disabled="isSecretaire" v-model="gradeForm.grade" type="number" step="0.5" required class="w-full bg-gray-50 disabled:opacity-75 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Décision</label>
                                <select :disabled="isSecretaire" v-model="gradeForm.status" required class="w-full bg-gray-50 disabled:opacity-75 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5 appearance-none">
                                    <option value="excellent">Validé (Excellent)</option>
                                    <option value="very_good">Validé (Très Bien)</option>
                                    <option value="good">Validé (Bien)</option>
                                    <option value="satisfactory">Validé (Passable)</option>
                                    <option value="graded">Validé</option>
                                    <option value="rejected">À retravailler</option>
                                    <option value="weak">Faible</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Feedback / Observations</label>
                            <textarea :disabled="isSecretaire" v-model="gradeForm.trainer_feedback" rows="3" class="w-full bg-gray-50 disabled:opacity-75 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-3.5 placeholder:font-normal" placeholder="Conseils à l'apprenant..."></textarea>
                        </div>

                        <div class="pt-4 flex gap-3">
                            <button type="button" @click="isGradeModalOpen = false" class="flex-1 py-4 bg-gray-100 text-gray-500 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-gray-200 transition">Fermer</button>
                            <button 
                                v-if="!isSecretaire"
                                type="submit" 
                                :disabled="gradeForm.processing"
                                class="flex-1 py-4 bg-gray-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-600 transition shadow-xl shadow-gray-200"
                            >
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
