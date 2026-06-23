<script setup>
import { ref, computed, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { 
    HomeIcon, 
    ChartBarIcon, 
    UsersIcon, 
    GlobeAltIcon, 
    AcademicCapIcon, 
    CheckBadgeIcon, 
    CalendarIcon, 
    UserGroupIcon, 
    ClipboardDocumentCheckIcon, 
    BriefcaseIcon, 
    DocumentChartBarIcon,
    BookOpenIcon, 
    PencilSquareIcon,
    BeakerIcon,
    ArchiveBoxIcon,
    HomeModernIcon,
    Cog6ToothIcon,
    UserIcon,
    ChatBubbleLeftRightIcon,
    EnvelopeIcon,
    NewspaperIcon,
    ShieldCheckIcon,
    ChevronDownIcon,
    ChevronRightIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    isOpen: Boolean
})

const page = usePage()
const roles = computed(() => page.props.auth.user.roles)

// Helper to determine if a URL matches the current page URL (supports full URLs and relative paths)
const isUrl = (url) => {
    if (!url) return false
    const path = url.replace(/^https?:\/\/[^\/]+/, '')
    if (path === '/') return page.url === '/'
    return page.url === path || page.url.startsWith(path + '/')
}

// -----------------------------------------------------------------------
// Navigation Definition Grouped by Section
// -----------------------------------------------------------------------

