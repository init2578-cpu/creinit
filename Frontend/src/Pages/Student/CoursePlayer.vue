<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { 
    computed,
    ref, 
    onMounted 
} from 'vue'
import { 
    ChevronLeftIcon, 
    ChevronRightIcon,
    Bars3CenterLeftIcon,
    PaperClipIcon,
    DocumentIcon,
    CheckCircleIcon,
    CloudArrowUpIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    module: Object,
    currentChapter: Object,
    allChapters: Array
})

const formattedVideoUrl = computed(() => {
    if (!props.currentChapter.video_url) return null;
    
    const url = props.currentChapter.video_url;
    // Regular YouTube URL
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
    const match = url.match(regExp);

    if (match && match[2].length === 11) {
        return `https://www.youtube-nocookie.com/embed/${match[2]}`;
    }

    return url; // Return as is if not YouTube or already embed
})

const isSidebarOpen = ref(true)

const exerciseForm = useForm({
    file: null,
    student_comment: ''
})

const submitExercise = () => {
    exerciseForm.post(route('student.exercises.submit', props.currentChapter.id), {
        onSuccess: () => {
            exerciseForm.reset()
        }
    })
}
</script>

<template>
    <Head :title="currentChapter.titre" />

    <AuthenticatedLayout no-padding>
        <div class="flex h-full overflow-hidden bg-white">
            <!-- Sidebar Navigation -->
            <transition name="slide">
                <aside v-if="isSidebarOpen" class="w-80 border-r border-gray-100 flex flex-col bg-gray-50/50 backdrop-blur-sm">
                    <div class="p-6 border-b border-gray-100 bg-white">
                        <Link :href="route('student.courses')" class="flex items-center gap-2 text-blue-600 font-black text-xs uppercase tracking-widest mb-4 hover:gap-3 transition-all">
                            <ChevronLeftIcon class="h-4 w-4" />
                            Retour aux modules
                        </Link>
                        <h2 class="text-xl font-black text-gray-900 leading-tight">{{ module.titre }}</h2>
                    </div>
                    
                    <nav class="flex-1 overflow-y-auto p-4 space-y-2">
                        <Link 
                            v-for="chapter in allChapters" 
                            :key="chapter.id"
                            :href="route('student.courses.show', [module.id, chapter.id])"
                            class="flex items-center gap-3 p-4 rounded-2xl transition-all duration-300"
                            :class="chapter.id === currentChapter.id 
                                ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' 
                                : 'text-gray-600 hover:bg-white hover:shadow-sm'"
                        >
                            <span class="flex-shrink-0 h-8 w-8 rounded-full flex items-center justify-center border text-xs font-black"
                                :class="chapter.id === currentChapter.id ? 'border-white/30 bg-white/20' : 'border-gray-200 bg-gray-50'"
                            >
                                {{ chapter.ordre }}
                            </span>
                            <span class="text-sm font-bold flex-1 truncate">{{ chapter.titre }}</span>
                        </Link>
                    </nav>
                </aside>
            </transition>

            <!-- Main Player Area -->
            <main class="flex-1 flex flex-col overflow-y-auto relative">
                <!-- Toggle Sidebar Button (Floating) -->
                <button 
                    @click="isSidebarOpen = !isSidebarOpen"
                    class="absolute left-6 top-6 z-20 p-3 bg-white border border-gray-100 rounded-2xl shadow-xl hover:scale-110 transition-all text-gray-500"
                >
                    <Bars3CenterLeftIcon class="h-6 w-6" />
                </button>

                <!-- Title Header Section -->
                <div class="h-64 bg-gradient-to-br from-blue-600 to-indigo-700 w-full flex items-center justify-center text-white relative overflow-hidden flex-shrink-0">
                    <div class="relative z-10 text-center">
                        <span class="text-xs font-black uppercase tracking-[0.3em] opacity-60 mb-2 block">Chapitre {{ currentChapter.ordre }}</span>
                        <h1 class="text-4xl md:text-5xl font-black tracking-tighter px-4 text-center">{{ currentChapter.titre }}</h1>
                    </div>
                    <!-- Decorative Background -->
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full blur-[100px] -translate-x-1/2 -translate-y-1/2"></div>
                        <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full blur-[100px] translate-x-1/2 translate-y-1/2"></div>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="max-w-4xl mx-auto w-full p-8 md:p-16 space-y-12">
                    <!-- Video player BEFORE text content -->
                    <div v-if="formattedVideoUrl && (currentChapter.video_position === 'before' || !currentChapter.video_position)" class="aspect-video bg-gray-900 w-full shadow-2xl rounded-[2.5rem] overflow-hidden flex-shrink-0">
                        <iframe 
                            :src="formattedVideoUrl" 
                            class="w-full h-full" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen
                        ></iframe>
                    </div>

                    <!-- Lesson Body -->
                    <article class="prose prose-blue prose-xl max-w-none prose-headings:font-black prose-headings:tracking-tight prose-p:text-gray-600 prose-p:leading-relaxed">
                        <div v-html="currentChapter.content || '<p class=\'text-gray-400 italic\'>Aucun contenu textuel pour ce chapitre.</p>'"></div>
                    </article>

                    <!-- Video player AFTER text content -->
                    <div v-if="formattedVideoUrl && currentChapter.video_position === 'after'" class="aspect-video bg-gray-900 w-full shadow-2xl rounded-[2.5rem] overflow-hidden flex-shrink-0">
                        <iframe 
                            :src="formattedVideoUrl" 
                            class="w-full h-full" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen
                        ></iframe>
                    </div>

                    <!-- Resources Section -->
                    <section v-if="currentChapter.attachments && currentChapter.attachments.length > 0" class="border-t border-gray-100 pt-12">
                        <h3 class="text-xl font-black text-gray-900 mb-6 flex items-center gap-3">
                            <PaperClipIcon class="h-6 w-6 text-blue-600" />
                            Ressources & Supports
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <a 
                                v-for="(file, idx) in currentChapter.attachments" 
                                :key="idx"
                                :href="route('modules.chapters.attachments.download', [currentChapter.id, idx])"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center gap-4 p-4 bg-gray-50 rounded-2xl border border-transparent hover:border-blue-200 hover:bg-blue-50 transition-all group"
                            >
                                <div class="h-12 w-12 bg-white rounded-xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                                    <DocumentIcon class="h-6 w-6 text-blue-600" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-black text-gray-900 truncate">{{ file.name }}</p>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest text-blue-600 cursor-pointer">Télécharger</p>
                                </div>
                            </a>
                        </div>
                    </section>

                    <!-- Exercise Section -->
                    <section class="bg-gray-50 rounded-[3rem] p-8 md:p-12 border border-gray-100">
                        <div class="flex items-center gap-6 mb-8">
                            <div class="h-16 w-16 bg-blue-600 text-white rounded-3xl flex items-center justify-center shadow-xl shadow-blue-200">
                                <CloudArrowUpIcon class="h-8 w-8" />
                            </div>
                            <div>
                                <h3 class="text-2xl font-black text-gray-900">Soumettre votre Exercice</h3>
                                <p class="text-gray-500 font-medium">Déposez votre travail pour évaluation par votre formateur.</p>
                            </div>
                        </div>

                        <form @submit.prevent="submitExercise" class="space-y-6">
                            <div class="relative">
                                <input 
                                    type="file" 
                                    @input="exerciseForm.file = $event.target.files[0]"
                                    class="hidden" 
                                    id="exercise-file"
                                    required
                                >
                                <label 
                                    for="exercise-file" 
                                    class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-200 rounded-3xl hover:border-blue-400 hover:bg-blue-50 transition-all cursor-pointer group"
                                >
                                    <div v-if="!exerciseForm.file" class="text-center">
                                        <DocumentIcon class="h-8 w-8 text-gray-300 mx-auto mb-2" />
                                        <span class="text-sm font-black text-gray-500">Cliquez pour choisir un fichier</span>
                                    </div>
                                    <div v-else class="flex items-center gap-3 text-blue-600">
                                        <CheckCircleIcon class="h-6 w-6" />
                                        <span class="font-black capitalize">{{ exerciseForm.file.name }}</span>
                                    </div>
                                </label>
                            </div>

                            <textarea 
                                v-model="exerciseForm.student_comment"
                                placeholder="Ajoutez un message pour votre formateur (optionnel)..."
                                class="w-full bg-white border-0 rounded-2xl focus:ring-2 focus:ring-blue-500 font-medium px-5 py-4 transition-all min-h-[100px] placeholder-gray-400"
                            ></textarea>

                            <button 
                                type="submit" 
                                :disabled="exerciseForm.processing || !exerciseForm.file"
                                class="w-full bg-gray-900 hover:bg-black text-white py-5 rounded-[1.5rem] font-black transition-all shadow-xl active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2"
                            >
                                <CloudArrowUpIcon class="h-5 w-5" />
                                {{ exerciseForm.processing ? 'Envoi en cours...' : 'Envoyer mon travail' }}
                            </button>
                        </form>
                    </section>
                </div>

                <!-- Footer Navigation -->
                <div class="border-t border-gray-100 bg-white p-8 flex items-center justify-between">
                    <button class="flex items-center gap-3 text-gray-400 font-black text-xs uppercase hover:text-gray-900 transition-all">
                        <ChevronLeftIcon class="h-4 w-4" />
                        Précédent
                    </button>
                    <button class="flex items-center gap-3 bg-blue-50 text-blue-600 px-8 py-3 rounded-2xl font-black text-xs uppercase hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                        Suivant
                        <ChevronRightIcon class="h-4 w-4" />
                    </button>
                </div>
            </main>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active {
    transition: all 0.3s ease-out;
}
.slide-enter-from, .slide-leave-to {
    transform: translateX(-100%);
    width: 0;
    opacity: 0;
}
</style>
