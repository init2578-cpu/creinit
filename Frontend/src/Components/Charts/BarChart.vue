<script setup>
import { computed } from 'vue'

const props = defineProps({
    labels: { type: Array, default: () => [] },
    data:   { type: Array, default: () => [] },
})

// Convert raw values to % (values can be 0–1 decimal or already 0–100)
const percentValues = computed(() =>
    props.data.map(v => {
        const n = parseFloat(v) || 0
        return n <= 1 ? Math.round(n * 100) : Math.round(n)
    })
)

// Max value for bar height calculation
const maxVal = computed(() => Math.max(...percentValues.value, 1))

// Short label for display (first 3 words max)
const shortLabels = computed(() =>
    props.labels.map(l => {
        const words = String(l).split(/\s+/)
        return words.length > 3 ? words.slice(0, 3).join(' ') + '…' : l
    })
)

// Color palette per bar
const colors = [
    { bar: '#6366f1', light: '#eef2ff' }, // indigo
    { bar: '#0ea5e9', light: '#e0f2fe' }, // sky
    { bar: '#10b981', light: '#d1fae5' }, // emerald
    { bar: '#f59e0b', light: '#fef3c7' }, // amber
    { bar: '#ec4899', light: '#fce7f3' }, // pink
    { bar: '#8b5cf6', light: '#ede9fe' }, // violet
    { bar: '#14b8a6', light: '#ccfbf1' }, // teal
]
</script>

<template>
    <div class="w-full">
        <!-- Empty state -->
        <div v-if="!labels || labels.length === 0"
             class="h-64 flex flex-col items-center justify-center gap-3 text-gray-300">
            <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <p class="text-sm font-bold">Aucune donnée disponible</p>
        </div>

        <!-- Chart -->
        <div v-else class="space-y-3">
            <!-- Y-axis reference lines + bars -->
            <div class="relative">
                <!-- Horizontal grid lines -->
                <div class="absolute inset-x-0 top-0 h-52 flex flex-col justify-between pointer-events-none">
                    <div v-for="tick in [100, 75, 50, 25, 0]" :key="tick"
                         class="flex items-center gap-2">
                        <span class="text-[9px] font-black text-gray-300 w-6 text-right shrink-0">{{ tick }}%</span>
                        <div class="flex-1 h-px" :class="tick === 0 ? 'bg-gray-200' : 'bg-gray-100/80'"></div>
                    </div>
                </div>

                <!-- Bars area -->
                <div class="ml-8 h-52 flex items-end gap-2 sm:gap-3">
                    <div
                        v-for="(label, i) in labels"
                        :key="i"
                        class="flex-1 flex flex-col items-center gap-1 group"
                        :title="`${label}: ${percentValues[i]}%`"
                    >
                        <!-- Value label above bar -->
                        <span class="text-[10px] font-black opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                              :style="{ color: colors[i % colors.length].bar }">
                            {{ percentValues[i] }}%
                        </span>

                        <!-- Bar -->
                        <div
                            class="w-full rounded-t-xl transition-all duration-700 ease-out relative overflow-hidden min-h-[4px] cursor-pointer hover:brightness-90"
                            :style="{
                                height: `${Math.max((percentValues[i] / maxVal) * 192, 4)}px`,
                                background: `linear-gradient(to top, ${colors[i % colors.length].bar}dd, ${colors[i % colors.length].bar}88)`,
                            }"
                        >
                            <!-- Shine effect -->
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity"
                                 style="background: linear-gradient(to right, transparent, rgba(255,255,255,0.2), transparent)">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- X-axis labels -->
            <div class="ml-8 flex gap-2 sm:gap-3">
                <div
                    v-for="(label, i) in labels"
                    :key="i"
                    class="flex-1 text-center"
                    :title="label"
                >
                    <span class="text-[9px] font-bold text-gray-400 leading-tight line-clamp-2 block">
                        {{ shortLabels[i] }}
                    </span>
                </div>
            </div>

            <!-- Legend dots -->
            <div class="ml-8 flex flex-wrap gap-x-4 gap-y-1 pt-2 border-t border-gray-50">
                <div v-for="(label, i) in labels" :key="i"
                     class="flex items-center gap-1.5" :title="label">
                    <span class="h-2 w-2 rounded-full shrink-0"
                          :style="{ background: colors[i % colors.length].bar }"></span>
                    <span class="text-[9px] font-bold text-gray-400 truncate max-w-[100px]">
                        {{ shortLabels[i] }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
