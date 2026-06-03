<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { 
    EnvelopeIcon, 
    EnvelopeOpenIcon, 
    TrashIcon, 
    UserIcon, 
    InboxIcon, 
    CalendarIcon, 
    MagnifyingGlassIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    messages: Array
})

const selectedMessage = ref(null)
const searchQuery = ref('')

// Filter messages based on search query
const filteredMessages = computed(() => {
    if (!searchQuery.value) return props.messages
    const query = searchQuery.value.toLowerCase()
    return props.messages.filter(m => 
        m.name.toLowerCase().includes(query) ||
        m.email.toLowerCase().includes(query) ||
        m.subject.toLowerCase().includes(query) ||
        m.message.toLowerCase().includes(query)
    )
})

const selectMessage = (message) => {
    selectedMessage.value = message
    if (!message.is_read) {
        router.patch(route('contact-messages.read', message.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                message.is_read = true
            }
        })
    }
}

const deleteMessage = (id) => {
    if (!confirm('Voulez-vous vraiment supprimer ce message ?')) return
    
    router.delete(route('contact-messages.destroy', id), {
        onSuccess: () => {
            if (selectedMessage.value && selectedMessage.value.id === id) {
                selectedMessage.value = null
            }
        }
    })
}

const formatDate = (dateStr) => {
    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }
    return new Date(dateStr).toLocaleDateString('fr-FR', options)
}
</script>

