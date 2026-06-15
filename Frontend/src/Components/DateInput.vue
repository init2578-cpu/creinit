<script>
export default {
    inheritAttrs: false
}
</script>

<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    required: {
        type: Boolean,
        default: false
    },
    placeholder: {
        type: String,
        default: 'jj/mm/aaaa'
    },
    minDate: {
        type: String,
        default: ''
    },
    maxDate: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue'])

const displayValue = ref('')
const showCalendar = ref(false)
const containerRef = ref(null)

// Calendar state
const getDefaultDate = () => {
    const now = new Date()
    if (props.maxDate) {
        const [maxY, maxM, maxD] = props.maxDate.split('-').map(Number)
        const maxDateObj = new Date(maxY, maxM - 1, maxD)
        if (now > maxDateObj) {
            return new Date(maxY, maxM - 1, 1)
        }
    }
    if (props.minDate) {
        const [minY, minM, minD] = props.minDate.split('-').map(Number)
        const minDateObj = new Date(minY, minM - 1, minD)
        if (now < minDateObj) {
            return new Date(minY, minM - 1, 1)
        }
    }
    return new Date(now.getFullYear(), now.getMonth(), 1)
}

const currentDate = ref(getDefaultDate())
const selectedDate = ref(null)

const months = [
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
]
const daysOfWeek = ['Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa', 'Di']

// Watch for changes in modelValue
watch(() => props.modelValue, (newVal) => {
    if (newVal && newVal.match(/^\d{4}-\d{2}-\d{2}$/)) {
        const [year, month, day] = newVal.split('-')
        displayValue.value = `${day}/${month}/${year}`
        selectedDate.value = new Date(parseInt(year), parseInt(month) - 1, parseInt(day))
        currentDate.value = new Date(parseInt(year), parseInt(month) - 1, 1)
    } else {
        displayValue.value = ''
        selectedDate.value = null
        currentDate.value = getDefaultDate()
    }
}, { immediate: true })

const currentMonthName = computed(() => months[currentDate.value.getMonth()])
const currentYear = computed(() => currentDate.value.getFullYear())

// Days in current month grid
const calendarDays = computed(() => {
    const year = currentDate.value.getFullYear()
    const month = currentDate.value.getMonth()
    
    // First day of month (0 = Sunday, 1 = Monday, etc.)
    const firstDayIndex = new Date(year, month, 1).getDay()
    // Convert Sunday = 0 to Sunday = 6, Monday = 0
    const startDayOffset = firstDayIndex === 0 ? 6 : firstDayIndex - 1
    
    const daysInMonth = new Date(year, month + 1, 0).getDate()
    const daysInPrevMonth = new Date(year, month, 0).getDate()
    
    const grid = []
    
    // Previous month's trailing days
    for (let i = startDayOffset - 1; i >= 0; i--) {
        grid.push({
            day: daysInPrevMonth - i,
            month: month === 0 ? 11 : month - 1,
            year: month === 0 ? year - 1 : year,
            isCurrentMonth: false
        })
    }
    
    // Current month's days
    for (let i = 1; i <= daysInMonth; i++) {
        grid.push({
            day: i,
            month: month,
            year: year,
            isCurrentMonth: true
        })
    }
    
    // Next month's leading days to complete the 42 days grid (6 weeks)
    const remaining = 42 - grid.length
    for (let i = 1; i <= remaining; i++) {
        grid.push({
            day: i,
            month: month === 11 ? 0 : month + 1,
            year: month === 11 ? year + 1 : year,
            isCurrentMonth: false
        })
    }
    
    return grid
})

const availableYears = computed(() => {
    const currentYr = new Date().getFullYear()
    let minYear = currentYr - 100
    let maxYear = currentYr + 10
    
    if (props.minDate) {
        minYear = parseInt(props.minDate.split('-')[0])
    }
    if (props.maxDate) {
        maxYear = parseInt(props.maxDate.split('-')[0])
    }
    
    const years = []
    for (let i = minYear; i <= maxYear; i++) {
        years.push(i)
    }
    return years.reverse() // Reverse to show newest years first
})

const onYearChange = (event) => {
    const newYear = parseInt(event.target.value)
    currentDate.value = new Date(newYear, currentDate.value.getMonth(), 1)
}

const prevMonth = () => {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1)
}

const nextMonth = () => {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1)
}

const selectDay = (dayObj) => {
    const formattedMonth = (dayObj.month + 1).toString().padStart(2, '0')
    const formattedDay = dayObj.day.toString().padStart(2, '0')
    const dateStr = `${dayObj.year}-${formattedMonth}-${formattedDay}`
    
    emit('update:modelValue', dateStr)
    showCalendar.value = false
}

