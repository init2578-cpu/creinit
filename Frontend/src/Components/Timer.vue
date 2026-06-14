<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'

const props = defineProps({
    durationMinutes: {
        type: Number,
        default: 30
    },
    absoluteEndTime: {
        type: String, // ISO string or any format Date can parse
        default: null
    }
})

const emit = defineEmits(['expired'])

const timeLeft = ref(props.durationMinutes * 60)
let timerInterval = null
const targetEndTime = ref(null)

const formattedTime = computed(() => {
    const hours = Math.floor(timeLeft.value / 3600)
    const minutes = Math.floor((timeLeft.value % 3600) / 60)
    const seconds = timeLeft.value % 60
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
})

const isLowTime = computed(() => timeLeft.value <= 300) // 5 minutes

onMounted(() => {
    const durationSeconds = props.durationMinutes * 60;
    let end;
    if (props.absoluteEndTime) {
        const globalEnd = new Date(props.absoluteEndTime);
        const maxSessionEnd = new Date(Date.now() + durationSeconds * 1000);
        end = globalEnd < maxSessionEnd ? globalEnd : maxSessionEnd;
    } else {
        end = new Date(Date.now() + durationSeconds * 1000);
    }
    targetEndTime.value = end;

    const tick = () => {
        const remaining = Math.max(0, Math.floor((targetEndTime.value - new Date()) / 1000));
        timeLeft.value = remaining;
        if (remaining <= 0) {
            clearInterval(timerInterval);
            emit('expired');
        }
    };

    tick();
    timerInterval = setInterval(tick, 1000);
})

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval)
})
</script>

<template>
    <div 
        class="text-2xl font-mono font-black px-6 py-3 rounded-2xl transition-all border backdrop-blur-md shadow-lg"
        :class="[
            isLowTime 
                ? 'text-red-500 bg-red-500/10 border-red-500/30 animate-pulse shadow-red-900/20' 
                : 'text-blue-400 bg-blue-500/10 border-blue-500/20 shadow-blue-900/10'
        ]"
    >
        {{ formattedTime }}
    </div>
</template>