<template>
    <Head title="Messages Contact" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-8 px-4 h-[calc(100vh-80px)] flex flex-col">
            <!-- Header Section -->
            <header class="mb-6 flex-shrink-0">
                <h1 class="text-4xl font-black text-gray-900 tracking-tight flex items-center gap-3">
                    <EnvelopeIcon class="h-10 w-10 text-blue-600" />
                    Messages de Contact
                </h1>
                <p class="text-gray-500 mt-2 font-medium">Consultez et gérez les transmissions reçues via le site vitrine.</p>
            </header>

            <!-- Search Bar -->
            <div class="mb-6 flex-shrink-0 relative">
                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" />
                <input 
                    v-model="searchQuery" 
                    type="text" 
                    placeholder="Rechercher par nom, email, sujet..." 
                    class="w-full pl-12 pr-6 py-4 bg-white border border-gray-100 rounded-2xl font-medium focus:ring-4 focus:ring-blue-100 focus:border-blue-600 transition-all outline-none"
                />
            </div>

            <!-- Main Content Area -->
            <div class="flex-1 min-h-0 bg-white rounded-[2.5rem] border border-gray-100 shadow-sm overflow-hidden flex flex-col md:flex-row mb-6">
                <!-- Inbox List Pane -->
                <div class="w-full md:w-5/12 border-r border-gray-100 flex flex-col h-full">
                    <div class="p-6 border-b border-gray-50 bg-gray-50/50 flex-shrink-0">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Boîte de Réception ({{ filteredMessages.length }})</span>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto min-h-0 divide-y divide-gray-50">
                        <!-- Empty State -->
                        <div v-if="filteredMessages.length === 0" class="text-center py-20 text-gray-400">
                            <InboxIcon class="h-12 w-12 mx-auto mb-4 text-gray-200" />
                            <p class="text-sm font-medium">Aucun message trouvé.</p>
                        </div>

                        <!-- Message Item -->
                        <div 
                            v-for="msg in filteredMessages" 
                            :key="msg.id"
                            @click="selectMessage(msg)"
                            class="p-6 cursor-pointer hover:bg-gray-50/50 transition-all relative flex flex-col gap-2"
                            :class="[
                                selectedMessage?.id === msg.id ? 'bg-blue-50/50 border-l-4 border-blue-600 pl-5' : '',
                                !msg.is_read ? 'bg-gray-50/30' : ''
                            ]"
                        >
                            <div class="flex justify-between items-start gap-4">
                                <div class="flex items-center gap-2 truncate">
                                    <span 
                                        v-if="!msg.is_read" 
                                        class="h-2.5 w-2.5 rounded-full bg-blue-600 flex-shrink-0"
                                        title="Non lu"
                                    ></span>
                                    <span 
                                        class="text-sm truncate max-w-[180px]"
                                        :class="!msg.is_read ? 'font-black text-gray-900' : 'font-semibold text-gray-700'"
                                    >
                                        {{ msg.name }}
                                    </span>
                                </div>
                                <span class="text-[10px] font-bold text-gray-400 whitespace-nowrap">{{ formatDate(msg.created_at) }}</span>
                            </div>
                            <span 
                                class="text-xs truncate"
                                :class="!msg.is_read ? 'font-extrabold text-gray-800' : 'font-medium text-gray-500'"
                            >
                                {{ msg.subject }}
                            </span>
                            <p 
                                class="text-xs line-clamp-2"
                                :class="!msg.is_read ? 'text-gray-600 font-medium' : 'text-gray-400 font-normal'"
                            >
                                {{ msg.message }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Message Detail Pane -->
                <div class="flex-1 flex flex-col h-full bg-slate-50/30">
                    <!-- Message Selected -->
                    <div v-if="selectedMessage" class="flex flex-col h-full">
                        <!-- Detail Header -->
                        <div class="p-8 border-b border-gray-100 bg-white flex justify-between items-center gap-6 flex-shrink-0">
                            <div>
                                <h3 class="text-lg font-black text-gray-900 tracking-tight">{{ selectedMessage.subject }}</h3>
                                <div class="flex items-center gap-3 mt-2 text-xs font-bold text-gray-500">
                                    <span class="flex items-center gap-1">
                                        <UserIcon class="h-4 w-4" />
                                        {{ selectedMessage.name }}
                                    </span>
                                    <span>&bull;</span>
                                    <a :href="'mailto:' + selectedMessage.email" class="text-blue-600 hover:underline">{{ selectedMessage.email }}</a>
                                </div>
                            </div>
                            
                            <button 
                                @click="deleteMessage(selectedMessage.id)"
                                class="p-3 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 hover:text-red-700 transition-all"
                                title="Supprimer le message"
                            >
                                <TrashIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Detail Body -->
                        <div class="flex-1 overflow-y-auto p-8 space-y-6 bg-white min-h-0">
                            <div class="flex gap-2 items-center text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                <CalendarIcon class="h-4 w-4" />
                                Reçu le {{ formatDate(selectedMessage.created_at) }}
                            </div>
                            
                            <div class="text-gray-700 leading-relaxed font-medium text-sm whitespace-pre-wrap">
                                {{ selectedMessage.message }}
                            </div>
                        </div>
                        
                        <!-- Detail Actions/Reply Hint -->
                        <div class="p-6 bg-gray-50/50 border-t border-gray-100 flex justify-end gap-3 flex-shrink-0">
                            <a 
                                :href="'mailto:' + selectedMessage.email + '?subject=Re: ' + encodeURIComponent(selectedMessage.subject)"
                                class="px-6 py-3 bg-gray-900 text-white rounded-xl font-black text-xs uppercase tracking-widest hover:bg-black transition-all shadow-md"
                            >
                                Répondre par Email
                            </a>
                        </div>
                    </div>

                    <!-- No Message Selected -->
                    <div v-else class="flex-1 flex flex-col items-center justify-center text-gray-400 p-8">
                        <div class="h-20 w-20 rounded-[2rem] bg-gray-100/50 flex items-center justify-center text-gray-300 mb-4">
                            <EnvelopeOpenIcon class="h-10 w-10" />
                        </div>
                        <h4 class="font-black text-gray-800 tracking-tight">Aucun Message Sélectionné</h4>
                        <p class="text-xs text-gray-400 mt-1 max-w-xs text-center font-medium">Sélectionnez un message dans la liste de gauche pour lire son contenu.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
