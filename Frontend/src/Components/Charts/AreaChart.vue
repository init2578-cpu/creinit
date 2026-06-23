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

// Helper to safely get event coordinates across different Chart.js versions
const getEventCoords = (event) => {
    if (!event) return { x: null, y: null }
    if (event.x !== undefined && event.y !== undefined) {
        return { x: event.x, y: event.y }
    }
    if (event.native) {
        if (event.native.offsetX !== undefined && event.native.offsetY !== undefined) {
            return { x: event.native.offsetX, y: event.native.offsetY }
        }
        const target = event.native.target || event.native.srcElement
        if (target && typeof target.getBoundingClientRect === 'function') {
            const rect = target.getBoundingClientRect()
            return {
                x: event.native.clientX - rect.left,
                y: event.native.clientY - rect.top
            }
        }
    }
    return { x: null, y: null }
}

const crosshairPlugin = {
    id: 'crosshair',
    afterInit(chart) {
        if (chart.config.type !== 'line') return
        chart.crosshair = {
            x: null,
            y: null,
            draw: false,
            index: null
        }
    },
    afterEvent(chart, args) {
        if (chart.config.type !== 'line') return
        
        const { event } = args
        if (!event) return
        
        const type = event.type
        
        if (!chart.crosshair) {
            chart.crosshair = {
                x: null,
                y: null,
                draw: false,
                index: null
            }
        }
        
        if (type === 'mousemove' || type === 'mouseout') {
            const chartArea = chart.chartArea
            const { x, y } = getEventCoords(event)
            
            if (type === 'mousemove' && x !== null && y !== null &&
                x >= chartArea.left && x <= chartArea.right &&
                y >= chartArea.top && y <= chartArea.bottom) {
                
                const xAxis = chart.scales.x
                const xVal = xAxis.getValueForPixel(x)
                const index = Math.max(0, Math.min(chart.data.labels.length - 1, Math.round(xVal)))
                
                chart.crosshair.x = x
                chart.crosshair.y = y
                chart.crosshair.draw = true
                chart.crosshair.index = index
            } else {
                chart.crosshair.x = null
                chart.crosshair.y = null
                chart.crosshair.draw = false
                chart.crosshair.index = null
            }
            
            args.changed = true
            chart.draw()
        }
    },
    afterDraw(chart) {
        if (chart.config.type !== 'line') return
        if (!chart.crosshair || !chart.crosshair.draw) return
        
        const { ctx, chartArea } = chart
        const { x, y, index } = chart.crosshair
        
        const xAxis = chart.scales.x
        const yAxis = chart.scales.y
        
        if (index === null || index === undefined || isNaN(index)) return
        
        const snappedX = xAxis.getPixelForValue(index)
        if (snappedX === undefined || snappedX === null || isNaN(snappedX)) return
        
        ctx.save()
        
        // Draw vertical line (snapped to nearest data point X)
        ctx.beginPath()
        ctx.setLineDash([4, 4])
        ctx.strokeStyle = '#64748b' // slate-500
        ctx.lineWidth = 1.2
        ctx.moveTo(snappedX, chartArea.top)
        ctx.lineTo(snappedX, chartArea.bottom)
        ctx.stroke()
        
        // Draw horizontal line (continuous at cursor Y)
        ctx.beginPath()
        ctx.setLineDash([4, 4])
        ctx.strokeStyle = '#64748b'
        ctx.lineWidth = 1.2
        ctx.moveTo(chartArea.left, y)
        ctx.lineTo(chartArea.right, y)
        ctx.stroke()
        
        // Labels
        const xLabel = chart.data.labels[index] || ''
        const yVal = yAxis.getValueForPixel(y)
        const yLabel = yVal.toFixed(1) + '%'
        
        // Draw X axis label box at the bottom
        if (xLabel) {
            ctx.font = 'bold 10px system-ui, -apple-system, sans-serif'
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
            ctx.font = 'bold 10px system-ui, -apple-system, sans-serif'
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

// Register the crosshairPlugin globally so ChartJS is guaranteed to execute it on Line charts
ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale, Filler, crosshairPlugin)

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
        tension: 0,
        borderWidth: 2.5,
        pointBackgroundColor: props.color,
        pointBorderColor: '#fff',
        pointBorderWidth: 1.5,
        pointRadius: 0,
        pointHoverRadius: 4,
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
