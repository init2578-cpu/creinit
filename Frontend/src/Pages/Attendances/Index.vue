<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { 
    ChevronRightIcon, 
    UserGroupIcon
} from '@heroicons/vue/24/outline'

defineProps({
    groups: {
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
                <p class="text-sm font-medium text-gray-500 mt-1">Sélectionnez un groupe actif pour émarger.</p>
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
        </div>
    </AuthenticatedLayout>
</template>

