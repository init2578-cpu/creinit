<script setup>
import { XMarkIcon, ArrowDownTrayIcon, DocumentIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    show: Boolean,
    attachment: Object
})

const emit = defineEmits(['close'])

const isImage = (mimeType) => mimeType?.startsWith('image/')
const isVideo = (mimeType) => mimeType?.startsWith('video/')
const isPdf   = (mimeType) => mimeType === 'application/pdf'
const isPreviewable = (mimeType) => isImage(mimeType) || isVideo(mimeType) || isPdf(mimeType)
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] overflow-hidden bg-black/90 backdrop-blur-sm flex items-center justify-center p-4 sm:p-8" @click.self="emit('close')">
        <!-- Close Button -->
        <button @click="emit('close')" class="absolute top-6 right-6 p-3 bg-white/10 hover:bg-white/20 text-white rounded-full transition-all z-10">
            <XMarkIcon class="h-8 w-8" />
        </button>

        <div v-if="attachment" class="relative w-full max-w-5xl flex flex-col items-center gap-4">

            <!-- Image -->
            <div v-if="isImage(attachment.mime_type)" class="rounded-2xl overflow-hidden shadow-2xl bg-black">
                <img :src="'/storage/' + attachment.path" class="max-w-full max-h-[82vh] object-contain" />
            </div>

            <!-- Video -->
            <div v-else-if="isVideo(attachment.mime_type)" class="rounded-2xl overflow-hidden shadow-2xl bg-black w-full">
                <video controls autoplay class="w-full max-h-[82vh]">
                    <source :src="'/storage/' + attachment.path" :type="attachment.mime_type">
                    Votre navigateur ne supporte pas la lecture de vidéos.
                </video>
            </div>

            <!-- PDF inline -->
            <div v-else-if="isPdf(attachment.mime_type)" class="w-full rounded-2xl overflow-hidden shadow-2xl bg-white" style="height: 82vh;">
                <iframe
                    :src="'/storage/' + attachment.path + '#toolbar=1&navpanes=0'"
                    class="w-full h-full border-0"
                    type="application/pdf"
                />
            </div>

            <!-- Non-previewable file (Word, Excel, etc.) -->
            <div v-else class="bg-white/10 backdrop-blur-md rounded-3xl p-12 flex flex-col items-center gap-4 text-white text-center shadow-2xl">
                <div class="h-20 w-20 bg-white/10 rounded-2xl flex items-center justify-center">
                    <DocumentIcon class="h-10 w-10 text-white/70" />
                </div>
                <div>
                    <p class="font-bold text-lg">{{ attachment.name }}</p>
                    <p class="text-white/50 text-sm mt-1">Ce type de fichier ne peut pas être prévisualisé.</p>
                </div>
            </div>

            <!-- Footer: filename + download -->
            <div class="flex flex-col items-center gap-2">
                <p v-if="!isPdf(attachment.mime_type)" class="text-white/70 font-semibold text-sm">{{ attachment.name }}</p>
                <div class="flex items-center gap-3">
                    <a
                        :href="'/storage/' + attachment.path"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/15 hover:bg-white/25 text-white rounded-xl text-sm font-bold transition-all"
                    >
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        Télécharger
                    </a>
                    <a
                        v-if="!isPreviewable(attachment.mime_type)"
                        :href="'/storage/' + attachment.path"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold transition-all"
                    >
                        Ouvrir dans le navigateur
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
