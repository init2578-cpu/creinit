<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import SignaturePad from 'signature_pad'
import QRScanner from '@/Components/QRScanner.vue'
import { 
    QrCodeIcon, 
    PencilIcon, 
    TrashIcon,
    CheckBadgeIcon,
    DevicePhoneMobileIcon,
    ArrowPathRoundedSquareIcon,
    ClipboardDocumentCheckIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    users: Array,
    availableAssets: Array,
    loans: Object // Paginated response
})

const signatureCanvas = ref(null)
let signaturePad = null
const showScanner = ref(true)

const form = useForm({
    user_id: '',
    asset_id: '',
    signature: ''
})

onMounted(() => {
    signaturePad = new SignaturePad(signatureCanvas.value, {
        backgroundColor: 'rgba(255, 255, 255, 0)', // Transparent
        penColor: 'rgb(31, 41, 55)',
        minWidth: 1,
        maxWidth: 3,
        velocityFilterWeight: 0.7
    })
    
    // Handle DPI and resizing
    resizeCanvas()
    window.addEventListener('resize', resizeCanvas)
})

function resizeCanvas() {
    const canvas = signatureCanvas.value
    if (!canvas) return

    // This part is borrowed from signature_pad documentation
    // to handle high-resolution screens
    const ratio =  Math.max(window.devicePixelRatio || 1, 1)
    canvas.width = canvas.offsetWidth * ratio
    canvas.height = canvas.offsetHeight * ratio
    canvas.getContext("2d").scale(ratio, ratio)
    
    signaturePad.clear() // otherwise signature gets misplaced
}

onBeforeUnmount(() => {
    window.removeEventListener('resize', resizeCanvas)
})

function clearSignature() {
    signaturePad.clear()
}

function handleQRScan(decodedText) {
    // Basic logic: assume the QR code contains the Asset ID or UUID
    // Here we'll try to find the asset by ID or UUID in the available list
    const found = props.availableAssets.find(a => a.id == decodedText || a.uuid == decodedText)
    if (found) {
        form.asset_id = found.id
        showScanner.value = false
    } else {
        window.platformAlert("Matériel non trouvé ou non disponible.", 'error')
    }
}

function submitCheckout() {
    if (signaturePad.isEmpty()) {
        window.platformAlert("La signature est obligatoire.", 'warning')
        return
    }

    form.signature = signaturePad.toDataURL()
    form.post(route('loans.checkout'), {
        onSuccess: () => {
            form.reset()
            signaturePad.clear()
        }
    })
}

const selectedSignatureUrl = ref(null)

function viewSignature(loan) {
    if (loan.signature_path) {
        selectedSignatureUrl.value = route('loans.signature', loan.id)
    }
}

function returnLoan(id) {
    if (confirm("Confirmer le retour de cet équipement ?")) {
        router.patch(route('loans.return', id))
    }
}
</script>

