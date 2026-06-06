<script setup>
import { ref } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    XMarkIcon, 
    WrenchIcon,
    ArchiveBoxIcon,
    IdentificationIcon,
    QrCodeIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
    NoSymbolIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    assets: Array
})

const isModalOpen = ref(false)
const isEditing = ref(false)
const editingAsset = ref(null)

const isQrModalOpen = ref(false)
const selectedAssetForQr = ref(null)

function openQrModal(asset) {
    selectedAssetForQr.value = asset
    isQrModalOpen.value = true
}

function closeQrModal() {
    isQrModalOpen.value = false
    selectedAssetForQr.value = null
}

function printQrCode() {
    if (!selectedAssetForQr.value) return
    const asset = selectedAssetForQr.value
    const printWindow = window.open('', '_blank')
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
            <head>
                <title>QR Code - ${asset.nom}</title>
                <style>
                    body {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        height: 90vh;
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                        margin: 0;
                        color: #1e293b;
                    }
                    .card {
                        border: 2px solid #e2e8f0;
                        border-radius: 1.5rem;
                        padding: 2.5rem;
                        text-align: center;
                        max-width: 320px;
                        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
                    }
                    .qr-container {
                        position: relative;
                        display: inline-block;
                    }
                    img.qr {
                        width: 220px;
                        height: 220px;
                        border: 4px solid #f1f5f9;
                        border-radius: 1rem;
                    }
                    .logo-overlay {
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        background-color: white;
                        padding: 4px;
                        border-radius: 8px;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                        border: 1px solid #e2e8f0;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .logo-overlay img {
                        width: 44px;
                        height: 44px;
                        object-fit: contain;
                        border-radius: 4px;
                    }
                    h1 {
                        margin-top: 1.5rem;
                        margin-bottom: 0.5rem;
                        font-size: 1.25rem;
                        font-weight: 800;
                    }
                    p {
                        font-size: 0.875rem;
                        color: #64748b;
                        margin: 0.25rem 0;
                        font-weight: 500;
                    }
                    .serial {
                        display: inline-block;
                        background-color: #f1f5f9;
                        padding: 0.25rem 0.75rem;
                        border-radius: 0.5rem;
                        font-size: 0.75rem;
                        font-weight: 700;
                        margin-top: 0.5rem;
                    }
                </style>
            </head>
            <body onload="window.print(); window.close();">
                <div class="card">
                    <div class="qr-container">
                        <img class="qr" src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${asset.uuid}" />
                        <div class="logo-overlay">
                            <img src="/images/logo-cre.png" alt="CRE Logo" />
                        </div>
                    </div>
                    <p>UUID: ${asset.uuid.substring(0, 8).toUpperCase()}</p>
                    ${asset.serie ? `<div class="serial">S/N: ${asset.serie}</div>` : ''}
                </div>
            </body>
        </html>
    `)
    printWindow.document.close()
}
function printAllQrCodes() {
    if (!props.assets || props.assets.length === 0) return
    const printWindow = window.open('', '_blank')
    
    let cardsHtml = ''
    props.assets.forEach(asset => {
        cardsHtml += `
            <div class="card">
                <div class="qr-container">
                    <img class="qr" src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${asset.uuid}" />
                    <div class="logo-overlay">
                        <img src="/images/logo-cre.png" alt="CRE Logo" />
                    </div>
                </div>
                <p>UUID: ${asset.uuid.substring(0, 8).toUpperCase()}</p>
                ${asset.serie ? `<div class="serial">S/N: ${asset.serie}</div>` : ''}
            </div>
        `
    })

    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
            <head>
                <title>QR Codes - Planche d'impression</title>
                <style>
                    body {
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                        margin: 20px;
                        color: #1e293b;
                        background-color: #fff;
                    }
                    .grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                        gap: 20px;
                    }
                    .card {
                        border: 2px solid #e2e8f0;
                        border-radius: 1rem;
                        padding: 1.5rem;
                        text-align: center;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
                        page-break-inside: avoid;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                    }
                    .qr-container {
                        position: relative;
                        display: inline-block;
                    }
                    img.qr {
                        width: 130px;
                        height: 130px;
                        border: 3px solid #f1f5f9;
                        border-radius: 0.75rem;
                    }
                    .logo-overlay {
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        background-color: white;
                        padding: 3px;
                        border-radius: 6px;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        border: 1px solid #e2e8f0;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .logo-overlay img {
                        width: 26px;
                        height: 26px;
                        object-fit: contain;
                        border-radius: 2px;
                    }
                    h1 {
                        margin-top: 1rem;
                        margin-bottom: 0.25rem;
                        font-size: 0.95rem;
                        font-weight: 800;
                        word-break: break-word;
                    }
                    p {
                        font-size: 0.75rem;
                        color: #64748b;
                        margin: 0.15rem 0;
                        font-weight: 500;
                    }
                    .serial {
                        display: inline-block;
                        background-color: #f1f5f9;
                        padding: 0.15rem 0.5rem;
                        border-radius: 0.35rem;
                        font-size: 0.65rem;
                        font-weight: 700;
                        margin-top: 0.35rem;
                    }
                    @media print {
                        body {
                            margin: 0;
                        }
                        .grid {
                            gap: 15px;
                        }
                    }
                </style>
            </head>
            <body onload="window.print(); window.close();">
                <div class="grid">
                    ${cardsHtml}
                </div>
            </body>
        </html>
    `)
    printWindow.document.close()
}

