<script setup>
import { Head, Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { storageUrl } from '@/utils/format'
import { 
    DocumentTextIcon, 
    ArrowRightIcon,
    MagnifyingGlassIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    posts: Object,
})
</script>

<template>
    <Head title="Actualités & Publications" />

    <GuestLayout>
        <!-- Hero Section -->
        <section class="relative pt-32 pb-20 bg-slate-950 overflow-hidden">
            <div class="absolute inset-0 bg-cyber-grid opacity-20"></div>
            <div class="absolute inset-0 bg-gradient-to-b from-indigo-500/10 via-slate-950 to-slate-950"></div>
            
            <div class="max-w-7xl mx-auto px-4 relative z-10 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 mb-6">
                    <span class="text-[10px] font-black text-indigo-400 uppercase tracking-[0.3em]">News Nexus</span>
                </div>
                <h1 class="text-5xl md:text-7xl font-black text-white tracking-tighter mb-6 font-display">
                    ACTUALITÉS <span class="bg-gradient-to-r from-indigo-400 to-cyan-400 bg-clip-text text-transparent">CRE</span>
                </h1>
                <p class="text-slate-400 max-w-2xl mx-auto text-lg">
                    Découvrez les dernières publications, annonces et avancées de notre écosystème d'innovation à Kolda.
                </p>
            </div>
        </section>

        <!-- Posts Grid -->
        <section class="py-12 bg-slate-950 min-h-[50vh]">
            <div class="max-w-7xl mx-auto px-4">
                
                <div v-if="posts.data.length === 0" class="text-center py-24 glass-dark rounded-[3rem] border border-white/5">
                    <DocumentTextIcon class="h-16 w-16 text-slate-700 mx-auto mb-4" />
                    <h3 class="text-2xl font-black text-white mb-2">Aucune publication</h3>
                    <p class="text-slate-500">Revenez plus tard pour de nouvelles annonces.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <Link 
                        v-for="post in posts.data" 
                        :key="post.id"
                        :href="route('public.posts.show', post.slug)"
                        class="glass-dark border border-white/5 rounded-[2.5rem] overflow-hidden group hover:border-indigo-500/30 transition-all duration-500 hover:-translate-y-2 flex flex-col"
                    >
                        <div class="h-56 w-full overflow-hidden relative">
                            <img v-if="post.image_path" :src="storageUrl(post.image_path)" :alt="post.title" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                            <div v-else class="w-full h-full bg-slate-900 flex items-center justify-center">
                                <DocumentTextIcon class="h-16 w-16 text-slate-700" />
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent"></div>
                            
                            <div class="absolute bottom-4 left-4">
                                <span class="px-3 py-1 bg-indigo-500/20 backdrop-blur-md text-indigo-300 text-[8px] font-black rounded-lg border border-indigo-500/30 uppercase tracking-widest">
                                    {{ new Date(post.published_at).toLocaleDateString() }}
                                </span>
                            </div>
                        </div>
                        <div class="p-8 flex-1 flex flex-col">
                            <h3 class="text-2xl font-black text-white mb-4 tracking-tight group-hover:text-indigo-400 transition-colors line-clamp-2">{{ post.title }}</h3>
                            <p class="text-slate-400 text-sm leading-relaxed line-clamp-3 mb-8 flex-1">
                                {{ post.excerpt || post.content.substring(0, 150) + '...' }}
                            </p>
                            <div class="flex items-center gap-2 text-[10px] font-black text-indigo-400 uppercase tracking-widest group-hover:text-white transition-colors mt-auto">
                                Lire l'article complet
                                <ArrowRightIcon class="h-4 w-4 group-hover:translate-x-1 transition-transform" />
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="posts.links && posts.links.length > 3" class="mt-16 flex items-center justify-center gap-2">
                    <template v-for="(link, key) in posts.links" :key="key">
                        <Link 
                            v-if="link.url"
                            :href="link.url" 
                            v-html="link.label"
                            class="px-4 py-2 text-sm font-bold rounded-xl transition-all border border-white/5"
                            :class="link.active ? 'bg-indigo-600 text-white shadow-[0_0_20px_rgba(79,70,229,0.3)]' : 'text-slate-400 hover:text-white hover:bg-slate-800'"
                        />
                        <span 
                            v-else 
                            v-html="link.label" 
                            class="px-4 py-2 text-sm font-bold text-slate-600 border border-white/5 rounded-xl opacity-50"
                        />
                    </template>
                </div>
            </div>
        </section>
    </GuestLayout>
</template>
