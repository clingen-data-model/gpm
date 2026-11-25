import { computed, ref, watch, onBeforeUnmount } from 'vue'
import api from '@/http/api'

const pending = new Map()
const generations = new Map()
const pendingCurations = new Map()

// Bound all frontend GCEP enrichment requests, including overlapping operations.
let activeCurations = 0
const waitingCurations = []
async function withCurationSlot(work) {
    await new Promise(resolve => {
        const start = () => { activeCurations++; resolve() }
        if (activeCurations < 2) start()
        else waitingCurations.push(start)
    })
    try { return await work() }
    finally { activeCurations--; waitingCurations.shift()?.() }
}
const symbolKey = symbol => String(symbol || '').trim().toUpperCase()

// Progress counts completed GPM symbol batches, not opaque GT result pages or time.
// A batch is complete only after the backend has validated every GT curation page.
export async function getCompleteGcepCurations(symbols, { isCurrent = () => true, onProgress = () => {}, onBatch = () => {} } = {}) {
    const unique = [...new Set(symbols.map(symbolKey).filter(Boolean))]
    const batches = []
    for (let start = 0; start < unique.length; start += 100) batches.push(unique.slice(start, start + 100))
    let next = 0, completed = 0, failure = null
    const results = []
    onProgress({ completed, total: batches.length })
    async function worker() {
        while (!failure && isCurrent() && next < batches.length) {
            const index = next++
            const batch = batches[index]
            const key = batch.join(', ')
            try {
                if (!pendingCurations.has(key)) {
                    pendingCurations.set(key, withCurationSlot(async () => {
                        const response = await api.post('/api/genes/check-gcep-genes', { gene_symbol: key })
                        if (!Array.isArray(response.data?.data)) throw new Error('Incomplete GeneTracker response.')
                        return response.data.data
                    }).finally(() => pendingCurations.delete(key)))
                }
                const rows = await pendingCurations.get(key)
                if (!isCurrent()) return
                results[index] = rows
                onBatch(batch, rows)
                completed++
                onProgress({ completed, total: batches.length })
            } catch (error) { failure ??= error }
        }
    }
    await Promise.all([worker(), worker()])
    if (!isCurrent()) return null
    if (failure) throw failure
    return results.flat()
}
export function getGcepPage(groupID, params) {
    const key = JSON.stringify([groupID, generations.get(groupID) || 0, params])
    if (!pending.has(key)) {
        const request = api.get(`/api/groups/${groupID}/expert-panel/genes/gcep-page`, { params })
            .then(response => response.data)
            .finally(() => pending.delete(key))
        pending.set(key, request)
    }
    return pending.get(key)
}

export function useGcepGenePages(props, { currentPage, pageSize, sortKey, sortOrder, globalQuery }) {
    const rows = ref([])
    const total = ref(0)
    const loading = ref(false)
    const error = ref('')
    const progress = ref(null)
    const revision = ref(0)
    let sequence = 0
    // Component-lifetime cache: each entry contains ALL curations, including confirmed empty results.
    // Local tier/membership changes do not invalidate unchanged GeneTracker facts.
    const curations = new Map()
    const source = computed(() => props.snapshotView
        ? props.genes.map(gene => gene.curation_source === 'snapshot' ? gene : {
            ...gene, details: [], statuses: ['No curation snapshot available'], expert_panels: [], snapshot_captured_at: [],
        })
        : props.paged ? rows.value : props.genes)

    function refresh() {
        generations.set(props.groupID, (generations.get(props.groupID) || 0) + 1)
        revision.value++
    }
    function reloadCurations() { curations.clear(); refresh() }
    function updateTiers(ids, tier) {
        const selected = new Set(ids)
        props.genes.forEach(gene => { if (selected.has(gene.id)) gene.tier = tier })
        refresh()
    }
    watch(() => props.genes, () => {
        const members = new Set(props.genes.map(gene => symbolKey(gene.gene_symbol)))
        for (const symbol of curations.keys()) if (!members.has(symbol)) curations.delete(symbol)
        refresh()
    })
    watch(() => props.groupID, () => { curations.clear(); rows.value = []; total.value = 0 })

    async function enrich(genes, isCurrent) {
        // The server may know about a new approval before the mounted group model does.
        if (genes.every(gene => gene.curation_source === 'snapshot')) return genes
        const missing = genes.map(gene => symbolKey(gene.gene_symbol)).filter(symbol => !curations.has(symbol))
        if (missing.length) {
            await getCompleteGcepCurations(missing, {
                isCurrent,
                onProgress: value => { if (isCurrent()) progress.value = value },
                onBatch: (symbols, entries) => {
                    if (!isCurrent()) return
                    const grouped = new Map(symbols.map(symbol => [symbol, []]))
                    entries.forEach(entry => grouped.get(symbolKey(entry.gene_symbol))?.push(entry))
                    for (const [symbol, details] of grouped) curations.set(symbol, details)
                },
            })
        }
        if (!isCurrent()) return null
        return genes.map(gene => {
            const details = curations.get(symbolKey(gene.gene_symbol)) || []
            return { ...gene, details,
                statuses: details.length ? [...new Set(details.map(row => row.curation_status || 'Unknown'))] : ['Not Curated'],
                expert_panels: [...new Set(details.map(row => row.expert_panel || ''))],
            }
        })
    }
    async function load() {
        const request = ++sequence
        if (!props.paged || props.snapshotView || !props.groupID) {
            loading.value = false
            error.value = ''
            progress.value = null
            return
        }
        const isCurrent = () => request === sequence
        loading.value = true
        error.value = ''
        progress.value = null
        try {
            let genes
            if (globalQuery.value) {
                genes = props.genes.map(gene => ({ ...gene }))
            } else {
                const result = await getGcepPage(props.groupID, {
                    page: currentPage.value, per_page: Number(pageSize.value),
                    sort: sortKey.value, direction: sortOrder.value, enrich: 0,
                })
                if (!isCurrent()) return
                total.value = result.total
                const last = Math.max(1, result.last_page)
                if (currentPage.value > last) { currentPage.value = last; return }
                genes = result.data
            }
            const result = await enrich(genes, isCurrent)
            if (!isCurrent()) return
            rows.value = result
            if (globalQuery.value) total.value = genes.length
        } catch (failure) {
            if (isCurrent()) error.value = failure.response?.data?.message || failure.message || 'Could not load curations. Please retry.'
        } finally {
            if (isCurrent()) { loading.value = false; progress.value = null }
        }
    }
    watch([() => props.groupID, () => props.paged, () => props.snapshotView, currentPage, pageSize, sortKey, sortOrder, globalQuery, revision], load, { immediate: true })
    onBeforeUnmount(() => { sequence++; loading.value = false; error.value = ''; progress.value = null; curations.clear() })
    return { source, total, loading, error, progress, refresh, reloadCurations, updateTiers }
}
