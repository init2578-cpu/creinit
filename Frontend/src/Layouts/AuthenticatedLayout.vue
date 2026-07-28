<script setup>
import { ref, watch, onMounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { usePasskeyRegister } from '@laravel/passkeys/vue'
import Sidebar from '@/Components/Sidebar.vue'
import Topbar from '@/Components/Topbar.vue'
import AlertModal from '@/Components/AlertModal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import CelebrationOverlay from '@/Components/CelebrationOverlay.vue'
import AssaneChat from '@/Components/AssaneChat.vue'
import PendingAttendanceNotifier from '@/Components/PendingAttendanceNotifier.vue'

const props = defineProps({
    noPadding: {
        type: Boolean,
        default: false
    }
})

const isSidebarOpen = ref(false)
const page = usePage()

const alertState = ref({
    type: 'success',
    title: '',
    message: ''
})

const celebrationState = ref({
    show: false,
    title: '',
    message: '',
    uuid: ''
})

const celebratedIds = ref(new Set(JSON.parse(localStorage.getItem('e_cre_celebrated_certs') || '[]')))

function toggleSidebar() {
    isSidebarOpen.value = !isSidebarOpen.value
}

function closeAlert() {
    alertState.value.message = ''
}

function closeCelebration() {
    celebrationState.value.show = false
}

const showPasskeyPrompt = ref(false)

const { register, isLoading: registerLoading, isSupported: isPasskeySupported } = usePasskeyRegister({
    onSuccess: () => {
        showPasskeyPrompt.value = false
        localStorage.setItem('dismiss_passkey_prompt', 'true')
        if (page.props.auth?.user) {
            page.props.auth.user.has_passkeys = true
        }
        window.platformAlert?.("Connexion par empreinte digitale configurée avec succès !", "success")
    },
    onError: (err) => {
        showPasskeyPrompt.value = false
        window.platformAlert?.("Impossible de configurer l'appareil : " + err.message, "error")
    }
})

function getDeviceName() {
    const ua = navigator.userAgent
    let browserName = "Navigateur"
    let osName = "Appareil"
    
    if (ua.includes("Firefox")) browserName = "Firefox"
    else if (ua.includes("Chrome")) browserName = "Chrome"
    else if (ua.includes("Safari") && !ua.includes("Chrome")) browserName = "Safari"
    else if (ua.includes("Edge")) browserName = "Edge"
    
    if (ua.includes("Windows")) osName = "PC Windows"
    else if (ua.includes("Macintosh")) osName = "Mac"
    else if (ua.includes("Linux")) osName = "PC Linux"
    else if (ua.includes("Android")) osName = "Android"
    else if (ua.includes("iPhone") || ua.includes("iPad")) osName = "iPhone/iPad"
    
    return `${osName} (${browserName})`
}

function handleRegisterPasskey() {
    const name = getDeviceName()
    register(name)
}

function dismissPasskeyPrompt() {
    showPasskeyPrompt.value = false
    localStorage.setItem('dismiss_passkey_prompt', 'true')
}

function markAsCelebrated(uuid) {
    if (!uuid) return
    celebratedIds.value.add(uuid)
    localStorage.setItem('e_cre_celebrated_certs', JSON.stringify([...celebratedIds.value]))
}

// Global helper for components to suppress celebration
onMounted(() => {
    window.markCertificateAsCelebrated = markAsCelebrated
    
    window.platformAlert = (message, type = 'success', title = '') => {
        alertState.value = {
            type: type,
            title: title || (type === 'success' ? 'Succès' : type === 'error' ? 'Erreur' : 'Information'),
            message: message
        }
    }

    // Proactively prompt user to configure passkeys if supported and not yet done
    setTimeout(() => {
        if (
            page.props.auth?.user && 
            !page.props.auth.user.has_passkeys && 
            isPasskeySupported.value && 
            localStorage.getItem('dismiss_passkey_prompt') !== 'true'
        ) {
            showPasskeyPrompt.value = true
        }
    }, 3000)
})

// Watch for celebratory notifications
watch(() => page.props.auth?.user?.unread_notifications, (notifications) => {
    if (!notifications) return
    
    const certNotif = notifications.find(n => 
        n.data.type === 'certificate_issued' && !celebratedIds.value.has(n.data.certificate_uuid)
    )

    if (certNotif) {
        markAsCelebrated(certNotif.data.certificate_uuid)
        celebrationState.value = {
            show: true,
            title: certNotif.data.title,
            message: certNotif.data.message,
            uuid: certNotif.data.certificate_uuid
        }
    }
}, { deep: true, immediate: true })

// Watch for flash messages
watch(() => page.props.flash, (flash) => {
    if (flash.success) {
        alertState.value = {
            type: 'success',
            title: 'Opération Réussie',
            message: flash.success
        }
    } else if (flash.error) {
        alertState.value = {
            type: 'error',
            title: 'Une erreur est survenue',
            message: flash.error
        }
    } else if (flash.warning) {
        alertState.value = {
            type: 'warning',
            title: 'Attention',
            message: flash.warning
        }
    } else if (flash.info) {
        alertState.value = {
            type: 'info',
            title: 'Information',
            message: flash.info
        }
    }
}, { deep: true, immediate: true })
</script>


<template>
    <div class="min-h-screen bg-gray-50 flex">
        <!-- Sidebar -->
        <Sidebar :is-open="isSidebarOpen" @toggle-sidebar="toggleSidebar" />

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col lg:ml-64 transition-all duration-300">
            <!-- Topbar -->
            <Topbar @toggle-sidebar="toggleSidebar" />

            <!-- Global Alert Modal (PROMPT 10) -->
            <AlertModal 
                :is-open="!!alertState.message"
                :type="alertState.type"
                :title="alertState.title"
                :message="alertState.message"
                @close="closeAlert"
            />

            <!-- Celebration Overlay (PROMPT 11) -->
            <CelebrationOverlay
                :show="celebrationState.show"
                :title="celebrationState.title"
                :message="celebrationState.message"
                :certificate-uuid="celebrationState.uuid"
                @close="closeCelebration"
            />

            <!-- Proactive Passkey Registration Prompt -->
            <ConfirmModal
                :is-open="showPasskeyPrompt"
                type="info"
                title="Activer l'empreinte ?"
                message="Activer la connexion par empreinte digitale / FaceID sur cet appareil pour vous connecter en un clic la prochaine fois."
                confirm-text="Activer"
                cancel-text="Plus tard"
                @confirm="handleRegisterPasskey"
                @cancel="dismissPasskeyPrompt"
            />

            <!-- Page Management -->
            <main :class="['flex-1 min-h-0', noPadding ? '' : 'p-4 sm:p-6 lg:p-8 pt-20 sm:pt-24 lg:pt-28']">
                <slot />
            </main>

            <!-- AI Assistant ASSANE -->
            <AssaneChat />

            <!-- Real-time Pending Attendance Warning Notifier -->
            <PendingAttendanceNotifier />

            <!-- Optional Footer -->
            <footer class="py-6 px-8 border-t border-gray-200 text-center text-sm text-gray-400">
                &copy; {{ new Date().getFullYear() }} Plateforme E-CRE Kolda.
            </footer>
        </div>

        <!-- Mobile Overlay -->
        <div 
            v-if="isSidebarOpen" 
            @click="isSidebarOpen = false"
            class="fixed inset-0 z-40 bg-gray-900 bg-opacity-50 lg:hidden"
        ></div>
    </div>
</template>
