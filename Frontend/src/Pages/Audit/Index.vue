<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
    ShieldCheckIcon,
    ArrowPathIcon,
    MagnifyingGlassIcon,
    UserIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    EyeIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    logs: Object,
    totals: Object,
    users: Array,
    filters: Object,
})

// -----------------------------------------------------------------------
// Filters
// -----------------------------------------------------------------------
const search   = ref(props.filters?.search    ?? '')
const userId   = ref(props.filters?.user_id   ?? '')
const event    = ref(props.filters?.event     ?? '')
const dateFrom = ref(props.filters?.date_from ?? '')
const dateTo   = ref(props.filters?.date_to   ?? '')

const applyFilters = () => {
    router.get(route('audit.index'), {
        search:    search.value    || undefined,
        user_id:   userId.value    || undefined,
        event:     event.value     || undefined,
        date_from: dateFrom.value  || undefined,
        date_to:   dateTo.value    || undefined,
    }, { preserveScroll: true, preserveState: true })
}

const resetFilters = () => {
    search.value   = ''
    userId.value   = ''
    event.value    = ''
    dateFrom.value = ''
    dateTo.value   = ''
    applyFilters()
}

// Auto-apply immediately on select changes (user, event type, dates)
watch(userId,   () => applyFilters())
watch(event,    () => applyFilters())
watch(dateFrom, () => { if (dateFrom.value) applyFilters() })
watch(dateTo,   () => { if (dateTo.value)   applyFilters() })

// Debounce search input (trigger only after 450ms of inactivity)
let searchTimer = null
watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => applyFilters(), 450)
})

// -----------------------------------------------------------------------
// Event styling
// -----------------------------------------------------------------------
const eventConfig = {
    login:   { label: 'Connexion',    bg: 'bg-emerald-100',  text: 'text-emerald-700',  dot: 'bg-emerald-500'  },
    logout:  { label: 'Déconnexion',  bg: 'bg-slate-100',    text: 'text-slate-600',    dot: 'bg-slate-400'    },
    created: { label: 'Création',     bg: 'bg-blue-100',     text: 'text-blue-700',     dot: 'bg-blue-500'     },
    updated: { label: 'Modification', bg: 'bg-amber-100',    text: 'text-amber-700',    dot: 'bg-amber-500'    },
    deleted: { label: 'Suppression',  bg: 'bg-rose-100',     text: 'text-rose-700',     dot: 'bg-rose-500'     },
    exported:{ label: 'Export',       bg: 'bg-purple-100',   text: 'text-purple-700',   dot: 'bg-purple-500'   },
    action:  { label: 'Action',       bg: 'bg-gray-100',     text: 'text-gray-600',     dot: 'bg-gray-400'     },
}

const getEventConfig = (ev) => eventConfig[ev] ?? eventConfig.action

// -----------------------------------------------------------------------
// Detail modal
// -----------------------------------------------------------------------
const selectedLog = ref(null)
const showDetail  = ref(false)

const openDetail = (log) => {
    selectedLog.value = log
    showDetail.value  = true
}

// -----------------------------------------------------------------------
// Stats
// -----------------------------------------------------------------------
const totalEvents = computed(() => {
    return Object.values(props.totals ?? {}).reduce((s, n) => s + Number(n), 0)
})

const eventTypes = ['login', 'logout', 'created', 'updated', 'deleted', 'exported']
</script>

