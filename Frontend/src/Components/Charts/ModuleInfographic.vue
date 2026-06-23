<script setup>
import { computed, onMounted, ref } from 'vue'

const props = defineProps({
    modules: { type: Array, default: () => [] }
})

const animated = ref(false)
onMounted(() => setTimeout(() => (animated.value = true), 150))

const total = computed(() => props.modules.reduce((s, m) => s + m.count, 0))

// Color palette
const palette = [
    { bg: '#7c3aed', light: '#8b5cf6', dark: '#6d28d9' },
    { bg: '#be123c', light: '#e11d48', dark: '#9f1239' },
    { bg: '#0e7490', light: '#0891b2', dark: '#155e75' },
    { bg: '#c2410c', light: '#ea580c', dark: '#9a3412' },
    { bg: '#1d4ed8', light: '#2563eb', dark: '#1e40af' },
    { bg: '#065f46', light: '#059669', dark: '#064e3b' },
    { bg: '#7e22ce', light: '#9333ea', dark: '#6b21a8' },
    { bg: '#9f1239', light: '#e11d48', dark: '#881337' },
]
const getColor = (i) => palette[i % palette.length]

// Layout constants — circle in center
const svgW = 960
const pillW = 270
const pillH = 60
const pillR = pillH / 2
const cx = svgW / 2  // center X

// Split into left/right halves
const leftModules = computed(() => {
    const n = props.modules.length
    const leftCount = Math.ceil(n / 2)
    return props.modules.slice(0, leftCount).map((m, i) => ({ ...m, color: getColor(i) }))
})

const rightModules = computed(() => {
    const n = props.modules.length
    const leftCount = Math.ceil(n / 2)
    return props.modules.slice(leftCount).map((m, i) => ({ ...m, color: getColor(leftCount + i) }))
})

const cr = 95  // circle radius

// Vertical spacing
const maxSide = computed(() => Math.max(leftModules.value.length, rightModules.value.length))
const svgH = computed(() => Math.max(340, maxSide.value * 80 + 80))
const cy = computed(() => svgH.value / 2)

// Compute Y position for a list, centered vertically
function getPositions(list) {
    const n = list.length
    if (n === 0) return []
    const spacing = Math.max(72, (svgH.value - 80) / n)
    const startY = (svgH.value - spacing * (n - 1)) / 2
    return list.map((m, i) => ({ ...m, y: startY + i * spacing }))
}

const leftPositioned = computed(() => getPositions(leftModules.value))
const rightPositioned = computed(() => getPositions(rightModules.value))

// Pill positions — increased gap so pulse paths are longer and more visible
const leftPillRightEdge = cx - cr - 130   // pills go to the left
const rightPillLeftEdge = cx + cr + 130   // pills start here on the right

// Helper: build SVG arc path on a circle of given radius, centered at (ocx, ocy)
function svgArc(ocx, ocy, r, startAngle, endAngle) {
    const x1 = ocx + r * Math.cos(startAngle)
    const y1 = ocy + r * Math.sin(startAngle)
    const x2 = ocx + r * Math.cos(endAngle)
    const y2 = ocy + r * Math.sin(endAngle)
    const large = Math.abs(endAngle - startAngle) > Math.PI ? 1 : 0
    return `M ${x1} ${y1} A ${r} ${r} 0 ${large} 1 ${x2} ${y2}`
}

// Small colored arcs drawn inside the circle border at each connector's departure angle
const departureArcs = computed(() => {
    const delta = 0.09 // set to 0.09 as requested
    const r = cr - 10  // inside the white circle near the edge
    const result = []

    for (const mod of leftPositioned.value) {
        const angle = Math.atan2(mod.y - cy.value, leftPillRightEdge - cx)
        result.push({ path: svgArc(cx, cy.value, r, angle - delta, angle + delta), color: mod.color.bg, light: mod.color.light })
    }
    for (const mod of rightPositioned.value) {
        const angle = Math.atan2(mod.y - cy.value, rightPillLeftEdge - cx)
        result.push({ path: svgArc(cx, cy.value, r, angle - delta, angle + delta), color: mod.color.bg, light: mod.color.light })
    }
    return result
})
</script>

