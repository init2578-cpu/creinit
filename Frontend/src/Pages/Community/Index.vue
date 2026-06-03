<script setup>
import { ref, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, Link, router } from '@inertiajs/vue3'
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
    FunnelIcon,
    VideoCameraIcon,
    DocumentTextIcon,
    ArrowDownTrayIcon,
    HeartIcon,
    HandThumbUpIcon,
    HandThumbDownIcon
} from '@heroicons/vue/24/outline'
import { 
    BookmarkIcon, 
    ChatBubbleLeftRightIcon, 
    HeartIcon as HeartSolid,
    HandThumbUpIcon as HandThumbUpSolid,
    HandThumbDownIcon as HandThumbDownSolid
} from '@heroicons/vue/24/solid'
import CreateAnnouncement from './Partials/CreateAnnouncement.vue'
import AttachmentViewer from './Partials/AttachmentViewer.vue'

const props = defineProps({
    announcements: Object,
    availableRoles: Array,
    canPost: Boolean
})

const isImage = (mimeType) => mimeType?.startsWith('image/')
const isVideo = (mimeType) => mimeType?.startsWith('video/')

const getFileIcon = (mimeType) => {
    if (mimeType?.includes('pdf')) return DocumentTextIcon
    if (mimeType?.includes('word') || mimeType?.includes('office')) return DocumentTextIcon
    return DocumentTextIcon
}

