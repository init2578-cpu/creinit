<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { storageUrl } from '@/utils/format'
import { 
    ArrowLeftIcon,
    CalendarIcon,
    UserIcon,
    DocumentTextIcon
} from '@heroicons/vue/24/outline'
import ContactForm from '@/Components/ContactForm.vue'

const props = defineProps({
    post: Object,
})

const currentUrl = ref('')
const copied = ref(false)

onMounted(() => {
    currentUrl.value = window.location.href
})

function copyUrl() {
    navigator.clipboard.writeText(currentUrl.value).then(() => {
        copied.value = true
        setTimeout(() => {
            copied.value = false
        }, 3000)
    })
}
</script>

<template>
    <Head :title="post.title" />

    <GuestLayout>
        <article class="relative pt-32 pb-24 bg-slate-950 min-h-screen">
            <!-- Background effects -->
            <div class="absolute inset-0 bg-cyber-grid opacity-10 pointer-events-none"></div>
            
            <div class="max-w-4xl mx-auto px-4 relative z-10">
                <!-- Navigation -->
                <div class="mb-10">
                    <Link :href="route('public.posts.index')" class="inline-flex items-center gap-2 text-xs font-black text-slate-500 hover:text-cyan-400 transition-colors uppercase tracking-widest">
                        <ArrowLeftIcon class="h-4 w-4" />
                        Retour aux actualités
                    </Link>
                </div>

                <!-- Header -->
                <header class="mb-12">
                    <h1 class="text-4xl md:text-6xl font-black text-white tracking-tighter mb-6 leading-tight font-display">
                        {{ post.title }}
                    </h1>
                    
                    <div class="flex flex-wrap items-center gap-6 text-sm font-bold text-slate-400 border-l-2 border-indigo-500 pl-4">
                        <div class="flex items-center gap-2">
                            <CalendarIcon class="h-5 w-5 text-indigo-400" />
                            {{ new Date(post.published_at).toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                        </div>
                        <div v-if="post.author" class="flex items-center gap-2">
                            <UserIcon class="h-5 w-5 text-indigo-400" />
                            Par {{ post.author.name }}
                        </div>
                    </div>
                </header>

                <!-- Hero Image -->
                <div v-if="post.image_path" class="w-full rounded-[3rem] overflow-hidden mb-16 shadow-[0_0_50px_rgba(0,0,0,0.5)] border border-white/5">
                    <img :src="storageUrl(post.image_path)" :alt="post.title" class="w-full h-auto block">
                </div>

                <!-- Excerpt (Lead) -->
                <div v-if="post.excerpt" class="mb-12 p-8 glass-dark rounded-[2rem] border border-indigo-500/20 text-indigo-100 text-lg md:text-xl font-medium leading-relaxed italic">
                    {{ post.excerpt }}
                </div>

                <!-- Content -->
                <div class="prose prose-invert prose-lg md:prose-xl max-w-none prose-headings:font-black prose-headings:tracking-tight prose-a:text-cyan-400 prose-img:rounded-3xl">
                    <!-- Note: For safety, if content contains HTML it should be v-html, but currently it's plain text in our setup unless we use a wysiwyg. We'll use whitespace-pre-wrap for plain text formatting for now -->
                    <div class="whitespace-pre-wrap text-slate-300 leading-loose">{{ post.content }}</div>
                </div>

                <!-- Partage -->
                <div class="mt-16 pt-8 border-t border-white/10">
                    <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Partager cet article</h4>
                    <div class="flex flex-wrap gap-3">
                        <!-- Facebook -->
                        <a 
                            :href="'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(currentUrl)" 
                            target="_blank" 
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 px-5 py-3 bg-[#1877F2]/10 hover:bg-[#1877F2]/20 border border-[#1877F2]/20 hover:border-[#1877F2]/40 text-[#1877F2] rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-300"
                        >
                            <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M9 8H7v3h2v9h4v-9h3.6l.4-3h-4V6.5c0-.8.4-1.5 1.5-1.5H17V1.5c-.8-.1-2.2-.2-3.6-.2C10.5 1.3 9 3 9 6.5V8z"/></svg>
                            Facebook
                        </a>
                        <!-- Twitter/X -->
                        <a 
                            :href="'https://twitter.com/intent/tweet?url=' + encodeURIComponent(currentUrl) + '&text=' + encodeURIComponent(post.title)" 
                            target="_blank" 
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 px-5 py-3 bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-300"
                        >
                            <svg class="h-3.5 w-3.5 fill-current" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            Twitter / X
                        </a>
                        <!-- LinkedIn -->
                        <a 
                            :href="'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(currentUrl)" 
                            target="_blank" 
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 px-5 py-3 bg-[#0A66C2]/10 hover:bg-[#0A66C2]/20 border border-[#0A66C2]/20 hover:border-[#0A66C2]/40 text-[#0A66C2] rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-300"
                        >
                            <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764.784 1.764 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                            LinkedIn
                        </a>
                        <!-- WhatsApp -->
                        <a 
                            :href="'https://api.whatsapp.com/send?text=' + encodeURIComponent(post.title + ' ' + currentUrl)" 
                            target="_blank" 
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 px-5 py-3 bg-[#25D366]/10 hover:bg-[#25D366]/20 border border-[#25D366]/20 hover:border-[#25D366]/40 text-[#25D366] rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-300"
                        >
                            <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.625 1.451 5.402.002 9.792-4.382 9.795-9.79.002-2.618-1.01-5.08-2.857-6.93C16.363 2.036 13.9 1.018 12.005 1.018 6.61 1.018 2.22 5.404 2.217 10.796c-.002 1.549.409 3.06 1.192 4.4l-.994 3.63 3.733-.979.13-.077z"/></svg>
                            WhatsApp
                        </a>
                        <!-- Copier le lien -->
                        <button 
                            type="button" 
                            @click="copyUrl"
                            class="inline-flex items-center gap-2 px-5 py-3 bg-cyan-500/10 hover:bg-cyan-500/20 border border-cyan-500/20 hover:border-cyan-500/40 text-cyan-400 rounded-xl text-xs font-black uppercase tracking-wider transition-all duration-300"
                        >
                            <svg v-if="copied" class="h-4 w-4 text-emerald-400 stroke-current fill-none" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            <svg v-else class="h-4 w-4 stroke-current fill-none" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                            {{ copied ? 'Lien copié !' : 'Copier le lien' }}
                        </button>
                    </div>
                </div>

                <!-- Contact Section -->
                <div class="mt-24 pt-16 border-t border-white/10">
                    <div class="text-center mb-12">
                        <h2 class="text-3xl md:text-5xl font-black text-white tracking-tighter mb-4 font-display">
                            Intéressé(e) ? <span class="text-cyan-500">Contactez-nous</span>
                        </h2>
                        <p class="text-slate-400 text-lg font-medium">Une question ou une proposition de collaboration ? Envoyez-nous un message.</p>
                    </div>
                    <div>
                        <ContactForm />
                    </div>
                </div>

                <!-- Footer details -->
                <footer class="mt-20 pt-8 border-t border-white/10 flex justify-between items-center">
                    <Link :href="route('public.posts.index')" class="px-8 py-4 bg-slate-900 text-white rounded-2xl font-black text-sm hover:bg-indigo-600 transition-all border border-white/5">
                        Toutes les actualités
                    </Link>
                </footer>
            </div>
        </article>
    </GuestLayout>
</template>
