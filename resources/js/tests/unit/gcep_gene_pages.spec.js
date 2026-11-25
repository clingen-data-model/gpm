import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import GeneCurationStatus from '@/components/expert_panels/GeneCurationStatus.vue'
import { getGcepPage, getCompleteGcepCurations } from '@/composables/gcep_gene_pages'
import api from '@/http/api'

vi.mock('@/http/api', () => ({ default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() } }))

let genes, groupNumber = 0
const enriched = gene => ({ ...gene, statuses: ['Not Curated'], expert_panels: [], details: [] })
const pageResponse = (rows, page, size) => ({ data: {
    data: rows.slice((page - 1) * size, page * size), total: rows.length,
    current_page: page, last_page: Math.max(1, Math.ceil(rows.length / size)),
} })
function serve(rows) {
    api.get.mockImplementation(async (_, { params }) => pageResponse(rows, params.page, params.per_page))
    api.post.mockImplementation(async (_, { gene_symbol }) => ({ data: { data: rows.filter(row => gene_symbol.split(', ').includes(row.gene_symbol)).flatMap(row => row.details.map((detail, index) => ({ ...detail, gene_symbol: row.gene_symbol, curation_id: detail.curation_id || `${row.id}-${index}` }))) } }))
}
function render() {
    return mount(GeneCurationStatus, {
        props: { groupID: `gcep-${++groupNumber}`, genes, paged: true },
        global: { plugins: [createStore({ mutations: { pushSuccess() {}, pushError() {} } })] },
    })
}
function button(wrapper, label) { return wrapper.findAll('button').find(b => b.text() === label) }

beforeEach(() => {
    vi.resetAllMocks()
    genes = Array.from({ length: 367 }, (_, i) => ({ id: i + 1, gene_symbol: `GENE${String(i + 1).padStart(4, '0')}`, tier: null }))
    serve(genes.map(enriched))
})