// Format manual input
const onInput = (event) => {
    let value = event.target.value
    let clean = value.replace(/\D/g, "").substring(0, 8)
    
    let formatted = ""
    if (clean.length > 0) formatted += clean.substring(0, 2)
    if (clean.length > 2) formatted += "/" + clean.substring(2, 4)
    if (clean.length > 4) formatted += "/" + clean.substring(4, 8)
    
    displayValue.value = formatted
    
    if (clean.length === 8) {
        const day = clean.substring(0, 2)
        const month = clean.substring(2, 4)
        const year = clean.substring(4, 8)
        const dateStr = `${year}-${month}-${day}`
        
        if (props.minDate) {
            const [minY, minM, minD] = props.minDate.split('-').map(Number)
            const minDateObj = new Date(minY, minM - 1, minD)
            const inputDateObj = new Date(parseInt(year), parseInt(month) - 1, parseInt(day))
            
            if (inputDateObj < minDateObj) {
                displayValue.value = ''
                emit('update:modelValue', '')
                return
            }
        }
        
        if (props.maxDate) {
            const [maxY, maxM, maxD] = props.maxDate.split('-').map(Number)
            const maxDateObj = new Date(maxY, maxM - 1, maxD)
            const inputDateObj = new Date(parseInt(year), parseInt(month) - 1, parseInt(day))
            
            if (inputDateObj > maxDateObj) {
                displayValue.value = ''
                emit('update:modelValue', '')
                return
            }
        }
        
        emit('update:modelValue', dateStr)
    } else {
        emit('update:modelValue', '')
    }
}

// Click outside handler to close dropdown
const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        showCalendar.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})

const isSelected = (dayObj) => {
    if (!selectedDate.value) return false
    return selectedDate.value.getDate() === dayObj.day &&
        selectedDate.value.getMonth() === dayObj.month &&
        selectedDate.value.getFullYear() === dayObj.year
}

const isToday = (dayObj) => {
    const today = new Date()
    return today.getDate() === dayObj.day &&
        today.getMonth() === dayObj.month &&
        today.getFullYear() === dayObj.year
}

const isDayDisabled = (dayObj) => {
    let disabled = false;
    const targetDateObj = new Date(dayObj.year, dayObj.month, dayObj.day)
    
    if (props.minDate) {
        const [minY, minM, minD] = props.minDate.split('-').map(Number)
        const minDateObj = new Date(minY, minM - 1, minD)
        if (targetDateObj < minDateObj) disabled = true;
    }
    
    if (props.maxDate) {
        const [maxY, maxM, maxD] = props.maxDate.split('-').map(Number)
        const maxDateObj = new Date(maxY, maxM - 1, maxD)
        if (targetDateObj > maxDateObj) disabled = true;
    }
    
    return disabled;
}
</script>

<template>
    <div ref="containerRef" class="relative w-full">
        <input
            type="text"
            v-bind="$attrs"
            :value="displayValue"
            @input="onInput"
            @focus="showCalendar = true"
            :placeholder="placeholder"
            :required="required"
            maxlength="10"
        />
        
        <!-- Calendar Dropdown -->
        <div v-if="showCalendar" class="absolute left-0 right-0 md:right-auto md:w-72 z-50 mt-2 p-4 bg-white border border-gray-100 rounded-3xl shadow-2xl animate-in fade-in slide-in-from-top-2 duration-200">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <button type="button" @click="prevMonth" class="p-1.5 hover:bg-gray-50 rounded-xl text-gray-600 transition">
                    <ChevronLeftIcon class="h-5 w-5" />
                </button>
                <div class="flex items-center gap-1.5">
                    <span class="font-black text-xs text-gray-900 uppercase tracking-wider">
                        {{ currentMonthName }}
                    </span>
                    <select 
                        :value="currentYear" 
                        @change="onYearChange"
                        class="font-black text-xs text-blue-600 uppercase tracking-wider bg-transparent border-none py-0 pl-1 pr-6 focus:ring-0 cursor-pointer appearance-none hover:bg-blue-50 rounded-lg transition-colors"
                        style="background-position: right 2px center; background-size: 12px;"
                    >
                        <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>
                <button type="button" @click="nextMonth" class="p-1.5 hover:bg-gray-50 rounded-xl text-gray-600 transition">
                    <ChevronRightIcon class="h-5 w-5" />
                </button>
            </div>
            
            <!-- Week Days Header -->
            <div class="grid grid-cols-7 gap-1 text-center mb-2">
                <span v-for="d in daysOfWeek" :key="d" class="text-[9px] font-black text-gray-400 uppercase tracking-widest py-1">
                    {{ d }}
                </span>
            </div>
            
            <!-- Days Grid -->
            <div class="grid grid-cols-7 gap-1">
                <button
                    v-for="(dayObj, idx) in calendarDays"
                    :key="idx"
                    type="button"
                    :disabled="isDayDisabled(dayObj)"
                    @click="!isDayDisabled(dayObj) && selectDay(dayObj)"
                    class="h-8 w-8 mx-auto rounded-xl flex items-center justify-center text-[10px] font-bold transition"
                    :class="[
                        dayObj.isCurrentMonth ? 'text-gray-700' : 'text-gray-300',
                        isDayDisabled(dayObj) ? 'opacity-30 cursor-not-allowed hover:bg-transparent text-gray-300' : 'hover:bg-gray-50',
                        isSelected(dayObj) && !isDayDisabled(dayObj) ? 'bg-orange-600 text-white font-black shadow-lg shadow-orange-100' : '',
                        isToday(dayObj) && !isSelected(dayObj) && !isDayDisabled(dayObj) ? 'border border-orange-200 text-orange-600' : ''
                    ]"
                >
                    {{ dayObj.day }}
                </button>
            </div>
        </div>
    </div>
</template>
