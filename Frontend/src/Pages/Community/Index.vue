<script setup>
import { ref, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { 
    PlusIcon, 
    MegaphoneIcon, 
    BellIcon, 
    ExclamationTriangleIcon, 
    CheckCircleIcon,
    TrashIcon,
    MapPinIcon,
    CalendarIcon,
    UserIcon,
    FunnelIcon
} from '@heroicons/vue/24/outline'
import { BookmarkIcon } from '@heroicons/vue/24/solid'
import CreateAnnouncement from './Partials/CreateAnnouncement.vue'

const props = defineProps({
    announcements: Object,
    availableRoles: Array,
    canPost: Boolean
})

const showCreateModal = ref(false)

const getCategoryIcon = (category) => {
    switch (category) {
        case 'warning': return ExclamationTriangleIcon
        case 'event': return CalendarIcon
        case 'success': return CheckCircleIcon
        default: return MegaphoneIcon
    }
}

const getCategoryColor = (category) => {
    switch (category) {
        case 'warning': return 'text-amber-600 bg-amber-50'
        case 'event': return 'text-purple-600 bg-purple-50'
        case 'success': return 'text-emerald-600 bg-emerald-50'
        default: return 'text-blue-600 bg-blue-50'
    }
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const deleteForm = useForm({})

const deleteAnnouncement = (id) => {
    if (confirm('Voulez-vous vraiment supprimer ce message ?')) {
        deleteForm.delete(route('community.destroy', id))
    }
}
</script>

<template>
    <Head title="Communauté" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-600 rounded-xl shadow-lg shadow-blue-200">
                        <ChatBubbleLeftRightIcon class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 leading-tight">Espace Communauté</h2>
                        <p class="text-xs font-medium text-gray-500">Échanges et informations partagées de CRE iNiT</p>
                    </div>
                </div>
                
                <button 
                    v-if="canPost"
                    @click="showCreateModal = true"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-md shadow-blue-200"
                >
                    <PlusIcon class="h-4 w-4 mr-2" />
                    Nouveau Message
                </button>
            </div>
        </template>

        <div class="py-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- pinned announcements -->
            <div v-if="announcements.data.some(a => a.is_pinned)" class="mb-8 space-y-4">
                <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest px-2">Messages Épinglés</h3>
                <div 
                    v-for="announcement in announcements.data.filter(a => a.is_pinned)" 
                    :key="announcement.id"
                    class="bg-white rounded-2xl shadow-sm border-2 border-blue-100 p-6 relative overflow-hidden"
                >
                    <div class="absolute top-0 right-0 p-4">
                        <BookmarkIcon class="h-5 w-5 text-blue-500" />
                    </div>
                    
                    <div class="flex gap-4">
                        <div :class="['h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0', getCategoryColor(announcement.category)]">
                            <component :is="getCategoryIcon(announcement.category)" class="h-6 w-6" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="text-lg font-bold text-gray-900">{{ announcement.title }}</h4>
                            </div>
                            <p class="text-gray-600 text-sm mb-4 whitespace-pre-wrap leading-relaxed">{{ announcement.content }}</p>
                            
                            <div class="flex items-center justify-between text-[11px] text-gray-400 font-medium">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-1.5">
                                        <div class="h-5 w-5 rounded-full overflow-hidden border border-gray-200">
                                            <img v-if="announcement.user.profile_photo_url" :src="announcement.user.profile_photo_url" class="h-full w-full object-cover">
                                            <UserIcon v-else class="h-full w-full p-1 text-gray-300" />
                                        </div>
                                        <span>{{ announcement.user.name }}</span>
                                    </div>
                                    <span>•</span>
                                    <span>{{ formatDate(announcement.created_at) }}</span>
                                </div>
                                <button 
                                    v-if="$page.props.auth.user.id === announcement.user_id || $page.props.auth.user.roles.includes('Directeur')"
                                    @click="deleteAnnouncement(announcement.id)"
                                    class="text-red-400 hover:text-red-600 transition-colors"
                                >
                                    Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- main feed -->
            <div class="space-y-6">
                <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest px-2">Fil d'actualité</h3>
                
                <div v-if="announcements.data.filter(a => !a.is_pinned).length === 0" class="bg-gray-50 rounded-2xl p-12 text-center border-2 border-dashed border-gray-200">
                    <MegaphoneIcon class="h-12 w-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500 font-medium">Aucun message pour le moment</p>
                </div>

                <div 
                    v-for="announcement in announcements.data.filter(a => !a.is_pinned)" 
                    :key="announcement.id"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow duration-300"
                >
                    <div class="flex gap-4">
                        <div :class="['h-12 w-12 rounded-xl flex items-center justify-center flex-shrink-0', getCategoryColor(announcement.category)]">
                            <component :is="getCategoryIcon(announcement.category)" class="h-6 w-6" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-lg font-bold text-gray-900 mb-1">{{ announcement.title }}</h4>
                            <p class="text-gray-600 text-sm mb-4 whitespace-pre-wrap leading-relaxed">{{ announcement.content }}</p>
                            
                            <div class="flex items-center justify-between text-[11px] text-gray-400 font-medium">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-1.5">
                                        <div class="h-5 w-5 rounded-full overflow-hidden border border-gray-200">
                                            <img v-if="announcement.user.profile_photo_url" :src="announcement.user.profile_photo_url" class="h-full w-full object-cover">
                                            <UserIcon v-else class="h-full w-full p-1 text-gray-300" />
                                        </div>
                                        <span class="text-gray-600">{{ announcement.user.name }}</span>
                                    </div>
                                    <span>•</span>
                                    <span>{{ formatDate(announcement.created_at) }}</span>
                                </div>
                                <button 
                                    v-if="$page.props.auth.user.id === announcement.user_id || $page.props.auth.user.roles.includes('Directeur')"
                                    @click="deleteAnnouncement(announcement.id)"
                                    class="text-red-400 hover:text-red-600 transition-colors"
                                >
                                    Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="announcements.links.length > 3" class="flex justify-center mt-8">
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, k) in announcements.links"
                            :key="k"
                            :href="link.url || '#'"
                            class="px-3 py-1 text-sm font-medium rounded-lg transition-colors"
                            :class="[
                                link.active ? 'bg-blue-600 text-white' : 'text-gray-500 hover:bg-gray-100',
                                !link.url ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <CreateAnnouncement 
            v-if="showCreateModal" 
            :show="showCreateModal" 
            :roles="availableRoles"
            @close="showCreateModal = false" 
        />
    </AuthenticatedLayout>
</template>

<style scoped>
.whitespace-pre-wrap {
    word-break: break-word;
}
</style>
