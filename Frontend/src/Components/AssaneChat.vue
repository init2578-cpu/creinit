<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { 
    ChatBubbleLeftRightIcon, 
    QuestionMarkCircleIcon, 
    SparklesIcon,
    XMarkIcon,
    PaperAirplaneIcon,
    CommandLineIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline'

const isOpen = ref(false)
const message = ref('')
const activeTab = ref('chat') // 'chat' or 'help'
const messages = ref([
    { role: 'assistant', content: 'Bonjour ! Je suis ASSANE, votre assistant e-CRE. Comment puis-je vous aider aujourd\'hui ?' }
])
const isLoading = ref(false)
const chatContainer = ref(null)

const toggleChat = () => {
    isOpen.value = !isOpen.value
    if (isOpen.value) {
        nextTick(() => scrollToBottom())
    }
}

const scrollToBottom = () => {
    if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight
    }
}

const sendMessage = async () => {
    if (!message.value.trim() || isLoading.value) return

    const userMessage = message.value
    messages.value.push({ role: 'user', content: userMessage })
    message.value = ''
    isLoading.value = true

    nextTick(() => scrollToBottom())

    try {
        const response = await axios.post(route('assane.chat'), {
            message: userMessage,
            history: messages.value.slice(0, -1)
        })

        messages.value.push({ role: 'assistant', content: response.data.response })
    } catch (error) {
        console.error('Assane Chat Error:', error)
        messages.value.push({ 
            role: 'assistant', 
            content: 'Désolé, j\'ai rencontré une erreur technique. Veuillez réessayer plus tard.' 
        })
    } finally {
        isLoading.value = false
        nextTick(() => scrollToBottom())
    }
}
</script>

