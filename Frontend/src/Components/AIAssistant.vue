<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { 
    ChatBubbleLeftRightIcon, 
    XMarkIcon, 
    PaperAirplaneIcon,
    SparklesIcon,
    CpuChipIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline'

const isOpen = ref(false)
const input = ref('')
const messages = ref([
    { role: 'bot', text: 'Séquence d\'accueil activée. Je suis Assane, votre guide neural. Comment puis-je assister votre progression aujourd\'hui ?' }
])
const isThinking = ref(false)
const scrollContainer = ref(null)

const knowledgeBase = [
    { keywords: ['bonjour', 'salut', 'hello', 'coucou', 'hey', 'hi'], response: 'Bonjour ! Je suis Assane, l\'unité d\'assistance de l\'E-CRE. Comment puis-je vous aider dans votre exploration technologique aujourd\'hui ?' },
    { keywords: ['va bien', 'ca va', 'comment vas-tu', 'santé'], response: 'Mes systèmes sont à 100% d\'efficacité opérationnelle. Merci de vous en soucier ! Comment se porte votre projet numérique ?' },
    { keywords: ['merci', 'remercie', 'thanks', 'super'], response: 'C\'est un plaisir d\'assister une unité biologique aussi courtoise. N\'hésitez pas si vous avez d\'autres questions !' },
    { keywords: ['aide', 'aider', 'help'], response: 'Je peux vous renseigner sur nos formations (IA, Développement, Robotique), sur le processus d\'inscription ou sur la localisation de nos laboratoires à Kolda. Que souhaitez-vous savoir ?' },
    { keywords: ['cre', 'mission', 'est quoi'], response: 'Le Centre de Recherche et d\'Essais (CRE) de Kolda est un pôle technologique majeur. Notre mission est de démocratiser l\'accès à l\'IA, au codage et aux technologies de pointe pour dynamiser l\'économie numérique régionale.' },
    { keywords: ['formation', 'module', 'apprendre'], response: 'Nous proposons des modules intensifs en Intelligence Artificielle, Développement Web (Laravel/Vue), Robotique, et Agriculture Numérique. Chaque parcours est conçu pour l\'excellence opérationnelle.' },
    { keywords: ['inscription', 'candidater', 'admettre'], response: 'Pour rejoindre le Nexus, utilisez le bouton "Candidater" dans le menu. Vous aurez besoin d\'une copie de votre CNI et de votre dernier diplôme (format PDF/Image).' },
    { keywords: ['ou est', 'localisation', 'localiser', 'quartier'], response: 'Le CRE est stratégiquement positionné au Quartier Bouna, à Kolda. Vous pouvez nous rendre visite pour explorer nos laboratoires d\'innovation.' },
    { keywords: ['qui est', 'directeur', 'equipe'], response: 'Notre équipe est composée d\'experts passionnés dirigés par un visionnaire de la tech sénégalaise, dédié à faire de Kolda un hub numérique incontournable.' }
]

const scrollToBottom = async () => {
    await nextTick()
    if (scrollContainer.value) {
        scrollContainer.value.scrollTop = scrollContainer.value.scrollHeight
    }
}

const sendMessage = () => {
    if (!input.value.trim()) return

    const userText = input.value.trim()
    messages.value.push({ role: 'user', text: userText })
    input.value = ''
    isThinking.value = true
    scrollToBottom()

    setTimeout(() => {
        const lowerText = userText.toLowerCase()
        let response = 'Mes circuits de recherche ne trouvent pas de correspondance précise. Voulez-vous que je redirige votre demande vers nos capteurs humains via la page Contact ?'
        
        for (const entry of knowledgeBase) {
            if (entry.keywords.some(k => lowerText.includes(k))) {
                response = entry.response
                break
            }
        }

        messages.value.push({ role: 'bot', text: response })
        isThinking.value = false
        scrollToBottom()
    }, 1500)
}

onMounted(() => {
    // Hidden entrance delay
    setTimeout(() => {
        if (!isOpen.value) {
            // Optional: peek sound or pulse
        }
    }, 5000)
})
</script>

<template>
    <div class="fixed bottom-4 right-4 sm:bottom-8 sm:right-8 z-[100] font-sans">
        <!-- Floating Bubble -->
        <button 
            @click="isOpen = !isOpen"
            class="h-16 w-16 rounded-2xl bg-cyan-500 text-slate-950 shadow-[0_0_30px_rgba(34,211,238,0.4)] flex items-center justify-center transition-all hover:scale-110 active:scale-95 group relative overflow-hidden"
            :class="{ 'rotate-90 opacity-0 pointer-events-none scale-0': isOpen }"
        >
            <div class="absolute inset-0 bg-gradient-to-tr from-white/20 to-transparent"></div>
            <ChatBubbleLeftRightIcon class="h-8 w-8 relative z-10" />
            <div class="absolute -top-1 -right-1 h-3 w-3 bg-red-500 rounded-full border-2 border-slate-950 animate-ping"></div>
        </button>

        <!-- Chat Window -->
        <transition 
            enter-active-class="transition duration-500 cubic-bezier(0.18, 0.89, 0.32, 1.28)"
            enter-from-class="translate-y-20 opacity-0 scale-90"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-active-class="transition duration-400 ease-in"
            leave-from-class="translate-y-0 opacity-100 scale-100"
            leave-to-class="translate-y-20 opacity-0 scale-90"
        >
            <div v-if="isOpen" class="absolute bottom-0 right-0 w-[calc(100vw-2rem)] sm:w-[400px] h-[75vh] sm:h-[600px] glass-chat border border-white/10 rounded-[2rem] sm:rounded-[2.5rem] shadow-[0_40px_100px_-20px_rgba(0,0,0,0.5)] flex flex-col overflow-hidden backdrop-blur-2xl">
                <!-- Header -->
                <div class="p-6 border-b border-white/5 bg-slate-900/40 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <div class="h-10 w-10 rounded-xl bg-cyan-500/20 border border-cyan-500/30 flex items-center justify-center text-cyan-400">
                                <CpuChipIcon class="h-6 w-6 animate-pulse" />
                            </div>
                            <div class="absolute -bottom-1 -right-1 h-3 w-3 bg-green-500 rounded-full border-2 border-slate-900"></div>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-cyan-500 uppercase tracking-widest leading-none mb-1">Système Actif</p>
                            <h4 class="text-white font-black tracking-tight">ASSISTANT ASSANE</h4>
                        </div>
                    </div>
                    <button @click="isOpen = false" class="p-2 text-slate-500 hover:text-white transition-colors">
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </div>

                <!-- Messages -->
                <div ref="scrollContainer" class="flex-1 overflow-y-auto p-6 space-y-6 scrollbar-nexus">
                    <div v-for="(msg, i) in messages" :key="i" class="flex" :class="msg.role === 'bot' ? 'justify-start' : 'justify-end'">
                        <div 
                            class="max-w-[85%] p-4 rounded-2xl text-sm leading-relaxed"
                            :class="msg.role === 'bot' 
                                ? 'bg-slate-800/50 text-slate-200 rounded-tl-none border border-white/5' 
                                : 'bg-cyan-500 text-slate-950 font-bold rounded-tr-none shadow-lg'"
                        >
                            {{ msg.text }}
                        </div>
                    </div>
                    
                    <!-- Thinking State -->
                    <div v-if="isThinking" class="flex justify-start">
                        <div class="bg-slate-800/50 p-4 rounded-2xl rounded-tl-none border border-white/5 flex gap-1">
                            <div class="h-1.5 w-1.5 bg-cyan-500 rounded-full animate-bounce"></div>
                            <div class="h-1.5 w-1.5 bg-cyan-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                            <div class="h-1.5 w-1.5 bg-cyan-500 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                        </div>
                    </div>
                </div>

                <!-- Suggestions -->
                <div class="px-6 py-4 flex gap-2 overflow-x-auto scrollbar-none">
                    <button 
                        v-for="suggest in ['Formations', 'Inscription', 'Localisation']" 
                        :key="suggest"
                        @click="input = suggest; sendMessage()"
                        class="whitespace-nowrap px-4 py-1.5 rounded-full bg-white/5 border border-white/10 text-[10px] font-black text-slate-400 hover:text-cyan-400 hover:border-cyan-500/30 transition-all uppercase tracking-widest"
                    >
                        {{ suggest }}
                    </button>
                </div>

                <!-- Input -->
                <div class="p-6 pt-0">
                    <div class="relative group">
                        <input 
                            v-model="input"
                            @keyup.enter="sendMessage"
                            type="text" 
                            placeholder="Interroger l'IA Assane..."
                            class="w-full bg-slate-900/80 border-white/10 rounded-2xl py-4 pl-6 pr-14 text-white placeholder-slate-600 focus:border-cyan-500/50 focus:ring-0 transition-all shadow-inner"
                        >
                        <button 
                            @click="sendMessage"
                            class="absolute right-2 top-2 h-12 w-12 rounded-xl bg-cyan-500 text-slate-950 flex items-center justify-center hover:bg-cyan-400 transition-colors shadow-lg"
                        >
                            <PaperAirplaneIcon class="h-6 w-6 -rotate-45" />
                        </button>
                    </div>
                    <p class="text-[8px] text-center text-slate-600 mt-4 uppercase tracking-[0.3em] font-black">
                        Neural Link Interface — Version 2.4.0
                    </p>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.glass-chat {
    background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.95));
}

.scrollbar-nexus::-webkit-scrollbar {
    width: 4px;
}
.scrollbar-nexus::-webkit-scrollbar-track {
    background: transparent;
}
.scrollbar-nexus::-webkit-scrollbar-thumb {
    background: rgba(34, 211, 238, 0.1);
    border-radius: 10px;
}
.scrollbar-nexus:hover::-webkit-scrollbar-thumb {
    background: rgba(34, 211, 238, 0.3);
}

.scrollbar-none::-webkit-scrollbar {
    display: none;
}
</style>
