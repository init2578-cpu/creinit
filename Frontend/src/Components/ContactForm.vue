<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import { PaperAirplaneIcon } from '@heroicons/vue/24/outline'

const form = useForm({
    name: '',
    email: '',
    subject: '',
    message: '',
})

const submit = () => {
    form.post(route('contact.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    })
}
</script>

<template>
    <div class="glass-dark border border-white/10 rounded-[2rem] sm:rounded-[2.5rem] p-6 sm:p-8 md:p-12 shadow-2xl relative overflow-hidden">
        <!-- Form Backdrop Decoration -->
        <div class="absolute -right-20 -top-20 w-40 h-40 bg-cyan-500/10 rounded-full blur-3xl"></div>

        <form @submit.prevent="submit" class="space-y-8 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Identité / Nom</label>
                    <input 
                        v-model="form.name"
                        type="text" 
                        placeholder="Ex: Amadou Diallo"
                        class="w-full bg-slate-900/50 border-white/5 rounded-2xl p-4 text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 transition-all"
                    >
                    <div v-if="form.errors.name" class="text-red-500 text-[10px] mt-1 ml-4 uppercase font-bold">{{ form.errors.name }}</div>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Adresse de Retour (Email)</label>
                    <input 
                        v-model="form.email"
                        type="email" 
                        placeholder="amadou@nexus.sn"
                        class="w-full bg-slate-900/50 border-white/5 rounded-2xl p-4 text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 transition-all"
                    >
                    <div v-if="form.errors.email" class="text-red-500 text-[10px] mt-1 ml-4 uppercase font-bold">{{ form.errors.email }}</div>
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Sujet de la Transmission</label>
                <input 
                    v-model="form.subject"
                    type="text" 
                    placeholder="Ex: Collaboration IA / Demande d'information"
                    class="w-full bg-slate-900/50 border-white/5 rounded-2xl p-4 text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 transition-all"
                >
                <div v-if="form.errors.subject" class="text-red-500 text-[10px] mt-1 ml-4 uppercase font-bold">{{ form.errors.subject }}</div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-4">Flux de Message</label>
                <textarea 
                    v-model="form.message"
                    rows="6"
                    placeholder="Décrivez votre projet ou votre question..."
                    class="w-full bg-slate-900/50 border-white/5 rounded-2xl p-4 text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 transition-all resize-none"
                ></textarea>
                <div v-if="form.errors.message" class="text-red-500 text-[10px] mt-1 ml-4 uppercase font-bold">{{ form.errors.message }}</div>
            </div>

            <button 
                type="submit" 
                :disabled="form.processing"
                class="w-full py-5 bg-cyan-500 text-slate-950 rounded-2xl font-black text-lg transition-all hover:bg-cyan-400 hover:shadow-[0_0_40px_rgba(34,211,238,0.4)] active:scale-95 flex items-center justify-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed group"
            >
                <span>{{ form.processing ? 'SYNCHRONISATION...' : 'TRANSMETTRE LE MESSAGE' }}</span>
                <PaperAirplaneIcon v-if="!form.processing" class="h-6 w-6 group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform" />
            </button>
            
            <div v-if="$page.props.flash.success" class="p-4 bg-cyan-500/10 border border-cyan-500/30 rounded-xl text-cyan-400 text-sm font-bold text-center animate-pulse">
                {{ $page.props.flash.success }}
            </div>
        </form>
    </div>
</template>

<style scoped>
.glass-dark {
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
</style>