const form = useForm({
    nom: '',
    serie: '',
    etat: 'bon',
    status: 'disponible'
})

function openCreateModal() {
    isEditing.value = false
    editingAsset.value = null
    form.reset()
    form.clearErrors()
    isModalOpen.value = true
}

function openEditModal(asset) {
    isEditing.value = true
    editingAsset.value = asset
    form.clearErrors()
    form.nom = asset.nom
    form.serie = asset.serie || ''
    form.etat = asset.etat
    form.status = asset.status
    isModalOpen.value = true
}

function closeModal() {
    isModalOpen.value = false
}

function submit() {
    if (isEditing.value) {
        form.put(route('assets.update', editingAsset.value.id), {
            onSuccess: () => closeModal()
        })
    } else {
        form.post(route('assets.store'), {
            onSuccess: () => closeModal()
        })
    }
}

function deleteAsset(id) {
    if (confirm("Êtes-vous sûr de vouloir supprimer ce matériel ? Cette action est irréversible.")) {
        router.delete(route('assets.destroy', id))
    }
}

const getStatusClass = (status) => {
    switch (status) {
        case 'disponible': return 'bg-green-50 text-green-600'
        case 'preté': return 'bg-blue-50 text-blue-600'
        case 'maintenance': return 'bg-amber-50 text-amber-600'
        default: return 'bg-gray-50 text-gray-600'
    }
}

const getEtatClass = (etat) => {
    switch (etat) {
        case 'bon': return 'bg-emerald-50 text-emerald-700 border-emerald-100'
        case 'endommagé': return 'bg-orange-50 text-orange-700 border-orange-100'
        case 'hors_service': return 'bg-red-50 text-red-700 border-red-100'
        default: return 'bg-gray-50 text-gray-700 border-gray-100'
    }
}
</script>

