<template>
  <AuthenticatedLayout title="Détails du Certificat">
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Référence : {{ certificate.reference_number }}
        </h2>
        <span
          class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full"
          :class="{
            'bg-yellow-100 text-yellow-800': certificate.status === 'pending',
            'bg-green-100 text-green-800': certificate.status === 'validated',
            'bg-red-100 text-red-800': certificate.status === 'rejected'
          }"
        >
          {{ formatStatus(certificate.status) }}
        </span>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Messages flash -->
        <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
          <span class="block sm:inline">{{ $page.props.flash.success }}</span>
        </div>
        <div v-if="$page.props.flash && $page.props.flash.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
          <span class="block sm:inline">{{ $page.props.flash.error }}</span>
        </div>

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6">
            
            <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ formatType(certificate.type) }}</h3>
            <p class="text-sm text-gray-500 mb-6">Créé le {{ new Date(certificate.created_at).toLocaleDateString() }}</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
              
              <!-- Identité -->
              <div>
                <dt class="text-sm font-medium text-gray-500">Prénom</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ certificate.applicant_first_name }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Nom</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ certificate.applicant_last_name }}</dd>
              </div>
              <div v-if="certificate.applicant_cni">
                <dt class="text-sm font-medium text-gray-500">Numéro CNI</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ certificate.applicant_cni }}</dd>
              </div>

              <div class="md:col-span-2 my-4 border-t border-gray-200"></div>

              <!-- Dynamic Data Fields -->
              <div v-for="(value, key) in certificate.data" :key="key" class="capitalize">
                <dt class="text-sm font-medium text-gray-500">{{ key.replace(/_/g, ' ') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ value || 'N/A' }}</dd>
              </div>

            </div>

            <!-- Actions d'Administration -->
            <div class="mt-10 pt-6 border-t border-gray-200 flex justify-end space-x-3">
              <!-- Bouton Validation (Seulement si en attente) -->
              <form v-if="certificate.status === 'pending'" @submit.prevent="approve">
                <button
                  type="submit"
                  class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150"
                  :disabled="approving"
                >
                  <span v-if="approving">Validation en cours...</span>
                  <span v-else>Valider et Générer PDF</span>
                </button>
              </form>

              <!-- Voir PDF (Si validé) -->
              <a v-if="certificate.status === 'validated' && certificate.pdf_path"
                 :href="'/storage/' + certificate.pdf_path"
                 target="_blank"
                 class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150"
              >
                Télécharger le PDF
              </a>
            </div>

          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  certificate: Object,
});

const approving = ref(false);

const approve = () => {
  if (confirm("Confirmer la validation ? Ce certificat sera par la suite bloqué en modification et enregistré définitivement dans le registre.")) {
    approving.value = true;
    router.post(route('civil-certificates.approve', props.certificate.id), {}, {
      onFinish: () => approving.value = false,
    });
  }
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