<template>
    <div class="fixed bottom-6 right-6 z-50">
        <!-- Floating Button -->
        <button 
            @click="toggleChat"
            class="w-16 h-16 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl shadow-2xl flex items-center justify-center transition-all duration-500 transform hover:scale-110 active:scale-95 group"
            :class="{ 'rotate-90': isOpen }"
        >
            <SparklesIcon v-if="!isOpen" class="h-8 w-8 group-hover:animate-pulse" />
            <XMarkIcon v-else class="h-8 w-8" />
        </button>

        <!-- Chat Window -->
        <div 
            v-if="isOpen"
            class="absolute bottom-24 right-0 w-[550px] h-[750px] bg-white/95 backdrop-blur-[32px] rounded-[3.5rem] shadow-[0_32px_128px_-32px_rgba(0,0,0,0.3)] overflow-hidden border border-white/40 flex transition-all duration-500 ease-[cubic-bezier(0.23,1,0.32,1)] animate-in zoom-in-95 fade-in slide-in-from-bottom-12 z-50 ring-1 ring-white/50"
        >
            <!-- Vertical Nav Bar -->
            <div class="w-20 bg-emerald-600/[0.03] border-r border-emerald-50 flex flex-col items-center py-10 gap-8 shrink-0">
                <div class="h-12 w-12 bg-emerald-600 text-white rounded-2xl flex items-center justify-center mb-4 shadow-xl shadow-emerald-200/50 transform hover:rotate-6 transition-transform">
                    <SparklesIcon class="h-7 w-7" />
                </div>
                
                <div class="flex flex-col gap-4">
                    <button 
                        @click="activeTab = 'chat'"
                        class="p-4 rounded-2xl transition-all duration-300 relative group"
                        :class="activeTab === 'chat' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-100' : 'text-emerald-300 hover:text-emerald-600 hover:bg-emerald-50'"
                    >
                        <ChatBubbleLeftRightIcon class="h-6 w-6 relative z-10" />
                        <div v-if="activeTab === 'chat'" class="absolute -left-1 top-4 bottom-4 w-1 bg-white rounded-full"></div>
                        <span class="absolute left-24 bg-gray-900 text-white text-[10px] font-black px-3 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity whitespace-nowrap uppercase tracking-widest">Discussion</span>
                    </button>
    
                    <button 
                        @click="activeTab = 'help'"
                        class="p-4 rounded-2xl transition-all duration-300 relative group"
                        :class="activeTab === 'help' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-100' : 'text-emerald-300 hover:text-emerald-600 hover:bg-emerald-50'"
                    >
                        <InformationCircleIcon class="h-6 w-6 relative z-10" />
                        <div v-if="activeTab === 'help'" class="absolute -left-1 top-4 bottom-4 w-1 bg-white rounded-full"></div>
                        <span class="absolute left-24 bg-gray-900 text-white text-[10px] font-black px-3 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity whitespace-nowrap uppercase tracking-widest">Guide</span>
                    </button>
                </div>

                <div class="mt-auto pb-6">
                    <button class="p-4 text-gray-300 hover:text-emerald-600 hover:bg-emerald-50 rounded-2xl transition-all group relative">
                        <QuestionMarkCircleIcon class="h-6 w-6" />
                        <span class="absolute left-24 bg-gray-900 text-white text-[10px] font-black px-3 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity whitespace-nowrap uppercase tracking-widest">Support</span>
                    </button>
                </div>
            </div>
    
            <!-- Content Area -->
            <div class="flex-1 flex flex-col min-w-0 bg-gradient-to-br from-white/40 to-transparent">
                <!-- Header -->
                <div class="p-10 pb-4 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="text-2xl font-black text-gray-900 tracking-tighter">ASSANE</h3>
                            <span class="px-2 py-0.5 bg-emerald-50 text-emerald-600 rounded-md text-[8px] font-black uppercase tracking-tighter border border-emerald-100/50">v2.0 AI</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex space-x-0.5">
                                <div class="h-1.5 w-1.5 bg-emerald-500 rounded-full animate-pulse"></div>
                                <div class="h-1.5 w-1.5 bg-emerald-500 rounded-full animate-pulse [animation-delay:0.2s]"></div>
                                <div class="h-1.5 w-1.5 bg-emerald-500 rounded-full animate-pulse [animation-delay:0.4s]"></div>
                            </div>
                            <span class="text-[10px] font-black text-emerald-600/70 uppercase tracking-[0.2em]">Système Intelligent Connecté</span>
                        </div>
                    </div>
                </div>
    
                <template v-if="activeTab === 'chat'">
                    <!-- Messages Area -->
                    <div 
                        ref="chatContainer"
                        class="flex-1 overflow-y-auto p-10 pt-4 space-y-6 custom-scrollbar scroll-smooth"
                    >
                        <div 
                            v-for="(msg, index) in messages" 
                            :key="index"
                            :class="['flex', msg.role === 'user' ? 'justify-end' : 'justify-start']"
                            class="animate-in slide-in-from-bottom-4 duration-500"
                        >
                            <div 
                                :class="[
                                    'max-w-[85%] p-5 rounded-[2rem] text-[13px] shadow-sm transition-all leading-relaxed',
                                    msg.role === 'user' 
                                        ? 'bg-gradient-to-br from-emerald-600 to-teal-700 text-white rounded-tr-none font-bold shadow-emerald-100 shadow-lg' 
                                        : 'bg-white/80 backdrop-blur-md text-gray-700 rounded-tl-none border border-white font-medium ring-1 ring-black/[0.02]'
                                ]"
                            >
                                {{ msg.content }}
                            </div>
                        </div>
    
                        <!-- Loading Indicator -->
                        <div v-if="isLoading" class="flex justify-start animate-pulse">
                            <div class="bg-white/80 backdrop-blur-md border border-white p-5 rounded-[2rem] rounded-tl-none shadow-sm flex items-center gap-3">
                                <div class="flex space-x-1">
                                    <div class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-bounce"></div>
                                    <div class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-bounce [animation-delay:0.2s]"></div>
                                    <div class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-bounce [animation-delay:0.4s]"></div>
                                </div>
                                <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Assane réfléchit...</span>
                            </div>
                        </div>
                    </div>
    
                    <!-- Input Area -->
                    <div class="p-8 px-10 bg-white/40 border-t border-emerald-50/50 mt-auto backdrop-blur-md">
                        <form @submit.prevent="sendMessage" class="relative group">
                            <input 
                                v-model="message"
                                type="text" 
                                placeholder="Posez votre question à l'IA..."
                                class="w-full bg-white/90 border border-emerald-100/50 rounded-[1.5rem] focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 font-bold px-6 py-5 pr-16 shadow-xl shadow-emerald-900/[0.03] group-hover:shadow-emerald-900/[0.06] transition-all outline-none text-sm placeholder:text-gray-300"
                                :disabled="isLoading"
                            />
                            <button 
                                type="submit"
                                :disabled="!message.trim() || isLoading"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 p-3 bg-emerald-600 text-white rounded-2xl disabled:bg-gray-200 transition-all shadow-lg shadow-emerald-200 hover:scale-105 active:scale-95"
                            >
                                <PaperAirplaneIcon class="h-5 w-5" />
                            </button>
                        </form>
                        <p class="text-center mt-4 text-[9px] font-black text-gray-300 uppercase tracking-widest">Interface IA Sécurisée de Kolda</p>
                    </div>
                </template>
    
                <template v-else-if="activeTab === 'help'">
                    <div class="flex-1 p-10 overflow-y-auto custom-scrollbar">
                        <h4 class="text-[10px] font-black text-emerald-600 uppercase tracking-[0.3em] mb-6">Centre de Ressources AI</h4>
                        <div class="space-y-6">
                            <div class="p-6 bg-white/60 rounded-[2rem] border border-white shadow-sm hover:shadow-md transition-all group">
                                <div class="h-10 w-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <ChatBubbleLeftRightIcon class="h-5 w-5" />
                                </div>
                                <p class="text-[11px] font-black text-emerald-900 mb-2 uppercase tracking-wider">Assistance Orientation</p>
                                <p class="text-xs text-gray-500 leading-relaxed font-medium">Posez des questions sur le processus d'inscription, les prérequis académiques ou les débouchés de chaque module de formation.</p>
                            </div>
                            
                            <div class="p-6 bg-white/60 rounded-[2rem] border border-white shadow-sm hover:shadow-md transition-all group">
                                <div class="h-10 w-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <CommandLineIcon class="h-5 w-5" />
                                </div>
                                <p class="text-[11px] font-black text-blue-900 mb-2 uppercase tracking-wider">Support Logistique</p>
                                <p class="text-xs text-gray-500 leading-relaxed font-medium">Informez-vous sur la disponibilité des équipements, les procédures d'emprunt et les horaires d'ouverture du CRE.</p>
                            </div>

                            <div class="p-6 bg-white/60 rounded-[2rem] border border-white shadow-sm hover:shadow-md transition-all group">
                                <div class="h-10 w-10 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                    <SparklesIcon class="h-5 w-5" />
                                </div>
                                <p class="text-[11px] font-black text-purple-900 mb-2 uppercase tracking-wider">Expertise Pédagogique</p>
                                <p class="text-xs text-gray-500 leading-relaxed font-medium">Obtenez des précisions sur les quiz, les sessions d'examen et les critères d'obtention des certificats CRE.</p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-in {
    animation-duration: 400ms;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>
