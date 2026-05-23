<template>
  <AuthenticatedLayout title="Certificats Civils">
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Registre des Certificats Civils
        </h2>
        <Link
          :href="route('civil-certificates.create')"
          class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150"
        >
          Nouvelle Demande
        </Link>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <div class="mb-4">
              <label for="type_filter" class="block text-sm font-medium text-gray-700">Filtrer par type</label>
              <select
                id="type_filter"
                v-model="selectedType"
                @change="filterByType"
                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
              >
                <option value="">Tous les certificats</option>
                <option v-for="type in types" :key="type" :value="type">
                  {{ formatType(type) }}
                </option>
              </select>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Référence</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Demandeur</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="cert in certificates.data" :key="cert.id" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ cert.reference_number }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                        {{ formatType(cert.type) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ cert.applicant_first_name }} {{ cert.applicant_last_name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <span
                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                        :class="{
                          'bg-yellow-100 text-yellow-800': cert.status === 'pending',
                          'bg-green-100 text-green-800': cert.status === 'validated',
                          'bg-red-100 text-red-800': cert.status === 'rejected'
                        }"
                      >
                        {{ formatStatus(cert.status) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <Link
                        :href="route('civil-certificates.show', cert.id)"
                        class="text-indigo-600 hover:text-indigo-900"
                      >
                        Consulter
                      </Link>
                    </td>
                  </tr>
                  <tr v-if="certificates.data.length === 0">
                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                      Aucun certificat trouvé.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Basic Pagination Controls Simulation -->
            <div class="mt-4 flex justify-between items-center" v-if="certificates.data.length > 0">
              <span class="text-sm text-gray-700">Affichage de la page {{ certificates.current_page }} sur {{ certificates.last_page }}</span>
            </div>

          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  certificates: Object,
  types: Array,
});

const selectedType = ref(new URLSearchParams(window.location.search).get('type') || '');

const filterByType = () => {
  router.get(route('civil-certificates.index'), { type: selectedType.value }, {
    preserveState: true,
    replace: true,
  });
};

const formatType = (type) => {
  const dictionary = {
    residence: 'Certificat de résidence',
    coutume: 'Certificat de coutume',
    indigence: 'Certificat d\'indigence',
    individualite: 'Certificat d\'individualité',
    vie_collective: 'Certificat de vie collaborative',
    vie_individuel: 'Certificat de vie individuel',
    non_inscrit_naissance: 'Certificat de non inscrit de naissance',
    acte_non_inexistant: 'Certificat d\'acte non inexistant',
  };
  return dictionary[type] || type;
};

const formatStatus = (status) => {
  switch (status) {
    case 'pending': return 'En attente';
    case 'validated': return 'Validé';
    case 'rejected': return 'Rejeté';
    default: return status;
  }
};
</script>
