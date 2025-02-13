import {ref, computed} from 'vue';
import api from '@/http/api'

const _entries = ref([]);

export const logEntries = computed(() => {
    return _entries.value;
});

export const fetchEntries = async (apiUrl) => {
    _entries.value = await api.get(apiUrl)
        .then(response => response.data.data);
}

export const saveEntry = async (apiUrl, entryData) => {
    return await api.post(apiUrl, entryData)
        .then(response => {
            _entries.value.push(response.data);
            return response.data
        });
}

export const updateEntry = async (apiUrl, entryData) => {
    return await api.put(apiUrl, entryData)
        .then(response => {
            const entryIdx = _entries.value.findIndex(entry => entry.id == response.data.id);
            _entries.value[entryIdx] = response.data;
            return response.data
        });
}

export const deleteEntry = async (apiUrl, entryId) => {
    return await api.delete(`${apiUrl}/${entryId}`)
        .then(response => {
            const entryIdx = _entries.value.findIndex(entry => entry.id == entryId);
            _entries.value.splice(entryIdx, 1);

            return response;
        });
}