const formatSize = (bytes) => {
    if (bytes === 0) return '0 B'
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

const showCreateModal = ref(false)
const editingAnnouncement = ref(null)
const viewingAttachment = ref(null)

const openViewer = (attachment) => {
    viewingAttachment.value = attachment
}

const closeViewer = () => {
    viewingAttachment.value = null
}

const openEditModal = (announcement) => {
    editingAnnouncement.value = announcement
    showCreateModal.value = true
}

const handleModalClose = () => {
    showCreateModal.value = false
    editingAnnouncement.value = null
}

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

const replies = ref({})

const submitReply = (announcementId) => {
    if (!replies.value[announcementId]?.trim()) return
    
    router.post(route('community.replies.store', announcementId), {
        content: replies.value[announcementId]
    }, {
        preserveScroll: true,
        onSuccess: () => {
            replies.value[announcementId] = ''
        }
    })
}

const deleteReply = (replyId) => {
    if (confirm('Voulez-vous supprimer cette réponse ?')) {
        router.delete(route('community.replies.destroy', replyId), { preserveScroll: true })
    }
}

const toggleLike = (announcementId, type = 'heart') => {
    router.post(route('community.likes.toggle', announcementId), { type }, { preserveScroll: true })
}
</script>

<template>
    <Head title="Communauté" />

    <AuthenticatedLayout>
        <div class="py-8 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header Section -->
            <div class="flex items-center justify-between mb-8 pb-6 border-b border-gray-100">
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
                    @click="showCreateModal = true; editingAnnouncement = null"
                    class="inline-flex items-center px-5 py-2.5 bg-blue-600 text-white rounded-2xl text-[11px] font-bold uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg shadow-blue-200 group"
                >
                    <PlusIcon class="h-4 w-4 mr-2 group-hover:rotate-90 transition-transform" />
                    Nouveau message
                </button>
            </div>

            <!-- Create / Edit Modal -->
            <CreateAnnouncement 
                :show="showCreateModal" 
                :roles="availableRoles"
                :announcement="editingAnnouncement"
                @close="handleModalClose" 
            />

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

                            <!-- Attachments (Pinned) -->
                            <div v-if="announcement.attachments && announcement.attachments.length > 0" class="mb-6 space-y-4">
                                <div v-if="announcement.attachments.some(a => isImage(a.mime_type))" class="flex flex-wrap gap-2">
                                    <div v-for="img in announcement.attachments.filter(a => isImage(a.mime_type))" 
                                        :key="img.path" 
                                        @click="openViewer(img)"
                                        class="h-28 w-28 rounded-xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-md transition-shadow cursor-pointer group"
                                    >
                                        <img :src="'/storage/' + img.path" class="h-full w-full object-cover group-hover:scale-110 transition-transform duration-500">
                                    </div>
                                </div>
                                <div v-if="announcement.attachments.some(a => isVideo(a.mime_type))" class="space-y-2">
                                    <div v-for="vid in announcement.attachments.filter(a => isVideo(a.mime_type))" 
                                        :key="vid.path" 
                                        @click="openViewer(vid)"
                                        class="max-w-md rounded-2xl overflow-hidden border border-gray-200 bg-black shadow-sm cursor-pointer group relative"
                                    >
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/20 group-hover:bg-black/40 transition-colors z-10">
                                            <div class="h-12 w-12 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center transform group-hover:scale-110 transition-transform">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <video class="w-full h-auto max-h-[300px]">
                                            <source :src="'/storage/' + vid.path" :type="vid.mime_type">
                                        </video>
                                    </div>
                                </div>
                                <div v-if="announcement.attachments.some(a => !isImage(a.mime_type) && !isVideo(a.mime_type))" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <a v-for="doc in announcement.attachments.filter(a => !isImage(a.mime_type) && !isVideo(a.mime_type))" :key="doc.path" :href="'/storage/' + doc.path" target="_blank" download class="flex items-center p-3 rounded-xl border border-gray-100 bg-gray-50/50 hover:bg-white hover:border-blue-200 hover:shadow-sm transition-all group">
                                        <div class="h-10 w-10 flex-shrink-0 bg-white rounded-lg flex items-center justify-center border border-gray-100 group-hover:border-blue-100 mr-3">
                                            <component :is="getFileIcon(doc.mime_type)" class="h-5 w-5 text-gray-500 group-hover:text-blue-500" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-bold text-gray-700 truncate group-hover:text-blue-700">{{ doc.name }}</p>
                                            <p class="text-[10px] text-gray-400 font-medium tracking-tighter">{{ formatSize(doc.size) }} • {{ doc.mime_type.split('/')[1] }}</p>
                                        </div>
                                        <ArrowDownTrayIcon class="h-4 w-4 text-gray-300 group-hover:text-blue-500" />
                                    </a>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between text-[11px] text-gray-400 font-medium">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-1.5">
                                        <div class="h-5 w-5 rounded-full overflow-hidden border border-gray-200">
                                            <template v-if="announcement.user">
                                                <img v-if="announcement.user.profile_photo_url" :src="announcement.user.profile_photo_url" class="h-full w-full object-cover">
                                                <UserIcon v-else class="h-full w-full p-1 text-gray-300" />
                                            </template>
                                            <template v-else>
                                                <div class="h-full w-full bg-gray-100 flex items-center justify-center">
                                                    <UserIcon class="h-3 w-3 text-gray-400" />
                                                </div>
                                            </template>
                                        </div>
                                        <span :class="announcement.is_anonymous ? 'text-indigo-600 italic' : 'text-gray-600'">
                                            {{ announcement.user ? announcement.user.name + (announcement.is_anonymous ? ' (Anonyme)' : '') : 'Utilisateur Anonyme' }}
                                        </span>
                                    </div>
                                    <span>•</span>
                                    <span>{{ formatDate(announcement.created_at) }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-1.5 p-1 bg-gray-50 rounded-xl border border-gray-100 shadow-sm">
                                        <button
                                            @click="toggleLike(announcement.id, 'heart')"
                                            class="flex items-center gap-1 p-1 rounded-lg transition-all"
                                            :class="announcement.user_reaction === 'heart' ? 'text-pink-500 bg-pink-50 shadow-sm' : 'text-gray-400 hover:text-pink-400 hover:bg-pink-50/50'"
                                        >
                                            <component :is="announcement.user_reaction === 'heart' ? HeartSolid : HeartIcon" class="h-4 w-4" />
                                            <span class="text-[10px] font-bold">{{ announcement.heart_count || 0 }}</span>
                                        </button>
                                        <div class="w-px h-3 bg-gray-200"></div>
                                        <button
                                            @click="toggleLike(announcement.id, 'thumb_up')"
                                            class="flex items-center gap-1 p-1 rounded-lg transition-all"
                                            :class="announcement.user_reaction === 'thumb_up' ? 'text-blue-500 bg-blue-50 shadow-sm' : 'text-gray-400 hover:text-blue-400 hover:bg-blue-50/50'"
                                        >
                                            <component :is="announcement.user_reaction === 'thumb_up' ? HandThumbUpSolid : HandThumbUpIcon" class="h-4 w-4" />
                                            <span class="text-[10px] font-bold">{{ announcement.thumb_up_count || 0 }}</span>
                                        </button>
                                        <div class="w-px h-3 bg-gray-200"></div>
                                        <button
                                            @click="toggleLike(announcement.id, 'thumb_down')"
                                            class="flex items-center gap-1 p-1 rounded-lg transition-all"
                                            :class="announcement.user_reaction === 'thumb_down' ? 'text-amber-600 bg-amber-50 shadow-sm' : 'text-gray-400 hover:text-amber-500 hover:bg-amber-50/50'"
                                        >
                                            <component :is="announcement.user_reaction === 'thumb_down' ? HandThumbDownSolid : HandThumbDownIcon" class="h-4 w-4" />
                                            <span class="text-[10px] font-bold">{{ announcement.thumb_down_count || 0 }}</span>
                                        </button>
                                    </div>
                                    <button 
                                        v-if="$page.props.auth.user.id === announcement.user_id || $page.props.auth.user.roles.includes('Directeur')"
                                        @click="openEditModal(announcement)"
                                        class="text-blue-500 hover:text-blue-700 transition-colors mr-3"
                                    >
                                        Modifier
                                    </button>
                                    <button 
                                        v-if="$page.props.auth.user.id === announcement.user_id || $page.props.auth.user.roles.includes('Directeur')"
                                        @click="deleteAnnouncement(announcement.id)"
                                        class="text-red-400 hover:text-red-600 transition-colors"
                                    >
                                        Supprimer
                                    </button>
                                </div>
                            </div>

                            <!-- Replies Section (Pinned) -->
                            <div class="mt-6 border-t border-gray-100 pt-4">
                                <h5 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">Réponses ({{ announcement.replies?.length || 0 }})</h5>
                                
                                <div v-if="announcement.replies?.length > 0" class="space-y-4 mb-4">
                                <div v-for="reply in announcement.replies" :key="reply.id"
                                     class="flex gap-3 p-4 rounded-2xl border transition-colors"
                                     :class="reply.user_id === $page.props.auth.user.id
                                         ? 'bg-indigo-50/60 border-indigo-200 border-l-4 border-l-indigo-400'
                                         : 'bg-gray-50/50 border-gray-100'"
                                >
                                        <div class="h-8 w-8 rounded-full overflow-hidden flex-shrink-0 border border-gray-200">
                                            <template v-if="reply.user">
                                                <img v-if="reply.user.profile_photo_url" :src="reply.user.profile_photo_url" class="h-full w-full object-cover">
                                                <UserIcon v-else class="h-full w-full p-1 text-gray-300" />
                                            </template>
                                            <template v-else>
                                                <div class="h-full w-full bg-gray-100 flex items-center justify-center">
                                                    <UserIcon class="h-3 w-3 text-gray-400" />
                                                </div>
                                            </template>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] font-bold text-gray-700">{{ reply.user ? reply.user.name : 'Utilisateur Anonyme' }}</span>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] text-gray-400 font-medium">{{ formatDate(reply.created_at) }}</span>
                                                    <button 
                                                        v-if="$page.props.auth.user.id === reply.user_id || $page.props.auth.user.roles.includes('Directeur')"
                                                        @click="deleteReply(reply.id)"
                                                        class="text-red-400 hover:text-red-600 transition-colors"
                                                    >
                                                        <TrashIcon class="h-3 w-3" />
                                                    </button>
                                                </div>
                                            </div>
                                            <p class="text-xs text-gray-600 mt-1 whitespace-pre-wrap leading-relaxed">{{ reply.content }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex gap-3 mt-4">
                                    <div class="h-10 w-10 rounded-full overflow-hidden flex-shrink-0 border border-gray-200 bg-gray-100 flex items-center justify-center">
                                        <template v-if="$page.props.auth.user.profile_photo_url">
                                            <img :src="$page.props.auth.user.profile_photo_url" class="h-full w-full object-cover">
                                        </template>
                                        <template v-else>
                                            <UserIcon class="h-5 w-5 text-gray-400" />
                                        </template>
                                    </div>
                                    <div class="flex-1 flex gap-2">
                                        <input 
                                            v-model="replies[announcement.id]"
                                            @keyup.enter="submitReply(announcement.id)"
                                            type="text" 
                                            placeholder="Écrire une réponse..."
                                            class="flex-1 rounded-2xl border-gray-100 bg-gray-50/50 px-4 py-2 text-xs focus:ring-blue-500 focus:border-blue-500 transition-all placeholder-gray-400 font-medium"
                                        />
                                        <button 
                                            @click="submitReply(announcement.id)"
                                            :disabled="!replies[announcement.id]?.trim()"
                                            class="px-5 py-2 bg-blue-600 text-white rounded-2xl text-[11px] font-bold uppercase tracking-wider hover:bg-blue-700 transition-all shadow-md shadow-blue-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Répondre
                                        </button>
                                    </div>
                                </div>
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

                            <!-- Attachments (Normal) -->
                            <div v-if="announcement.attachments && announcement.attachments.length > 0" class="mb-6 space-y-4">
                                <div v-if="announcement.attachments.some(a => isImage(a.mime_type))" class="flex flex-wrap gap-2">
                                    <div v-for="img in announcement.attachments.filter(a => isImage(a.mime_type))" 
                                        :key="img.path" 
                                        @click="openViewer(img)"
                                        class="h-28 w-28 rounded-xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-md transition-shadow cursor-pointer group"
                                    >
                                        <img :src="'/storage/' + img.path" class="h-full w-full object-cover group-hover:scale-110 transition-transform duration-500">
                                    </div>
                                </div>
                                <div v-if="announcement.attachments.some(a => isVideo(a.mime_type))" class="space-y-2">
                                    <div v-for="vid in announcement.attachments.filter(a => isVideo(a.mime_type))" 
                                        :key="vid.path" 
                                        @click="openViewer(vid)"
                                        class="max-w-md rounded-2xl overflow-hidden border border-gray-200 bg-black shadow-sm cursor-pointer group relative"
                                    >
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/20 group-hover:bg-black/40 transition-colors z-10">
                                            <div class="h-12 w-12 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center transform group-hover:scale-110 transition-transform">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <video class="w-full h-auto max-h-[300px]">
                                            <source :src="'/storage/' + vid.path" :type="vid.mime_type">
                                        </video>
                                    </div>
                                </div>
                                <div v-if="announcement.attachments.some(a => !isImage(a.mime_type) && !isVideo(a.mime_type))" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <a v-for="doc in announcement.attachments.filter(a => !isImage(a.mime_type) && !isVideo(a.mime_type))" :key="doc.path" :href="'/storage/' + doc.path" target="_blank" download class="flex items-center p-3 rounded-xl border border-gray-100 bg-gray-50/50 hover:bg-white hover:border-blue-200 hover:shadow-sm transition-all group">
                                        <div class="h-10 w-10 flex-shrink-0 bg-white rounded-lg flex items-center justify-center border border-gray-100 group-hover:border-blue-100 mr-3">
                                            <component :is="getFileIcon(doc.mime_type)" class="h-5 w-5 text-gray-500 group-hover:text-blue-500" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-bold text-gray-700 truncate group-hover:text-blue-700">{{ doc.name }}</p>
                                            <p class="text-[10px] text-gray-400 font-medium tracking-tighter">{{ formatSize(doc.size) }} • {{ doc.mime_type.split('/')[1] }}</p>
                                        </div>
                                        <ArrowDownTrayIcon class="h-4 w-4 text-gray-300 group-hover:text-blue-500" />
                                    </a>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between text-[11px] text-gray-400 font-medium">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-1.5">
                                        <div class="h-5 w-5 rounded-full overflow-hidden border border-gray-200">
                                            <template v-if="announcement.user">
                                                <img v-if="announcement.user.profile_photo_url" :src="announcement.user.profile_photo_url" class="h-full w-full object-cover">
                                                <UserIcon v-else class="h-full w-full p-1 text-gray-300" />
                                            </template>
                                            <template v-else>
                                                <div class="h-full w-full bg-gray-100 flex items-center justify-center">
                                                    <UserIcon class="h-3 w-3 text-gray-400" />
                                                </div>
                                            </template>
                                        </div>
                                        <span :class="announcement.is_anonymous ? 'text-indigo-600 italic font-bold' : 'text-gray-600'">
                                            {{ announcement.user ? announcement.user.name + (announcement.is_anonymous ? ' (Anonyme)' : '') : 'Utilisateur Anonyme' }}
                                        </span>
                                    </div>
                                    <span>•</span>
                                    <span>{{ formatDate(announcement.created_at) }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-1.5 p-1 bg-gray-50 rounded-xl border border-gray-100 shadow-sm">
                                        <button
                                            @click="toggleLike(announcement.id, 'heart')"
                                            class="flex items-center gap-1 p-1 rounded-lg transition-all"
                                            :class="announcement.user_reaction === 'heart' ? 'text-pink-500 bg-pink-50 shadow-sm' : 'text-gray-400 hover:text-pink-400 hover:bg-pink-50/50'"
                                        >
                                            <component :is="announcement.user_reaction === 'heart' ? HeartSolid : HeartIcon" class="h-4 w-4" />
                                            <span class="text-[10px] font-bold">{{ announcement.heart_count || 0 }}</span>
                                        </button>
                                        <div class="w-px h-3 bg-gray-200"></div>
                                        <button
                                            @click="toggleLike(announcement.id, 'thumb_up')"
                                            class="flex items-center gap-1 p-1 rounded-lg transition-all"
                                            :class="announcement.user_reaction === 'thumb_up' ? 'text-blue-500 bg-blue-50 shadow-sm' : 'text-gray-400 hover:text-blue-400 hover:bg-blue-50/50'"
                                        >
                                            <component :is="announcement.user_reaction === 'thumb_up' ? HandThumbUpSolid : HandThumbUpIcon" class="h-4 w-4" />
                                            <span class="text-[10px] font-bold">{{ announcement.thumb_up_count || 0 }}</span>
                                        </button>
                                        <div class="w-px h-3 bg-gray-200"></div>
                                        <button
                                            @click="toggleLike(announcement.id, 'thumb_down')"
                                            class="flex items-center gap-1 p-1 rounded-lg transition-all"
                                            :class="announcement.user_reaction === 'thumb_down' ? 'text-amber-600 bg-amber-50 shadow-sm' : 'text-gray-400 hover:text-amber-500 hover:bg-amber-50/50'"
                                        >
                                            <component :is="announcement.user_reaction === 'thumb_down' ? HandThumbDownSolid : HandThumbDownIcon" class="h-4 w-4" />
                                            <span class="text-[10px] font-bold">{{ announcement.thumb_down_count || 0 }}</span>
                                        </button>
                                    </div>
                                    <button 
                                        v-if="$page.props.auth.user.id === announcement.user_id || $page.props.auth.user.roles.includes('Directeur')"
                                        @click="openEditModal(announcement)"
                                        class="text-blue-500 hover:text-blue-700 transition-colors mr-3"
                                    >
                                        Modifier
                                    </button>
                                    <button 
                                        v-if="$page.props.auth.user.id === announcement.user_id || $page.props.auth.user.roles.includes('Directeur')"
                                        @click="deleteAnnouncement(announcement.id)"
                                        class="text-red-400 hover:text-red-600 transition-colors"
                                    >
                                        Supprimer
                                    </button>
                                </div>
                            </div>

                            <!-- Replies Section (Normal) -->
                            <div class="mt-6 border-t border-gray-100 pt-4">
                                <h5 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">Réponses ({{ announcement.replies?.length || 0 }})</h5>
                                
                                <div v-if="announcement.replies?.length > 0" class="space-y-4 mb-4">
                                <div v-for="reply in announcement.replies" :key="reply.id"
                                     class="flex gap-3 p-4 rounded-2xl border transition-colors"
                                     :class="reply.user_id === $page.props.auth.user.id
                                         ? 'bg-indigo-50/60 border-indigo-200 border-l-4 border-l-indigo-400'
                                         : 'bg-gray-50/50 border-gray-100'"
                                >
                                        <div class="h-8 w-8 rounded-full overflow-hidden flex-shrink-0 border border-gray-200">
                                            <template v-if="reply.user">
                                                <img v-if="reply.user.profile_photo_url" :src="reply.user.profile_photo_url" class="h-full w-full object-cover">
                                                <UserIcon v-else class="h-full w-full p-1 text-gray-300" />
                                            </template>
                                            <template v-else>
                                                <div class="h-full w-full bg-gray-100 flex items-center justify-center">
                                                    <UserIcon class="h-3 w-3 text-gray-400" />
                                                </div>
                                            </template>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[11px] font-bold text-gray-700">{{ reply.user ? reply.user.name : 'Utilisateur Anonyme' }}</span>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] text-gray-400 font-medium">{{ formatDate(reply.created_at) }}</span>
                                                    <button 
                                                        v-if="$page.props.auth.user.id === reply.user_id || $page.props.auth.user.roles.includes('Directeur')"
                                                        @click="deleteReply(reply.id)"
                                                        class="text-red-400 hover:text-red-600 transition-colors"
                                                    >
                                                        <TrashIcon class="h-3 w-3" />
                                                    </button>
                                                </div>
                                            </div>
                                            <p class="text-xs text-gray-600 mt-1 whitespace-pre-wrap leading-relaxed">{{ reply.content }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex gap-3 mt-4">
                                    <div class="h-10 w-10 rounded-full overflow-hidden flex-shrink-0 border border-gray-200 bg-gray-100 flex items-center justify-center">
                                        <template v-if="$page.props.auth.user.profile_photo_url">
                                            <img :src="$page.props.auth.user.profile_photo_url" class="h-full w-full object-cover">
                                        </template>
                                        <template v-else>
                                            <UserIcon class="h-5 w-5 text-gray-400" />
                                        </template>
                                    </div>
                                    <div class="flex-1 flex gap-2">
                                        <input 
                                            v-model="replies[announcement.id]"
                                            @keyup.enter="submitReply(announcement.id)"
                                            type="text" 
                                            placeholder="Écrire une réponse..."
                                            class="flex-1 rounded-2xl border-gray-100 bg-gray-50/50 px-4 py-2 text-xs focus:ring-blue-500 focus:border-blue-500 transition-all placeholder-gray-400 font-medium"
                                        />
                                        <button 
                                            @click="submitReply(announcement.id)"
                                            :disabled="!replies[announcement.id]?.trim()"
                                            class="px-5 py-2 bg-blue-600 text-white rounded-2xl text-[11px] font-bold uppercase tracking-wider hover:bg-blue-700 transition-all shadow-md shadow-blue-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Répondre
                                        </button>
                                    </div>
                                </div>
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



        <!-- Attachment Viewer Lightbox -->
        <AttachmentViewer 
            :show="!!viewingAttachment" 
            :attachment="viewingAttachment" 
            @close="closeViewer" 
        />
    </AuthenticatedLayout>
</template>

<style scoped>
.whitespace-pre-wrap {
    word-break: break-word;
}
</style>
