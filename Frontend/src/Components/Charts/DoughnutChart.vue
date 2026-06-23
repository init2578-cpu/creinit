<script setup>
import { computed, onMounted, ref } from 'vue'

const props = defineProps({
    male: { type: Number, default: 0 },
    female: { type: Number, default: 0 }
})

const animated = ref(false)
onMounted(() => setTimeout(() => (animated.value = true), 100))

const total = computed(() => props.male + props.female)
const femaleRatio = computed(() => total.value > 0 ? props.female / total.value : 0)
const maleRatio = computed(() => total.value > 0 ? props.male / total.value : 0)

const femalePercent = computed(() => total.value > 0 ? Math.round(props.female / total.value * 100) : 0)
const malePercent = computed(() => total.value > 0 ? Math.round(props.male / total.value * 100) : 50)

// SVG donut parameters
const cx = 100
const cy = 100
const r = 72
const strokeWidth = 20
const circumference = 2 * Math.PI * r

// Female arc (starts at top = -90deg)
const femaleDash = computed(() => animated.value ? femaleRatio.value * circumference : 0)
const maleDash = computed(() => animated.value ? maleRatio.value * circumference : 0)
const femaleOffset = computed(() => circumference * 0.25) // start gap at top
const maleOffset = computed(() => circumference * 0.25 + femaleDash.value)
</script>

<template>
    <div class="relative flex flex-col items-center">
        <!-- SVG Donut -->
        <div class="relative w-[200px] h-[200px]">
            <svg viewBox="0 0 200 200" class="w-full h-full -rotate-90">
                <!-- Track -->
                <circle
                    :cx="cx" :cy="cy" :r="r"
                    fill="none"
                    stroke="#f1f5f9"
                    :stroke-width="strokeWidth"
                />
                <!-- Female arc -->
                <circle
                    :cx="cx" :cy="cy" :r="r"
                    fill="none"
                    stroke="url(#femaleGrad)"
                    :stroke-width="strokeWidth"
                    stroke-linecap="round"
                    :stroke-dasharray="`${femaleDash} ${circumference}`"
                    :stroke-dashoffset="-femaleOffset + circumference"
                    style="transition: stroke-dasharray 1.2s cubic-bezier(.4,2,.6,1);"
                />
                <!-- Male arc -->
                <circle
                    :cx="cx" :cy="cy" :r="r"
                    fill="none"
                    stroke="url(#maleGrad)"
                    :stroke-width="strokeWidth"
                    stroke-linecap="round"
                    :stroke-dasharray="`${maleDash} ${circumference}`"
                    :stroke-dashoffset="-maleOffset + circumference"
                    style="transition: stroke-dasharray 1.2s cubic-bezier(.4,2,.6,1) 0.1s;"
                />
                <defs>
                    <linearGradient id="femaleGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#f472b6"/>
                        <stop offset="100%" stop-color="#db2777"/>
                    </linearGradient>
                    <linearGradient id="maleGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#60a5fa"/>
                        <stop offset="100%" stop-color="#2563eb"/>
                    </linearGradient>
                </defs>
            </svg>

            <!-- Center label -->
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-3xl font-black text-gray-900 leading-none">{{ total }}</span>
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">Total</span>
            </div>
        </div>

        <!-- Legend Pills -->
        <div class="mt-5 flex items-center gap-4 w-full justify-center">
            <div class="flex items-center gap-2">
                <div class="h-3 w-3 rounded-full bg-gradient-to-br from-pink-400 to-pink-600 shadow-sm shadow-pink-300"></div>
                <span class="text-[11px] font-black text-gray-500 uppercase tracking-wide">Femmes</span>
                <span class="text-[11px] font-black text-pink-600">{{ femalePercent }}%</span>
            </div>
            <div class="h-4 w-px bg-gray-200"></div>
            <div class="flex items-center gap-2">
                <div class="h-3 w-3 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 shadow-sm shadow-blue-300"></div>
                <span class="text-[11px] font-black text-gray-500 uppercase tracking-wide">Hommes</span>
                <span class="text-[11px] font-black text-blue-600">{{ malePercent }}%</span>
            </div>
        </div>
    </div>
</template>
