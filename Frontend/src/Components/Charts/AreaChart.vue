<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import { 
    Chart as ChartJS, 
    Title, 
    Tooltip, 
    Legend, 
    LineElement, 
    PointElement,
    CategoryScale, 
    LinearScale,
    Filler
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale, Filler)

const props = defineProps({
    labels: Array,
    data: Array,
    label: {
        type: String,
        default: 'Données'
    },
    color: {
        type: String,
        default: '#10b981' // emerald-500
    }
})

const chartData = computed(() => ({
    labels: props.labels,
    datasets: [{
        label: props.label,
        data: props.data,
        borderColor: props.color,
        backgroundColor: props.color + '20', // Add transparency for area fill
        fill: true,
        tension: 0.4,
        borderWidth: 3,
        pointBackgroundColor: props.color,
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
    }]
}))

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                color: '#f3f4f6'
            },
            ticks: {
                font: {
                    weight: 'bold'
                }
            }
        },
        x: {
            grid: {
                display: false
            },
            ticks: {
                font: {
                    weight: 'bold'
                }
            }
        }
    },
    plugins: {
        legend: {
            display: false
        }
    }
}
</script>

<template>
    <div class="h-64">
        <Line :data="chartData" :options="chartOptions" />
    </div>
</template>