const navigation = computed(() => {
    let sections = []

    if (roles.value.includes('Directeur')) {
        sections.push(
            {
                title: 'Principal',
                items: [
                    { name: 'Tableau de Bord', href: route('dashboard.director'), icon: HomeIcon },
                    { name: 'Communauté', href: route('community.index'), icon: ChatBubbleLeftRightIcon, badge: page.props.auth.user?.unread_announcements_count },
                    { name: 'Actualités (Vitrine)', href: route('admin.posts.index'), icon: NewspaperIcon },
                    { name: 'Messages Contact', href: route('contact-messages.index'), icon: EnvelopeIcon, badge: page.props.auth.user?.unread_messages_count },
                ]
            },
            {
                title: 'Scolarité & Pédagogie',
                items: [
                    { name: 'Inscriptions', href: route('applications.index'), icon: UserGroupIcon },
                    { name: 'Groupes de Formation', href: route('groups.index'), icon: UserGroupIcon },
                    { name: 'Validations', href: route('nominations.index'), icon: CheckBadgeIcon, badge: page.props.auth.user?.pending_nominations_count },
                    { name: 'Apprenants', href: route('students.index'), icon: AcademicCapIcon },
                    { name: 'Stagiaires', href: route('trainees.index'), icon: BriefcaseIcon },
                    { name: 'Emplois du temps', href: route('schedules.index'), icon: CalendarIcon },
                    { name: 'Émargement', href: route('attendance.index'), icon: ClipboardDocumentCheckIcon },
                    { name: 'Attestations', href: route('certificates.index'), icon: CheckBadgeIcon },
                    { name: 'Espace Cours (Aperçu)', href: route('student.courses'), icon: AcademicCapIcon },
                ]
            },
            {
                title: 'Évaluations',
                items: [
                    { name: 'Examens', href: route('exams.index'), icon: PencilSquareIcon },
                    { name: 'Exercices (Correction)', href: route('exercises.index'), icon: ClipboardDocumentCheckIcon },
                ]
            },
            {
                title: 'Logistique & Salles',
                items: [
                    { name: 'Gestion des Salles', href: route('rooms.index'), icon: HomeModernIcon },
                    { name: 'Inventaire Matériel', href: route('assets.index'), icon: ArchiveBoxIcon },
                    { name: 'Prêts & Retours', href: route('loans.index'), icon: BriefcaseIcon },
                ]
            },
            {
                title: 'Administration',
                items: [
                    { name: 'Gestion des Formations', href: route('modules.index'), icon: BookOpenIcon },
                    { name: 'Congés', href: route('leaves.index'), icon: CalendarIcon },
                    { name: 'Rayonnement', href: route('ecosystem.index'), icon: GlobeAltIcon },
                    { name: 'Utilisateurs', href: route('users.index'), icon: UsersIcon },
                ]
            },
            {
                title: 'Analyses & Système',
                items: [
                    { name: 'Statistiques', href: route('stats.index'), icon: ChartBarIcon },
                    { name: 'Rapports', href: route('reports.index'), icon: DocumentChartBarIcon },
                    { name: 'Paramètres', href: route('settings.index'), icon: Cog6ToothIcon },
                    { name: 'Audit', href: route('audit.index'), icon: ShieldCheckIcon },
                ]
            }
        )
    } else if (roles.value.includes('Formateur') || page.props.auth.user?.is_trainer) {
        sections.push(
            {
                title: 'Principal',
                items: [
                    { name: 'Mes Groupes', href: route('trainer.groups'), icon: AcademicCapIcon },
                    { name: 'Communauté', href: route('community.index'), icon: ChatBubbleLeftRightIcon, badge: page.props.auth.user?.unread_announcements_count },
                    { name: 'Aperçu Plateforme', href: route('student.courses'), icon: AcademicCapIcon },
                ]
            },
            {
                title: 'Pédagogie & Suivi',
                items: [
                    { name: 'Émargement', href: route('attendances.trainer-groups'), icon: ClipboardDocumentCheckIcon },
                    { name: 'Gestion des Cours', href: route('modules.index'), icon: BookOpenIcon },
                    { name: 'Progression', href: route('chapter-progress.groups'), icon: ChartBarIcon, badge: page.props.auth.user?.unread_rejections_count },
                    { name: 'Emploi du Temps', href: route('schedules.index'), icon: CalendarIcon },
                ]
            },
            {
                title: 'Évaluations',
                items: [
                    { name: 'Examens', href: route('exams.index'), icon: PencilSquareIcon },
                    { name: 'Exercices (Correction)', href: route('exercises.index'), icon: ClipboardDocumentCheckIcon },
                ]
            },
            {
                title: 'Autres',
                items: [
                    { name: 'Inventaire Matériel', href: route('assets.index'), icon: ArchiveBoxIcon },
                    { name: 'Congés', href: route('leaves.index'), icon: CalendarIcon },
                ]
            }
        )
    } else if (roles.value.includes('Secrétaire')) {
        sections.push(
            {
                title: 'Principal',
                items: [
                    { name: 'Tableau de Bord', href: route('dashboard.director'), icon: HomeIcon },
                    { name: 'Communauté', href: route('community.index'), icon: ChatBubbleLeftRightIcon, badge: page.props.auth.user?.unread_announcements_count },
                    { name: 'Actualités (Vitrine)', href: route('admin.posts.index'), icon: NewspaperIcon },
                    { name: 'Messages Contact', href: route('contact-messages.index'), icon: EnvelopeIcon, badge: page.props.auth.user?.unread_messages_count },
                ]
            },
            {
                title: 'Scolarité & Pédagogie',
                items: [
                    { name: 'Inscriptions', href: route('applications.index'), icon: UserGroupIcon },
                    { name: 'Groupes de Formation', href: route('groups.index'), icon: UserGroupIcon },
                    { name: 'Validations', href: route('nominations.index'), icon: CheckBadgeIcon, badge: page.props.auth.user?.pending_nominations_count },
                    { name: 'Emplois du temps', href: route('schedules.index'), icon: CalendarIcon },
                    { name: 'Émargement', href: route('attendance.index'), icon: ClipboardDocumentCheckIcon },
                ]
            },
            {
                title: 'Évaluations',
                items: [
                    { name: 'Examens', href: route('exams.index'), icon: PencilSquareIcon },
                    { name: 'Exercices (Correction)', href: route('exercises.index'), icon: ClipboardDocumentCheckIcon },
                ]
            },
            {
                title: 'Logistique & Salles',
                items: [
                    { name: 'Gestion des Salles', href: route('rooms.index'), icon: HomeModernIcon },
                    { name: 'Inventaire Matériel', href: route('assets.index'), icon: ArchiveBoxIcon },
                    { name: 'Prêts & Retours', href: route('loans.index'), icon: BriefcaseIcon },
                ]
            },
            {
                title: 'Administration',
                items: [
                    { name: 'Congés', href: route('leaves.index'), icon: CalendarIcon },
                    { name: 'Rayonnement', href: route('ecosystem.index'), icon: GlobeAltIcon },
                    { name: 'Rapports', href: route('reports.index'), icon: DocumentChartBarIcon },
                ]
            }
        )
    } else if (roles.value.includes('Apprenant') || roles.value.includes('Stagiaire')) {
        sections.push(
            {
                title: 'Principal',
                items: [
                    { name: 'Mon Parcours', href: route('student.dashboard'), icon: AcademicCapIcon },
                    { name: 'Communauté', href: route('community.index'), icon: ChatBubbleLeftRightIcon, badge: page.props.auth.user?.unread_announcements_count },
                ]
            },
            {
                title: 'Cours & Suivi',
                items: [
                    { name: 'Mes Cours', href: route('student.courses'), icon: BookOpenIcon },
                    { name: 'Validation Progression', href: route('chapter-progress.groups'), icon: ChartBarIcon, badge: page.props.auth.user?.unread_progressions_count }
                ]
            },
            {
                title: 'Évaluations',
                items: [
                    { name: 'Exercices', href: route('student.exercises.index'), icon: BeakerIcon, badge: page.props.auth.user?.unread_exercises_count },
                    { name: 'Examens', href: route('student.exams.index'), icon: PencilSquareIcon, badge: page.props.auth.user?.unread_exams_count },
                ]
            }
        )
    }

    return sections
})

