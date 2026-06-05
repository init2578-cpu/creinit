<script setup>
import { Head, Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { storageUrl } from '@/utils/format'
import { 
    ArrowLeftIcon,
    CalendarIcon,
    UserIcon,
    DocumentTextIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    post: Object,
})
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
                <div v-if="post.image_path" class="w-full h-auto md:h-[500px] rounded-[3rem] overflow-hidden mb-16 shadow-[0_0_50px_rgba(0,0,0,0.5)] border border-white/5">
                    <img :src="storageUrl(post.image_path)" :alt="post.title" class="w-full h-full object-cover">
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
