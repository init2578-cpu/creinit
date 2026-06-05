<script setup>
import { ref } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { storageUrl } from '@/utils/format'
import { 
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    EyeIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    XCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    posts: Object
})

function deletePost(id) {
    if (confirm("Êtes-vous sûr de vouloir supprimer cette publication ?")) {
        router.delete(route('admin.posts.destroy', id))
    }
}
</script>

<template>
    <Head title="Gestion des Actualités (Vitrine)" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-8 px-4">
            <header class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight">Actualités (Vitrine)</h1>
                    <p class="text-gray-500">Gérez les publications affichées sur la partie publique du site.</p>
                </div>
                <div class="flex items-center gap-4">
                    <Link 
                        :href="route('admin.posts.create')"
                        class="px-6 py-3 bg-indigo-600 text-white rounded-2xl font-black text-sm flex items-center gap-2 hover:bg-indigo-700 transition shadow-lg shadow-indigo-100"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Nouvelle Publication
                    </Link>
                </div>
            </header>

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 text-gray-400 text-[10px] uppercase font-black tracking-widest">
                            <th class="px-8 py-4">Publication</th>
                            <th class="px-8 py-4">Auteur</th>
                            <th class="px-8 py-4">Statut</th>
                            <th class="px-8 py-4">Date de Publication</th>
                            <th class="px-8 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="post in posts.data" :key="post.id" class="hover:bg-gray-50/50 transition-colors group">
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 bg-indigo-50 rounded-xl flex items-center justify-center font-black overflow-hidden border border-gray-100 shrink-0">
                                        <img v-if="post.image_path" :src="storageUrl(post.image_path)" class="h-full w-full object-cover">
                                        <DocumentTextIcon v-else class="h-6 w-6 text-indigo-400" />
                                    </div>
                                    <div>
                                        <p class="font-black text-gray-900 line-clamp-1" :title="post.title">{{ post.title }}</p>
                                        <div class="flex items-center gap-1.5 text-xs text-gray-400 font-medium line-clamp-1">
                                            {{ post.excerpt || 'Aucun extrait' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <span class="text-sm font-bold text-gray-700">{{ post.author?.name }}</span>
                            </td>
                            <td class="px-8 py-5">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                                    :class="post.is_published ? 'bg-green-50 text-green-600' : 'bg-yellow-50 text-yellow-600'"
                                >
                                    <CheckCircleIcon v-if="post.is_published" class="h-3.5 w-3.5" />
                                    <XCircleIcon v-else class="h-3.5 w-3.5" />
                                    {{ post.is_published ? 'Publié' : 'Brouillon' }}
                                </span>
                            </td>
                            <td class="px-8 py-5 text-sm text-gray-400 font-bold">
                                {{ post.published_at ? new Date(post.published_at).toLocaleDateString() : '-' }}
                            </td>
                            <td class="px-8 py-5 text-right">
                                <div class="flex justify-end gap-2">
                                    <a 
                                        v-if="post.is_published"
                                        :href="route('public.posts.show', post.slug)"
                                        target="_blank"
                                        class="p-2 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all shadow-sm shadow-blue-50"
                                        title="Voir en ligne"
                                    >
                                        <EyeIcon class="h-5 w-5" />
                                    </a>
                                    <Link 
                                        :href="route('admin.posts.edit', post.id)"
                                        class="p-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-600 hover:text-white transition-all shadow-sm shadow-indigo-50"
                                        title="Modifier"
                                    >
                                        <PencilSquareIcon class="h-5 w-5" />
                                    </Link>
                                    <button 
                                        @click="deletePost(post.id)"
                                        class="p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-sm shadow-red-50"
                                        title="Supprimer"
                                    >
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!posts.data.length">
                            <td colspan="5" class="px-8 py-12 text-center text-gray-500 font-medium">
                                Aucune publication trouvée. Commencez par en créer une !
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div v-if="posts.links && posts.links.length > 3" class="px-8 py-4 border-t border-gray-50 flex items-center justify-center gap-1">
                    <template v-for="(link, key) in posts.links" :key="key">
                        <Link 
                            v-if="link.url"
                            :href="link.url" 
                            v-html="link.label"
                            class="px-3 py-1 text-sm font-bold rounded-lg transition-colors"
                            :class="link.active ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-gray-500 hover:bg-gray-100'"
                        />
                        <span 
                            v-else 
                            v-html="link.label" 
                            class="px-3 py-1 text-sm font-bold text-gray-300"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
