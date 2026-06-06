<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { Html5Qrcode } from 'html5-qrcode'
import { CameraIcon, PhotoIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    fps: { type: Number, default: 10 },
    qrbox: { type: Number, default: 250 },
})

const emit = defineEmits(['scan'])

const scannerId = 'qr-reader-' + Math.random().toString(36).substr(2, 9)
let html5QrCode = null
const fileInput = ref(null)
const useFallback = ref(false)
const isScanning = ref(false)

onMounted(() => {
    html5QrCode = new Html5Qrcode(scannerId)

    // Tente d'ouvrir la caméra arrière directement
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: props.fps, qrbox: props.qrbox },
        (decodedText) => {
            emit('scan', decodedText)
        },
        (errorMessage) => {
            // erreurs d'analyse ignorées
        }
    ).then(() => {
        isScanning.value = true
    }).catch((err) => {
        console.warn("L'accès direct à la caméra a échoué :", err)
        useFallback.value = true
    })
})

onUnmounted(() => {
    if (html5QrCode && html5QrCode.isScanning) {
        html5QrCode.stop().catch(error => {
            console.error('Failed to stop html5QrCode', error)
        })
    }
})

function triggerFileInput() {
    fileInput.value.click()
}

function handleFileUpload(event) {
    if (event.target.files.length === 0) return

    const file = event.target.files[0]
    html5QrCode.scanFile(file, true)
        .then(decodedText => {
            emit('scan', decodedText)
        })
        .catch(err => {
            if (window.platformAlert) {
                window.platformAlert("Aucun QR Code trouvé sur l'image.", 'warning')
            } else {
                alert("Aucun QR Code trouvé sur l'image.")
            }
        })
    
    event.target.value = ''
}
</script>

<template>
    <div class="bg-white rounded-2xl overflow-hidden border border-gray-200">
        <!-- Live Camera Container -->
        <div v-show="!useFallback" :id="scannerId" class="w-full"></div>

        <!-- Fallback File Input -->
        <div v-if="useFallback" class="p-8 text-center bg-gray-50">
            <CameraIcon class="h-12 w-12 text-gray-400 mx-auto mb-4" />
            <h3 class="text-sm font-bold text-gray-900 mb-2">Caméra non disponible</h3>
            <p class="text-xs text-gray-500 mb-6 max-w-xs mx-auto">
                L'accès direct a été bloqué par votre navigateur (souvent dû à l'absence de HTTPS). Vous pouvez tout de même utiliser l'appareil photo en cliquant ci-dessous.
            </p>
            
            <input 
                type="file" 
                ref="fileInput" 
                accept="image/*" 
                capture="environment" 
                class="hidden" 
                @change="handleFileUpload"
            >
            <button 
                type="button"
                @click="triggerFileInput"
                class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-xl font-bold text-sm hover:bg-blue-700 transition-colors shadow-sm"
            >
                <PhotoIcon class="h-5 w-5" />
                Prendre une photo
            </button>
        </div>
    </div>
</template>

<style>
/* Hide the default stop/start buttons if any are injected */
#qr-reader-* video {
    border-radius: 1rem !important;
}
</style>