<template>
    <Head title="Prêt de Matériel" />

    <AuthenticatedLayout>
        <div class="max-w-3xl mx-auto py-8 px-4">
            <header class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Emprunt d'équipement</h1>
                <p class="text-gray-500">Scanner le matériel et recueillir la signature de l'emprunteur.</p>
            </header>

            <form @submit.prevent="submitCheckout" class="space-y-8">
                <!-- 1. Sélection de l'Apprenant -->
                <section class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <label class="block text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                        <CheckBadgeIcon class="h-5 w-5 text-blue-500" />
                        Étape 1 : Sélectionner l'emprunteur
                    </label>
                    <select 
                        v-model="form.user_id" 
                        class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                        required
                    >
                        <option value="" disabled>Choisir un utilisateur...</option>
                        <option v-for="user in users" :key="user.id" :value="user.id">
                            {{ user.name }} ({{ user.email || user.telephone }})
                        </option>
                    </select>
                </section>

                <!-- 2. Sélection du Matériel (QR ou Manuel) -->
                <section class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <label class="block text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                        <QrCodeIcon class="h-5 w-5 text-blue-500" />
                        Étape 2 : Identifier le matériel
                    </label>
                    
                    <div class="flex flex-col sm:flex-row gap-4 mb-4">
                        <button 
                            type="button"
                            @click="showScanner = !showScanner"
                            class="flex-1 py-3 px-4 bg-gray-900 text-white rounded-xl font-bold hover:bg-black transition flex items-center justify-center gap-2"
                        >
                            <DevicePhoneMobileIcon class="h-5 w-5" />
                            {{ showScanner ? 'Fermer le scanner' : 'Scanner le QR Code' }}
                        </button>
                    </div>

                    <div v-if="showScanner" class="mb-4">
                        <QRScanner @scan="handleQRScan" />
                    </div>

                    <select 
                        v-model="form.asset_id" 
                        class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500"
                        required
                    >
                        <option value="" disabled>Ou choisir manuellement...</option>
                        <option v-for="asset in availableAssets" :key="asset.id" :value="asset.id">
                            {{ asset.nom }} - S/N: {{ asset.serie }}
                        </option>
                    </select>
                </section>

                <!-- 3. Signature Numérique -->
                <section class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <label class="block text-sm font-bold text-gray-700 flex items-center gap-2">
                            <PencilIcon class="h-5 w-5 text-blue-500" />
                            Étape 3 : Signature de l'utilisateur
                        </label>
                        <button 
                            type="button" 
                            @click="clearSignature"
                            class="text-xs font-bold text-red-500 hover:text-red-700 flex items-center gap-1"
                        >
                            <TrashIcon class="h-4 w-4" />
                            Effacer
                        </button>
                    </div>

                    <div class="relative border-2 border-dashed border-gray-200 rounded-2xl bg-gray-50 overflow-hidden group">
                        <!-- Visual Guide -->
                        <div class="absolute inset-0 pointer-events-none flex flex-col items-center justify-end pb-12 opacity-30 group-focus-within:opacity-10 transition">
                            <p v-if="form.user_id" class="text-xs font-bold text-gray-400 mb-1">
                                Signer ici ({{ users.find(s => s.id === form.user_id)?.name }})
                            </p>
                            <div class="w-2/3 h-[1px] bg-gray-400"></div>
                        </div>

                        <canvas 
                            ref="signatureCanvas" 
                            class="w-full h-48 touch-none cursor-crosshair relative z-10"
                        ></canvas>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-2 text-center uppercase tracking-widest font-black">
                        La signature engage la responsabilité de l'emprunteur pour le matériel cité.
                    </p>
                </section>
                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="w-full py-4 bg-blue-600 text-white rounded-2xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-100 disabled:opacity-50"
                >
                    Confirmer le prêt
                </button>
            </form>

            <!-- 4. Liste des Emprunts Récents -->
            <div class="mt-16">
                <h2 class="text-xl font-black text-gray-900 mb-6 flex items-center gap-2">
                    <ClipboardDocumentCheckIcon class="h-6 w-6 text-blue-600" />
                    Flux des Emprunts
                </h2>
                
                <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-black tracking-widest border-b border-gray-100">
                                    <th class="px-6 py-4">Emprunteur</th>
                                    <th class="px-6 py-4">Matériel</th>
                                    <th class="px-6 py-4">Date</th>
                                    <th class="px-6 py-4 text-center">Statut</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="loan in loans.data" :key="loan.id" class="hover:bg-gray-50/50 transition">
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-bold text-gray-900">{{ loan.user.name }}</p>
                                        <p v-if="loan.giver" class="text-[10px] text-gray-400 font-bold mt-0.5">Par : {{ loan.giver.name }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-xs font-bold text-gray-600">{{ loan.asset.nom }}</p>
                                        <p class="text-[10px] text-gray-400 font-mono">{{ loan.asset.serie }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ new Date(loan.borrowed_at).toLocaleDateString('fr-FR') }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span v-if="loan.returned_at" class="px-3 py-1 bg-gray-100 text-gray-400 text-[10px] font-black uppercase tracking-widest rounded-lg">
                                            Rendu
                                        </span>
                                        <span v-else class="px-3 py-1 bg-blue-50 text-blue-600 text-[10px] font-black uppercase tracking-widest rounded-lg border border-blue-100">
                                            En cours
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right flex items-center justify-end gap-2">
                                        <button 
                                            v-if="loan.signature_path"
                                            type="button"
                                            @click="viewSignature(loan)"
                                            class="p-2 text-slate-500 hover:bg-slate-100 rounded-xl transition flex items-center gap-1"
                                            title="Voir la signature"
                                        >
                                            <PencilIcon class="h-4 w-4" />
                                            <span class="text-[10px] font-black uppercase tracking-widest">Signature</span>
                                        </button>
                                        <button 
                                            v-if="!loan.returned_at"
                                            type="button"
                                            @click="returnLoan(loan.id)"
                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-xl transition flex items-center gap-1"
                                            title="Marquer comme rendu"
                                        >
                                            <ArrowPathRoundedSquareIcon class="h-5 w-5" />
                                            <span class="text-[10px] font-black uppercase tracking-widest">Rendre</span>
                                        </button>
                                        <span v-else class="text-[10px] text-gray-300 font-black uppercase tracking-widest italic px-2">
                                            Terminé
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="loans.data.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-400 font-bold italic">
                                        Aucun emprunt enregistré.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal de Visualisation de Signature -->
            <div v-if="selectedSignatureUrl" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500/75 backdrop-blur-sm transition-opacity" @click="selectedSignatureUrl = null"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div class="relative inline-block align-middle bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full border border-gray-100">
                        <div class="bg-white px-6 pt-6 pb-4 sm:p-8">
                            <div class="flex items-center justify-between border-b border-gray-50 pb-4 mb-6">
                                <h3 class="text-lg font-black text-gray-900 flex items-center gap-2">
                                    <PencilIcon class="h-5 w-5 text-indigo-600" />
                                    Signature de l'emprunteur
                                </h3>
                                <button @click="selectedSignatureUrl = null" class="p-1.5 hover:bg-gray-100 rounded-lg transition text-gray-400 hover:text-gray-600">
                                    <XMarkIcon class="h-5 w-5" />
                                </button>
                            </div>
                            
                            <div class="bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 p-8 flex items-center justify-center">
                                <img :src="selectedSignatureUrl" alt="Signature" class="max-h-48 object-contain" />
                            </div>
                        </div>
                        <div class="bg-gray-50 px-6 py-4 sm:px-8 sm:flex sm:flex-row-reverse border-t border-gray-100">
                            <button 
                                type="button" 
                                @click="selectedSignatureUrl = null" 
                                class="w-full inline-flex justify-center rounded-xl border border-gray-200 shadow-sm px-4 py-2 bg-white text-sm font-bold text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto"
                            >
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