<template>
    <Head title="Gestion du Matériel" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-8 px-4">
            <!-- Header section -->
            <header class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight">Gestion du Matériel</h1>
                    <p class="text-gray-500 mt-1">Inventaire physique et suivi de l'état des équipements.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button 
                        v-if="assets && assets.length > 0"
                        @click="printAllQrCodes"
                        class="flex items-center gap-2 px-6 py-3 bg-white text-slate-700 border border-slate-200 rounded-2xl font-bold hover:bg-slate-50 transition-all active:scale-95 shadow-sm"
                    >
                        <QrCodeIcon class="h-5 w-5 text-slate-500" />
                        Imprimer tous les QR Codes
                    </button>
                    <button 
                        @click="openCreateModal"
                        class="flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:shadow-indigo-300 transition-all group active:scale-95"
                    >
                        <PlusIcon class="h-5 w-5 group-hover:rotate-90 transition-transform duration-300" />
                        Nouveau Matériel
                    </button>
                </div>
            </header>

            <!-- Table of assets -->
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-black tracking-widest">
                            <th class="px-8 py-4">Matériel & ID</th>
                            <th class="px-8 py-4">Numéro de Série</th>
                            <th class="px-8 py-4 text-center">État Physique</th>
                            <th class="px-8 py-4 text-center">Disponibilité</th>
                            <th class="px-8 py-4">Enregistrement</th>
                            <th class="px-8 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="asset in assets" :key="asset.id" class="hover:bg-gray-50/50 transition-colors group">
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center">
                                        <ArchiveBoxIcon class="h-6 w-6" />
                                    </div>
                                    <div>
                                        <p class="font-black text-gray-900 leading-tight">{{ asset.nom }}</p>
                                        <button 
                                            @click="openQrModal(asset)"
                                            class="flex items-center gap-1.5 mt-1 text-slate-400 hover:text-indigo-600 transition group/qr"
                                            title="Afficher le QR Code"
                                        >
                                            <QrCodeIcon class="h-3.5 w-3.5" />
                                            <span class="text-[10px] font-mono font-bold uppercase underline decoration-dashed">{{ asset.uuid.substring(0, 8) }}</span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <span class="px-2.5 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-black tracking-tight border border-gray-200">
                                    {{ asset.serie || 'N/A' }}
                                </span>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border"
                                    :class="getEtatClass(asset.etat)"
                                >
                                    <CheckCircleIcon v-if="asset.etat === 'bon'" class="h-3.5 w-3.5" />
                                    <ExclamationCircleIcon v-else-if="asset.etat === 'endommagé'" class="h-3.5 w-3.5" />
                                    <NoSymbolIcon v-else class="h-3.5 w-3.5" />
                                    {{ asset.etat.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                                        :class="getStatusClass(asset.status)"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="asset.status === 'disponible' ? 'bg-green-600' : (asset.status === 'preté' ? 'bg-blue-600' : 'bg-amber-600')"></span>
                                        {{ asset.status }}
                                    </span>
                                    <p v-if="asset.status === 'preté' && asset.borrower" class="text-[10px] text-slate-500 font-bold max-w-[150px] truncate" :title="`Emprunteur: ${asset.borrower.name}`">
                                        Par : {{ asset.borrower.name }}
                                    </p>
                                    <p v-if="asset.status === 'preté' && asset.giver" class="text-[9px] text-slate-400 font-medium max-w-[150px] truncate" :title="`Donné par: ${asset.giver.name}`">
                                        Donné par : {{ asset.giver.name }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs text-gray-900 font-black">{{ asset.registered_by?.name || 'Système' }}</span>
                                    <span class="text-[10px] text-gray-400 font-bold">{{ asset.created_at }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <div class="flex justify-end gap-2 translate-x-4 opacity-0 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
                                    <button 
                                        @click="openQrModal(asset)"
                                        class="p-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-600 hover:text-white transition-all shadow-sm shadow-indigo-50"
                                        title="Voir le QR Code"
                                    >
                                        <QrCodeIcon class="h-5 w-5" />
                                    </button>
                                    <button 
                                        @click="openEditModal(asset)"
                                        class="p-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-600 hover:text-white transition-all shadow-sm shadow-indigo-50"
                                        title="Modifier"
                                    >
                                        <PencilSquareIcon class="h-5 w-5" />
                                    </button>
                                    <button 
                                        @click="deleteAsset(asset.id)"
                                        class="p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm shadow-red-50"
                                        title="Supprimer"
                                    >
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="assets.length === 0">
                            <td colspan="6" class="px-8 py-12 text-center text-gray-400 italic font-medium">
                                Aucun matériel enregistré dans l'inventaire.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add/Edit Asset Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl relative">
                <button @click="closeModal" class="absolute right-8 top-8 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-all">
                    <XMarkIcon class="h-6 w-6" />
                </button>

                <h2 class="text-2xl font-black text-gray-900 tracking-tight mb-2">
                    {{ isEditing ? 'Modifier le matériel' : 'Ajouter un matériel' }}
                </h2>
                <p class="text-gray-500 text-sm mb-8">Remplissez les informations essentielles de l'équipement.</p>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Nom -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Nom de l'équipement</label>
                        <div class="relative">
                            <ArchiveBoxIcon class="h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input v-model="form.nom" type="text" placeholder="Ex: Vidéoprojecteur Epson EB-W06" class="w-full pl-12 pr-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm" required>
                        </div>
                        <p v-if="form.errors.nom" class="text-xs text-red-500 mt-1.5 font-bold ml-1">{{ form.errors.nom }}</p>
                    </div>

                    <!-- Série -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Numéro de Série / Tag</label>
                        <div class="relative">
                            <IdentificationIcon class="h-5 w-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input v-model="form.serie" type="text" placeholder="Ex: SN-2024-001" class="w-full pl-12 pr-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Etat -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">État Physique</label>
                            <select v-model="form.etat" class="w-full px-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm appearance-none">
                                <option value="bon">Bon</option>
                                <option value="endommagé">Endommagé</option>
                                <option value="hors_service">Hors Service</option>
                            </select>
                        </div>
                        <!-- Status -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Disponibilité</label>
                            <select v-model="form.status" class="w-full px-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm appearance-none">
                                <option value="disponible">Disponible</option>
                                <option value="preté">Prêté</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button 
                            type="button" 
                            @click="closeModal"
                            class="flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black text-sm hover:bg-gray-200 transition-all"
                        >
                            Annuler
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="flex-[2] py-4 bg-indigo-600 text-white rounded-2xl font-black text-sm shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all disabled:opacity-50"
                        >
                            {{ isEditing ? 'Enregistrer les modifications' : 'Ajouter à l\'inventaire' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- View QR Code Modal -->
        <div v-if="isQrModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white w-full max-w-sm rounded-[2.5rem] p-8 shadow-2xl relative text-center">
                <button @click="closeQrModal" class="absolute right-8 top-8 p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-all">
                    <XMarkIcon class="h-6 w-6" />
                </button>

                <h2 class="text-2xl font-black text-gray-900 tracking-tight mb-2">QR Code du Matériel</h2>
                <p class="text-gray-500 text-sm mb-6">Collez ce code sur l'équipement pour l'identifier facilement.</p>

                <div v-if="selectedAssetForQr" class="space-y-6">
                    <div class="p-4 bg-slate-50 rounded-[2rem] inline-block relative">
                        <img 
                            :src="`https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${selectedAssetForQr.uuid}`" 
                            :alt="selectedAssetForQr.nom" 
                            class="w-56 h-56 mx-auto border-4 border-white rounded-2xl shadow-sm"
                        >
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="bg-white p-1 rounded-xl shadow-md border border-slate-100 flex items-center justify-center">
                                <img src="/images/logo-cre.png" alt="CRE Logo" class="w-12 h-12 object-contain rounded-lg">
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs text-gray-400 font-mono mt-1 uppercase font-bold">UUID: {{ selectedAssetForQr.uuid }}</p>
                        <span v-if="selectedAssetForQr.serie" class="inline-block mt-2 px-3 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-black border border-gray-200">
                            S/N: {{ selectedAssetForQr.serie }}
                        </span>
                    </div>

                    <div class="flex gap-4 pt-2">
                        <button 
                            type="button" 
                            @click="closeQrModal"
                            class="flex-1 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black text-sm hover:bg-gray-200 transition-all"
                        >
                            Fermer
                        </button>
                        <button 
                            type="button" 
                            @click="printQrCode"
                            class="flex-[2] py-4 bg-indigo-600 text-white rounded-2xl font-black text-sm shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center justify-center gap-2"
                        >
                            <QrCodeIcon class="h-5 w-5" />
                            Imprimer le QR
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
