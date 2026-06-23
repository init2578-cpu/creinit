<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, computed, nextTick } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import RichTextEditor from '@/Components/RichTextEditor.vue'
import draggable from 'vuedraggable'
import { 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    ChevronRightIcon,
    BookOpenIcon,
    Bars4Icon,
    VideoCameraIcon,
    TagIcon,
    EyeIcon,
    EyeSlashIcon,
    PaperClipIcon,
    DocumentIcon,
    XMarkIcon,
    ClockIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    modules: Array,
    modules_detailed: Array
})

// Module Form
const isModuleModalOpen = ref(false)
const editingModule = ref(null)
const moduleForm = useForm({
    titre: '',
    code_module: '',
    description: '',
    quota_heures: '',
    start_date: '',
    end_date: ''
})

function openModuleModal(module = null) {
    editingModule.value = module
    if (module) {
        moduleForm.titre = module.titre
        moduleForm.code_module = module.code_module
        moduleForm.description = module.description
        moduleForm.quota_heures = module.quota_heures
        moduleForm.start_date = module.start_date || ''
        moduleForm.end_date = module.end_date || ''
    } else {
        moduleForm.reset()
    }
    isModuleModalOpen.value = true
}

function submitModule() {
    if (editingModule.value) {
        moduleForm.put(route('modules.update', editingModule.value.id), {
            onSuccess: () => {
                isModuleModalOpen.value = false
                moduleForm.reset()
            }
        })
    } else {
        moduleForm.post(route('modules.store'), {
            onSuccess: () => {
                isModuleModalOpen.value = false
                moduleForm.reset()
            }
        })
    }
}

function deleteModule(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce module ?')) {
        router.delete(route('modules.destroy', id))
    }
}

// Chapter Management
const selectedModule = ref(null)
const isChapterModalOpen = ref(false)
const chapterForm = useForm({
    titre: '',
    ordre: '',
    content: '',
    video_url: '',
    is_published: false,
    attachments: [],
    new_attachments: []
})
const localChapters = ref([])

function openChapterModal(module) {
    selectedModule.value = props.modules_detailed.find(m => m.id === module.id)
    localChapters.value = [...selectedModule.value.chapters]
    isChapterModalOpen.value = true
}

const editingChapter = ref(null)

function editChapter(chapter) {
    editingChapter.value = chapter
    chapterForm.titre = chapter.titre
    chapterForm.ordre = chapter.ordre
    chapterForm.is_published = chapter.is_published
    chapterForm.content = chapter.content || ''
    chapterForm.video_url = chapter.video_url || ''
    chapterForm.attachments = chapter.attachments || []
    chapterForm.new_attachments = []
}

function cancelEditChapter() {
    editingChapter.value = null
    chapterForm.reset()
}

function submitChapter() {
    if (editingChapter.value) {
        // Changed to direct POST to ensure maximum compatibility with multi-part data
        chapterForm.post(route('modules.chapters.update', editingChapter.value.id), {
            forceFormData: true,
            onSuccess: () => {
                cancelEditChapter()
                selectedModule.value = props.modules_detailed.find(m => m.id === selectedModule.value.id)
                localChapters.value = [...selectedModule.value.chapters]
            }
        })
    } else {
        chapterForm.post(route('modules.chapters.store', selectedModule.value.id), {
            onSuccess: () => {
                chapterForm.reset()
                selectedModule.value = props.modules_detailed.find(m => m.id === selectedModule.value.id)
                localChapters.value = [...selectedModule.value.chapters]
            }
        })
    }
}

function togglePublish(chapter) {
    router.post(route('modules.chapters.update', chapter.id), {
        titre: chapter.titre,
        is_published: chapter.is_published,
        content: chapter.content || '',
        video_url: chapter.video_url || '',
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedModule.value = props.modules_detailed.find(m => m.id === selectedModule.value.id)
            localChapters.value = [...selectedModule.value.chapters]
            if (editingChapter.value && editingChapter.value.id === chapter.id) {
                chapterForm.is_published = chapter.is_published
            }
        }
    })
}

function deleteChapter(chapterId) {
    if (confirm('Supprimer ce chapitre ?')) {
        router.delete(route('modules.chapters.destroy', chapterId), {
            onSuccess: () => {
                selectedModule.value = props.modules_detailed.find(m => m.id === selectedModule.value.id)
                localChapters.value = [...selectedModule.value.chapters]
            }
        })
    }
}

