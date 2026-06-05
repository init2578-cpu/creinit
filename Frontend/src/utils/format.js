/**
 * Formats a time string or number to HH:mm:ss (24h format)
 * @param {string|number} time - The time to format (e.g., '8:0', '14:30', or 8)
 * @returns {string} - Formatted time string
 */
export function formatTime(time) {
    if (time === null || time === undefined) return '--:--'
    
    let hours = '00'
    let minutes = '00'

    if (typeof time === 'number') {
        hours = Math.floor(time).toString().padStart(2, '0')
    } else if (typeof time === 'string') {
        // Handle cases like '08:00', '14:30:00', '8h00'
        const cleanTime = time.replace('h', ':')
        const parts = cleanTime.split(':')
        
        hours = (parts[0] || '00').padStart(2, '0')
        minutes = (parts[1] || '00').padStart(2, '0')
    }

    return `${hours}:${minutes}`
}

/**
 * Formats a datetime string to HH:mm:ss
 * @param {string} dateStr 
 */
export function formatDateTimeToTime(dateStr) {
    if (!dateStr) return '--:--:--'
    const date = new Date(dateStr)
    if (isNaN(date.getTime())) return formatTime(dateStr) // Fallback to string parsing
    
    const h = date.getHours().toString().padStart(2, '0')
    const m = date.getMinutes().toString().padStart(2, '0')
    const s = date.getSeconds().toString().padStart(2, '0')
    
    return `${h}:${m}:${s}`
}

/**
 * Formats a date string to DD-MM-YYYY
 * @param {string} dateStr 
 */
export function formatDate(dateStr) {
    if (!dateStr) return '--/--/----'
    const date = new Date(dateStr)
    if (isNaN(date.getTime())) return dateStr
    
    const d = date.getDate().toString().padStart(2, '0')
    const m = (date.getMonth() + 1).toString().padStart(2, '0')
    const y = date.getFullYear()
    
    return `${d}-${m}-${y}`
}

/**
 * Returns a proper URL for a storage-managed or public asset.
 * Handles paths that already start with '/' (public folder)
 * and relative paths that need the /storage/ prefix.
 * @param {string|null} path - The stored file path
 * @returns {string|null}
 */
export function storageUrl(path) {
    if (!path) return null
    if (path.startsWith('http://') || path.startsWith('https://')) return path
    if (path.startsWith('/')) return path  // Already an absolute public path
    return '/storage/' + path              // Relative storage path
}
