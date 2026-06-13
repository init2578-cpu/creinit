<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue'])

const displayValue = ref('')

// Watch for changes in modelValue (e.g. initial load or parent update)
watch(() => props.modelValue, (newVal) => {
    if (newVal && newVal.match(/^\d{4}-\d{2}-\d{2}$/)) {
        const [year, month, day] = newVal.split('-')
        displayValue.value = `${day}/${month}/${year}`
    } else if (newVal && newVal.match(/^\d{2}\/\d{2}\/\d{4}$/)) {
        displayValue.value = newVal
    } else if (!newVal) {
        displayValue.value = ''
    }
}, { immediate: true })

const onInput = (event) => {
    let value = event.target.value
    
    // Remove all non-numeric characters
    let clean = value.replace(/\D/g, "")
    
    // Limit to 8 digits
    clean = clean.substring(0, 8)
    
    // Build formatted string
    let formatted = ""
    if (clean.length > 0) {
        formatted += clean.substring(0, 2)
    }
    if (clean.length > 2) {
        formatted += "/" + clean.substring(2, 4)
    }
    if (clean.length > 4) {
        formatted += "/" + clean.substring(4, 8)
    }
    
    // Update local display value
    displayValue.value = formatted
    
    // If complete, convert to YYYY-MM-DD and emit
    if (clean.length === 8) {
        const day = clean.substring(0, 2)
        const month = clean.substring(2, 4)
        const year = clean.substring(4, 8)
        emit('update:modelValue', `${year}-${month}-${day}`)
    } else {
        emit('update:modelValue', '')
    }
}
</script>

<template>
    <input
        type="text"
        :value="displayValue"
        @input="onInput"
        placeholder="jj/mm/aaaa"
        maxlength="10"
    />
</template>