<template>
    <div class="w-full overflow-x-auto">
        <div v-if="!modules.length" class="flex items-center justify-center h-48 text-gray-400 font-bold text-sm">
            Aucun module disponible
        </div>

        <div v-else :style="{ minWidth: '700px' }">
            <svg
                :viewBox="`0 0 ${svgW} ${svgH}`"
                :height="svgH"
                class="w-full"
                xmlns="http://www.w3.org/2000/svg"
            >
                <defs>
                    <filter id="cs" x="-30%" y="-30%" width="160%" height="160%">
                        <feDropShadow dx="0" dy="6" stdDeviation="14" flood-color="rgba(0,0,0,0.15)" />
                    </filter>
                    <filter id="ps" x="-10%" y="-30%" width="120%" height="160%">
                        <feDropShadow dx="0" dy="4" stdDeviation="8" flood-color="rgba(0,0,0,0.18)" />
                    </filter>
                    <filter id="circle-shadow" x="-50%" y="-50%" width="200%" height="200%">
                        <feDropShadow dx="0" dy="3" stdDeviation="5" flood-color="rgba(0,0,0,0.32)" />
                    </filter>
                    <radialGradient id="cg" cx="35%" cy="30%" r="65%">
                        <stop offset="0%" stop-color="#ffffff"/>
                        <stop offset="100%" stop-color="#e8edf5"/>
                    </radialGradient>
                    <!-- Per-module gradients -->
                    <linearGradient
                        v-for="(m, i) in [...leftPositioned, ...rightPositioned]"
                        :key="'g' + i"
                        :id="'pg' + i"
                        x1="0%" y1="0%" x2="100%" y2="0%"
                    >
                        <stop offset="0%" :stop-color="m.color.dark"/>
                        <stop offset="100%" :stop-color="m.color.light"/>
                    </linearGradient>
                </defs>

                <!-- ══ LEFT SIDE ══ -->
                <g v-for="(mod, i) in leftPositioned" :key="'lc-' + i">
                    <!-- Connector line with unique ID -->
                    <path
                        :id="`lpath${i}`"
                        :d="`M ${cx - cr * 0.85} ${cy + (mod.y - cy) * 0.5}
                             C ${cx - cr - 20} ${cy}, ${leftPillRightEdge + 40} ${mod.y}, ${leftPillRightEdge} ${mod.y}`"
                        :stroke="mod.color.bg"
                        stroke-width="2"
                        fill="none"
                        stroke-dasharray="5 4"
                        :opacity="animated ? 0.7 : 0"
                        style="transition: opacity 0.7s ease;"
                    />
                    <!-- Junction dot -->
                    <circle
                        :cx="leftPillRightEdge"
                        :cy="mod.y"
                        r="4"
                        :fill="mod.color.bg"
                        :opacity="animated ? 1 : 0"
                        style="transition: opacity 0.6s ease;"
                    />
                    <!-- Pulse 1 -->
                    <circle r="5" :fill="mod.color.light" opacity="0.95" filter="url(#glow-dot)">
                        <filter :id="`glow-l${i}`">
                            <feGaussianBlur stdDeviation="3" result="blur"/>
                            <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
                        </filter>
                        <animateMotion
                            :dur="`${1.8 + i * 0.15}s`"
                            repeatCount="indefinite"
                            :begin="`${i * 0.3}s`"
                        >
                            <mpath :href="`#lpath${i}`"/>
                        </animateMotion>
                    </circle>
                    <!-- Pulse 2 (offset) -->
                    <circle r="3.5" :fill="mod.color.bg" opacity="0.6">
                        <animateMotion
                            :dur="`${1.8 + i * 0.15}s`"
                            repeatCount="indefinite"
                            :begin="`${i * 0.3 + 0.9}s`"
                        >
                            <mpath :href="`#lpath${i}`"/>
                        </animateMotion>
                    </circle>
                </g>

                <!-- Left pills (right-aligned: pill right edge = leftPillRightEdge) -->
                <g
                    v-for="(mod, i) in leftPositioned"
                    :key="'lp-' + i"
                    :style="`transition: transform 0.7s cubic-bezier(.4,2,.6,1) ${i * 90}ms, opacity 0.7s ease ${i * 90}ms; opacity: ${animated ? 1 : 0}; transform: translateX(${animated ? 0 : -24}px);`"
                >
                    <!-- Pill body -->
                    <rect
                        :x="leftPillRightEdge - pillW - pillR"
                        :y="mod.y - pillH / 2"
                        :width="pillW"
                        :height="pillH"
                        :rx="pillR"
                        :fill="`url(#pg${i})`"
                        filter="url(#ps)"
                    />
                    <!-- Left icon circle with code -->
                    <circle
                        :cx="leftPillRightEdge - pillW - pillR + pillR"
                        :cy="mod.y"
                        r="28"
                        :fill="mod.color.dark"
                        stroke="white"
                        stroke-width="2.5"
                        filter="url(#circle-shadow)"
                    />
                    <text
                        :x="leftPillRightEdge - pillW - pillR + pillR"
                        :y="mod.y + 1"
                        text-anchor="middle"
                        dominant-baseline="middle"
                        fill="white"
                        font-size="9"
                        font-weight="900"
                        font-family="system-ui, sans-serif"
                        letter-spacing="0.5"
                    >{{ mod.code.length > 5 ? mod.code.substring(0,5) : mod.code }}</text>
                    <!-- Count badge on right of pill -->
                    <circle
                        :cx="leftPillRightEdge - pillR"
                        :cy="mod.y"
                        r="22"
                        fill="rgba(0,0,0,0.3)"
                        stroke="white"
                        stroke-width="1.5"
                        stroke-opacity="0.4"
                        filter="url(#circle-shadow)"
                    />
                    <text
                        :x="leftPillRightEdge - pillR"
                        :y="mod.y + 1"
                        text-anchor="middle"
                        dominant-baseline="middle"
                        fill="white"
                        font-size="13"
                        font-weight="900"
                        font-family="system-ui, sans-serif"
                    >{{ mod.count }}</text>

                    <!-- Title & percent inside pill (no code here anymore) -->
                    <text
                        :x="leftPillRightEdge - pillW - pillR + pillR * 2 + 10"
                        :y="mod.y - 3"
                        fill="white"
                        font-size="13"
                        font-weight="900"
                        font-family="system-ui, sans-serif"
                    >{{ mod.titre.length > 22 ? mod.titre.substring(0, 21) + '…' : mod.titre }}</text>
                    <text
                        :x="leftPillRightEdge - pillW - pillR + pillR * 2 + 10"
                        :y="mod.y + 18"
                        fill="rgba(255,255,255,0.5)"
                        font-size="9"
                        font-weight="700"
                        font-family="system-ui, sans-serif"
                    >{{ mod.percent }}% des apprenants{{ !mod.is_active ? ' · Suspendu' : '' }}</text>
                </g>

                <!-- ══ RIGHT SIDE ══ -->
                <g v-for="(mod, i) in rightPositioned" :key="'rc-' + i">
                    <path
                        :id="`rpath${i}`"
                        :d="`M ${cx + cr * 0.85} ${cy + (mod.y - cy) * 0.5}
                             C ${cx + cr + 20} ${cy}, ${rightPillLeftEdge - 40} ${mod.y}, ${rightPillLeftEdge} ${mod.y}`"
                        :stroke="mod.color.bg"
                        stroke-width="2"
                        fill="none"
                        stroke-dasharray="5 4"
                        :opacity="animated ? 0.7 : 0"
                        style="transition: opacity 0.7s ease;"
                    />
                    <!-- Junction dot -->
                    <circle
                        :cx="rightPillLeftEdge"
                        :cy="mod.y"
                        r="4"
                        :fill="mod.color.bg"
                        :opacity="animated ? 1 : 0"
                        style="transition: opacity 0.6s ease;"
                    />
                    <!-- Pulse 1 -->
                    <circle r="5" :fill="mod.color.light" opacity="0.95" filter="url(#glow-dot)">
                        <animateMotion
                            :dur="`${1.8 + i * 0.15}s`"
                            repeatCount="indefinite"
                            :begin="`${i * 0.3 + 0.15}s`"
                        >
                            <mpath :href="`#rpath${i}`"/>
                        </animateMotion>
                    </circle>
                    <!-- Pulse 2 (offset) -->
                    <circle r="3.5" :fill="mod.color.bg" opacity="0.6">
                        <animateMotion
                            :dur="`${1.8 + i * 0.15}s`"
                            repeatCount="indefinite"
                            :begin="`${i * 0.3 + 1.05}s`"
                        >
                            <mpath :href="`#rpath${i}`"/>
                        </animateMotion>
                    </circle>
                </g>

                <!-- Right pills -->
                <g
                    v-for="(mod, i) in rightPositioned"
                    :key="'rp-' + i"
                    :style="`transition: transform 0.7s cubic-bezier(.4,2,.6,1) ${i * 90}ms, opacity 0.7s ease ${i * 90}ms; opacity: ${animated ? 1 : 0}; transform: translateX(${animated ? 0 : 24}px);`"
                >
                    <!-- Pill body -->
                    <rect
                        :x="rightPillLeftEdge + pillR"
                        :y="mod.y - pillH / 2"
                        :width="pillW"
                        :height="pillH"
                        :rx="pillR"
                        :fill="`url(#pg${leftPositioned.length + i})`"
                        filter="url(#ps)"
                    />
                    <!-- Right icon circle with code -->
                    <circle
                        :cx="rightPillLeftEdge + pillR"
                        :cy="mod.y"
                        r="28"
                        :fill="mod.color.dark"
                        stroke="white"
                        stroke-width="2.5"
                        filter="url(#circle-shadow)"
                    />
                    <text
                        :x="rightPillLeftEdge + pillR"
                        :y="mod.y + 1"
                        text-anchor="middle"
                        dominant-baseline="middle"
                        fill="white"
                        font-size="9"
                        font-weight="900"
                        font-family="system-ui, sans-serif"
                        letter-spacing="0.5"
                    >{{ mod.code.length > 5 ? mod.code.substring(0,5) : mod.code }}</text>
                    <!-- Count badge on right -->
                    <circle
                        :cx="rightPillLeftEdge + pillR + pillW - pillR"
                        :cy="mod.y"
                        r="22"
                        fill="rgba(0,0,0,0.3)"
                        stroke="white"
                        stroke-width="1.5"
                        stroke-opacity="0.4"
                        filter="url(#circle-shadow)"
                    />
                    <text
                        :x="rightPillLeftEdge + pillR + pillW - pillR"
                        :y="mod.y + 1"
                        text-anchor="middle"
                        dominant-baseline="middle"
                        fill="white"
                        font-size="13"
                        font-weight="900"
                        font-family="system-ui, sans-serif"
                    >{{ mod.count }}</text>

                    <!-- Title & percent inside pill (no code here anymore) -->
                    <text
                        :x="rightPillLeftEdge + pillR * 2 + 14"
                        :y="mod.y - 3"
                        fill="white"
                        font-size="13"
                        font-weight="900"
                        font-family="system-ui, sans-serif"
                    >{{ mod.titre.length > 22 ? mod.titre.substring(0, 21) + '…' : mod.titre }}</text>
                    <text
                        :x="rightPillLeftEdge + pillR * 2 + 14"
                        :y="mod.y + 18"
                        fill="rgba(255,255,255,0.5)"
                        font-size="9"
                        font-weight="700"
                        font-family="system-ui, sans-serif"
                    >{{ mod.percent }}% des apprenants{{ !mod.is_active ? ' · Suspendu' : '' }}</text>
                </g>

                <!-- ══ CENTRAL CIRCLE ══ -->
                <defs>
                    <filter id="glow-dot" x="-80%" y="-80%" width="260%" height="260%">
                        <feGaussianBlur stdDeviation="4" result="blur"/>
                        <feMerge>
                            <feMergeNode in="blur"/>
                            <feMergeNode in="blur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                    <filter id="glow-center" x="-40%" y="-40%" width="180%" height="180%">
                        <feGaussianBlur stdDeviation="8" result="blur"/>
                        <feMerge>
                            <feMergeNode in="blur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                </defs>

                <!-- Animated expanding rings (pulse from center) -->
                <circle :cx="cx" :cy="cy" :r="cr + 5" fill="none" stroke="#818cf8" stroke-width="2" opacity="0">
                    <animate attributeName="r" :from="cr + 5" :to="cr + 45" dur="2.4s" repeatCount="indefinite" begin="0s"/>
                    <animate attributeName="opacity" from="0.6" to="0" dur="2.4s" repeatCount="indefinite" begin="0s"/>
                </circle>
                <circle :cx="cx" :cy="cy" :r="cr + 5" fill="none" stroke="#818cf8" stroke-width="1.5" opacity="0">
                    <animate attributeName="r" :from="cr + 5" :to="cr + 45" dur="2.4s" repeatCount="indefinite" begin="0.8s"/>
                    <animate attributeName="opacity" from="0.4" to="0" dur="2.4s" repeatCount="indefinite" begin="0.8s"/>
                </circle>
                <circle :cx="cx" :cy="cy" :r="cr + 5" fill="none" stroke="#818cf8" stroke-width="1" opacity="0">
                    <animate attributeName="r" :from="cr + 5" :to="cr + 45" dur="2.4s" repeatCount="indefinite" begin="1.6s"/>
                    <animate attributeName="opacity" from="0.3" to="0" dur="2.4s" repeatCount="indefinite" begin="1.6s"/>
                </circle>

                <!-- Soft glow ring -->
                <circle :cx="cx" :cy="cy" :r="cr + 14" fill="rgba(99,102,241,0.07)" />
                <!-- Dashed accent ring -->
                <circle
                    :cx="cx" :cy="cy" :r="cr + 18"
                    fill="none"
                    stroke="#818cf8"
                    stroke-width="1.5"
                    stroke-dasharray="7 6"
                    opacity="0.35"
                />

                <!-- Main white circle -->
                <circle :cx="cx" :cy="cy" :r="cr" fill="url(#cg)" filter="url(#cs)" />

                <!-- Departure arcs: colored arc INSIDE the circle for each connector -->
                <path
                    v-for="(arc, i) in departureArcs"
                    :key="'darc-' + i"
                    :d="arc.path"
                    :stroke="arc.color"
                    stroke-width="6"
                    fill="none"
                    stroke-linecap="round"
                    opacity="0.95"
                />
                <!-- Thin bright highlight on each departure arc -->
                <path
                    v-for="(arc, i) in departureArcs"
                    :key="'darc-h-' + i"
                    :d="arc.path"
                    :stroke="arc.light"
                    stroke-width="2"
                    fill="none"
                    stroke-linecap="round"
                    opacity="0.75"
                />

                <!-- Center text -->
                <text :x="cx" :y="cy - 32" text-anchor="middle" fill="#475569" font-size="10" font-weight="900" font-family="system-ui, sans-serif" letter-spacing="2">RÉPARTITION</text>
                <text :x="cx" :y="cy - 14" text-anchor="middle" fill="#475569" font-size="10" font-weight="900" font-family="system-ui, sans-serif" letter-spacing="2">PAR MODULE</text>
                <!-- Big total -->
                <text :x="cx" :y="cy + 24" text-anchor="middle" fill="#4f46e5" font-size="40" font-weight="900" font-family="system-ui, sans-serif">{{ total }}</text>
                <text :x="cx" :y="cy + 44" text-anchor="middle" fill="#94a3b8" font-size="10" font-weight="700" font-family="system-ui, sans-serif">apprenants</text>
            </svg>

        </div>
    </div>
</template>
