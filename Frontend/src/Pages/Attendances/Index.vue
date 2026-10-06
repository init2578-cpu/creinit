<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { 
    ChevronRightIcon, 
    UserGroupIcon, 
    LockClosedIcon, 
    ClipboardDocumentCheckIcon,
    ArchiveBoxIcon
} from '@heroicons/vue/24/outline'

defineProps({
    groups: {
        type: Array,
        default: () => []
    },
    closed_groups: {
        type: Array,
        default: () => []
    }
})
</script>

<template>
    <Head title="Émargement - Sélection du groupe" />

    <AuthenticatedLayout>
        <div class="max-w-5xl mx-auto py-8 px-4 font-sans space-y-8">
            <div>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">Mes Groupes pour l'émargement</h1>
                <p class="text-sm font-medium text-gray-500 mt-1">Sélectionnez un groupe actif pour émarger ou consultez l'historique de vos groupes clôturés.</p>
            </div>

            <!-- Active Groups -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gray-400 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Groupes Actifs (En cours de formation)
                    </h2>
                    <span class="text-xs font-bold text-gray-400">{{ groups.length }} groupe(s)</span>
                </div>

                <div v-if="groups.length === 0" class="bg-white rounded-3xl p-8 text-center shadow-sm border border-gray-100">
                    <UserGroupIcon class="h-12 w-12 text-gray-300 mx-auto mb-3" />
                    <p class="text-sm font-bold text-gray-700">Aucun groupe actif à émarger pour le moment.</p>
                    <p class="text-xs text-gray-400 mt-1">Les groupes actifs avec un emploi du temps apparaîtront ici.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Link 
                        v-for="group in groups" 
                        :key="group.id"
                        :href="route('attendances.take', group.id)"
                        class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:border-blue-400 hover:shadow-md transition-all group relative overflow-hidden"
                    >
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-black text-gray-900 group-hover:text-blue-600 transition-colors">
                                        {{ group.nom_groupe }}
                                    </h3>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        Actif
                                    </span>
                                </div>
                                <p class="text-xs font-semibold text-gray-500">{{ group.module?.nom_module || group.module?.titre }}</p>
                                <div class="pt-2 flex items-center gap-2 text-xs font-bold text-gray-400">
                                    <span class="px-2.5 py-0.5 rounded-lg bg-gray-50 text-gray-600 border border-gray-100">
                                        {{ group.annee_academique }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-2xl group-hover:bg-blue-50 text-gray-400 group-hover:text-blue-600 transition-all">
                                <ChevronRightIcon class="h-5 w-5" />
                            </div>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Closed Groups (Archive & Historical Records) -->
            <div v-if="closed_groups && closed_groups.length > 0" class="pt-6 border-t border-gray-200/70 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-black uppercase tracking-widest text-gray-400 flex items-center gap-2">
                        <LockClosedIcon class="h-3.5 w-3.5 text-gray-400" />
                        Groupes Clôturés (Historiques & Émargements archivés)
                    </h2>
                    <span class="text-xs font-bold text-gray-400">{{ closed_groups.length }} groupe(s)</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Link 
                        v-for="group in closed_groups" 
                        :key="group.id"
                        :href="route('groups.attendances.history', group.id)"
                        class="bg-gray-50/80 hover:bg-white p-6 rounded-3xl border border-gray-200/80 hover:border-slate-300 hover:shadow-sm transition-all group"
                    >
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-black text-gray-800 group-hover:text-slate-900 transition-colors">
                                        {{ group.nom_groupe }}
                                    </h3>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-rose-50 text-rose-700 border border-rose-100 inline-flex items-center gap-1">
                                        <LockClosedIcon class="h-2.5 w-2.5" />
                                        Clôturé
                                    </span>
                                </div>
                                <p class="text-xs font-semibold text-gray-500">{{ group.module?.nom_module || group.module?.titre }}</p>
                                <div class="pt-2 flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 group-hover:underline">
                                        <ClipboardDocumentCheckIcon class="h-4 w-4" />
                                        Consulter l'historique complet
                                    </span>
                                </div>
                            </div>
                            <div class="p-3 bg-white rounded-2xl group-hover:bg-slate-100 text-gray-400 group-hover:text-gray-700 transition-all border border-gray-100">
                                <ChevronRightIcon class="h-5 w-5" />
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