<template>
    <Head title="Journal d'Audit" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-slate-50">
            <!-- ============================================================
                 Header
            ============================================================ -->
            <div class="bg-white border-b border-slate-100 px-8 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-2xl bg-slate-900 flex items-center justify-center shadow-lg">
                            <ShieldCheckIcon class="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Journal d'Audit</h1>
                            <p class="text-sm text-slate-500 font-medium">Traçabilité complète de toutes les interactions utilisateurs</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-black text-slate-900">{{ totalEvents.toLocaleString() }}</div>
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">événements enregistrés</div>
                    </div>
                </div>
            </div>

            <div class="px-8 py-6 space-y-6">

                <!-- ============================================================
                     Stats Tiles
                ============================================================ -->
                <div class="grid grid-cols-3 md:grid-cols-6 gap-3">
                    <div
                        v-for="ev in eventTypes"
                        :key="ev"
                        @click="event = ev; applyFilters()"
                        class="bg-white rounded-2xl p-4 border cursor-pointer transition-all hover:shadow-md"
                        :class="[
                            event === ev ? 'border-slate-800 shadow-sm' : 'border-slate-100',
                        ]"
                    >
                        <div class="flex items-center gap-2 mb-2">
                            <span class="h-2 w-2 rounded-full flex-shrink-0" :class="getEventConfig(ev).dot"></span>
                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ getEventConfig(ev).label }}</span>
                        </div>
                        <div class="text-2xl font-black text-slate-800">{{ (totals[ev] ?? 0).toLocaleString() }}</div>
                    </div>
                </div>

                <!-- ============================================================
                     Filters Bar
                ============================================================ -->
                <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
                    <div class="flex flex-wrap gap-3 items-end">
                        <!-- Search -->
                        <div class="flex-1 min-w-[200px]">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1.5">Recherche</label>
                            <div class="relative">
                                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                <input
                                    v-model="search"
                                    @keydown.enter="applyFilters"
                                    type="text"
                                    placeholder="Description, action…"
                                    class="w-full pl-9 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                                />
                            </div>
                        </div>

                        <!-- User filter -->
                        <div class="min-w-[180px]">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1.5">Utilisateur</label>
                            <select v-model="userId" class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900/10 bg-white">
                                <option value="">Tous les utilisateurs</option>
                                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>

                        <!-- Event filter -->
                        <div class="min-w-[160px]">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1.5">Type d'événement</label>
                            <select v-model="event" class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900/10 bg-white">
                                <option value="">Tous les types</option>
                                <option v-for="ev in eventTypes" :key="ev" :value="ev">{{ getEventConfig(ev).label }}</option>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div class="min-w-[140px]">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1.5">Du</label>
                            <input v-model="dateFrom" type="date" class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900/10" />
                        </div>

                        <!-- Date To -->
                        <div class="min-w-[140px]">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1.5">Au</label>
                            <input v-model="dateTo" type="date" class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900/10" />
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2 self-end">
                            <button @click="resetFilters" class="flex items-center gap-2 px-4 py-2.5 bg-white text-slate-600 text-sm font-bold rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors">
                                <ArrowPathIcon class="h-4 w-4" />
                                Réinitialiser
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ============================================================
                     Table
                ============================================================ -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-50 flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-600">
                            {{ logs.total.toLocaleString() }} résultat(s) — page {{ logs.current_page }} / {{ logs.last_page }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="text-left px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Date & Heure</th>
                                    <th class="text-left px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Utilisateur</th>
                                    <th class="text-left px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Événement</th>
                                    <th class="text-left px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Description</th>
                                    <th class="text-left px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">Ressource</th>
                                    <th class="text-left px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-wider">IP</th>
                                    <th class="text-left px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-wider w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-if="logs.data.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400 font-medium">
                                        Aucun événement enregistré pour ces filtres.
                                    </td>
                                </tr>
                                <tr
                                    v-for="log in logs.data"
                                    :key="log.id"
                                    class="hover:bg-slate-50/80 transition-colors"
                                >
                                    <!-- Date -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-xs font-bold text-slate-500 font-mono">{{ log.created_at }}</span>
                                    </td>

                                    <!-- User -->
                                    <td class="px-6 py-4">
                                        <div v-if="log.user" class="flex items-center gap-2.5">
                                            <div class="h-7 w-7 rounded-full overflow-hidden bg-slate-200 border border-white shadow-sm flex-shrink-0">
                                                <img v-if="log.user.profile_photo_url" :src="log.user.profile_photo_url" class="h-full w-full object-cover" />
                                                <UserIcon v-else class="h-full w-full p-1.5 text-slate-400" />
                                            </div>
                                            <div>
                                                <div class="text-xs font-bold text-slate-800 leading-none">{{ log.user.name }}</div>
                                                <div class="text-[10px] text-slate-400 mt-0.5">{{ log.user.roles?.[0] ?? '' }}</div>
                                            </div>
                                        </div>
                                        <span v-else class="text-xs text-slate-400 italic">Système</span>
                                    </td>

                                    <!-- Event badge -->
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                                            :class="[getEventConfig(log.event).bg, getEventConfig(log.event).text]"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full flex-shrink-0" :class="getEventConfig(log.event).dot"></span>
                                            {{ getEventConfig(log.event).label }}
                                        </span>
                                    </td>

                                    <!-- Description -->
                                    <td class="px-6 py-4 max-w-[300px]">
                                        <p class="text-xs text-slate-700 font-medium leading-relaxed truncate">{{ log.description }}</p>
                                    </td>

                                    <!-- Resource -->
                                    <td class="px-6 py-4">
                                        <span v-if="log.auditable_name !== '—'" class="text-[10px] font-bold bg-slate-100 text-slate-600 px-2 py-1 rounded">
                                            {{ log.auditable_name }} #{{ log.auditable_id }}
                                        </span>
                                        <span v-else class="text-slate-300">—</span>
                                    </td>

                                    <!-- IP -->
                                    <td class="px-6 py-4">
                                        <span class="text-[10px] font-mono text-slate-400">{{ log.ip_address ?? '—' }}</span>
                                    </td>

                                    <!-- Detail button -->
                                    <td class="px-6 py-4">
                                        <button
                                            v-if="log.old_values || log.new_values"
                                            @click="openDetail(log)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors"
                                        >
                                            <EyeIcon class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs text-slate-400 font-medium">
                            Affichage {{ logs.from ?? 0 }}–{{ logs.to ?? 0 }} sur {{ logs.total }}
                        </span>
                        <div class="flex items-center gap-2">
                            <Link
                                v-if="logs.prev_page_url"
                                :href="logs.prev_page_url"
                                class="p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors"
                            >
                                <ChevronLeftIcon class="h-4 w-4" />
                            </Link>
                            <span class="text-xs font-bold text-slate-700 px-3 py-2 rounded-xl bg-slate-100">
                                {{ logs.current_page }} / {{ logs.last_page }}
                            </span>
                            <Link
                                v-if="logs.next_page_url"
                                :href="logs.next_page_url"
                                class="p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors"
                            >
                                <ChevronRightIcon class="h-4 w-4" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================
             Detail Modal
        ============================================================ -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showDetail && selectedLog" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showDetail = false"></div>
                    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-xl max-h-[80vh] overflow-y-auto">
                        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                            <h2 class="text-lg font-black text-slate-900">Détail de l'Événement</h2>
                            <button @click="showDetail = false" class="p-1.5 rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition-colors text-xs font-bold">✕</button>
                        </div>
                        <div class="p-6 space-y-5">
                            <div class="flex items-center gap-3">
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black uppercase tracking-wider"
                                    :class="[getEventConfig(selectedLog.event).bg, getEventConfig(selectedLog.event).text]"
                                >
                                    <span class="h-2 w-2 rounded-full" :class="getEventConfig(selectedLog.event).dot"></span>
                                    {{ getEventConfig(selectedLog.event).label }}
                                </span>
                                <span class="text-xs text-slate-400 font-mono">{{ selectedLog.created_at }}</span>
                            </div>

                            <p class="text-sm text-slate-700 font-medium bg-slate-50 rounded-2xl p-4 leading-relaxed">
                                {{ selectedLog.description }}
                            </p>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div class="bg-slate-50 rounded-2xl p-3">
                                    <div class="text-[10px] font-black text-slate-400 uppercase mb-1">URL</div>
                                    <div class="font-mono text-slate-600 break-all">{{ selectedLog.url ?? '—' }}</div>
                                </div>
                                <div class="bg-slate-50 rounded-2xl p-3">
                                    <div class="text-[10px] font-black text-slate-400 uppercase mb-1">Méthode HTTP</div>
                                    <span class="font-black text-slate-800">{{ selectedLog.method ?? '—' }}</span>
                                </div>
                            </div>

                            <!-- Old values -->
                            <div v-if="selectedLog.old_values" class="rounded-2xl border border-rose-100 bg-rose-50 p-4">
                                <div class="text-[10px] font-black text-rose-400 uppercase mb-2">Avant modification</div>
                                <pre class="text-xs text-rose-700 overflow-x-auto whitespace-pre-wrap">{{ JSON.stringify(selectedLog.old_values, null, 2) }}</pre>
                            </div>

                            <!-- New values -->
                            <div v-if="selectedLog.new_values" class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                                <div class="text-[10px] font-black text-emerald-400 uppercase mb-2">Après modification</div>
                                <pre class="text-xs text-emerald-700 overflow-x-auto whitespace-pre-wrap">{{ JSON.stringify(selectedLog.new_values, null, 2) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from, .modal-leave-to {
    opacity: 0;
}
</style>