describe('GCEP page loading', () => {
    it.each([20, 100, 367, 1001])('loads only the first 20 of %i genes', async count => {
        genes = Array.from({ length: count }, (_, i) => ({ id: i + 1, gene_symbol: `GENE${i}`, tier: null }))
        serve(genes.map(enriched))
        const wrapper = render()
        await flushPromises()
        expect(api.get).toHaveBeenCalledTimes(1)
        expect(api.get.mock.calls[0][1].params.per_page).toBe(20)
        expect(wrapper.findAll('li')).toHaveLength(20)
        expect(wrapper.text()).toContain(`Page 1 of ${Math.ceil(count / 20)}`)
        wrapper.unmount()
    })

    it('fetches the requested page and server sort, preserving selection across pages', async () => {
        const wrapper = render()
        await flushPromises()
        await wrapper.find('li input[type=checkbox]').setValue(true)
        await button(wrapper, 'Next').trigger('click')
        await flushPromises()
        expect(api.get.mock.calls.at(-1)[1].params.page).toBe(2)
        expect(wrapper.find('li').text()).toContain('GENE0021')
        expect(wrapper.text()).toContain('1 gene(s) selected')
        await button(wrapper, 'Remove Selected').trigger('click')
        expect(wrapper.text()).toContain('GENE0001')
        await wrapper.findAll('select')[0].setValue('tier')
        await flushPromises()
        expect(api.get.mock.calls.at(-1)[1].params.sort).toBe('tier')
        wrapper.unmount()
    })

    it('loads all genes for global search and preserves all curation details', async () => {
        const rows = genes.map(enriched)
        rows[366].statuses = ['Published', 'Precuration']
        rows[366].details = Array.from({ length: 5 }, (_, i) => ({ curation_id: `c${i}`, expert_panel: 'Faraway panel', disease_name: `Disease ${i}` }))
        rows[366].expert_panels = ['Faraway panel']
        serve(rows)
        const wrapper = render()
        await flushPromises()
        await wrapper.find('input[type=text]').setValue('Faraway')
        await flushPromises()
        expect(api.get).toHaveBeenCalledTimes(1)
        expect(api.post).toHaveBeenCalledTimes(5) // first 20 plus remaining 347 in four batches
        expect(wrapper.findAll('li')).toHaveLength(1)
        expect(wrapper.text()).toContain('GENE0367')
        await button(wrapper, 'Show Details').trigger('click')
        expect(wrapper.text()).toContain('Disease 4')
        await wrapper.find('input[type=text]').setValue('Disease')
        await flushPromises()
        expect(api.get).toHaveBeenCalledTimes(1)
        wrapper.unmount()
    })

    it('loads global filter options and permits paging the complete results', async () => {
        const rows = genes.map(enriched)
        rows[366].details = [{ moi_name: 'Rare MOI', classification: 'Definitive' }]
        serve(rows)
        const wrapper = render()
        await flushPromises()
        await button(wrapper, 'Filters').trigger('click')
        await flushPromises()
        expect(wrapper.text()).toContain('Rare MOI')
        await button(wrapper, 'Next').trigger('click')
        await flushPromises()
        expect(wrapper.text()).toContain('Page 2 of 19')
        const moi = wrapper.findAll('select').find(s => s.text().includes('All MOIs'))
        await moi.setValue('Rare MOI')
        await flushPromises()
        expect(wrapper.findAll('li')).toHaveLength(1)
        expect(wrapper.text()).toContain('GENE0367')
        wrapper.unmount()
    })

    it('does not present partial global results as complete and retries', async () => {
        const wrapper = render()
        await flushPromises()
        api.post.mockRejectedValueOnce(new Error('GT timeout'))
        await wrapper.find('input[type=text]').setValue('GENE')
        await flushPromises()
        expect(wrapper.find('[role=alert]').text()).toContain('GT timeout')
        expect(wrapper.findAll('li')).toHaveLength(20)
        expect(wrapper.text()).toContain('Showing previous results')
        expect(wrapper.text()).not.toContain('No genes found')
        serve(genes.map(enriched))
        await button(wrapper, 'Retry').trigger('click')
        await flushPromises()
        expect(wrapper.find('[role=alert]').exists()).toBe(false)
        expect(wrapper.findAll('li')).toHaveLength(20)
        wrapper.unmount()
    })

    it('ignores an old response after the page size changes', async () => {
        let resolveOld
        api.get.mockImplementationOnce(() => new Promise(resolve => { resolveOld = resolve }))
        const wrapper = render()
        await wrapper.findAll('select')[1].setValue('100')
        await flushPromises()
        expect(wrapper.findAll('li')).toHaveLength(100)
        resolveOld(pageResponse(genes.map(enriched), 1, 20))
        await flushPromises()
        expect(wrapper.findAll('li')).toHaveLength(100)
        wrapper.unmount()
    })

    it('deduplicates concurrent identical page requests', async () => {
        await Promise.all([getGcepPage('shared', { page: 1, per_page: 20 }), getGcepPage('shared', { page: 1, per_page: 20 })])
        expect(api.get).toHaveBeenCalledTimes(1)
    })

    it('refreshes tier ordering after save and clamps the last page after removal', async () => {
        const wrapper = render()
        await flushPromises()
        api.put.mockResolvedValue({ data: {} })
        await wrapper.find('li select').setValue('2')
        await flushPromises()
        expect(api.put.mock.calls[0][1]).toEqual({ ids: [1], tier: '2' })
        expect(api.get).toHaveBeenCalledTimes(2)
        // A membership refresh causes a fresh page read, without retaining a stale full-scope cache.
        genes = genes.slice(0, 1)
        serve(genes.map(enriched))
        await wrapper.setProps({ genes })
        await flushPromises()
        expect(wrapper.text()).toContain('Page 1 of 1')
        expect(wrapper.findAll('li')).toHaveLength(1)
        wrapper.unmount()
    })

    it('does not expose mutations in readonly review mode', async () => {
        const wrapper = render()
        await wrapper.setProps({ readonly: true, editing: false })
        await flushPromises()
        expect(wrapper.find('li input[type=checkbox]').exists()).toBe(false)
        expect(wrapper.find('li select').exists()).toBe(false)
        expect(button(wrapper, 'Remove?')).toBeUndefined()
        expect(api.put).not.toHaveBeenCalled()
        wrapper.unmount()
    })

    it('preserves the previous tier when saving fails', async () => {
        const wrapper = render()
        await flushPromises()
        const log = vi.spyOn(console, 'error').mockImplementation(() => {})
        api.put.mockRejectedValue(new Error('denied'))
        await wrapper.find('li select').setValue('2')
        await flushPromises()
        expect(api.get).toHaveBeenCalledTimes(1)
        expect(wrapper.vm.paginatedGenes[0].tier).toBeNull()
        expect(wrapper.find('li select').element.value).toBe('')
        log.mockRestore()
        wrapper.unmount()
    })

    it('enriches only added genes and removes deleted genes from global options without GT calls', async () => {
        const rows = genes.map(enriched)
        rows[366].details = [{ moi_name: 'Unique MOI' }]
        serve(rows)
        const wrapper = render()
        await flushPromises()
        await button(wrapper, 'Filters').trigger('click')
        await flushPromises()
        const calls = api.post.mock.calls.length
        expect(wrapper.text()).toContain('Unique MOI')
        genes = [...genes.slice(0, 366), { id: 9999, gene_symbol: 'NEW', tier: null }]
        serve([...rows.slice(0, 366), { ...enriched(genes[366]), details: [{ moi_name: 'New MOI' }] }])
        await wrapper.setProps({ genes })
        await flushPromises()
        expect(api.post).toHaveBeenCalledTimes(calls + 1)
        expect(api.post.mock.calls.at(-1)[1]).toEqual({ gene_symbol: 'NEW' })
        expect(wrapper.text()).not.toContain('Unique MOI')
        expect(wrapper.text()).toContain('New MOI')
        genes = genes.slice(0, 366)
        await wrapper.setProps({ genes })
        await flushPromises()
        expect(api.post).toHaveBeenCalledTimes(calls + 1)
        expect(wrapper.text()).not.toContain('New MOI')
        wrapper.unmount()
    })

    it('preserves GT data across tier saves and ordinary/global mode switches', async () => {
        const wrapper = render()
        await flushPromises()
        await button(wrapper, 'Filters').trigger('click')
        await flushPromises()
        const calls = api.post.mock.calls.length
        api.put.mockResolvedValue({ data: {} })
        await wrapper.find('li select').setValue('2')
        await flushPromises()
        expect(api.post).toHaveBeenCalledTimes(calls)
        expect(genes[0].tier).toBe('2')
        await button(wrapper, 'Filters').trigger('click')
        await flushPromises()
        await button(wrapper, 'Filters').trigger('click')
        await flushPromises()
        expect(api.post).toHaveBeenCalledTimes(calls)
        wrapper.unmount()
    })

    it('uses two enrichment requests at most and reports completed batches, not estimated time', async () => {
        const pending = []
        let active = 0, maximum = 0
        api.post.mockImplementation(() => new Promise(resolve => {
            active++; maximum = Math.max(maximum, active)
            pending.push(() => { active--; resolve({ data: { data: [] } }) })
        }))
        const progress = []
        const result = getCompleteGcepCurations(genes.map(g => g.gene_symbol), { onProgress: p => progress.push(p) })
        await flushPromises()
        expect(pending).toHaveLength(2)
        expect(progress).toEqual([{ completed: 0, total: 4 }])
        pending.shift()()
        await flushPromises()
        expect(progress.at(-1)).toEqual({ completed: 1, total: 4 })
        while (pending.length) { pending.shift()(); await flushPromises() }
        await result
        expect(maximum).toBe(2)
        expect(progress.at(-1)).toEqual({ completed: 4, total: 4 })
    })

    it('shows progress and stops publishing or scheduling batches after unmount', async () => {
        const wrapper = render()
        await flushPromises()
        const pending = []
        api.post.mockImplementation(() => new Promise(resolve => pending.push(resolve)))
        await button(wrapper, 'Filters').trigger('click')
        await flushPromises()
        expect(wrapper.find('progress').attributes('max')).toBe('4')
        expect(wrapper.text()).toContain('0 of 4 batches complete')
        const calls = api.post.mock.calls.length
        wrapper.unmount()
        pending.forEach(resolve => resolve({ data: { data: [] } }))
        await flushPromises()
        expect(api.post).toHaveBeenCalledTimes(calls)
    })

    it('fills a page after removal using only the newly exposed gene', async () => {
        const wrapper = render()
        await flushPromises()
        genes = genes.slice(1)
        serve(genes.map(enriched))
        await wrapper.setProps({ genes })
        await flushPromises()
        expect(api.post).toHaveBeenCalledTimes(2)
        expect(api.post.mock.calls.at(-1)[1]).toEqual({ gene_symbol: 'GENE0021' })
        expect(wrapper.find('li').text()).toContain('GENE0002')
        wrapper.unmount()
    })

    it('prevents duplicate removal requests while showing removal feedback', async () => {
        const wrapper = render()
        await flushPromises()
        let finish
        api.delete.mockImplementation(() => new Promise(resolve => { finish = resolve }))
        await button(wrapper, 'Remove?').trigger('click')
        const saving = wrapper.vm.removeGenes()
        await flushPromises()
        expect(wrapper.text()).toContain('Removing gene...')
        await wrapper.vm.removeGenes()
        expect(api.delete).toHaveBeenCalledTimes(1)
        finish({ data: {} })
        await saving
        await flushPromises()
        expect(wrapper.emitted('removed')).toHaveLength(1)
        wrapper.unmount()
    })

    it('clamps an emptied last page without re-enriching cached genes', async () => {
        genes = genes.slice(0, 21)
        serve(genes.map(enriched))
        const wrapper = render()
        await flushPromises()
        await button(wrapper, 'Next').trigger('click')
        await flushPromises()
        expect(wrapper.text()).toContain('Page 2 of 2')
        const calls = api.post.mock.calls.length
        genes = genes.slice(0, 20)
        serve(genes.map(enriched))
        await wrapper.setProps({ genes })
        await flushPromises()
        expect(wrapper.text()).toContain('Page 1 of 1')
        expect(wrapper.findAll('li')).toHaveLength(20)
        expect(api.post).toHaveBeenCalledTimes(calls)
        wrapper.unmount()
    })
})