// Collapsible accordion states
const collapsedSections = ref({})

const updateCollapsedStates = () => {
    navigation.value.forEach(section => {
        const hasActiveItem = section.items.some(item => isUrl(item.href))
        if (hasActiveItem) {
            collapsedSections.value[section.title] = false
        } else if (collapsedSections.value[section.title] === undefined) {
            collapsedSections.value[section.title] = true
        }
    })
}

// Initialize collapsed states and update when page URL changes
watch(() => page.url, updateCollapsedStates, { immediate: true })

const toggleSection = (title) => {
    collapsedSections.value[title] = !collapsedSections.value[title]
}
</script>

<template>
    <aside 
        class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform transition-transform duration-300 lg:translate-x-0 flex flex-col"
        :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="flex items-center justify-center h-20 border-b border-gray-100 px-6">
            <img src="/images/logo-cre.png" alt="CRE Logo" class="h-20 w-auto object-contain">
        </div>

        <nav class="flex-1 mt-6 px-4 space-y-4 overflow-y-auto min-h-0 pb-6 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <div v-for="section in navigation" :key="section.title" class="space-y-1">
                <!-- Section Title Header (Clickable Accordion) -->
                <button 
                    @click="toggleSection(section.title)"
                    class="w-full px-4 py-2 text-[9px] font-black text-slate-400 uppercase tracking-widest flex items-center justify-between hover:text-slate-700 transition-colors focus:outline-none"
                >
                    <div class="flex items-center gap-2 flex-1">
                        <span>{{ section.title }}</span>
                        <div class="h-[1px] bg-slate-100 flex-1"></div>
                    </div>
                    <component 
                        :is="collapsedSections[section.title] ? ChevronRightIcon : ChevronDownIcon" 
                        class="h-3 w-3 ml-2 text-slate-400 transition-transform duration-200"
                    />
                </button>
                
                <!-- Section Menu Items -->
                <div v-show="!collapsedSections[section.title]" class="space-y-1 pl-1 transition-all duration-200">
                    <Link 
                        v-for="item in section.items" 
                        :key="item.name" 
                        :href="item.href"
                        class="flex items-center justify-between px-4 py-2 text-xs font-semibold rounded-lg transition-colors group"
                        :class="isUrl(item.href) 
                            ? 'bg-blue-50 text-blue-700 font-bold shadow-sm' 
                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    >
                        <div class="flex items-center">
                            <component 
                                :is="item.icon" 
                                class="mr-3 h-4.5 w-4.5 transition-colors" 
                                :class="isUrl(item.href) ? 'text-blue-700' : 'text-gray-400 group-hover:text-gray-500'" 
                            />
                            {{ item.name }}
                        </div>
                        <span 
                            v-if="item.badge && item.badge > 0" 
                            class="ml-auto inline-flex items-center justify-center px-2 py-0.5 text-[9px] font-black leading-none text-white bg-red-600 rounded-full"
                        >
                            {{ item.badge }}
                        </span>
                    </Link>
                </div>
            </div>
        </nav>

        <div class="w-full p-4 border-t border-gray-200 bg-gray-50/50">
            <div class="flex items-center gap-3 px-3 py-1">
                <div class="h-8 w-8 rounded-full overflow-hidden border border-gray-200 bg-white shadow-sm">
                    <img v-if="page.props.auth.user.profile_photo_url" :src="page.props.auth.user.profile_photo_url" class="h-full w-full object-cover">
                    <UserIcon v-else class="h-full w-full p-1.5 text-gray-400" />
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-black text-gray-900 truncate max-w-[140px]">{{ page.props.auth.user.name }}</span>
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-tighter">{{ page.props.auth.user.roles[0] }}</span>
                </div>
            </div>
        </div>
    </aside>
</template>
