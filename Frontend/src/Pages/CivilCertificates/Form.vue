<template>
  <AuthenticatedLayout title="Demande de Certificat">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Faire une demande de certificat civil
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6">
            <form @submit.prevent="submitForm">
              
              <!-- Sélection du Type -->
              <div class="mb-6">
                <label for="type" class="block text-sm font-medium text-gray-700">Type de Certificat</label>
                <select id="type" v-model="form.type" @change="resetDynamicFields" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm">
                  <option v-for="t in types" :key="t" :value="t">{{ formatType(t) }}</option>
                </select>
                <p v-if="form.errors.type" class="text-sm text-red-600 mt-1">{{ form.errors.type }}</p>
              </div>

              <div class="bg-gray-50 p-4 rounded-md mb-6 border border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informations d'identité du demandeur</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Prénom</label>
                    <input type="text" v-model="form.applicant_first_name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                    <p v-if="form.errors.applicant_first_name" class="text-sm text-red-600 mt-1">{{ form.errors.applicant_first_name }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Nom</label>
                    <input type="text" v-model="form.applicant_last_name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Numéro CNI (Optionnel)</label>
                    <input type="text" v-model="form.applicant_cni" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                  </div>
                </div>
              </div>

              <!-- Champs Dynamiques (data) selon le type -->
              <div class="bg-indigo-50 p-4 rounded-md mb-6 border border-indigo-100" v-if="form.type">
                <h3 class="text-lg font-medium text-indigo-900 mb-4">Détails spécifiques au {{ formatType(form.type) }}</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <!-- Motif (Commun à plusieurs) -->
                  <div class="md:col-span-2" v-if="['residence', 'coutume', 'indigence', 'individualite'].includes(form.type)">
                    <label class="block text-sm font-medium text-gray-700">Motif de la demande</label>
                    <textarea v-model="form.data.motif" required rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                  </div>

                  <!-- Date de naissance (Coutume, Individualite, Vie Individuel, Acte non inexistant) -->
                  <div v-if="['coutume', 'individualite', 'vie_individuel', 'acte_non_inexistant'].includes(form.type)">
                    <label class="block text-sm font-medium text-gray-700">Date de Naissance</label>
                    <input type="date" v-model="form.data.date_naissance" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                  </div>

                  <!-- Lieu de naissance (Coutume, Individualite, Non inscrit) -->
                  <div v-if="['coutume', 'individualite', 'non_inscrit_naissance'].includes(form.type)">
                    <label class="block text-sm font-medium text-gray-700">Lieu de Naissance</label>
                    <input type="text" v-model="form.data.lieu_naissance" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                  </div>

                  <!-- Sexe (Coutume) -->
                  <div v-if="form.type === 'coutume'">
                    <label class="block text-sm font-medium text-gray-700">Sexe</label>
                    <select v-model="form.data.sexe" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                      <option value="M">Masculin</option>
                      <option value="F">Féminin</option>
                    </select>
                  </div>

                  <!-- Adresse (Residence, Indigence) -->
                  <div class="md:col-span-2" v-if="['residence', 'indigence'].includes(form.type)">
                    <label class="block text-sm font-medium text-gray-700">Adresse complète</label>
                    <input type="text" v-model="form.data.adresse" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                  </div>

                  <!-- Durée de résidence (Residence) -->
                  <div v-if="form.type === 'residence'">
                    <label class="block text-sm font-medium text-gray-700">Durée de résidence (ex: Depuis 5 ans)</label>
                    <input type="text" v-model="form.data.duree_residence" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                  </div>

                  <!-- Ressources / Foyer (Indigence) -->
                  <div v-if="form.type === 'indigence'">
                    <label class="block text-sm font-medium text-gray-700">Ressources financières (mensuel approx FCFA)</label>
                    <input type="number" v-model="form.data.ressources" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                  </div>
                  <div v-if="form.type === 'indigence'">
                    <label class="block text-sm font-medium text-gray-700">Composition du foyer (Nb. de personnes)</label>
                    <input type="number" v-model="form.data.composition_foyer" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                  </div>

                  <!-- Liens familiaux (Individualite) -->
                  <div class="md:col-span-2" v-if="form.type === 'individualite'">
                    <label class="block text-sm font-medium text-gray-700">Liens Familiaux à justifier</label>
                    <textarea v-model="form.data.liens_familiaux" required rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                  </div>

                  <!-- Liste membres (Vie Collective) -->
                  <div class="md:col-span-2" v-if="form.type === 'vie_collective'">
                    <label class="block text-sm font-medium text-gray-700">Liste des individus et CNIs (Résidence commune)</label>
                    <textarea v-model="form.data.membres_identites" required rows="3" placeholder="Nom Prénom - CNI" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                  </div>

                  <!-- Date approximative naissance (Non Inscrit) -->
                  <div v-if="form.type === 'non_inscrit_naissance'">
                    <label class="block text-sm font-medium text-gray-700">Date de Naissance Approximative</label>
                    <input type="text" v-model="form.data.date_approximative" required placeholder="Vres 2000" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                  </div>

                  <!-- Période recherche (Non Inscrit) -->
                  <div v-if="form.type === 'non_inscrit_naissance'">
                    <label class="block text-sm font-medium text-gray-700">Période de recherche</label>
                    <input type="text" v-model="form.data.periode_recherche" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                  </div>

                  <!-- Type d'acte recherché / Recherches prealables (Acte non inexistant) -->
                  <div class="md:col-span-2" v-if="form.type === 'acte_non_inexistant'">
                    <label class="block text-sm font-medium text-gray-700">Type d'acte recherché et recherches préalables effectuées</label>
                    <textarea v-model="form.data.recherches_prealables" required rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                  </div>

                  <!-- 2 Témoins (Res, Coutume, Indigence, Individualite, Vie Coll) -->
                  <div class="md:col-span-2 border-t border-indigo-200 mt-4 pt-4" v-if="['residence', 'coutume', 'indigence', 'individualite', 'vie_collective'].includes(form.type)">
                    <h4 class="text-sm font-bold text-indigo-800 mb-2">Informations sur les Témoins</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label class="block text-xs font-medium text-gray-600">Témoin 1 (Nom Prénom + CNI)</label>
                        <input type="text" v-model="form.data.temoin_1" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                      </div>
                      <div>
                        <label class="block text-xs font-medium text-gray-600">Témoin 2 (Nom Prénom + CNI)</label>
                        <input type="text" v-model="form.data.temoin_2" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                      </div>
                    </div>
                  </div>

                </div>
              </div>

              <!-- Submit -->
              <div class="flex items-center justify-end mt-4">
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150"
                >
                  Soumettre la demande
                </button>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  type: String,
  types: Array,
});

const form = useForm({
  type: props.type,
  applicant_first_name: '',
  applicant_last_name: '',
  applicant_cni: '',
  data: {},
});

const resetDynamicFields = () => {
  form.data = {};
};

const submitForm = () => {
  form.post(route('civil-certificates.store'));
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
</script>
