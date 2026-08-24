<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { ChevronRightIcon, UserGroupIcon, BookOpenIcon, LockClosedIcon } from '@heroicons/vue/24/outline'

defineProps({
    groups: Array
})
</script>

<template>
    <Head title="Progression - Sélection du groupe" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto py-6 px-4">
            <h1 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
                <BookOpenIcon class="h-8 w-8 text-blue-600" />
                Suivi de Progression Pédagogique
            </h1>

            <div v-if="groups.length === 0" class="bg-white rounded-xl p-8 text-center shadow-sm border border-gray-100">
                <UserGroupIcon class="h-12 w-12 text-gray-300 mx-auto mb-4" />
                <p class="text-gray-500">Aucun groupe ne vous est affecté pour le moment.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Link 
                    v-for="group in groups" 
                    :key="group.id"
                    :href="route('chapter-progress.index', group.id)"
                    class="bg-white p-6 rounded-2xl shadow-sm border transition-all group hover:shadow-md"
                    :class="group.status === 'closed' ? 'border-rose-100 bg-rose-50/10 hover:border-rose-300' : 'border-gray-100 hover:border-blue-300'"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h2 class="text-lg font-bold text-gray-900 group-hover:text-blue-600 transition-colors">
                                    {{ group.nom_groupe }}
                                </h2>
                                <!-- Status Badge -->
                                <span 
                                    class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md border"
                                    :class="group.status === 'closed' 
                                        ? 'bg-rose-50 text-rose-700 border-rose-200' 
                                        : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                                >
                                    <span v-if="group.status !== 'closed'" class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <LockClosedIcon v-else class="h-3 w-3 text-rose-600" />
                                    {{ group.status === 'closed' ? 'Clôturé' : 'En cours' }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">{{ group.module?.titre || group.module?.nom_module }}</p>
                            <div class="mt-4 inline-flex items-center text-xs font-semibold px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700">
                                {{ group.annee_academique }}
                            </div>
                        </div>
                        <div class="h-10 w-10 bg-gray-50 rounded-full flex items-center justify-center group-hover:bg-blue-50 transition-colors">
                            <ChevronRightIcon class="h-5 w-5 text-gray-400 group-hover:text-blue-600 transition-colors" />
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