it('switches an in-flight live list to saved snapshots without retaining loading or live data', async () => {
    let finish
    api.post.mockImplementation(() => new Promise(resolve => { finish = resolve }))
    const wrapper = render()
    await flushPromises()
    expect(wrapper.text()).toContain('Loading GeneTracker')
    const saved = genes.map(gene => ({ ...gene, curation_source: 'snapshot', statuses: ['Published'], details: [], snapshot_captured_at: ['2024-12-20T10:00:00Z'] }))
    await wrapper.setProps({ snapshotView: true, genes: saved, readonly: true, editing: false })
    await flushPromises()
    expect(wrapper.text()).not.toContain('Loading GeneTracker')
    expect(wrapper.text()).not.toContain('Refresh curations')
    expect(wrapper.text()).toContain('Published')
    expect(wrapper.findAll('li')).toHaveLength(20)
    const posts = api.post.mock.calls.length
    finish({ data: { data: [{ gene_symbol: genes[0].gene_symbol, curation_id: 'old', curation_status: 'Live stale data' }] } })
    await flushPromises()
    expect(wrapper.text()).not.toContain('Live stale data')
    await button(wrapper, 'Next').trigger('click')
    await flushPromises()
    expect(api.post).toHaveBeenCalledTimes(posts)
    expect(api.get).toHaveBeenCalledTimes(1)
    await wrapper.setProps({ genes: [{ ...genes[0], statuses: ['Live stale data'], details: [{ curation_status: 'Live stale data' }] }] })
    await flushPromises()
    expect(wrapper.text()).not.toContain('Live stale data')
    expect(wrapper.text()).toContain('No curation snapshot available')
    wrapper.unmount()
})

it('honors snapshot rows from the server before the mounted approval flag updates', async () => {
    const saved = genes.map(gene => ({ ...gene, curation_source: 'snapshot', statuses: ['Published'], details: [], snapshot_captured_at: ['2024-12-20T10:00:00Z'] }))
    api.get.mockImplementation(async (_, { params }) => pageResponse(saved, params.page, params.per_page))
    const wrapper = render()
    await flushPromises()
    expect(wrapper.text()).toContain('Published')
    expect(api.post).not.toHaveBeenCalled()
    wrapper.unmount()
})
