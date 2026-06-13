<script setup>
import { computed } from 'vue'

const props = defineProps({
    value: { type: Number, default: 0 },
    max: { type: Number, default: 100 },
    color: { type: String, default: '#ef4444' },
    trackColor: { type: String, default: '#f3f4f6' },
    size: { type: Number, default: 100 },
    strokeWidth: { type: Number, default: 10 },
    label: { type: String, default: '' },
})

const radius = computed(() => (props.size / 2) - props.strokeWidth)
const circumference = computed(() => 2 * Math.PI * radius.value)
const dashOffset = computed(() => {
    const pct = Math.min(props.value / props.max, 1)
    return circumference.value * (1 - pct * 0.75)
})
const startAngle = -135
</script>

<template>
    <div class="relative inline-flex items-center justify-center" :style="{ width: size + 'px', height: size + 'px' }">
        <svg :width="size" :height="size" class="absolute inset-0" :style="{ transform: 'rotate(-135deg)' }">
            <!-- Track -->
            <circle
                :cx="size / 2" :cy="size / 2" :r="radius"
                fill="none"
                :stroke="trackColor"
                :stroke-width="strokeWidth"
                :stroke-dasharray="`${circumference * 0.75} ${circumference * 0.25}`"
                stroke-linecap="round"
            />
            <!-- Progress -->
            <circle
                :cx="size / 2" :cy="size / 2" :r="radius"
                fill="none"
                :stroke="color"
                :stroke-width="strokeWidth"
                :stroke-dasharray="`${circumference * 0.75} ${circumference * 0.25}`"
                :stroke-dashoffset="dashOffset"
                stroke-linecap="round"
                style="transition: stroke-dashoffset 1.2s cubic-bezier(0.4,0,0.2,1)"
            />
        </svg>
        <div class="relative z-10 text-center">
            <p class="text-xl font-black text-gray-900 leading-none">{{ value }}%</p>
            <p v-if="label" class="text-[9px] font-bold text-gray-400 mt-0.5 uppercase tracking-wider">{{ label }}</p>
        </div>
    </div>
</template>
