<script setup>
import { computed, onMounted, ref } from 'vue'
import { AcademicCapIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    modules: { type: Array, default: () => [] }
})

const animated = ref(false)
onMounted(() => setTimeout(() => (animated.value = true), 200))

const total = computed(() => props.modules.reduce((s, m) => s + m.count, 0))

// Color palette for each module pill
const palette = [
    { pill: '#7c3aed', pillLight: '#8b5cf6', icon: '#6d28d9', line: '#7c3aed', text: '#ede9fe' },
    { pill: '#be123c', pillLight: '#e11d48', icon: '#9f1239', line: '#be123c', text: '#ffe4e6' },
    { pill: '#0e7490', pillLight: '#0891b2', icon: '#155e75', line: '#0e7490', text: '#cffafe' },
    { pill: '#c2410c', pillLight: '#ea580c', icon: '#9a3412', line: '#c2410c', text: '#ffedd5' },
    { pill: '#1d4ed8', pillLight: '#2563eb', icon: '#1e40af', line: '#1d4ed8', text: '#dbeafe' },
]

const getColor = (index) => palette[index % palette.length]

// SVG layout constants
const svgW = 680
const svgH = computed(() => Math.max(380, props.modules.length * 80 + 80))
const cx = 170           // center circle X
const cy = computed(() => svgH.value / 2)
const cr = 100           // center circle radius
const lineEndX = cx + cr + 30  // where connector exits circle
const pillX = lineEndX + 80    // pill left edge X
const pillW = 340
const pillH = 58
const pillR = pillH / 2

const modulePositions = computed(() => {
    const n = props.modules.length
    if (n === 0) return []
    const spacing = Math.max(72, (svgH.value - 80) / n)
    const startY = (svgH.value - spacing * (n - 1)) / 2
    return props.modules.map((mod, i) => ({
        ...mod,
        y: startY + i * spacing,
        color: getColor(i),
    }))
})
</script>

