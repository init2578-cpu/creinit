<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { 
    CheckBadgeIcon, 
    BookOpenIcon, 
    ClockIcon,
    ExclamationCircleIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    group: Object,
    progress: Object // Keyed by chapter_id
})

const form = useForm({
    group_id: props.group.id,
    chapter_ids: []
})

function toggleChapter(chapterId) {
    const index = form.chapter_ids.indexOf(chapterId)
    if (index > -1) {
        form.chapter_ids.splice(index, 1)
    } else {
        form.chapter_ids.push(chapterId)
    }
}

function submitProgression() {
    form.post(route('chapter-progress.submit'), {
        preserveScroll: true,
        onSuccess: () => {
            form.chapter_ids = []
        }
    })
}

function cancelProgression(progressId) {
    if (confirm('Voulez-vous vraiment annuler la progression de ce chapitre ?')) {
        router.delete(route('chapter-progress.cancel', progressId), {
            preserveScroll: true
        })
    }
}
</script>

<template>
    <Head :title="'Suivi - ' + group.nom_groupe" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto py-8 px-4">
            <header class="mb-8 flex items-end justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ group.nom_groupe }}</h1>
                    <p class="text-gray-500">Gérer l'avancement pédagogique du module : {{ group.module.nom_module }}</p>
                </div>
                <div class="bg-blue-50 px-4 py-2 rounded-xl border border-blue-100 flex items-center gap-3">
                    <div class="text-right">
                        <p class="text-[10px] uppercase font-bold text-blue-400 leading-none">Formateur</p>
                        <p class="text-sm font-bold text-blue-700">{{ group.formateur.name }}</p>
                    </div>
                </div>
            </header>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-50 bg-gray-50/50">
                    <h2 class="font-bold text-gray-800 flex items-center gap-2">
                        <BookOpenIcon class="h-5 w-5 text-blue-500" />
                        Programme du Module
                    </h2>
                </div>

                <div class="divide-y divide-gray-50">
                    <div 
                        v-for="chapter in group.module.chapters" 
                        :key="chapter.id"
                        class="p-6 flex items-center justify-between group hover:bg-gray-50 transition"
                    >
                        <div class="flex items-center gap-4">
                            <div 
                                v-if="!progress[chapter.id] || progress[chapter.id].status === 'rejected'"
                                @click="toggleChapter(chapter.id)"
                                class="h-6 w-6 rounded-lg border-2 cursor-pointer flex items-center justify-center transition"
                                :class="[
                                    form.chapter_ids.includes(chapter.id) ? 'bg-blue-600 border-blue-600' : 'border-gray-200',
                                    progress[chapter.id]?.status === 'rejected' ? 'border-red-500 bg-red-50' : ''
                                ]"
                            >
                                <CheckBadgeIcon v-if="form.chapter_ids.includes(chapter.id)" class="h-4 w-4 text-white" />
                            </div>
                            
                            <div v-else-if="progress[chapter.id].status !== 'rejected'" class="h-6 w-6 rounded-lg flex items-center justify-center" :class="progress[chapter.id].status === 'approved' ? 'bg-green-100 text-green-600' : 'bg-yellow-100 text-yellow-600'">
                                <CheckBadgeIcon v-if="progress[chapter.id].status === 'approved'" class="h-4 w-4" />
                                <ClockIcon v-else class="h-4 w-4" />
                            </div>

                            <div>
                                <p class="font-bold text-gray-900 leading-tight">{{ chapter.titre }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">Chapitre #{{ chapter.ordre }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span v-if="progress[chapter.id]" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border" 
                                :class="[
                                    progress[chapter.id].status === 'approved' ? 'bg-green-50 text-green-700 border-green-100' : '',
                                    progress[chapter.id].status === 'pending' ? 'bg-yellow-50 text-yellow-700 border-yellow-100' : '',
                                    progress[chapter.id].status === 'rejected' ? 'bg-red-50 text-red-700 border-red-100' : ''
                                ]"
                            >
                                <template v-if="progress[chapter.id].status === 'approved'">Validé</template>
                                <template v-else-if="progress[chapter.id].status === 'pending'">En attente</template>
                                <template v-else-if="progress[chapter.id].status === 'rejected'">Rejeté</template>
                            </span>
                            <span v-else class="text-xs text-gray-300 italic group-hover:text-gray-400 transition">Non entamé</span>

                            <!-- Revoke validation -->
                            <button 
                                v-if="progress[chapter.id]"
                                type="button"
                                @click="cancelProgression(progress[chapter.id].id)"
                                class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-all duration-250"
                                title="Annuler la validation"
                            >
                                <XMarkIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="form.chapter_ids.length > 0" class="p-6 bg-blue-600 flex items-center justify-between">
                    <p class="text-white font-bold">
                        {{ form.chapter_ids.length }} chapitre(s) sélectionné(s)
                    </p>
                    <button 
                        @click="submitProgression"
                        :disabled="form.processing"
                        class="px-6 py-2.5 bg-white text-blue-600 rounded-xl font-black text-sm hover:shadow-lg transition uppercase tracking-wider"
                    >
                        Soumettre pour validation
                    </button>
                </div>
            </div>

            <div v-if="group.module.chapters.length === 0" class="p-12 text-center">
                <ExclamationCircleIcon class="h-12 w-12 text-gray-300 mx-auto mb-4" />
                <p class="text-gray-500">Aucun chapitre n'est défini pour ce module.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
