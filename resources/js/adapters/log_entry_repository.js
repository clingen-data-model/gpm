import { ref, computed } from 'vue'
import api from '@/http/api'

const _entries = ref([])
export const logEntries = computed(() => _entries.value)

const inflight = new Map()
let lastKey = null

const buildKey = (url, params = {}) => `${url}::${JSON.stringify(params)}`

export const fetchEntries = async (apiUrl, params = undefined) => {
    const key = buildKey(apiUrl, params || {})
    lastKey = key
    
    if (inflight.has(key)) return inflight.get(key)

    const p = api
        .get(apiUrl, params ? { params } : undefined)
        .then((response) => {
        const data = response?.data?.data ?? response?.data ?? []      
        if (lastKey === key) {
            _entries.value = Array.isArray(data) ? data : []
        }
        return _entries.value
        })
        .catch((err) => {      
        if (lastKey === key) _entries.value = []
        throw err
        })
        .finally(() => {
        inflight.delete(key)
        })

    inflight.set(key, p)
    return p
}

export const saveEntry = async (apiUrl, entryData) => {
    const { data } = await api.post(apiUrl, entryData)
    _entries.value.push(data)
    return data
}

export const updateEntry = async (apiUrl, entryData) => {
    const { data } = await api.put(apiUrl, entryData)
    const idx = _entries.value.findIndex((e) => e.id === data.id)
    if (idx !== -1) _entries.value[idx] = data
    return data
}

export const deleteEntry = async (apiUrl, entryId) => {
    const res = await api.delete(`${apiUrl}/${entryId}`)
    const idx = _entries.value.findIndex((e) => e.id === entryId)
    if (idx !== -1) _entries.value.splice(idx, 1)
    return res
}