<template>
    <div class="w-full overflow-x-auto">
        <div v-if="!modules.length" class="flex items-center justify-center h-48 text-gray-400 font-bold text-sm">
            Aucun module disponible
        </div>
        <div v-else class="relative" :style="{ minWidth: '640px' }">
            <svg
                :viewBox="`0 0 ${svgW} ${svgH}`"
                :height="svgH"
                class="w-full"
                xmlns="http://www.w3.org/2000/svg"
            >
                <!-- Defs: filters and gradients -->
                <defs>
                    <filter id="shadow-center" x="-30%" y="-30%" width="160%" height="160%">
                        <feDropShadow dx="0" dy="6" stdDeviation="14" flood-color="rgba(0,0,0,0.18)" />
                    </filter>
                    <filter id="shadow-pill" x="-10%" y="-30%" width="120%" height="160%">
                        <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="rgba(0,0,0,0.15)" />
                    </filter>
                    <radialGradient id="center-grad" cx="35%" cy="30%" r="65%">
                        <stop offset="0%" stop-color="#ffffff" />
                        <stop offset="100%" stop-color="#e8edf5" />
                    </radialGradient>
                    <!-- Per-module pill gradients -->
                    <linearGradient
                        v-for="(mod, i) in modulePositions"
                        :key="'grad-' + i"
                        :id="'pill-grad-' + i"
                        x1="0%" y1="0%" x2="100%" y2="0%"
                    >
                        <stop offset="0%" :stop-color="mod.color.pill" />
                        <stop offset="100%" :stop-color="mod.color.pillLight" />
                    </linearGradient>
                </defs>

                <!-- === Connector lines === -->
                <g v-for="(mod, i) in modulePositions" :key="'line-' + i">
                    <!-- Line from circle edge to pill -->
                    <path
                        :d="`M ${cx + cr * Math.cos(Math.atan2(mod.y - cy, lineEndX - cx))} ${cy + cr * Math.sin(Math.atan2(mod.y - cy, lineEndX - cx))}
                             C ${lineEndX + 20} ${cy}, ${pillX - 40} ${mod.y}, ${pillX} ${mod.y}`"
                        :stroke="mod.color.line"
                        stroke-width="2"
                        fill="none"
                        stroke-dasharray="5 3"
                        :opacity="animated ? 1 : 0"
                        style="transition: opacity 0.8s ease;"
                    />
                    <!-- Small dot on circle edge -->
                    <circle
                        :cx="cx + cr * Math.cos(Math.atan2(mod.y - cy, lineEndX - cx))"
                        :cy="cy + cr * Math.sin(Math.atan2(mod.y - cy, lineEndX - cx))"
                        r="4"
                        :fill="mod.color.line"
                        :opacity="animated ? 1 : 0"
                        style="transition: opacity 0.6s ease;"
                    />
                </g>

                <!-- === Module pills === -->
                <g v-for="(mod, i) in modulePositions" :key="'pill-' + i"
                    :transform="`translate(0, ${animated ? 0 : 20})`"
                    :style="`transition: transform 0.7s cubic-bezier(.4,2,.6,1) ${i * 80}ms, opacity 0.7s ease ${i * 80}ms; opacity: ${animated ? 1 : 0}`"
                >
                    <!-- Icon circle -->
                    <circle
                        :cx="pillX + 28"
                        :cy="mod.y"
                        r="28"
                        :fill="mod.color.icon"
                        filter="url(#shadow-pill)"
                    />
                    <!-- Pill body (starts where icon circle center is, so icon overlaps left edge) -->
                    <rect
                        :x="pillX + 28"
                        :y="mod.y - pillH / 2"
                        :width="pillW"
                        :height="pillH"
                        :rx="pillR"
                        :fill="`url(#pill-grad-${i})`"
                        filter="url(#shadow-pill)"
                    />
                    <!-- Count badge at far right of pill -->
                    <circle
                        :cx="pillX + 28 + pillW - 28"
                        :cy="mod.y"
                        r="22"
                        fill="rgba(0,0,0,0.2)"
                    />
                    <text
                        :x="pillX + 28 + pillW - 28"
                        :y="mod.y + 1"
                        text-anchor="middle"
                        dominant-baseline="middle"
                        fill="white"
                        font-size="13"
                        font-weight="900"
                        font-family="system-ui, sans-serif"
                    >{{ mod.count }}</text>

                    <!-- Module code label -->
                    <text
                        :x="pillX + 68"
                        :y="mod.y - 10"
                        fill="rgba(255,255,255,0.65)"
                        font-size="9"
                        font-weight="800"
                        font-family="system-ui, sans-serif"
                        letter-spacing="2"
                        text-transform="uppercase"
                    >{{ mod.code }}</text>
                    <!-- Module title -->
                    <text
                        :x="pillX + 68"
                        :y="mod.y + 10"
                        :fill="mod.color.text"
                        font-size="14"
                        font-weight="900"
                        font-family="system-ui, sans-serif"
                    >{{ mod.titre.length > 24 ? mod.titre.substring(0, 23) + '…' : mod.titre }}</text>
                    <!-- Percent small -->
                    <text
                        :x="pillX + 68"
                        :y="mod.y + 26"
                        fill="rgba(255,255,255,0.45)"
                        font-size="9"
                        font-weight="700"
                        font-family="system-ui, sans-serif"
                    >{{ mod.percent }}% des apprenants{{ !mod.is_active ? ' · Suspendu' : '' }}</text>

                    <!-- Suspended badge -->
                    <g v-if="!mod.is_active">
                        <rect :x="pillX + 28 + pillW - 90" :y="mod.y - 30" width="55" height="16" rx="8" fill="#ef4444" opacity="0.85"/>
                        <text :x="pillX + 28 + pillW - 62" :y="mod.y - 22" text-anchor="middle" dominant-baseline="middle" fill="white" font-size="8" font-weight="900" font-family="system-ui, sans-serif">SUSPENDU</text>
                    </g>
                </g>

                <!-- === Central circle === -->
                <circle :cx="cx" :cy="cy" r="cr + 12" fill="rgba(99,102,241,0.08)" />
                <circle
                    :cx="cx" :cy="cy" :r="cr"
                    fill="url(#center-grad)"
                    filter="url(#shadow-center)"
                />
                <!-- Dashed outer ring accent -->
                <circle
                    :cx="cx" :cy="cy" :r="cr + 16"
                    fill="none"
                    stroke="#6366f1"
                    stroke-width="2"
                    stroke-dasharray="8 6"
                    opacity="0.3"
                />

                <!-- Center text -->
                <text :x="cx" :y="cy - 28" text-anchor="middle" fill="#1e293b" font-size="11" font-weight="900" font-family="system-ui, sans-serif" letter-spacing="1">RÉPARTITION</text>
                <text :x="cx" :y="cy - 10" text-anchor="middle" fill="#1e293b" font-size="11" font-weight="900" font-family="system-ui, sans-serif" letter-spacing="1">PAR MODULE</text>
                <!-- Big total number -->
                <text :x="cx" :y="cy + 22" text-anchor="middle" fill="#4f46e5" font-size="36" font-weight="900" font-family="system-ui, sans-serif">{{ total }}</text>
                <text :x="cx" :y="cy + 42" text-anchor="middle" fill="#64748b" font-size="10" font-weight="700" font-family="system-ui, sans-serif">apprenants</text>

                <!-- Small chart icon below text -->
                <text :x="cx" :y="cy + 62" text-anchor="middle" fill="#a5b4fc" font-size="18">📊</text>
            </svg>
        </div>
    </div>
</template>
