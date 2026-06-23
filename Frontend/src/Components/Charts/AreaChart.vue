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
const crosshairPlugin = {
    id: 'crosshair',
    afterInit(chart) {
        chart.crosshair = {
            x: null,
            y: null,
            draw: false,
            index: null
        }
    },
    afterEvent(chart, args) {
        const { event } = args
        const { type } = event
        
        if (type === 'mousemove' || type === 'mouseout') {
            const chartArea = chart.chartArea
            if (type === 'mousemove' &&
                event.x >= chartArea.left && event.x <= chartArea.right &&
                event.y >= chartArea.top && event.y <= chartArea.bottom) {
                
                const xAxis = chart.scales.x
                const xVal = xAxis.getValueForPixel(event.x)
                const index = Math.max(0, Math.min(chart.data.labels.length - 1, Math.round(xVal)))
                
                chart.crosshair = {
                    x: event.x,
                    y: event.y,
                    draw: true,
                    index: index
                }
            } else {
                chart.crosshair = {
                    x: null,
                    y: null,
                    draw: false,
                    index: null
                }
            }
            chart.update('none')
        }
    },
    afterDraw(chart) {
        if (!chart.crosshair || !chart.crosshair.draw) return
        
        const { ctx, chartArea } = chart
        const { x, y, index } = chart.crosshair
        
        const xAxis = chart.scales.x
        const yAxis = chart.scales.y
        
        const snappedX = xAxis.getPixelForValue(index)
        
        ctx.save()
        
        // Draw vertical line
        ctx.beginPath()
        ctx.setLineDash([4, 4])
        ctx.strokeStyle = '#64748b' // slate-500
        ctx.lineWidth = 1.2
        ctx.moveTo(snappedX, chartArea.top)
        ctx.lineTo(snappedX, chartArea.bottom)
        ctx.stroke()
        
        // Draw horizontal line
        ctx.beginPath()
        ctx.moveTo(chartArea.left, y)
        ctx.lineTo(chartArea.right, y)
        ctx.stroke()
        
        // Labels
        const xLabel = chart.data.labels[index] || ''
        const yVal = yAxis.getValueForPixel(y)
        const yLabel = yVal.toFixed(1) + '%'
        
        // Draw X axis label box at the bottom
        if (xLabel) {
            ctx.font = 'bold 10px system-ui, sans-serif'
            const xTextWidth = ctx.measureText(xLabel).width
            const xBoxW = xTextWidth + 12
            const xBoxH = 20
            const xBoxX = snappedX - xBoxW / 2
            const xBoxY = chartArea.bottom
            
            ctx.fillStyle = '#1e293b' // slate-800
            ctx.fillRect(xBoxX, xBoxY, xBoxW, xBoxH)
            
            ctx.fillStyle = '#ffffff'
            ctx.textBaseline = 'middle'
            ctx.textAlign = 'center'
            ctx.fillText(xLabel, snappedX, xBoxY + xBoxH / 2)
        }
        
        // Draw Y axis label box at the left
        if (yLabel) {
            ctx.font = 'bold 10px system-ui, sans-serif'
            const yTextWidth = ctx.measureText(yLabel).width
            const yBoxW = yTextWidth + 12
            const yBoxH = 20
            const yBoxX = chartArea.left - yBoxW
            const yBoxY = y - yBoxH / 2
            
            ctx.fillStyle = '#1e293b' // slate-800
            ctx.fillRect(yBoxX, yBoxY, yBoxW, yBoxH)
            
            ctx.fillStyle = '#ffffff'
            ctx.textBaseline = 'middle'
            ctx.textAlign = 'center'
            ctx.fillText(yLabel, yBoxX + yBoxW / 2, y + 1)
        }
        
        ctx.restore()
    }
}
</script>

<template>
    <div class="h-64">
        <Line :data="chartData" :options="chartOptions" :plugins="[crosshairPlugin]" />
    </div>
</template>
