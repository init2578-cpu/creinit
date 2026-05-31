<script setup>
import { XMarkIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    show: Boolean,
    attachment: Object
})

const emit = defineEmits(['close'])

const isImage = (mimeType) => mimeType?.startsWith('image/')
const isVideo = (mimeType) => mimeType?.startsWith('video/')
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] overflow-hidden bg-black/90 backdrop-blur-sm flex items-center justify-center p-4 sm:p-8" @click.self="emit('close')">
        <!-- Close Button -->
        <button @click="emit('close')" class="absolute top-6 right-6 p-3 bg-white/10 hover:bg-white/20 text-white rounded-full transition-all z-10">
            <XMarkIcon class="h-8 w-8" />
        </button>

        <!-- Media Display -->
        <div class="relative max-w-7xl max-h-full flex flex-col items-center">
            <div v-if="attachment" class="rounded-2xl overflow-hidden shadow-2xl bg-black">
                <img v-if="isImage(attachment.mime_type)" :src="'/storage/' + attachment.path" class="max-w-full max-h-[85vh] object-contain" />
                
                <video v-else-if="isVideo(attachment.mime_type)" controls autoplay class="max-w-full max-h-[85vh]">
                    <source :src="'/storage/' + attachment.path" :type="attachment.mime_type">
                    Votre navigateur ne supporte pas la lecture de vidéos.
                </video>
            </div>

            <!-- metadata & actions -->
            <div v-if="attachment" class="mt-4 flex flex-col items-center text-center">
                <h3 class="text-white font-bold text-lg leading-tight">{{ attachment.name }}</h3>
                <div class="flex items-center gap-4 mt-2">
                    <a :href="'/storage/' + attachment.path" download class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl text-sm font-bold transition-all">
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        Télécharger
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
