<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { Html5QrcodeScanner } from 'html5-qrcode'

const props = defineProps({
    fps: { type: Number, default: 10 },
    qrbox: { type: Number, default: 250 },
})

const emit = defineEmits(['scan'])

const scannerId = 'qr-reader-' + Math.random().toString(36).substr(2, 9)
let html5QrcodeScanner = null

onMounted(() => {
    html5QrcodeScanner = new Html5QrcodeScanner(
        scannerId, 
        { fps: props.fps, qrbox: props.qrbox },
        /* verbose= */ false
    )
    
    html5QrcodeScanner.render((decodedText) => {
        emit('scan', decodedText)
        // Optionally stop scanning after find
        // html5QrcodeScanner.clear()
    }, (error) => {
        // Silent error for scan misses
    })

    // Astuce pour forcer l'ouverture de l'appareil photo sur mobile
    // lorsque le navigateur bloque l'accès direct à la caméra (ex: pas de HTTPS)
    const container = document.getElementById(scannerId)
    if (container) {
        const forceCamera = () => {
            const fileInput = container.querySelector('input[type="file"]')
            if (fileInput && !fileInput.hasAttribute('capture')) {
                fileInput.setAttribute('capture', 'environment')
            }
        }
        
        forceCamera() // Immediate check
        
        const observer = new MutationObserver(() => {
            forceCamera()
        })
        observer.observe(container, { childList: true, subtree: true })
    }
})

onUnmounted(() => {
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear().catch(error => {
            console.error('Failed to clear html5QrcodeScanner', error)
        })
    }
})
</script>

<template>
    <div class="bg-white rounded-2xl overflow-hidden border border-gray-200">
        <div :id="scannerId" class="w-full"></div>
    </div>
</template>

<style>
/* Customizing html5-qrcode styles to match our premium look */
#qr-reader__dashboard {
    padding: 1rem !important;
}
#qr-reader__camera_selection, #qr-reader__dashboard_section_csr button {
    border-radius: 0.75rem !important;
    border: 1px solid #e5e7eb !important;
    padding: 0.5rem 1rem !important;
    font-size: 0.875rem !important;
    font-weight: 600 !important;
}
#qr-reader__dashboard_section_csr button {
    background-color: #2563eb !important;
    color: white !important;
    border: none !important;
}
</style>
