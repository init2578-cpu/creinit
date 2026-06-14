<script setup>
import { ref, computed } from 'vue'
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
    NewspaperIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    isOpen: Boolean
})

const page = usePage()
const roles = computed(() => page.props.auth.user.roles)

// -----------------------------------------------------------------------
// Navigation Definition (PROMPT 1)
// -----------------------------------------------------------------------

const navigation = computed(() => {
    let menu = []

    if (roles.value.includes('Directeur')) {
        menu.push(
            { name: 'Tableau de Bord', href: route('dashboard.director'), icon: HomeIcon },
            { name: 'Communauté', href: route('community.index'), icon: ChatBubbleLeftRightIcon, badge: page.props.auth.user?.unread_announcements_count },
            { name: 'Actualités (Vitrine)', href: route('admin.posts.index'), icon: NewspaperIcon },
            { name: 'Messages Contact', href: route('contact-messages.index'), icon: EnvelopeIcon, badge: page.props.auth.user?.unread_messages_count },
            { name: 'Inscriptions', href: route('applications.index'), icon: UserGroupIcon },
            { name: 'Groupes de Formation', href: route('groups.index'), icon: UserGroupIcon },
            { name: 'Validations', href: route('nominations.index'), icon: CheckBadgeIcon },
            { name: 'Apprenants', href: route('students.index'), icon: AcademicCapIcon },
            { name: 'Stagiaires', href: route('trainees.index'), icon: BriefcaseIcon },
            { name: 'Emplois du temps', href: route('schedules.index'), icon: CalendarIcon },
            { name: 'Émargement', href: route('attendance.index'), icon: ClipboardDocumentCheckIcon },
            { name: 'Attestations', href: route('certificates.index'), icon: CheckBadgeIcon },
            { name: 'Examens', href: route('exams.index'), icon: PencilSquareIcon },
            { name: 'Exercices (Correction)', href: route('exercises.index'), icon: ClipboardDocumentCheckIcon },
            { name: 'Gestion des Salles', href: route('rooms.index'), icon: HomeModernIcon },
            { name: 'Gestion des Formations', href: route('modules.index'), icon: BookOpenIcon },
            { name: 'Espace Cours (Aperçu)', href: route('student.courses'), icon: AcademicCapIcon },
            { name: 'Inventaire Matériel', href: route('assets.index'), icon: ArchiveBoxIcon },
            { name: 'Prêts & Retours', href: route('loans.index'), icon: BriefcaseIcon },
            { name: 'Rayonnement', href: route('ecosystem.index'), icon: GlobeAltIcon },
            { name: 'Utilisateurs', href: route('users.index'), icon: UsersIcon },
            { name: 'Statistiques', href: route('stats.index'), icon: ChartBarIcon },
            { name: 'Rapports', href: route('reports.index'), icon: DocumentChartBarIcon },
            { name: 'Paramètres', href: route('settings.index'), icon: Cog6ToothIcon },
        )
    } else if (roles.value.includes('Formateur') || page.props.auth.user?.is_trainer) {
        menu.push(
            { name: 'Mes Groupes', href: route('trainer.groups'), icon: AcademicCapIcon },
            { name: 'Communauté', href: route('community.index'), icon: ChatBubbleLeftRightIcon, badge: page.props.auth.user?.unread_announcements_count },
            { name: 'Émargement', href: route('attendances.trainer-groups'), icon: ClipboardDocumentCheckIcon },
            { name: 'Gestion des Cours', href: route('modules.index'), icon: BookOpenIcon },
            { name: 'Examens', href: route('exams.index'), icon: PencilSquareIcon },
            { name: 'Exercices (Correction)', href: route('exercises.index'), icon: ClipboardDocumentCheckIcon },
            { name: 'Aperçu Plateforme', href: route('student.courses'), icon: AcademicCapIcon },
            { name: 'Inventaire Matériel', href: route('assets.index'), icon: ArchiveBoxIcon },
            { name: 'Progression', href: route('chapter-progress.groups'), icon: ChartBarIcon },
            { name: 'Emploi du Temps', href: route('schedules.index'), icon: CalendarIcon },
        )
    } else if (roles.value.includes('Secrétaire')) {
        menu.push(
            { name: 'Tableau de Bord', href: route('dashboard.director'), icon: HomeIcon },
            { name: 'Communauté', href: route('community.index'), icon: ChatBubbleLeftRightIcon, badge: page.props.auth.user?.unread_announcements_count },
            { name: 'Actualités (Vitrine)', href: route('admin.posts.index'), icon: NewspaperIcon },
            { name: 'Messages Contact', href: route('contact-messages.index'), icon: EnvelopeIcon, badge: page.props.auth.user?.unread_messages_count },
            { name: 'Inscriptions', href: route('applications.index'), icon: UserGroupIcon },
            { name: 'Groupes de Formation', href: route('groups.index'), icon: UserGroupIcon },
            { name: 'Validations', href: route('nominations.index'), icon: CheckBadgeIcon },
            { name: 'Gestion des Salles', href: route('rooms.index'), icon: HomeModernIcon },
            { name: 'Emplois du temps', href: route('schedules.index'), icon: CalendarIcon },
            { name: 'Émargement', href: route('attendance.index'), icon: ClipboardDocumentCheckIcon },
            { name: 'Inventaire Matériel', href: route('assets.index'), icon: ArchiveBoxIcon },
            { name: 'Prêts & Retours', href: route('loans.index'), icon: BriefcaseIcon },
            { name: 'Rapports', href: route('reports.index'), icon: DocumentChartBarIcon },
        )
    } else if (roles.value.includes('Apprenant') || roles.value.includes('Stagiaire')) {
        menu.push(
            { name: 'Mon Parcours', href: route('student.dashboard'), icon: AcademicCapIcon },
            { name: 'Communauté', href: route('community.index'), icon: ChatBubbleLeftRightIcon, badge: page.props.auth.user?.unread_announcements_count },
            { name: 'Mes Cours', href: route('student.courses'), icon: BookOpenIcon },
            { name: 'Exercices', href: route('student.exercises.index'), icon: BeakerIcon },
            { name: 'Examens', href: route('student.exams.index'), icon: PencilSquareIcon },
            { name: 'Validation Progression', href: route('chapter-progress.groups'), icon: ChartBarIcon }
        )
    }

    return menu
})

const isUrl = (url) => page.url.startsWith(url)
</script>

<template>
    <aside 
        class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform transition-transform duration-300 lg:translate-x-0 flex flex-col"
        :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="flex items-center justify-center h-20 border-b border-gray-100 px-6">
            <img src="/images/logo-cre.png" alt="CRE Logo" class="h-20 w-auto object-contain">
        </div>

        <nav class="flex-1 mt-6 px-4 space-y-1 overflow-y-auto min-h-0">
            <Link 
                v-for="item in navigation" 
                :key="item.name" 
                :href="item.href"
                class="flex items-center justify-between px-4 py-2.5 text-sm font-medium rounded-lg transition-colors"
                :class="isUrl(item.href) 
                    ? 'bg-blue-50 text-blue-700' 
                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
                <div class="flex items-center">
                    <component :is="item.icon" class="mr-3 h-5 w-5" />
                    {{ item.name }}
                </div>
                <span 
                    v-if="item.badge && item.badge > 0" 
                    class="ml-auto inline-flex items-center justify-center px-2 py-0.5 text-[10px] font-black leading-none text-white bg-red-600 rounded-full"
                >
                    {{ item.badge }}
                </span>
            </Link>
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