function deleteAttachment(index) {
    if (confirm('Supprimer cette pièce jointe ?')) {
        router.delete(route('modules.chapters.attachments.destroy', [editingChapter.value.id, index]), {
            onSuccess: () => {
                selectedModule.value = props.modules_detailed.find(m => m.id === selectedModule.value.id)
                localChapters.value = [...selectedModule.value.chapters]
                // Refresh editingChapter to update the list
                editingChapter.value = localChapters.value.find(c => c.id === editingChapter.value.id)
                chapterForm.attachments = editingChapter.value.attachments || []
            }
        })
    }
}

const isReordering = ref(false)


function handleReorder() {
    isReordering.value = true
    
    // Use the localChapters state
    const chapters = localChapters.value.map((chapter, index) => ({
        id: chapter.id,
        ordre: index + 1
    }))
    
    router.post(route('modules.chapters.reorder', selectedModule.value.id), {
        chapters
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isReordering.value = false
            selectedModule.value = props.modules_detailed.find(m => m.id === selectedModule.value.id)
            localChapters.value = [...selectedModule.value.chapters]
        },
        onError: () => {
            isReordering.value = false
            // Reset to prop state if error
            localChapters.value = [...selectedModule.value.chapters]
        }
    })
}

</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-8 px-4">
            <header class="mb-10 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div>
                    <h1 class="text-4xl font-black text-gray-900 tracking-tight">Gestion des Formations</h1>
                    <p class="text-gray-500 mt-2 font-medium">Définissez vos modules et structurez vos programmes d'apprentissage.</p>
                </div>
                <button @click="openModuleModal()" class="flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-2xl font-black text-sm hover:bg-blue-700 transition shadow-xl shadow-blue-100 uppercase tracking-widest">
                    <PlusIcon class="h-5 w-5" />
                    Nouveau Module
                </button>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Module Card -->
                <div v-for="module in modules" :key="module.id" 
                    class="group bg-white rounded-[2.5rem] border border-gray-100 p-8 hover:shadow-2xl hover:shadow-gray-200/50 transition duration-500 relative flex flex-col h-full overflow-hidden">
                    
                    <div class="absolute top-0 right-0 p-8 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button @click="openModuleModal(module)" class="p-2 bg-gray-50 text-gray-400 hover:text-blue-600 rounded-xl transition">
                            <PencilSquareIcon class="h-5 w-5" />
                        </button>
                        <button @click="deleteModule(module.id)" class="p-2 bg-gray-50 text-gray-400 hover:text-red-600 rounded-xl transition">
                            <TrashIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="mb-6 flex items-start gap-4">
                        <div class="p-4 bg-blue-50 text-blue-600 rounded-3xl">
                            <BookOpenIcon class="h-8 w-8" />
                        </div>
                        <div>
                            <span class="text-[10px] font-black text-blue-500 uppercase tracking-widest">{{ module.code_module }}</span>
                            <h3 class="text-xl font-black text-gray-900 leading-tight mt-1">{{ module.titre }}</h3>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span v-if="module.start_date && module.end_date" class="px-2.5 py-1 bg-amber-50 text-amber-600 rounded-lg text-[9px] font-black uppercase tracking-widest border border-amber-100">
                                    Du {{ new Date(module.start_date).toLocaleDateString('fr-FR') }} au {{ new Date(module.end_date).toLocaleDateString('fr-FR') }}
                                </span>
                                <span v-else-if="module.start_date" class="px-2.5 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-[9px] font-black uppercase tracking-widest border border-emerald-100">
                                    Permanent (depuis {{ new Date(module.start_date).toLocaleDateString('fr-FR') }})
                                </span>
                                <span v-else class="px-2.5 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-[9px] font-black uppercase tracking-widest border border-emerald-100">
                                    Formation Permanente
                                </span>
                            </div>
                        </div>
                    </div>

                    <p class="text-gray-500 text-sm font-medium line-clamp-3 mb-8 flex-grow">
                        {{ module.description || "Aucune description fournie pour ce module de formation." }}
                    </p>

                    <div class="grid grid-cols-2 gap-4 pt-6 border-t border-gray-50">
                        <div class="flex items-center gap-2 text-gray-400">
                            <ClockIcon class="h-4 w-4" />
                            <span class="text-xs font-black uppercase">{{ module.quota_heures }}h Quota</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-400">
                            <Bars4Icon class="h-4 w-4" />
                            <span class="text-xs font-black uppercase">{{ module.chapters_count }} Chapitres</span>
                        </div>
                    </div>

                    <button @click="openChapterModal(module)" class="mt-8 w-full py-4 bg-gray-900 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-600 hover:shadow-xl hover:shadow-blue-200 transition-all flex items-center justify-center gap-2">
                        Gérer le contenu
                        <ChevronRightIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Module Modal -->
        <div v-if="isModuleModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm">
            <div class="bg-white w-full max-w-xl rounded-[3rem] overflow-hidden shadow-2xl">
                <div class="p-10 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-3xl font-black text-gray-900 tracking-tight">{{ editingModule ? 'Modifier le Module' : 'Nouveau Module' }}</h3>
                    <button @click="isModuleModalOpen = false" class="p-3 hover:bg-gray-100 rounded-2xl transition text-gray-400">
                        <XMarkIcon class="h-7 w-7" />
                    </button>
                </div>
                <form @submit.prevent="submitModule" class="p-10 space-y-6">
                    <div class="grid grid-cols-3 gap-6">
                        <div class="col-span-1">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Code</label>
                            <input v-model="moduleForm.code_module" type="text" required placeholder="EX: DEV-01" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-4">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Titre de la formation</label>
                            <input v-model="moduleForm.titre" type="text" required placeholder="Ex: Développement Web Fullstack" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-4">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Description</label>
                        <textarea v-model="moduleForm.description" rows="4" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-4" placeholder="Objectifs pédagogiques..."></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Quota Horaire (Heures)</label>
                            <div class="relative">
                                <input v-model="moduleForm.quota_heures" type="number" required class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-4">
                                <ClockIcon class="h-6 w-6 absolute right-5 top-1/2 -translate-y-1/2 text-gray-300" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Date d'ouverture</label>
                            <input v-model="moduleForm.start_date" type="date" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-4 text-sm text-gray-700">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Date de fermeture <span class="text-blue-400/80 font-bold">(Optionnel si permanent)</span></label>
                        <input v-model="moduleForm.end_date" type="date" class="w-full bg-gray-50 border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-bold px-5 py-4 text-sm text-gray-700">
                    </div>
                    <div class="pt-6 flex gap-4">
                        <button type="button" @click="isModuleModalOpen = false" class="flex-1 py-5 bg-gray-100 text-gray-600 rounded-[1.5rem] font-black transition">Annuler</button>
                        <button type="submit" :disabled="moduleForm.processing" class="flex-2 py-5 bg-blue-600 text-white rounded-[1.5rem] font-black shadow-xl shadow-blue-100 hover:bg-blue-700 transition disabled:opacity-50">
                            {{ editingModule ? 'Enregistrer les modifications' : 'Créer le module' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Chapter Management Modal -->
        <div v-if="isChapterModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm">
            <div class="bg-gray-50 w-full max-w-5xl h-[85vh] rounded-[3rem] overflow-hidden shadow-2xl flex flex-col border border-white/20">
                <div class="bg-white p-10 flex items-center justify-between border-b border-gray-100 flex-shrink-0">
                    <div>
                        <span class="text-[10px] font-black text-blue-500 uppercase tracking-widest">{{ selectedModule.code_module }}</span>
                        <h3 class="text-3xl font-black text-gray-900 tracking-tight">{{ selectedModule.titre }}</h3>
                        <p class="text-gray-400 font-bold text-xs uppercase mt-1 tracking-widest">Plan de cours & Chapitres</p>
                    </div>
                    <button @click="isChapterModalOpen = false" class="p-3 hover:bg-gray-100 rounded-2xl transition text-gray-400">
                        <XMarkIcon class="h-7 w-7" />
                    </button>
                </div>
                
                <div class="flex-1 flex flex-col md:flex-row gap-10 p-10 overflow-hidden min-h-0">
                    <!-- Chapter List -->
                    <div class="flex-grow-0 md:flex-none md:w-[22rem] flex flex-col min-h-0 overflow-y-auto pr-2 custom-scrollbar">
                        <div class="flex items-center justify-between mb-6 flex-shrink-0">
                            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest">Chapitres ({{ localChapters.length }})</h4>
                            <button @click="cancelEditChapter()" 
                                    type="button"
                                    class="flex items-center gap-1 px-2.5 py-1.5 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-100 transition text-[10px] font-black uppercase tracking-widest">
                                <PlusIcon class="h-3.5 w-3.5" />
                                Nouveau
                            </button>
                        </div>

                        <div v-if="selectedModule.chapters.length === 0" class="flex flex-col items-center justify-center py-20 bg-white rounded-[2rem] border-2 border-dashed border-gray-200 text-gray-400 flex-grow">
                            <Bars4Icon class="h-12 w-12 mb-4" />
                            <p class="font-black text-sm uppercase tracking-widest">Aucun chapitre</p>
                        </div>
                        
                        <draggable 
                            v-model="localChapters" 
                            item-key="id"
                            handle=".drag-handle"
                            @end="handleReorder"
                            class="space-y-4"
                        >
                            <template #item="{element, index}">
                                <div 
                                    class="bg-white p-5 rounded-2xl border flex items-center gap-4 group transition-all duration-300 cursor-pointer shadow-sm"
                                    :class="[
                                        editingChapter && editingChapter.id === element.id
                                            ? 'border-blue-500 bg-blue-50/20 ring-2 ring-blue-500/10'
                                            : 'border-gray-100 hover:border-blue-200 hover:bg-gray-50/30',
                                        {'opacity-50 pointer-events-none': isReordering}
                                    ]"
                                    @click="editChapter(element)"
                                >
                                    <!-- Drag Handle -->
                                    <div class="drag-handle cursor-grab active:cursor-grabbing p-1.5 -ml-1.5 text-gray-300 hover:text-blue-500 transition-colors" @click.stop>
                                        <Bars4Icon class="h-5 w-5" />
                                    </div>
                                    
                                    <!-- Number -->
                                    <div 
                                        class="flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center font-black text-xs transition-colors"
                                        :class="[
                                            editingChapter && editingChapter.id === element.id
                                                ? 'bg-blue-100 text-blue-700'
                                                : 'bg-gray-50 text-gray-400 group-hover:bg-blue-50 group-hover:text-blue-600'
                                        ]"
                                    >
                                        {{ index + 1 }}
                                    </div>
                                    
                                    <!-- Title -->
                                    <div class="flex-grow min-w-0">
                                        <h4 class="font-black text-gray-900 leading-tight truncate" :title="element.titre">{{ element.titre }}</h4>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="flex items-center gap-1.5 flex-shrink-0" @click.stop>
                                        <!-- Status Badge Button -->
                                        <button 
                                            type="button" 
                                            @click="element.is_published = !element.is_published; togglePublish(element)" 
                                            class="flex items-center gap-1 px-2 py-1 rounded-lg border text-[8px] font-black uppercase tracking-wider transition-all"
                                            :class="element.is_published 
                                                ? 'bg-green-50 text-green-700 border-green-100' 
                                                : 'bg-gray-50 text-gray-400 border-gray-100'"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="element.is_published ? 'bg-green-500' : 'bg-gray-400'"></span>
                                            {{ element.is_published ? 'Public' : 'Brouillon' }}
                                        </button>
                                        
                                        <!-- Delete -->
                                        <button 
                                            type="button" 
                                            @click="deleteChapter(element.id)" 
                                            class="p-1.5 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition"
                                            title="Supprimer"
                                        >
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                        
                                        <!-- Chevron -->
                                        <ChevronRightIcon 
                                            class="h-3.5 w-3.5 text-gray-300 group-hover:text-blue-500 transition-all duration-300"
                                            :class="{'text-blue-500 translate-x-0.5': editingChapter && editingChapter.id === element.id}"
                                        />
                                    </div>
                                </div>
                            </template>
                        </draggable>
                    </div>

                    <!-- Editor / Content Form -->
                    <div class="flex-1 min-w-0 flex flex-col min-h-0 overflow-y-auto pl-2 custom-scrollbar">
                        <div class="bg-white p-8 rounded-[2rem] border border-gray-100 shadow-sm">
                            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-6">
                                {{ editingChapter ? 'Modifier le chapitre' : 'Ajouter un chapitre' }}
                            </h4>
                            <form @submit.prevent="submitChapter" class="space-y-4">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Titre du chapitre</label>
                                    <input v-model="chapterForm.titre" type="text" required placeholder="Ex: Introduction au DOM" class="w-full bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 font-bold px-4 py-3 text-sm">
                                </div>
                                <div v-if="editingChapter" class="space-y-4 pt-4 border-t border-gray-50 mt-4">
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between mb-1.5">
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Contenu du cours</label>
                                            <div class="h-1.5 w-1.5 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]"></div>
                                        </div>
                                        <RichTextEditor :key="editingChapter.id" v-model="chapterForm.content" />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">URL Vidéo</label>
                                        <div class="relative">
                                            <input v-model="chapterForm.video_url" type="url" placeholder="https://youtube.com/embed/..." class="w-full bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 font-bold px-4 py-3 text-sm pr-10">
                                            <VideoCameraIcon class="h-4 w-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-300" />
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Pièces Jointes</label>
                                        
                                        <!-- List Existing -->
                                        <div v-if="chapterForm.attachments.length > 0" class="space-y-2 mb-4">
                                            <div v-for="(file, idx) in chapterForm.attachments" :key="idx" class="flex items-center justify-between p-3 bg-white border border-gray-100 rounded-xl">
                                                <div class="flex items-center gap-3 truncate">
                                                    <DocumentIcon class="h-4 w-4 text-blue-500" />
                                                    <span class="text-xs font-bold truncate">{{ file.name }}</span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <a :href="route('modules.chapters.attachments.download', [editingChapter.id, idx])" target="_blank" class="p-1.5 text-gray-300 hover:text-blue-500 transition">
                                                        <EyeIcon class="h-4 w-4" />
                                                    </a>
                                                    <button type="button" @click="deleteAttachment(idx)" class="p-1.5 text-gray-300 hover:text-red-500 transition">
                                                        <XMarkIcon class="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Upload New -->
                                        <div class="relative group">
                                            <input 
                                                type="file" 
                                                multiple 
                                                @input="chapterForm.new_attachments = $event.target.files"
                                                class="hidden" 
                                                id="chapter-attachments"
                                            >
                                            <label for="chapter-attachments" class="flex flex-col items-center justify-center w-full p-4 border-2 border-dashed border-gray-100 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition-all cursor-pointer group">
                                                <div v-if="chapterForm.new_attachments.length === 0" class="text-center">
                                                    <PaperClipIcon class="h-5 w-5 text-gray-300 mx-auto mb-1 group-hover:text-blue-500" />
                                                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Ajouter des fichiers</span>
                                                </div>
                                                <div v-else class="text-center text-blue-600">
                                                    <PaperClipIcon class="h-5 w-5 mx-auto mb-1" />
                                                    <span class="text-[9px] font-black uppercase tracking-widest">{{ chapterForm.new_attachments.length }} fichiers sélectionnés</span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <input type="checkbox" v-model="chapterForm.is_published" id="is_published" class="rounded text-blue-600 focus:ring-blue-500">
                                        <label for="is_published" class="text-[10px] font-black text-gray-400 uppercase tracking-widest cursor-pointer">Publier le chapitre</label>
                                    </div>
                                </div>
                                <div v-else class="py-12 px-6 text-center border-2 border-dashed border-gray-100 rounded-[2rem] bg-gray-50/50">
                                    <div class="h-16 w-16 bg-white rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-sm">
                                        <PencilSquareIcon class="h-8 w-8 text-blue-500" />
                                    </div>
                                    <h5 class="text-sm font-black text-gray-900 mb-2">Modifier le contenu</h5>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase leading-loose tracking-widest px-4">
                                        Sélectionnez un chapitre à gauche pour rédiger son contenu, ajouter des vidéos ou des documents.
                                    </p>
                                </div>
                                <button type="submit" :disabled="chapterForm.processing" class="w-full py-4 bg-blue-600 text-white rounded-xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition shadow-lg shadow-blue-100 disabled:opacity-50 mt-4">
                                    {{ editingChapter ? 'Enregistrer le contenu' : 'Ajouter le chapitre' }}
                                </button>
                                <button v-if="editingChapter" type="button" @click="cancelEditChapter" class="w-full py-3 bg-gray-100 text-gray-600 rounded-xl font-black text-[10px] uppercase tracking-widest transition mt-2">
                                    Annuler
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-8 border-t border-gray-100 flex justify-end flex-shrink-0">
                    <button @click="isChapterModalOpen = false" class="px-8 py-3 bg-gray-900 text-white rounded-xl font-black text-xs uppercase tracking-widest">Terminer</button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
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
