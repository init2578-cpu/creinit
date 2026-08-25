<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DateInput from '@/Components/DateInput.vue';
import { Head, useForm, Link, usePage, router } from '@inertiajs/vue3';
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
    XMarkIcon,
    CheckIcon,
    ArrowPathIcon,
    PencilSquareIcon,
    DocumentDuplicateIcon,
    UserIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    exams: Array,
    modules: Array,
    groups: Array,
    trainers: {
        type: Array,
        default: () => []
    }
});

const page = usePage();
const roles = computed(() => page.props.auth.user.roles);
const isDirecteur = computed(() => roles.value.includes('Directeur'));
const isSecretaire = computed(() => roles.value.includes('Secrétaire'));
const isTrainer = computed(() => page.props.auth.user.is_trainer);

const isModalOpen = ref(false);
const isGradeModalOpen = ref(false);
const isAnswersModalOpen = ref(false);
const showScoreBeforeClose = ref(false);
const showUnlockConfirm = ref(false);
const pendingUnlockStudent = ref(null);
const editingExam = ref(null);
const selectedExamForGrades = ref(null);
const selectedStudentForAnswers = ref(null);
const isQuestionModalOpen = ref(false);
const editingQuestion = ref(null);
const selectedExamForQuestionId = ref(null);
const selectedExamForQuestion = computed(() => {
    if (!selectedExamForQuestionId.value) return null;
    return props.exams.find(e => e.id === selectedExamForQuestionId.value);
});

const groupSearchQuery = ref('');

const filteredGroups = computed(() => {
    let result = props.groups || [];
    
    // Always filter out closed groups
    result = result.filter(g => g.status !== 'closed');
    
    // Filter by module if selected
    if (form.module_id) {
        result = result.filter(g => g.module_id === parseInt(form.module_id));
    }
    
    // Filter by search query
    if (groupSearchQuery.value) {
        const query = groupSearchQuery.value.toLowerCase();
        result = result.filter(g => 
            g.nom_groupe.toLowerCase().includes(query) || 
            (g.annee_academique && g.annee_academique.toLowerCase().includes(query))
        );
        return result;
    }
    
    // If no search query, return the first 10
    return result.slice(0, 10);
});

