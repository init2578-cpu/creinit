<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { 
    Bars3Icon, 
    BellIcon, 
    UserCircleIcon,
    ChevronDownIcon,
    ArrowRightOnRectangleIcon
} from '@heroicons/vue/24/outline'

const emit = defineEmits(['toggleSidebar'])
const page = usePage()

const isProfileOpen = ref(false)
const isNotificationsOpen = ref(false)
const imageError = ref(false)

function markAsRead(id) {
    // We would need a route for this, or just handle it optimistically
    // For now we'll just link to the destination
}

function markAllAsRead() {
    router.post(route('notifications.mark-all-as-read'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            isNotificationsOpen.value = false
        }
    })
}
</script>

<template>
    <!-- Click-outside backdrop -->
    <div 
        v-if="isNotificationsOpen || isProfileOpen" 
        @click="isNotificationsOpen = false; isProfileOpen = false" 
        class="fixed inset-0 z-40 bg-black/10 sm:bg-transparent"
    ></div>

    <header class="h-16 bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="px-4 sm:px-6 lg:px-8 h-full flex items-center justify-between">
            <div class="flex items-center lg:hidden">
                <button 
                    @click="emit('toggleSidebar')"
                    class="p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none"
                >
                    <Bars3Icon class="h-6 w-6" />
                </button>
            </div>

            <div class="flex-1 flex justify-end items-center gap-4">
                <!-- Notifications -->
                <div class="relative z-50">
                    <button 
                        @click.stop="isNotificationsOpen = !isNotificationsOpen; isProfileOpen = false"
                        class="p-2 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-50 focus:outline-none relative"
                    >
                        <BellIcon class="h-6 w-6" />
                        <span 
                            v-if="page.props.auth.user.unread_notifications_count > 0"
                            class="absolute top-1.5 right-1.5 block h-4 w-4 rounded-full bg-red-500 ring-2 ring-white text-[10px] text-white font-black flex items-center justify-center"
                        >
                            {{ page.props.auth.user.unread_notifications_count }}
                        </span>
                    </button>

                    <div 
                        v-if="isNotificationsOpen" 
                        class="fixed sm:absolute top-16 sm:top-auto right-2 sm:right-0 left-2 sm:left-auto mt-2 sm:mt-3 sm:w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 py-2 z-50 overflow-hidden max-w-[calc(100vw-1rem)]"
                    >
                        <div class="px-4 py-2 border-b border-gray-100/50 flex items-center justify-between">
                            <h3 class="text-xs font-black uppercase tracking-widest text-gray-400">Notifications</h3>
                            <span class="text-[9px] font-bold text-blue-500 px-1.5 py-0.5 bg-blue-50 rounded-full">
                                {{ page.props.auth.user.unread_notifications_count }} nouvelles
                            </span>
                        </div>
                        
                        <div class="max-h-[70vh] overflow-y-auto">
                            <div v-if="page.props.auth.user.unread_notifications?.length > 0">
                                <Link 
                                    v-for="notif in page.props.auth.user.unread_notifications" 
                                    :key="notif.id"
                                    :href="notif.data.action_url ? notif.data.action_url : (
                                          notif.data.type === 'nomination_proposed' ? route('nominations.index') : 
                                          notif.data.type === 'new_exam_available' ? route('student.exams.index') :
                                          notif.data.type === 'new_exercise_available' ? route('student.exercises.index') : 
                                          notif.data.type === 'exercise_graded' ? route('student.exercises.index') : 
                                          (notif.data.type === 'leave_status' || notif.data.type === 'new_leave_request') ? route('leaves.index') : '#'
                                    )"
                                    class="block px-4 py-3 hover:bg-gray-50/50 transition border-b border-gray-50 last:border-0"
                                    @click="isNotificationsOpen = false"
                                >
                                    <p class="text-[10px] font-black uppercase tracking-widest text-blue-600 mb-1">{{ notif.data.title }}</p>
                                    <p class="text-xs text-gray-700 font-medium leading-normal mb-2">{{ notif.data.message }}</p>
                                    <p class="text-[9px] text-gray-400 italic">Il y a quelques instants</p>
                                </Link>
                            </div>
                            <div v-else class="px-4 py-8 text-center">
                                <BellIcon class="h-8 w-8 text-gray-200 mx-auto mb-2" />
                                <p class="text-xs text-gray-400 font-medium italic">Aucune nouvelle notification</p>
                            </div>
                        </div>
                        
                        <div class="px-4 py-2 bg-gray-50/50 border-t border-gray-100 flex justify-center">
                            <button 
                                @click="markAllAsRead"
                                class="text-[9px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-600"
                            >
                                Tout marquer comme lu
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Profile Dropdown -->
                <div class="relative z-50">
                    <button 
                        @click.stop="isProfileOpen = !isProfileOpen; isNotificationsOpen = false"
                        class="flex items-center gap-3 p-1 rounded-full hover:bg-gray-50 transition focus:outline-none pr-3"
                    >
                        <div class="h-9 w-9 rounded-full overflow-hidden border-2 border-white shadow-sm bg-gray-100 ring-1 ring-gray-100">
                            <img v-if="page.props.auth.user.profile_photo_url && !imageError" :src="page.props.auth.user.profile_photo_url" @error="imageError = true" class="h-full w-full object-cover">
                            <UserCircleIcon v-else class="h-full w-full text-gray-300" />
                        </div>
                        <div class="hidden sm:block text-left">
                            <div class="flex items-center gap-1.5">
                                <p class="text-sm font-medium text-gray-700 leading-none">{{ page.props.auth.user.name }}</p>
                            </div>
                            <p class="text-xs text-gray-400 mt-0.5 capitalize">{{ page.props.auth.user.roles[0] }}</p>
                        </div>
                        <ChevronDownIcon class="h-4 w-4 text-gray-400" />
                    </button>

                    <div 
                        v-if="isProfileOpen" 
                        class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 py-1 transition transform origin-top-right z-50"
                    >
                        <Link :href="route('profile.edit')" @click="isProfileOpen = false" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Mon profil</Link>
                        <Link :href="route('profile.settings')" @click="isProfileOpen = false" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Paramètres</Link>
                        <hr class="my-1 border-gray-100">
                        <Link 
                            :href="route('logout')" 
                            method="post" 
                            as="button" 
                            class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                        >
                            <ArrowRightOnRectangleIcon class="h-4 w-4" />
                            Déconnexion
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
