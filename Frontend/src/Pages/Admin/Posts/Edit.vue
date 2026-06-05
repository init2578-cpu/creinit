<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { storageUrl } from '@/utils/format'
import { 
    ArrowLeftIcon,
    DocumentTextIcon,
    PhotoIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    post: Object
})

const form = useForm({
    title: props.post.title,
    excerpt: props.post.excerpt || '',
    content: props.post.content,
    image: null,
    is_published: props.post.is_published,
    _method: 'put' // For Inertia file uploads with PUT method
})

function handleImageUpload(e) {
    if (e.target.files.length > 0) {
        form.image = e.target.files[0]
    }
}

function submit() {
    form.post(route('admin.posts.update', props.post.id), {
        forceFormData: true,
    })
}
</script>

<template>
    <Head title="Modifier l'Actualité" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto py-8 px-4">
            <header class="mb-8 flex items-center justify-between">
                <div>
                    <Link :href="route('admin.posts.index')" class="inline-flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-indigo-600 transition-colors mb-2">
                        <ArrowLeftIcon class="h-4 w-4" />
                        Retour aux actualités
                    </Link>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight">Modifier la Publication</h1>
                </div>
            </header>

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Titre de l'actualité *</label>
                        <input 
                            v-model="form.title" 
                            type="text" 
                            required
                            class="w-full px-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm"
                        >
                        <p v-if="form.errors.title" class="text-xs text-red-500 mt-1.5 font-bold ml-1">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Image de couverture</label>
                        
                        <div v-if="post.image_path" class="mb-4">
                            <p class="text-xs text-gray-500 font-bold mb-2 ml-1">Image actuelle :</p>
                            <img :src="storageUrl(post.image_path)" class="h-32 rounded-xl object-cover border border-gray-200">
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="h-20 w-20 bg-gray-50 rounded-xl flex items-center justify-center border-2 border-dashed border-gray-200">
                                <PhotoIcon class="h-8 w-8 text-gray-300" />
                            </div>
                            <input 
                                type="file" 
                                @change="handleImageUpload"
                                accept="image/*"
                                class="w-full px-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-black file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                            >
                        </div>
                        <p class="text-xs text-gray-400 mt-2 ml-1 font-medium">Laissez vide pour conserver l'image actuelle.</p>
                        <p v-if="form.errors.image" class="text-xs text-red-500 mt-1.5 font-bold ml-1">{{ form.errors.image }}</p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Extrait (Court résumé)</label>
                        <textarea 
                            v-model="form.excerpt" 
                            rows="2"
                            class="w-full px-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-bold focus:ring-2 focus:ring-indigo-600 transition-all text-sm resize-none"
                        ></textarea>
                        <p v-if="form.errors.excerpt" class="text-xs text-red-500 mt-1.5 font-bold ml-1">{{ form.errors.excerpt }}</p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Contenu Complet *</label>
                        <textarea 
                            v-model="form.content" 
                            required
                            rows="10"
                            class="w-full px-6 py-4 bg-gray-50 border-0 rounded-[1.25rem] font-medium focus:ring-2 focus:ring-indigo-600 transition-all text-sm"
                        ></textarea>
                        <p v-if="form.errors.content" class="text-xs text-red-500 mt-1.5 font-bold ml-1">{{ form.errors.content }}</p>
                    </div>

                    <div class="flex items-center justify-between p-6 bg-gray-50 rounded-[1.25rem]">
                        <div>
                            <span class="block text-sm font-black text-gray-900">Publier ?</span>
                            <span class="text-xs text-gray-500 font-medium">Rendez cet article visible sur la vitrine publique.</span>
                        </div>
                        <button 
                            type="button"
                            @click="form.is_published = !form.is_published"
                            class="w-14 h-7 rounded-full transition-all duration-300 relative focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            :class="form.is_published ? 'bg-indigo-600' : 'bg-gray-300'"
                        >
                            <span 
                                class="absolute top-1 left-1 w-5 h-5 bg-white rounded-full transition-all duration-300 shadow-sm"
                                :class="form.is_published ? 'translate-x-7' : 'translate-x-0'"
                            ></span>
                        </button>
                    </div>

                    <div class="flex justify-end gap-4 pt-6 border-t border-gray-100">
                        <Link 
                            :href="route('admin.posts.index')"
                            class="px-8 py-4 bg-gray-100 text-gray-600 rounded-2xl font-black text-sm hover:bg-gray-200 transition-all"
                        >
                            Annuler
                        </Link>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="px-8 py-4 bg-indigo-600 text-white rounded-2xl font-black text-sm hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 disabled:opacity-50 flex items-center gap-2"
                        >
                            <DocumentTextIcon class="h-5 w-5" />
                            Mettre à jour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
