<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'

defineProps({
    auth: Object
})
</script>

<template>
    <Head title="Tableau de bord" />

    <AuthenticatedLayout>
        <div class="py-6 -mt-6 sm:-mt-8 lg:-mt-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-2xl rounded-[3rem] border border-gray-100 flex flex-col md:flex-row items-center p-10 gap-10">
                    <div class="h-40 w-40 rounded-[2.5rem] bg-blue-600 flex items-center justify-center overflow-hidden text-white text-5xl font-black shadow-2xl shadow-blue-200">
                        <img v-if="auth.user.profile_photo_url" :src="auth.user.profile_photo_url" class="h-full w-full object-cover">
                        <template v-else>{{ auth.user.name.charAt(0) }}</template>
                    </div>
                    <div class="text-center md:text-left">
                        <h2 class="text-4xl font-black text-gray-900 tracking-tight mb-4">
                            Bienvenue, <span class="text-blue-600">{{ auth.user.name }}</span> !
                        </h2>
                        <p class="text-lg text-gray-500 font-medium">
                            Vous êtes connecté en tant que <span class="px-4 py-1.5 bg-blue-50 text-blue-700 rounded-xl text-sm font-black uppercase tracking-widest">{{ auth.user.roles[0] }}</span>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
