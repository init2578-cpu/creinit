<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

const isOpen = ref(false)
const message = ref('')
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
            history: messages.value.slice(0, -1) // Send history excluding the last message
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
            class="w-14 h-14 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full shadow-2xl flex items-center justify-center transition-all duration-300 transform hover:scale-110 active:scale-95"
            :class="{ 'rotate-90': isOpen }"
        >
            <svg v-if="!isOpen" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- Chat Window -->
        <div 
            v-if="isOpen"
            class="fixed sm:absolute bottom-20 right-2 sm:right-0 left-2 sm:left-auto max-w-[calc(100vw-1rem)] sm:w-96 bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-100 flex flex-col transition-all duration-300 ease-out animate-in fade-in slide-in-from-bottom-5"
        >
            <!-- Header -->
            <div class="bg-emerald-600 p-4 text-white flex items-center space-x-3">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <span class="font-bold text-lg">A</span>
                </div>
                <div>
                    <h3 class="font-bold">ASSANE</h3>
                    <p class="text-xs text-emerald-100">En ligne pour vous aider</p>
                </div>
            </div>

            <!-- Messages Area -->
            <div 
                ref="chatContainer"
                class="flex-1 h-96 overflow-y-auto p-4 space-y-4 bg-gray-50"
            >
                <div 
                    v-for="(msg, index) in messages" 
                    :key="index"
                    :class="['flex', msg.role === 'user' ? 'justify-end' : 'justify-start']"
                >
                    <div 
                        :class="[
                            'max-w-[85%] p-3 rounded-2xl text-sm shadow-sm',
                            msg.role === 'user' 
                                ? 'bg-emerald-600 text-white rounded-tr-none' 
                                : 'bg-white text-gray-800 rounded-tl-none border border-gray-100'
                        ]"
                    >
                        {{ msg.content }}
                    </div>
                </div>

                <!-- Loading Indicator -->
                <div v-if="isLoading" class="flex justify-start">
                    <div class="bg-white border border-gray-100 p-3 rounded-2xl rounded-tl-none shadow-sm flex space-x-1">
                        <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce [animation-delay:0.2s]"></div>
                        <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce [animation-delay:0.4s]"></div>
                    </div>
                </div>
            </div>

            <!-- Input Area -->
            <div class="p-4 border-t border-gray-100 bg-white">
                <form @submit.prevent="sendMessage" class="flex items-center space-x-2">
                    <input 
                        v-model="message"
                        type="text" 
                        placeholder="Posez votre question..."
                        class="flex-1 border-none focus:ring-0 text-sm py-2 px-1"
                        :disabled="isLoading"
                    />
                    <button 
                        type="submit"
                        :disabled="!message.trim() || isLoading"
                        class="text-emerald-600 hover:text-emerald-700 disabled:text-gray-300 transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-in {
    animation-duration: 300ms;
}
</style>
