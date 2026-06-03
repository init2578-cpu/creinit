<script setup>
import { Doughnut } from 'vue-chartjs'
import { 
    Chart as ChartJS, 
    Title, 
    Tooltip, 
    Legend, 
    ArcElement, 
    CategoryScale 
} from 'chart.js'

import { computed } from 'vue'

ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale)

const props = defineProps({
    male: Number,
    female: Number
})

const chartData = computed(() => ({
    labels: ['Hommes', 'Femmes'],
    datasets: [{
        data: [props.male, props.female],
        backgroundColor: ['#2563eb', '#db2777'], // blue-600, pink-600
        hoverOffset: 4,
        borderWidth: 0,
        borderRadius: 10,
    }]
}))

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    const label = context.label || ''
                    const value = context.raw || 0
                    const total = props.male + props.female
                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0
                    return `${label}: ${value} (${percentage}%)`
                }
            }
        }
    }
}
</script>

<template>
    <div class="h-64">
        <Doughnut :data="chartData" :options="chartOptions" />
    </div>
</template>