const form = useForm({
    module_id: '',
    titre: '',
    type: 'online',
    description: '',
    duree_minutes: 60,
    total_points: 20,
    scheduled_at: '',
    scheduled_end: '',
    document: null,
    group_ids: [],
    user_id: ''
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

const currentHour = computed(() => new Date().getHours());
const currentMinute = computed(() => new Date().getMinutes());

const filteredStartHourOptions = computed(() => hourOptions);
const filteredStartMinuteOptions = computed(() => minuteOptions);
const filteredEndHourOptions = computed(() => hourOptions);
const filteredEndMinuteOptions = computed(() => minuteOptions);

const gradeForm = useForm({
    grades: [] // Array of { user_id, score }
});

const openQuestionForm = useForm({
    open_question_scores: {},
    score: null,
    bonus: 0,
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

const formatLocalDate = (dateObj) => {
    const year = dateObj.getFullYear();
    const month = (dateObj.getMonth() + 1).toString().padStart(2, '0');
    const day = dateObj.getDate().toString().padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const todayString = computed(() => {
    return formatLocalDate(new Date());
});

const openModal = (exam = null) => {
    editingExam.value = exam;
    
    const setInitialTimes = () => {
        const now = new Date();
        startDate.value = todayString.value;
        startHour.value = now.getHours().toString().padStart(2, '0');
        let roundedMin = Math.ceil(now.getMinutes() / 5) * 5;
        if (roundedMin >= 60) {
            startHour.value = ((now.getHours() + 1) % 24).toString().padStart(2, '0');
            roundedMin = 0;
        }
        startMin.value = roundedMin.toString().padStart(2, '0');

        const endNow = new Date(now.getTime() + 120 * 60000); // 2 hours later
        endDate.value = formatLocalDate(endNow);
        endHour.value = endNow.getHours().toString().padStart(2, '0');
        let roundedEndMin = Math.ceil(endNow.getMinutes() / 5) * 5;
        if (roundedEndMin >= 60) {
            endHour.value = ((endNow.getHours() + 1) % 24).toString().padStart(2, '0');
            roundedEndMin = 0;
        }
        endMin.value = roundedEndMin.toString().padStart(2, '0');

        form.scheduled_at = `${startDate.value}T${startTime.value}`;
        form.scheduled_end = `${endDate.value}T${endTime.value}`;
    };

    if (exam) {
        form.module_id = exam.module_id;
        form.titre = exam.titre;
        form.type = exam.type;
        form.description = exam.description;
        form.duree_minutes = exam.duree_minutes;
        form.total_points = exam.total_points;
        form.group_ids = exam.groups ? exam.groups.map(g => g.id) : [];
        form.user_id = exam.user_id;
        if (exam.scheduled_at) {
            const dateObj = new Date(exam.scheduled_at);
            startDate.value = formatLocalDate(dateObj);
            startHour.value = dateObj.getHours().toString().padStart(2, '0');
            startMin.value = (Math.round(dateObj.getMinutes() / 5) * 5).toString().padStart(2, '0');
            if (parseInt(startMin.value) > 55) startMin.value = '55';
            
            const endObj = new Date(dateObj.getTime() + exam.duree_minutes * 60000);
            endDate.value = formatLocalDate(endObj);
            endHour.value = endObj.getHours().toString().padStart(2, '0');
            endMin.value = (Math.round(endObj.getMinutes() / 5) * 5).toString().padStart(2, '0');
            if (parseInt(endMin.value) > 55) endMin.value = '55';
            
            form.scheduled_at = `${startDate.value}T${startTime.value}`;
            form.scheduled_end = `${endDate.value}T${endTime.value}`;
        } else {
            setInitialTimes();
        }
    } else {
        form.reset();
        form.group_ids = [];
        form.user_id = page.props.auth.user.id;
        setInitialTimes();
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
        form.transform((data) => {
            const transformed = { ...data, _method: 'PUT' };
            if (!(transformed.document instanceof File)) {
                delete transformed.document;
            }
            return transformed;
        }).post(route('exams.update', editingExam.value.id), {
            onSuccess: () => closeModal(),
            forceFormData: true,
        });
    } else {
        form.transform((data) => {
            const transformed = { ...data };
            if (!(transformed.document instanceof File)) {
                delete transformed.document;
            }
            return transformed;
        }).post(route('exams.store'), {
            onSuccess: () => closeModal(),
            forceFormData: true,
        });
    }
};

const deleteExam = (id) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet examen ?')) {
        form.delete(route('exams.destroy', id));
    }
};

const duplicateExam = (id) => {
    let confirmMsg = 'Voulez-vous dupliquer cet examen ? Une nouvelle copie sera créée, prête à être assignée à un autre groupe.';
    if (isDirecteur.value) {
        confirmMsg = 'Voulez-vous dupliquer cet examen ? Une nouvelle copie sera créée. Vous pourrez ensuite l\'éditer pour l\'attribuer à un autre formateur, lui assigner des groupes et une date.';
    }
    if (confirm(confirmMsg)) {
        router.post(route('exams.duplicate', id), {}, {
            preserveScroll: true
        });
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

const openAnswersModal = (student) => {
    selectedStudentForAnswers.value = student;
    const existingScores = student.answers?._question_scores || {};
    const initialScores = {};

    if (selectedExamForGrades.value?.questions) {
        selectedExamForGrades.value.questions.forEach(q => {
            if (q.type === 'open') {
                initialScores[q.id] = existingScores[q.id] !== undefined ? existingScores[q.id] : 0;
            }
        });
    }

    openQuestionForm.open_question_scores = initialScores;
    openQuestionForm.score = student.score;
    openQuestionForm.bonus = student.bonus || 0;
    isAnswersModalOpen.value = true;
};

const calculatedLiveScore = computed(() => {
    if (!selectedExamForGrades.value || !selectedStudentForAnswers.value) return 0;
    let qcmEarned = 0;
    let openEarned = 0;
    const answers = selectedStudentForAnswers.value.answers || {};

    selectedExamForGrades.value.questions?.forEach(q => {
        if (q.type === 'qcm') {
            const correctOptions = q.options?.filter(o => o.is_correct).map(o => o.id) || [];
            const userAns = answers[q.id] || [];
            const userAnsArray = Array.isArray(userAns) ? userAns : (userAns !== undefined && userAns !== null ? [userAns] : []);
            const ptsPerOpt = correctOptions.length > 0 ? q.points / correctOptions.length : 0;
            let qPts = 0;
            userAnsArray.forEach(id => {
                if (correctOptions.includes(Number(id))) {
                    qPts += ptsPerOpt;
                } else {
                    qPts -= ptsPerOpt;
                }
            });
            if (qPts < 0) qPts = 0;
            if (qPts > q.points) qPts = q.points;
            qcmEarned += qPts;
        } else {
            const pts = parseFloat(openQuestionForm.open_question_scores[q.id]) || 0;
            openEarned += Math.min(pts, q.points);
        }
    });

    const totalPts = selectedExamForGrades.value.total_points || selectedExamForGrades.value.questions?.reduce((s, q) => s + (parseFloat(q.points) || 0), 0) || 20;
    const finalScore = totalPts > 0 ? ((qcmEarned + openEarned) / totalPts) * 20 : 0;
    return Math.round(finalScore * 100) / 100;
});

const hasOpenQuestions = computed(() => {
    return selectedExamForGrades.value?.questions?.some(q => q.type === 'open');
});

const isOpenQuestionGraded = (student) => {
    if (!student || !student.answers || !student.answers._question_scores) return false;
    return Object.keys(student.answers._question_scores).length > 0;
};

const submitOpenQuestionGrades = () => {
    if (selectedExamForGrades.value?.questions) {
        for (const q of selectedExamForGrades.value.questions) {
            if (q.type === 'open') {
                const enteredScore = parseFloat(openQuestionForm.open_question_scores[q.id]);
                if (enteredScore > q.points) {
                    window.platformAlert(`La note saisie pour la question "${q.enonce.substring(0, 30)}..." dépasse le maximum autorisé (${q.points} pts).`, "error");
                    return;
                }
            }
        }
    }

    openQuestionForm.score = calculatedLiveScore.value;
    openQuestionForm.post(route('exams.grade-open-questions', {
        exam: selectedExamForGrades.value.id,
        user: selectedStudentForAnswers.value.user_id
    }), {
        preserveScroll: true,
        onSuccess: async () => {
            const response = await axios.get(route('exams.results', selectedExamForGrades.value.id));
            gradeForm.grades = response.data;
            const updated = gradeForm.grades.find(s => s.user_id === selectedStudentForAnswers.value.user_id);
            if (updated) {
                selectedStudentForAnswers.value = updated;
            }
            window.platformAlert("Correction enregistrée avec succès !", "success");
        },
        onError: (err) => {
            console.error(err);
            window.platformAlert("Erreur lors de l'enregistrement de la correction.", "error");
        }
    });
};

const closeAnswersModalWithScore = () => {
    // Always show score summary before closing
    showScoreBeforeClose.value = true;
};

const confirmCloseAnswersModal = () => {
    showScoreBeforeClose.value = false;
    isAnswersModalOpen.value = false;
};

const isOptionSelected = (answers, questionId, optionId) => {
    if (!answers || !answers[questionId]) return false;
    const ans = answers[questionId];
    return Array.isArray(ans) ? ans.includes(optionId) : ans == optionId;
};

const submitGrades = () => {
    gradeForm.post(route('exams.enter-grades', selectedExamForGrades.value.id), {
        onSuccess: () => {
            isGradeModalOpen.value = false;
            window.platformAlert("Les notes ont été enregistrées avec succès.", "success");
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
            window.platformAlert("Certaines notes sont invalides ou manquantes.", "error");
        }
    });
};

const unlockExam = (userId) => {
    const student = gradeForm.grades.find(s => s.user_id === userId);
    pendingUnlockStudent.value = student || { user_id: userId, name: 'cet apprenant' };
    showUnlockConfirm.value = true;
};

const confirmUnlock = () => {
    const userId = pendingUnlockStudent.value?.user_id;
    showUnlockConfirm.value = false;
    router.post(route('exams.unlock', { exam: selectedExamForGrades.value.id, user: userId }), {}, {
        preserveScroll: true,
        onSuccess: async () => {
            const response = await axios.get(route('exams.results', selectedExamForGrades.value.id));
            gradeForm.grades = response.data;
            pendingUnlockStudent.value = null;
        }
    });
};

const confirmUnblockOnly = () => {
    const userId = pendingUnlockStudent.value?.user_id;
    showUnlockConfirm.value = false;
    router.post(route('exams.unblock', { exam: selectedExamForGrades.value.id, user: userId }), {}, {
        preserveScroll: true,
        onSuccess: async () => {
            const response = await axios.get(route('exams.results', selectedExamForGrades.value.id));
            gradeForm.grades = response.data;
            pendingUnlockStudent.value = null;
            window.platformAlert("L'examen a été débloqué. L'apprenant peut reprendre là où il s'était arrêté.", "success");
        }
    });
};

const openQuestionModal = (exam, question = null) => {
    selectedExamForQuestionId.value = exam.id;
    editingQuestion.value = question;
    questionForm.clearErrors();
    if (question) {
        questionForm.enonce = question.enonce;
        questionForm.expected_answer = question.expected_answer || '';
        questionForm.points = question.points;
        questionForm.type = question.type;
        questionForm.options = (question.options && question.options.length > 0) ? [...question.options] : [{ texte: '', is_correct: false }, { texte: '', is_correct: false }];
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
    const newPoints = parseFloat(questionForm.points) || 0;
    
    if (editingQuestion.value) {
        currentPoints -= parseFloat(editingQuestion.value.points) || 0;
    }
    
    return (currentPoints + newPoints) > selectedExamForQuestion.value.total_points;
});

const submitQuestionForm = () => {
    if (isQuestionLimitExceeded.value) {
        window.platformAlert(`Dépassement du barème : le total ne peut pas dépasser ${selectedExamForQuestion.value.total_points} pts.`, 'error');
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
            .post(route('exams.questions.store', selectedExamForQuestion.value.id), {
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

const isExamStarted = (exam) => {
    if (!exam || !exam.scheduled_at) return false;
    return new Date(exam.scheduled_at) <= new Date();
};

const canManageExam = (exam) => {
    if (!exam) return false;
    if (exam.can_manage !== undefined) return exam.can_manage;
    if (isSecretaire.value) return false;
    if (isDirecteur.value) return true;
    if (isTrainer.value) {
        return exam.user_id === page.props.auth.user.id;
    }
    return true;
};

const canModifyExam = (exam) => {
    if (!exam) return false;
    if (exam.can_modify !== undefined) return exam.can_modify;
    if (isSecretaire.value) return false;
    if (isDirecteur.value) return true;
    if (isTrainer.value && exam.user_id !== page.props.auth.user.id) return false;
    return !isExamStarted(exam) || !exam.exam_results || exam.exam_results.length === 0;
};

// Removed handleTimeInput since we now use dropdowns

// Reactive duration calculation and time constraints enforcement
watch(() => [startDate.value, startHour.value, startMin.value, endDate.value, endHour.value, endMin.value], () => {

    // 2. Adjust endHour/endMin if end time is before start time on the same day
    if (endDate.value === startDate.value) {
        const selH = parseInt(startHour.value) || 0;
        const selM = parseInt(startMin.value) || 0;
        const eh = parseInt(endHour.value) || 0;
        const em = parseInt(endMin.value) || 0;

        if (eh < selH) {
            endHour.value = startHour.value;
            endMin.value = startMin.value;
        } else if (eh === selH && em < selM) {
            endMin.value = startMin.value;
        }
    }

    // Auto sync endDate if empty
    if (startDate.value && (!endDate.value || endDate.value === '')) {
        endDate.value = startDate.value;
    }

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
        form.duree_minutes = Math.max(0, Math.round(diffMs / 60000));
    } else {
        form.duree_minutes = 60;
    }
});

const addOption = () => {
    questionForm.options.push({ texte: '', is_correct: false });
};

const removeOption = (index) => {
    if (questionForm.options.length > 2) {
        questionForm.options.splice(index, 1);
    }
};

function approveExam(examId) {
    if (confirm('Voulez-vous vraiment valider cet examen ? Il sera alors visible par les apprenants.')) {
        router.patch(route('exams.approve', examId), {}, {
            preserveScroll: true
        });
    }
}

</script>

<template>
    <Head title="Gestion des Examens" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight flex items-center gap-2.5">
                        <AcademicCapIcon class="h-8 w-8 text-blue-600 shrink-0" />
                        Administration des Examens
                    </h2>
                    <p class="mt-1 text-xs text-gray-500 font-medium">Gérez vos sessions d'examens en ligne et sur table.</p>
                </div>
                <button 
                    v-if="!isSecretaire"
                    @click="openModal()"
                    class="flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-900 text-white rounded-xl font-bold text-xs hover:bg-blue-600 transition-all shadow-md hover:shadow-blue-100 shrink-0"
                >
                    <PlusIcon class="h-4 w-4" />
                    Nouvel Examen
                </button>
            </header>

            <!-- Stats Bar -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:-translate-y-0.5 hover:shadow-md transition-all duration-300 group">
                    <div class="h-10 w-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform duration-300">
                        <DocumentTextIcon class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Total Examens</p>
                        <p class="text-xl font-black text-gray-900 mt-0.5">{{ exams.length }}</p>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:-translate-y-0.5 hover:shadow-md transition-all duration-300 group">
                    <div class="h-10 w-10 bg-green-50 text-green-600 rounded-xl flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform duration-300">
                        <CheckCircleIcon class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Sessions en Ligne</p>
                        <p class="text-xl font-black text-gray-900 mt-0.5">{{ exams.filter(e => e.type === 'online').length }}</p>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:-translate-y-0.5 hover:shadow-md transition-all duration-300 group">
                    <div class="h-10 w-10 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform duration-300">
                        <PencilIcon class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Épreuves sur Table</p>
                        <p class="text-xl font-black text-gray-900 mt-0.5">{{ exams.filter(e => e.type === 'paper').length }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse min-w-[950px]">
                        <thead>
                            <tr class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-black tracking-widest border-b border-gray-100">
                                <th class="px-4 py-3 whitespace-nowrap">Examen</th>
                                <th class="px-4 py-3 whitespace-nowrap">Type</th>
                                <th class="px-4 py-3 text-center whitespace-nowrap">Planification</th>
                                <th class="px-4 py-3 text-center whitespace-nowrap">Score Max</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="exam in exams" :key="exam.id" class="hover:bg-gray-50/50 transition group">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center shrink-0 group-hover:bg-blue-600 group-hover:text-white transition">
                                            <AcademicCapIcon class="h-5 w-5" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <p class="font-bold text-sm text-gray-900 tracking-tight leading-snug">{{ exam.titre }}</p>
                                                <span v-if="!exam.is_approved" class="px-2 py-0.5 bg-yellow-50 text-yellow-700 border border-yellow-100 text-[9px] font-black uppercase rounded tracking-wider shrink-0 whitespace-nowrap">
                                                    En attente
                                                </span>
                                                <span v-else class="px-2 py-0.5 bg-green-50 text-green-700 border border-green-100 text-[9px] font-black uppercase rounded tracking-wider shrink-0 whitespace-nowrap">
                                                    Validé
                                                </span>
                                                <div v-if="exam.user" class="px-2 py-0.5 rounded-md bg-gray-100 border border-gray-200 flex items-center gap-1 shrink-0 whitespace-nowrap" :title="`Proposé le ${new Date(exam.created_at).toLocaleDateString('fr-FR')} à ${new Date(exam.created_at).toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'})} par ${exam.user.name}`">
                                                    <span class="h-3.5 w-3.5 rounded-full bg-gray-300 text-[8px] flex items-center justify-center font-black text-white shrink-0">{{ exam.user.name.charAt(0) }}</span>
                                                    <span class="text-[9px] font-black text-gray-600 tracking-wider whitespace-nowrap">{{ exam.user.name }}</span>
                                                    <span class="text-[8px] font-bold text-gray-400 border-l border-gray-300 pl-1 ml-0.5 whitespace-nowrap">le {{ new Date(exam.created_at).toLocaleDateString('fr-FR') }} à {{ new Date(exam.created_at).toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'}) }}</span>
                                                </div>
                                                <div v-if="isExamEnded(exam) && (exam.exam_results?.length < exam.expected_results_count)" 
                                                      class="px-2 py-0.5 bg-amber-500 text-white text-[8px] font-black uppercase rounded shadow-md shadow-amber-100 ring-2 ring-amber-50 flex items-center gap-1 shrink-0 whitespace-nowrap">
                                                    <span class="h-1 w-1 rounded-full bg-white animate-ping"></span>
                                                    MANQUE {{ exam.expected_results_count - exam.exam_results?.length }} NOTES
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                                <span class="text-[9px] text-gray-400 font-black uppercase tracking-wider italic">
                                                    {{ exam.module?.titre }}
                                                </span>
                                                <span v-if="exam.groups && exam.groups.length > 0" class="text-gray-300 text-[9px]">•</span>
                                                <div class="flex gap-1 flex-wrap">
                                                    <span 
                                                        v-for="g in exam.groups" 
                                                        :key="g.id"
                                                        class="px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded text-[8px] font-black uppercase tracking-wider border border-blue-100 whitespace-nowrap"
                                                    >
                                                        {{ g.nom_groupe }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest border inline-block" :class="getTypeClass(exam.type)">
                                        {{ getTypeLabel(exam.type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex flex-col items-center gap-0.5">
                                        <div class="flex flex-col items-center">
                                            <span class="text-xs font-black text-gray-900 flex items-center gap-1 uppercase tracking-tight whitespace-nowrap">
                                                <CalendarIcon class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                                                {{ exam.scheduled_at ? new Date(exam.scheduled_at).toLocaleDateString('fr-FR') : 'Non planifié' }}
                                            </span>
                                            <span v-if="exam.scheduled_at" class="text-[9px] font-bold text-blue-600 mt-0.5 whitespace-nowrap">
                                                {{ new Date(exam.scheduled_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', hour12: false }) }}
                                                -
                                                {{ new Date(new Date(exam.scheduled_at).getTime() + exam.duree_minutes * 60000).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', hour12: false }) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1.5 whitespace-nowrap">
                                            <span class="text-[9px] text-gray-400 font-bold flex items-center gap-1 whitespace-nowrap">
                                                <ClockIcon class="h-3 w-3 shrink-0" />
                                                {{ exam.duree_minutes }} min
                                            </span>
                                            <span v-if="isExamEnded(exam)" class="text-[8px] font-black text-red-500 bg-red-50 px-1.5 py-0.5 rounded border border-red-100 uppercase tracking-widest whitespace-nowrap">
                                                Terminé
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="text-xs font-black text-gray-900">{{ parseFloat(exam.total_points).toFixed(0) }}</span>
                                    <span class="text-[9px] text-gray-400 font-bold ml-0.5">pts</span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1 shrink-0 whitespace-nowrap">
                                        <button v-if="isDirecteur && !exam.is_approved" @click="approveExam(exam.id)" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition shrink-0" title="Valider l'examen">
                                            <CheckCircleIcon class="h-5 w-5" />
                                        </button>
                                        <a 
                                            v-if="exam.type === 'paper' && exam.document_path" 
                                            :href="route('exams.download-file', exam.id)" 
                                            target="_blank"
                                            class="p-1.5 text-purple-600 hover:bg-purple-50 rounded-lg transition flex items-center justify-center shrink-0" 
                                            title="Voir l'énoncé proposé"
                                        >
                                            <DocumentIcon class="h-5 w-5" />
                                        </a>
                                        <button v-if="exam.type === 'online' && (canManageExam(exam) || isSecretaire || exam.can_view_questions)" @click="openQuestionModal(exam)" class="p-1.5 text-blue-500 hover:bg-blue-50 rounded-lg transition shrink-0" :title="canManageExam(exam) ? 'Gérer les questions' : 'Voir les questions'">
                                            <QueueListIcon class="h-5 w-5" />
                                        </button>
                                        <button 
                                            v-if="exam.is_approved && !isSecretaire && (canManageExam(exam) || exam.can_view_questions)"
                                            @click="openGradeModal(exam)" 
                                            class="p-1.5 text-green-600 hover:bg-green-50 rounded-lg transition shrink-0" 
                                            :title="canManageExam(exam) ? 'Consulter les notes / Saisie' : 'Consulter les résultats'"
                                        >
                                            <ClipboardDocumentCheckIcon class="h-5 w-5" />
                                        </button>
                                        <button 
                                            v-else-if="!isSecretaire && canManageExam(exam)"
                                            disabled 
                                            class="p-1.5 text-gray-300 cursor-not-allowed rounded-lg transition shrink-0" 
                                            title="L'attribution des notes est bloquée tant que le directeur n'a pas validé l'épreuve"
                                        >
                                            <ClipboardDocumentCheckIcon class="h-5 w-5" />
                                        </button>
                                        <button v-if="canModifyExam(exam)" @click="openModal(exam)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition shrink-0" title="Modifier">
                                            <PencilIcon class="h-5 w-5" />
                                        </button>
                                        <button v-if="!isSecretaire && canManageExam(exam)" @click="duplicateExam(exam.id)" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition shrink-0" title="Dupliquer pour un autre groupe">
                                            <DocumentDuplicateIcon class="h-5 w-5" />
                                        </button>
                                        <button v-if="canModifyExam(exam)" @click="deleteExam(exam.id)" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition shrink-0" title="Supprimer">
                                            <TrashIcon class="h-5 w-5" />
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
            <div class="bg-white w-full max-w-3xl rounded-2xl overflow-hidden shadow-2xl border border-gray-100 flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-white">
                    <div>
                        <h3 class="text-xl font-black text-gray-900 tracking-tight flex items-center gap-2.5">
                            <span class="p-1.5 bg-blue-50 text-blue-600 rounded-xl">
                                <PlusIcon v-if="!editingExam" class="h-5 w-5" />
                                <PencilIcon v-else class="h-5 w-5" />
                            </span>
                            {{ editingExam ? 'Modifier l\'examen' : 'Nouvel Examen' }}
                        </h3>
                        <p class="text-[10px] text-gray-400 font-bold mt-0.5 uppercase tracking-widest italic ml-9">Session d'évaluation e-CRE</p>
                    </div>
                    <button @click="closeModal" class="p-2 hover:bg-gray-100 rounded-xl transition-all duration-300 transform hover:rotate-90">
                        <XMarkIcon class="h-5 w-5 text-gray-400" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-0 overflow-y-auto custom-scrollbar flex-1 bg-gray-50/30">
                    <div class="p-6 space-y-6">
                        <div v-if="Object.keys(form.errors).length > 0" class="p-3 bg-red-50 border border-red-100 rounded-xl text-red-600 space-y-1">
                            <p class="text-[10px] font-black uppercase tracking-widest flex items-center gap-2 mb-1 text-red-700">
                                <XMarkIcon class="h-4 w-4" /> Erreurs de validation
                            </p>
                            <ul class="list-disc pl-4 text-xs font-bold space-y-0.5">
                                <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                            </ul>
                        </div>

                        <!-- Section: Informations Générales -->
                        <div class="space-y-3">
                            <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] flex items-center gap-2 mb-4">
                                <span class="h-px w-8 bg-blue-200"></span>
                                Informations Générales
                            </h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">
                                        Module
                                    </label>
                                    <div class="relative group">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 group-focus-within:text-blue-600 transition-colors pointer-events-none">
                                            <QueueListIcon class="h-4 w-4" />
                                        </span>
                                        <select v-model="form.module_id" required class="w-full pl-10 pr-8 py-2.5 bg-white border border-gray-200 focus:border-blue-600 rounded-xl font-bold text-xs text-gray-700 focus:ring-0 transition-all">
                                            <option value="">Sélectionner un module</option>
                                            <option v-for="m in modules" :key="m.id" :value="m.id">{{ m.titre }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">
                                        Titre de l'examen
                                    </label>
                                    <div class="relative group">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-blue-600 transition-colors pointer-events-none">
                                            <AcademicCapIcon class="h-5 w-5" />
                                        </span>
                                        <input v-model="form.titre" type="text" required placeholder="Ex: Examen Final Module 1" class="w-full pl-12 pr-4 py-4 bg-white border-2 border-transparent focus:border-blue-600 rounded-2xl font-bold text-gray-700 focus:ring-0 transition-all outline-none">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Formateur (Directeur uniquement) -->
                            <div v-if="isDirecteur" class="space-y-2">
                                <label class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">
                                    Formateur responsable de l'examen
                                </label>
                                <div class="relative group">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-blue-600 transition-colors pointer-events-none">
                                        <UserIcon class="h-5 w-5" />
                                    </span>
                                    <select v-model="form.user_id" required class="w-full pl-12 pr-10 py-4 bg-white border-2 border-transparent focus:border-blue-600 rounded-2xl font-bold text-gray-700 focus:ring-0 transition-all">
                                        <option value="">Sélectionner un formateur</option>
                                        <option v-for="t in trainers" :key="t.id" :value="t.id">{{ t.name }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Groupes affectés -->
                            <div v-if="isDirecteur || isTrainer" class="space-y-3 pt-2">
                                <div class="flex items-center justify-between mb-2 ml-1">
                                    <label class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                        Groupes affectés
                                    </label>
                                    <input 
                                        type="text" 
                                        v-model="groupSearchQuery" 
                                        placeholder="Rechercher un groupe..." 
                                        class="text-xs px-3 py-1.5 border border-gray-200 rounded-lg focus:border-blue-600 focus:ring-0 outline-none placeholder:text-gray-300 font-bold"
                                    >
                                </div>
                                <div class="bg-gray-100/50 p-4 rounded-[1.5rem] border border-gray-200/50 max-h-48 overflow-y-auto custom-scrollbar">
                                    <div v-if="filteredGroups.length === 0" class="text-center py-4 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                        Aucun groupe disponible pour ce module
                                    </div>
                                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <label 
                                            v-for="g in filteredGroups" 
                                            :key="g.id"
                                            class="flex items-center gap-3 p-3 bg-white border border-gray-100 rounded-2xl cursor-pointer hover:border-blue-300 transition-all select-none shadow-sm"
                                        >
                                            <input 
                                                type="checkbox" 
                                                :value="g.id" 
                                                v-model="form.group_ids"
                                                class="h-5 w-5 rounded-lg text-blue-600 focus:ring-blue-500 border-gray-300 cursor-pointer shadow-sm transition-all"
                                            >
                                            <div class="flex flex-col">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-xs font-black text-gray-700 tracking-tight">{{ g.nom_groupe }}</span>
                                                    <span 
                                                        class="px-1.5 py-0.2 text-[8px] font-black uppercase tracking-wider rounded border"
                                                        :class="g.status === 'closed' ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100'"
                                                    >
                                                        {{ g.status === 'closed' ? 'Clôturé' : 'En cours' }}
                                                    </span>
                                                </div>
                                                <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">Année: {{ g.annee_academique }}</span>
                                            </div>
                                        </label>
                                    </div>
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
                                        Type d'examen
                                    </label>
                                    <div class="grid grid-cols-2 gap-2 p-1.5 bg-gray-200/60 rounded-[1.5rem]">
                                        <button 
                                            type="button"
                                            @click="form.type = 'online'"
                                            :class="form.type === 'online' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                            class="py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all duration-300"
                                        >
                                            En Ligne
                                        </button>
                                        <button 
                                            type="button"
                                            @click="form.type = 'paper'"
                                            :class="form.type === 'paper' ? 'bg-white text-purple-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                            class="py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest transition-all duration-300"
                                        >
                                            Sur Table
                                        </button>
                                    </div>
                                </div>

                                <div v-if="form.type === 'paper'" class="animate-in fade-in slide-in-from-top-2 duration-300">
                                    <label class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">
                                        Énoncé (PDF)
                                    </label>
                                    <div class="relative group h-[56px]">
                                        <input 
                                            type="file" 
                                            @input="form.document = $event.target.files[0]"
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                            accept=".pdf,.doc,.docx"
                                        />
                                        <div class="w-full h-full bg-white border-2 border-transparent group-hover:border-purple-600 rounded-2xl px-4 flex items-center justify-between transition-all shadow-sm">
                                            <span class="text-[10px] font-bold text-gray-500 truncate max-w-[180px]">
                                                {{ form.document ? form.document.name : 'Veuillez joindre l\'énoncé' }}
                                            </span>
                                            <ArrowUpTrayIcon class="h-5 w-5 text-purple-400 group-hover:scale-110 transition-transform" />
                                        </div>
                                    </div>
                                    <div v-if="editingExam && editingExam.document_path" class="mt-2 text-xs font-medium text-gray-500 flex items-center gap-2">
                                        <span>Fichier actuel :</span>
                                        <a 
                                            :href="route('exams.download-file', editingExam.id)" 
                                            target="_blank" 
                                            class="text-purple-600 hover:text-purple-700 underline font-bold flex items-center gap-1"
                                        >
                                            <DocumentIcon class="h-4 w-4 inline" />
                                            Télécharger/Voir
                                        </a>
                                    </div>
                                </div>

                                <div v-else>
                                    <label class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">
                                        Notation
                                    </label>
                                    <div class="flex items-center gap-3 bg-white p-3.5 rounded-2xl border-2 border-transparent focus-within:border-blue-600 transition-all shadow-sm" :class="{'border-red-500 bg-red-50/50': isModalTotalPointsInvalid}">
                                        <div class="h-10 w-12 bg-gray-50 rounded-xl flex items-center justify-center text-blue-600 shadow-sm border border-gray-100" :class="{'text-red-600': isModalTotalPointsInvalid}">
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
                                        Début de l'épreuve (jj/mm/aaaa)
                                    </label>
                                    <div class="flex gap-2">
                                        <div class="relative flex-1 group">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-orange-600 transition-colors pointer-events-none">
                                                <CalendarIcon class="h-5 w-5" />
                                            </span>
                                            <DateInput v-model="startDate" required class="w-full pl-12 pr-4 py-4 bg-white border-2 border-transparent focus:border-orange-600 rounded-2xl font-bold text-gray-700 focus:ring-0 transition-all outline-none text-xs" />
                                        </div>
                                        <div class="flex items-center bg-white border-2 border-transparent focus-within:border-orange-600 rounded-2xl px-3 transition-all">
                                            <select v-model="startHour" class="bg-transparent border-0 font-black text-xs p-2 focus:ring-0 cursor-pointer text-gray-700">
                                                <option v-for="h in filteredStartHourOptions" :key="h" :value="h">{{ h }}h</option>
                                            </select>
                                            <span class="text-gray-300 font-black text-xs">:</span>
                                            <select v-model="startMin" class="bg-transparent border-0 font-black text-xs p-2 focus:ring-0 cursor-pointer text-gray-700">
                                                <option v-for="m in filteredStartMinuteOptions" :key="m" :value="m">{{ m }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <label class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 ml-1">
                                        Fin de l'épreuve (jj/mm/aaaa)
                                    </label>
                                    <div class="flex gap-2">
                                        <div class="relative flex-1 group">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-orange-600 transition-colors pointer-events-none">
                                                <CalendarIcon class="h-5 w-5" />
                                            </span>
                                            <DateInput v-model="endDate" required class="w-full pl-12 pr-4 py-4 bg-white border-2 border-transparent focus:border-orange-600 rounded-2xl font-bold text-gray-700 focus:ring-0 transition-all outline-none text-xs" />
                                        </div>
                                        <div class="flex items-center bg-white border-2 border-transparent focus-within:border-orange-600 rounded-2xl px-3 transition-all">
                                            <select v-model="endHour" class="bg-transparent border-0 font-black text-xs p-2 focus:ring-0 cursor-pointer text-gray-700">
                                                <option v-for="h in filteredEndHourOptions" :key="h" :value="h">{{ h }}h</option>
                                            </select>
                                            <span class="text-gray-300 font-black text-xs">:</span>
                                            <select v-model="endMin" class="bg-transparent border-0 font-black text-xs p-2 focus:ring-0 cursor-pointer text-gray-700">
                                                <option v-for="m in filteredEndMinuteOptions" :key="m" :value="m">{{ m }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="form.scheduled_at && form.scheduled_end" class="flex items-center gap-4 bg-orange-50/60 p-5 rounded-[2rem] border border-orange-100/50 animate-in zoom-in duration-300">
                                <div class="h-12 w-12 bg-white rounded-2xl flex items-center justify-center text-orange-600 shadow-sm border border-orange-100/30">
                                    <ClockIcon class="h-6 w-6" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-orange-400 uppercase tracking-widest">Durée de la session</p>
                                    <p v-if="form.duree_minutes > 0" class="text-xl font-black text-orange-600 tracking-tight">{{ form.duree_minutes }} <span class="text-sm font-bold">minutes</span></p>
                                    <p v-else class="text-xl font-black text-red-600 tracking-tight">Intervalle invalide</p>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="space-y-3">
                            <label class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">
                                Description / Consignes
                            </label>
                            <div class="relative group">
                                <span class="absolute top-4 left-0 flex items-start pl-4 text-gray-400 group-focus-within:text-blue-600 transition-colors pointer-events-none">
                                    <DocumentTextIcon class="h-5 w-5" />
                                </span>
                                <textarea v-model="form.description" rows="3" placeholder="Écrivez ici les consignes pour les apprenants..." class="w-full pl-12 pr-4 py-4 bg-white border-2 border-transparent focus:border-blue-600 rounded-[2rem] font-bold text-gray-700 focus:ring-0 transition-all outline-none"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="p-8 bg-gray-50 border-t border-gray-100 flex gap-4 sticky bottom-0">
                        <button 
                            type="button" 
                            @click="closeModal" 
                            class="flex-1 py-4 bg-white text-gray-600 hover:text-gray-900 rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-gray-50 hover:border-gray-300 hover:shadow-sm border border-gray-200 transition-all duration-300 active:scale-98 flex items-center justify-center gap-2"
                        >
                            <XMarkIcon class="h-4 w-4 text-gray-400" />
                            Annuler
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="flex-1 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-2xl font-black text-[11px] uppercase tracking-widest disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-blue-500/20 hover:shadow-xl hover:shadow-blue-500/30 hover:-translate-y-0.5 active:translate-y-0 active:scale-98 transition-all duration-300 flex items-center justify-center gap-2"
                        >
                            <div v-if="form.processing" class="h-4 w-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                            <CheckIcon v-else class="h-4 w-4 text-white" />
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
                                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-tr from-gray-50 to-gray-100 text-gray-400 flex items-center justify-center font-black text-sm border-2 border-gray-100 shadow-inner group-hover:from-green-50 group-hover:to-green-100 group-hover:text-green-600 group-hover:border-green-300 transition-all duration-300">
                                        {{ student.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="font-black text-gray-800 text-sm tracking-tight">{{ student.name }}</p>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Apprenant e-CRE</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4">
                                    <template v-if="student.status === 'blocked' || student.status === 'started'">
                                        <!-- Voir la copie (Brouillon) -->
                                        <button 
                                            v-if="selectedExamForGrades?.type === 'online'" 
                                            type="button" 
                                            @click="openAnswersModal(student)" 
                                            class="p-2 text-blue-500 hover:text-blue-700 hover:bg-blue-50 rounded-xl transition-colors shadow-sm border border-blue-100 flex items-center gap-1.5"
                                            title="Voir les réponses sauvegardées"
                                        >
                                            <EyeIcon class="h-5 w-5" />
                                            <span class="text-[10px] font-black uppercase tracking-widest hidden sm:inline">Copie</span>
                                        </button>

                                        <span class="px-3 py-1 font-bold rounded-lg text-[10px] uppercase"
                                              :class="student.status === 'blocked' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600'">
                                            {{ student.status === 'blocked' ? 'Bloqué' : 'Débloqué / En cours' }}
                                        </span>
                                        <!-- Score sauvegardé pour les examens interrompus -->
                                        <div v-if="student.score !== null && student.score !== undefined" class="flex flex-col items-center px-3 py-1.5 rounded-xl border" :class="student.score >= 10 ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200'">
                                            <span class="text-[8px] font-black uppercase tracking-widest mb-0.5" :class="student.score >= 10 ? 'text-emerald-500' : 'text-red-400'">Note obtenue</span>
                                            <span class="text-base font-black leading-none" :class="student.score >= 10 ? 'text-emerald-700' : 'text-red-600'">
                                                {{ student.score }}<span class="text-[9px] font-bold text-gray-400">/20</span>
                                            </span>
                                        </div>
                                        <button v-if="!isExamEnded(selectedExamForGrades)" type="button" @click="unlockExam(student.user_id)" class="px-3 py-1 bg-gray-900 hover:bg-gray-800 text-white rounded-lg text-[10px] font-bold uppercase transition-colors shadow-sm">
                                            Gérer
                                        </button>
                                    </template>
                                    <template v-else>
                                        <!-- Voir la copie -->
                                        <button 
                                            v-if="student.status === 'completed' && selectedExamForGrades?.type === 'online'" 
                                            type="button" 
                                            @click="openAnswersModal(student)" 
                                            class="p-2 text-blue-500 hover:text-blue-700 hover:bg-blue-50 rounded-xl transition-colors shadow-sm border border-blue-100 flex items-center gap-1.5"
                                            title="Voir la copie"
                                        >
                                            <EyeIcon class="h-5 w-5" />
                                            <span class="text-[10px] font-black uppercase tracking-widest hidden sm:inline">Copie</span>
                                        </button>

                                        <!-- Badge Statut Correction Questions Ouvertes -->
                                        <span v-if="student.status === 'completed' && hasOpenQuestions" 
                                              class="px-2 py-1 text-[9px] font-black uppercase tracking-wider rounded-lg border"
                                              :class="isOpenQuestionGraded(student) ? 'bg-green-50 text-green-700 border-green-200' : 'bg-purple-50 text-purple-700 border-purple-200 animate-pulse'">
                                            {{ isOpenQuestionGraded(student) ? 'Q. Notées' : 'À corriger' }}
                                        </span>

                                        <!-- Note de Base -->
                                        <div class="relative w-28 group-focus-within:scale-105 transition-transform duration-300">
                                            <input 
                                                v-model="student.score" 
                                                type="number" 
                                                step="any" 
                                                min="0" 
                                                :max="20"
                                                :disabled="isSecretaire || (selectedExamForGrades?.type === 'paper' && student.is_graded && !isDirecteur)"
                                                class="w-full bg-gray-50 border-2 border-transparent focus:border-green-600 rounded-2xl font-black text-center px-3 py-3 text-xs transition-all focus:bg-white focus:ring-0 outline-none text-gray-700 shadow-inner disabled:opacity-60 disabled:cursor-not-allowed"
                                                placeholder="0.00"
                                                :title="(selectedExamForGrades?.type === 'paper' && student.is_graded && !isDirecteur) ? 'Note déjà saisie. Seul le directeur peut la modifier.' : 'Saisir la note'"
                                            >
                                            <div class="absolute -top-2.5 -right-1">
                                                <span class="px-1.5 py-0.5 bg-gray-900 text-white text-[7px] font-black rounded-lg shadow-lg">Base /20</span>
                                            </div>
                                        </div>

                                        <!-- Bonus / Plus -->
                                        <div class="relative w-28 group-focus-within:scale-105 transition-transform duration-300">
                                            <input 
                                                v-model="student.bonus" 
                                                type="number" 
                                                step="any" 
                                                min="0" 
                                                :disabled="selectedExamForGrades?.type === 'paper' && student.is_graded && !isDirecteur"
                                                class="w-full bg-green-50/30 border-2 border-dashed border-green-200 focus:border-green-600 focus:border-solid focus:bg-white rounded-2xl font-black text-center px-3 py-3 text-xs transition-all focus:ring-0 outline-none text-green-700 shadow-sm disabled:opacity-60 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:border-gray-200"
                                                placeholder="0.00"
                                                :title="selectedExamForGrades?.type === 'paper' && student.is_graded && !isDirecteur ? 'Note déjà saisie. Seul le directeur peut la modifier.' : 'Saisir un bonus'"
                                            >
                                            <div class="absolute -top-2.5 -right-1">
                                                <span class="px-1.5 py-0.5 bg-green-600 text-white text-[7px] font-black rounded-lg shadow-lg">Bonus (+)</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Reset / Unlock for completed exams if needed -->
                                        <button v-if="student.status === 'completed' && !isExamEnded(selectedExamForGrades)" type="button" @click="unlockExam(student.user_id)" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors" title="Réinitialiser la tentative de l'étudiant">
                                            <ArrowPathIcon class="h-5 w-5" />
                                        </button>
                                    </template>
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
                            {{ gradeForm.processing ? 'Enregistrement...' : 'Valider et Publier les notes' }}
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
                    <div :class="!canModifyExam(selectedExamForQuestion) ? 'lg:col-span-5' : 'lg:col-span-2'" class="p-8 space-y-6 overflow-y-auto custom-scrollbar bg-gray-50/20">
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
                                                v-if="canModifyExam(selectedExamForQuestion)"
                                                type="number"
                                                v-model.number="q.points"
                                                @change="updateQuestionPoints(q)"
                                                min="0"
                                                step="0.5"
                                                class="w-8 text-center bg-transparent border-0 font-black text-[10px] p-0 focus:ring-0 text-gray-900"
                                            />
                                            <span v-else class="w-8 text-center bg-transparent border-0 font-black text-[10px] p-0 text-gray-900">{{ q.points }}</span>
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
                                <div class="flex flex-col gap-1">
                                    <button v-if="canModifyExam(selectedExamForQuestion)" @click="openQuestionModal(selectedExamForQuestion, q)" class="opacity-0 group-hover:opacity-100 p-2 text-gray-300 hover:text-blue-500 hover:bg-blue-50 rounded-xl transition-all duration-300" title="Modifier la question">
                                        <PencilSquareIcon class="h-5 w-5" />
                                    </button>
                                    <button v-if="canModifyExam(selectedExamForQuestion)" @click="deleteQuestion(q.id)" class="opacity-0 group-hover:opacity-100 p-2 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all duration-300" title="Supprimer la question">
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
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
                    <div v-if="canModifyExam(selectedExamForQuestion)" class="lg:col-span-3 p-10 space-y-8 overflow-y-auto custom-scrollbar bg-white">
                        <div>
                            <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] flex items-center gap-2 mb-6">
                                <PlusIcon v-if="!editingQuestion" class="h-4 w-4" />
                                <PencilSquareIcon v-else class="h-4 w-4" />
                                {{ editingQuestion ? 'Modifier la question' : 'Nouvelle Question' }}
                            </h4>
                            
                            <div v-if="Object.keys(questionForm.errors).length > 0" class="p-4 bg-red-50 border border-red-100 rounded-2xl text-red-600 mb-6 space-y-1">
                                <p class="text-[10px] font-black uppercase tracking-widest flex items-center gap-2 mb-1 text-red-700">
                                    <XMarkIcon class="h-4 w-4" /> Erreurs de validation
                                </p>
                                <ul class="list-disc pl-4 text-xs font-bold space-y-0.5">
                                    <li v-for="(err, key) in questionForm.errors" :key="key">{{ err }}</li>
                                </ul>
                            </div>

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
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-blue-600 transition-colors pointer-events-none">
                                                <ClipboardDocumentCheckIcon class="h-5 w-5" />
                                            </span>
                                            <input v-model="questionForm.points" type="number" step="0.5" class="w-full bg-gray-50 border-2 border-transparent focus:border-blue-600 rounded-2xl font-black pl-12 pr-12 py-4 text-center text-gray-700 outline-none transition-all shadow-inner focus:bg-white">
                                            <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none">
                                                <span class="text-[10px] font-black text-gray-400">PTS</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Énoncé de la question</label>
                                    <div class="relative group">
                                        <span class="absolute top-4 left-0 flex items-start pl-4 text-gray-400 group-focus-within:text-blue-600 transition-colors pointer-events-none">
                                            <DocumentTextIcon class="h-5 w-5" />
                                        </span>
                                        <textarea v-model="questionForm.enonce" rows="3" required placeholder="Tapez ici votre question..." class="w-full pl-12 pr-4 py-4 bg-gray-50 border-2 border-transparent focus:border-blue-600 rounded-[2rem] font-bold text-gray-700 focus:ring-0 outline-none transition-all shadow-inner focus:bg-white"></textarea>
                                    </div>
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
                                        <div v-for="(opt, idx) in questionForm.options" :key="idx" class="flex items-center gap-3 p-3 bg-gray-50/50 rounded-2xl border-2 border-transparent transition-all focus-within:border-blue-600 focus-within:bg-white shadow-sm hover:border-blue-100 group">
                                            <input type="checkbox" v-model="opt.is_correct" class="h-5 w-5 rounded-lg text-green-500 focus:ring-green-500 border-gray-300 cursor-pointer shadow-sm transition-all hover:scale-110" title="Marquer comme correcte">
                                            <input v-model="opt.texte" placeholder="Saisissez l'option..." class="flex-1 bg-transparent border-0 font-bold text-sm px-2 focus:ring-0 text-gray-700 p-0 outline-none">
                                            <button v-if="questionForm.options.length > 2" type="button" @click="removeOption(idx)" class="opacity-0 group-hover:opacity-100 p-2 text-gray-300 hover:text-red-500 transition-all hover:bg-red-50 rounded-xl">
                                                <XMarkIcon class="h-5 w-5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Expected Answer for Open Question -->
                                <div v-if="questionForm.type === 'open'" class="space-y-3 pt-4 border-t border-gray-50 animate-in slide-in-from-bottom-5 duration-500">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="p-1.5 bg-blue-50 text-blue-500 rounded-lg">
                                            <DocumentTextIcon class="h-4 w-4" />
                                        </span>
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Corrigé / Réponse attendue (Optionnel)</label>
                                    </div>
                                    <div class="relative group">
                                        <textarea v-model="questionForm.expected_answer" rows="3" placeholder="Saisissez les éléments de réponse attendus..." class="w-full px-6 py-4 bg-gray-50 border-2 border-transparent focus:border-blue-600 rounded-[2rem] font-bold text-gray-700 focus:ring-0 outline-none transition-all shadow-inner focus:bg-white"></textarea>
                                    </div>
                                    <p class="text-[9px] font-bold text-gray-400 pl-2">Sert de référence lors de la correction.</p>
                                </div>

                                <div v-if="isQuestionLimitExceeded" class="p-4 bg-red-50 border border-red-100 rounded-2xl flex items-center gap-3 text-red-600 animate-pulse">
                                    <XMarkIcon class="h-5 w-5 shrink-0" />
                                    <p class="text-[9px] font-black uppercase tracking-widest">Le cumul avec cette question ({{ currentExamQuestionsTotalPoints + parseFloat(questionForm.points) }}) dépasse le total autorisé.</p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <button 
                                        type="button" 
                                        v-if="editingQuestion"
                                        @click="openQuestionModal(selectedExamForQuestion, null)" 
                                        class="px-6 py-5 bg-gray-100 text-gray-500 rounded-[2rem] font-black text-xs uppercase tracking-[0.3em] hover:bg-gray-200 active:scale-[0.98] transition-all flex items-center justify-center gap-2 shadow-sm"
                                    >
                                        <XMarkIcon class="h-4 w-4" />
                                        Annuler
                                    </button>
                                    <button 
                                        type="submit" 
                                        :disabled="questionForm.processing || isQuestionLimitExceeded"
                                        class="flex-1 py-5 bg-blue-600 text-white rounded-[2rem] font-black text-xs uppercase tracking-[0.3em] hover:bg-blue-700 active:scale-[0.98] transition-all shadow-xl shadow-blue-100 flex items-center justify-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <CheckCircleIcon class="h-5 w-5" />
                                        {{ questionForm.processing ? 'Enregistrement...' : (editingQuestion ? 'Mettre à jour' : 'Valider la question') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Answers / Copy Modal -->
        <div v-if="isAnswersModalOpen && selectedStudentForAnswers" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm transition-all duration-300">
            <div class="bg-white w-full max-w-4xl rounded-[3rem] overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.2)] border border-gray-100 flex flex-col max-h-[90vh]">
                <!-- Header -->
                <div class="p-8 border-b border-gray-100 flex items-center justify-between bg-white relative z-10 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="h-14 w-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center shadow-inner">
                            <EyeIcon class="h-8 w-8" />
                        </div>
                        <div>
                            <h3 class="text-2xl font-black text-gray-900 tracking-tight">Copie de {{ selectedStudentForAnswers.name }}</h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="px-2 py-0.5 bg-blue-50 text-blue-600 border border-blue-100 text-[10px] font-black uppercase rounded tracking-wider">
                                    {{ selectedExamForGrades?.titre }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <button @click="isAnswersModalOpen = false" class="p-3 hover:bg-gray-100 rounded-2xl transition-all duration-300 transform hover:rotate-90">
                        <XMarkIcon class="h-6 w-6 text-gray-400" />
                    </button>
                </div>
                
                <div class="flex-1 overflow-y-auto custom-scrollbar p-8 bg-gray-50/30">
                    <div class="space-y-6">
                        <div v-for="(question, qIndex) in selectedExamForGrades?.questions" :key="question.id" class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                            <div class="flex items-start justify-between gap-4 mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="px-2.5 py-1 bg-gray-100 text-gray-600 text-[9px] font-black uppercase tracking-widest rounded-lg">
                                            Question {{ qIndex + 1 }}
                                        </span>
                                        <span class="px-2.5 py-1 text-[9px] font-black uppercase tracking-widest rounded-lg border" :class="question.type === 'qcm' ? 'bg-indigo-50 text-indigo-600 border-indigo-100' : 'bg-purple-50 text-purple-600 border-purple-100'">
                                            {{ question.type === 'qcm' ? 'QCM' : 'Question Ouverte' }}
                                        </span>
                                        <span class="text-[10px] font-bold text-gray-400 ml-auto">{{ question.points }} pts</span>
                                    </div>
                                    <p class="text-base font-bold text-gray-800">{{ question.enonce }}</p>
                                </div>
                            </div>

                            <div v-if="question.type === 'qcm'" class="mt-4 pt-4 border-t border-gray-50">
                                <div class="space-y-2">
                                    <div v-for="opt in question.options" :key="opt.id" class="flex items-center justify-between p-3 rounded-xl border text-sm" :class="[
                                        isOptionSelected(selectedStudentForAnswers.answers, question.id, opt.id) ? (opt.is_correct ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800') : (opt.is_correct ? 'bg-green-50/30 border-green-100 text-green-700 opacity-60' : 'bg-gray-50 border-gray-100 text-gray-500 opacity-60')
                                    ]">
                                        <div class="flex items-center gap-3">
                                            <div class="h-4 w-4 rounded-md border flex items-center justify-center" :class="isOptionSelected(selectedStudentForAnswers.answers, question.id, opt.id) ? (opt.is_correct ? 'border-green-500 bg-green-500' : 'border-red-500 bg-red-500') : 'border-gray-300'">
                                                <div v-if="isOptionSelected(selectedStudentForAnswers.answers, question.id, opt.id)" class="h-2 w-2 bg-white rounded-sm"></div>
                                            </div>
                                            <span class="font-bold">{{ opt.texte }}</span>
                                        </div>
                                        <div v-if="opt.is_correct" class="flex items-center gap-1 text-green-600 text-[10px] font-black uppercase tracking-widest">
                                            <CheckCircleIcon class="h-4 w-4" />
                                            <span>Correcte</span>
                                        </div>
                                        <div v-if="isOptionSelected(selectedStudentForAnswers.answers, question.id, opt.id) && !opt.is_correct" class="flex items-center gap-1 text-red-600 text-[10px] font-black uppercase tracking-widest">
                                            <XCircleIcon class="h-4 w-4" />
                                            <span>Choix apprenant</span>
                                        </div>
                                    </div>
                                    <div v-if="!selectedStudentForAnswers.answers || !selectedStudentForAnswers.answers[question.id] || (Array.isArray(selectedStudentForAnswers.answers[question.id]) && selectedStudentForAnswers.answers[question.id].length === 0)" class="text-xs text-red-500 font-bold italic mt-2 flex items-center gap-1">
                                        <XCircleIcon class="h-4 w-4" />
                                        Aucune réponse fournie.
                                    </div>
                                </div>
                            </div>
                            
                            <div v-else class="mt-4 pt-4 border-t border-gray-50 grid gap-4">
                                <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                                    <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-2 flex items-center gap-1.5">
                                        <DocumentTextIcon class="h-4 w-4" /> Réponse de l'apprenant
                                    </p>
                                    <p v-if="selectedStudentForAnswers.answers && selectedStudentForAnswers.answers[question.id]" class="text-sm font-medium text-gray-800 whitespace-pre-wrap">{{ selectedStudentForAnswers.answers[question.id] }}</p>
                                    <p v-else class="text-sm text-red-500 font-bold italic flex items-center gap-1">
                                        <XCircleIcon class="h-4 w-4" />
                                        Aucune réponse fournie.
                                    </p>
                                </div>
                                <div v-if="question.expected_answer" class="bg-blue-50/50 p-4 rounded-2xl border border-blue-100/50">
                                    <p class="text-[10px] text-blue-500 font-black uppercase tracking-widest mb-2 flex items-center gap-1.5">
                                        <ClipboardDocumentCheckIcon class="h-4 w-4" /> Corrigé attendu
                                    </p>
                                    <p class="text-sm font-medium text-blue-900 whitespace-pre-wrap">{{ question.expected_answer }}</p>
                                </div>

                                <!-- Input pour attribuer une note à la question ouverte -->
                                <div v-if="!isSecretaire && canManageExam(selectedExamForGrades)" class="bg-purple-50/60 p-4 rounded-2xl border border-purple-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                    <div>
                                        <label class="text-xs font-black text-purple-900 uppercase tracking-wider block mb-1">
                                            Note attribuée à cette question
                                        </label>
                                        <p class="text-[11px] text-purple-600 font-medium">Saisissez les points attribués sur {{ question.points }} pts max.</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="number"
                                            v-model.number="openQuestionForm.open_question_scores[question.id]"
                                            step="any"
                                            min="0"
                                            :max="question.points"
                                            class="w-24 px-3 py-2 bg-white border border-purple-200 rounded-xl font-black text-purple-900 text-center text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none"
                                            placeholder="0"
                                        />
                                        <span class="text-xs font-bold text-purple-700">/ {{ question.points }} pts</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="!selectedExamForGrades?.questions || selectedExamForGrades.questions.length === 0" class="text-center py-10 text-gray-400 font-bold">
                            Aucune question trouvée.
                        </div>
                    </div>
                </div>

                <!-- Footer / Action bar -->
                <div class="p-6 bg-white border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 relative z-10 shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-black">
                            <CheckCircleIcon class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Note Globale Calculée</p>
                            <p class="text-xl font-black text-purple-900">{{ calculatedLiveScore }} <span class="text-xs font-bold text-gray-400">/ 20</span></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <button @click="closeAnswersModalWithScore" type="button" class="flex-1 sm:flex-none px-6 py-3 bg-gray-100 text-gray-600 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-gray-200 transition-all">
                            Fermer
                        </button>
                        <button 
                            v-if="!isSecretaire && canManageExam(selectedExamForGrades) && hasOpenQuestions"
                            @click="submitOpenQuestionGrades" 
                            :disabled="openQuestionForm.processing"
                            type="button" 
                            class="flex-1 sm:flex-none px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-purple-100 transition-all flex items-center justify-center gap-2 disabled:opacity-50"
                        >
                            <CheckCircleIcon class="h-4 w-4" />
                            {{ openQuestionForm.processing ? 'Enregistrement...' : 'Enregistrer la correction' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Score Summary Before Close Overlay -->
        <div v-if="showScoreBeforeClose && selectedStudentForAnswers" class="fixed inset-0 z-[70] flex items-center justify-center bg-gray-900/70 backdrop-blur-sm p-4">
            <div class="bg-white w-full max-w-sm rounded-[3rem] shadow-2xl p-10 text-center border border-gray-100">
                <div class="w-20 h-20 mx-auto mb-6 rounded-3xl flex items-center justify-center"
                    :class="calculatedLiveScore >= 10 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'">
                    <CheckCircleIcon class="h-10 w-10" />
                </div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Note finale de</p>
                <h3 class="text-xl font-black text-gray-900 mb-6 tracking-tight">{{ selectedStudentForAnswers.name }}</h3>
                <div class="py-8 px-6 rounded-3xl mb-6"
                    :class="calculatedLiveScore >= 10 ? 'bg-emerald-50 border border-emerald-100' : 'bg-red-50 border border-red-100'">
                    <div class="flex items-end justify-center gap-1">
                        <span class="text-7xl font-black tracking-tighter leading-none"
                            :class="calculatedLiveScore >= 10 ? 'text-emerald-600' : 'text-red-500'">
                            {{ calculatedLiveScore }}
                        </span>
                        <span class="text-2xl font-bold text-gray-400 mb-2">/20</span>
                    </div>
                    <p class="mt-3 text-sm font-bold"
                        :class="calculatedLiveScore >= 10 ? 'text-emerald-700' : 'text-red-600'">
                        {{ calculatedLiveScore >= 16 ? 'Excellent !' : calculatedLiveScore >= 14 ? 'Très bien' : calculatedLiveScore >= 12 ? 'Bien' : calculatedLiveScore >= 10 ? 'Passable' : 'Insuffisant' }}
                    </p>
                </div>
                <p class="text-xs text-gray-400 font-medium mb-6">
                    Examen : <strong class="text-gray-700">{{ selectedExamForGrades?.titre }}</strong>
                </p>
                <div class="flex gap-3">
                    <button @click="showScoreBeforeClose = false" type="button"
                        class="flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-gray-200 transition-all">
                        Retour
                    </button>
                    <button @click="confirmCloseAnswersModal" type="button"
                        class="flex-1 py-4 bg-gray-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-gray-700 transition-all">
                        Fermer
                    </button>
                </div>
            </div>
        </div>

        <!-- Unlock / Reset Confirmation Modal -->
        <div v-if="showUnlockConfirm && pendingUnlockStudent" class="fixed inset-0 z-[80] flex items-center justify-center bg-gray-900/70 backdrop-blur-sm p-4">
            <div class="bg-white w-full max-w-md rounded-[3rem] shadow-2xl p-10 text-center border border-gray-100">

                <!-- Header -->
                <div class="w-20 h-20 mx-auto mb-5 bg-amber-50 text-amber-500 rounded-3xl flex items-center justify-center">
                    <ExclamationTriangleIcon class="h-10 w-10" />
                </div>
                <h3 class="text-xl font-black text-gray-900 mb-1 tracking-tight">Gérer la tentative</h3>
                <p class="text-sm text-gray-500 font-medium leading-relaxed mb-1">Que souhaitez-vous faire pour</p>
                <p class="text-base font-black text-gray-900 mb-8">{{ pendingUnlockStudent.name }} ?</p>

                <!-- Two action cards -->
                <div class="grid grid-cols-2 gap-4 mb-8">
                    <!-- Option 1: Débloquer (resume) -->
                    <button
                        @click="confirmUnblockOnly"
                        type="button"
                        class="group flex flex-col items-center gap-3 p-5 rounded-3xl border-2 border-emerald-200 bg-emerald-50 hover:bg-emerald-100 hover:border-emerald-400 transition-all text-left"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-emerald-100 group-hover:bg-emerald-200 text-emerald-600 flex items-center justify-center transition-all">
                            <CheckCircleIcon class="h-7 w-7" />
                        </div>
                        <div>
                            <p class="font-black text-emerald-800 text-xs uppercase tracking-widest mb-1">Débloquer</p>
                            <p class="text-[10px] text-emerald-600 leading-tight">Il reprend là où il s'était arrêté, ses réponses sont conservées.</p>
                        </div>
                    </button>

                    <!-- Option 2: Recommencer (reset) -->
                    <button
                        @click="confirmUnlock"
                        type="button"
                        class="group flex flex-col items-center gap-3 p-5 rounded-3xl border-2 border-red-200 bg-red-50 hover:bg-red-100 hover:border-red-400 transition-all text-left"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-red-100 group-hover:bg-red-200 text-red-500 flex items-center justify-center transition-all">
                            <ArrowPathIcon class="h-7 w-7" />
                        </div>
                        <div>
                            <p class="font-black text-red-700 text-xs uppercase tracking-widest mb-1">Recommencer</p>
                            <p class="text-[10px] text-red-500 leading-tight">Sa tentative est supprimée. Il repart à zéro.</p>
                        </div>
                    </button>
                </div>

                <!-- Cancel -->
                <button
                    @click="showUnlockConfirm = false; pendingUnlockStudent = null"
                    type="button"
                    class="w-full py-3.5 bg-gray-100 text-gray-600 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-gray-200 transition-all"
                >
                    Annuler
                </button>
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
