
import { reactive, ref } from 'vue'
import { useStore } from 'vuex'

export default function useEmitCheckpoints () {
    const store = useStore()

    const emitting = ref(false)
    const emittingRow = reactive({})

    const isActive = g => g.group_status_id === 2 || g.status?.id === 2 || (g.status?.name || '').toLowerCase() === 'active'

    /**
     * @param {number[]} ids
     * @param {{ rowId?: number, queue?: boolean, toast?: boolean }} opts
     */
    const emitCheckpoints = async (ids, { rowId, queue = true, toast = true } = {}) => {
        if (!Array.isArray(ids) || ids.length === 0) {
            if (toast) store.commit('pushError', 'No groups to checkpoint.')
            return null
        }

        if (rowId) emittingRow[rowId] = true
        else emitting.value = true

        try {
            const res = await store.dispatch('groups/checkpoints', { group_ids: ids, queue })
            const accepted = res?.accepted ?? 0
            const denied   = (res?.denied_ids || []).length
            const missing  = (res?.not_found_ids || []).length

            if (toast) {
                if (accepted > 0) {
                    store.commit('pushSuccess', `Queued checkpoints: ${accepted} accepted${denied ? `, ${denied} denied` : ''}${missing ? `, ${missing} missing` : ''}.`)
                } else {
                    store.commit('pushError', `No groups accepted. ${denied ? `${denied} denied. ` : ''}${missing ? `${missing} not found.` : ''}`)
                }
            }
            return res
        } catch (e) {
            if (toast) store.commit('pushError', e?.response?.data?.message || 'Failed to queue Checkpoints.')
            return null
        } finally {
            if (rowId) emittingRow[rowId] = false
            else emitting.value = false
        }
    }
    return { isActive, emitting, emittingRow, emitCheckpoints }
}
