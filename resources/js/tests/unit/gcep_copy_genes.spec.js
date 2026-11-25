import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import GeneCurationStatus from '@/components/expert_panels/GeneCurationStatus.vue'
import api from '@/http/api'

vi.mock('@/http/api', () => ({ default: { get: vi.fn(), post: vi.fn() } }))
let genes, writeText, groupID = 0
const button = wrapper => wrapper.findAll('button').find(b => b.text().startsWith('Copy Genes'))
function render(props = {}) {
    return mount(GeneCurationStatus, {
        props: { genes, groupID: `copy-${++groupID}`, paged: true, ...props },
        global: { plugins: [createStore({ mutations: { pushSuccess() {}, pushError() {} } })] },
    })
}
beforeEach(() => {
    vi.resetAllMocks()
    vi.stubEnv('GT_FRONTEND_URL', 'https://gt.example.test/')
    writeText = vi.fn().mockResolvedValue(undefined)
    Object.defineProperty(navigator, 'clipboard', { configurable: true, value: { writeText } })
    genes = Array.from({ length: 1000 }, (_, i) => ({ id: i + 1, gene_symbol: `GENE${String(i + 1).padStart(4, '0')}`, tier: i < 135 ? 1 : 2 }))
    api.get.mockImplementation(async (_, { params }) => ({ data: {
        data: genes.slice((params.page - 1) * params.per_page, params.page * params.per_page),
        total: genes.length, current_page: params.page, last_page: Math.ceil(genes.length / params.per_page),
    } }))
    api.post.mockImplementation(async (_, { gene_symbol }) => ({ data: { data: gene_symbol.split(', ').flatMap(symbol => {
        const index = genes.findIndex(g => g.gene_symbol === symbol)
        return [1, 2].map(n => ({ gene_symbol: symbol, curation_id: `${symbol}-${n}`,
            curation_status: index < 135 ? 'Published' : 'Precuration',
            expert_panel: index < 135 ? 'Panel Z' : 'Panel A',
            disease_name: index < 200 ? 'Target disease' : 'Other disease', moi_name: 'Autosomal dominant',
        }))
    }) } }))
})

describe('Copy GCEP genes', () => {
    it('copies all 1000 genes while preserving page and checkbox selection without fetching', async () => {
        const wrapper = render()
        await flushPromises()
        wrapper.vm.currentPage = 2
        await flushPromises()
        wrapper.vm.selectedGenes = [21]
        const gets = api.get.mock.calls.length, posts = api.post.mock.calls.length
        await button(wrapper).trigger('click')
        await flushPromises()
        expect(writeText).toHaveBeenCalledExactlyOnceWith(genes.map(g => g.gene_symbol).join('\n'))
        expect(wrapper.text()).toContain('Copied 1000 gene symbols')
        expect(wrapper.vm.currentPage).toBe(2)
        expect(wrapper.vm.selectedGenes).toEqual([21])
        expect(api.get).toHaveBeenCalledTimes(gets)
        expect(api.post).toHaveBeenCalledTimes(posts)
        wrapper.unmount()
    })

    it.each([
        ['tier', vm => { vm.filterTier = '1' }, 135],
        ['status', vm => { vm.selectedStatuses = ['Published'] }, 135],
        ['disease search', vm => { vm.search = 'Target disease' }, 200],
        ['symbol search', vm => { vm.search = 'GENE0001' }, 1],
        ['combined criteria', vm => { vm.search = 'Target disease'; vm.filterTier = '2'; vm.selectedStatuses = ['Precuration'] }, 65],
    ])('copies every matching gene for %s without extra requests or duplicate curations', async (_, query, count) => {
        const wrapper = render()
        await flushPromises()
        query(wrapper.vm)
        await flushPromises()
        const posts = api.post.mock.calls.length
        const expected = wrapper.vm.sortedGenes.map(g => g.gene_symbol)
        await button(wrapper).trigger('click')
        await flushPromises()
        const symbols = writeText.mock.calls[0][0].split('\n')
        expect(symbols).toEqual(expected)
        expect(symbols).toHaveLength(count)
        expect(new Set(symbols).size).toBe(count)
        expect(api.post).toHaveBeenCalledTimes(posts)
        wrapper.unmount()
    })

    it.each(['statuses', 'expert_panel'])('preserves the existing global %s ordering', async sort => {
        const wrapper = render()
        await flushPromises()
        wrapper.vm.sortKey = sort
        wrapper.vm.sortOrder = 'desc'
        await flushPromises()
        const expected = wrapper.vm.sortedGenes.map(g => g.gene_symbol).join('\n')
        await button(wrapper).trigger('click')
        expect(writeText).toHaveBeenCalledWith(expected)
        wrapper.unmount()
    })

    it('uses descending local tier ordering with ascending ID ties and deduplicates rows', async () => {
        genes = [{ id: 3, gene_symbol: 'C', tier: 1 }, { id: 2, gene_symbol: 'B', tier: 2 }, { id: 1, gene_symbol: 'A', tier: 2 }, { id: 1, gene_symbol: 'A', tier: 2 }]
        const wrapper = render()
        await flushPromises()
        wrapper.vm.sortKey = 'tier'
        wrapper.vm.sortOrder = 'desc'
        await flushPromises()
        await button(wrapper).trigger('click')
        expect(writeText).toHaveBeenCalledWith('A\nB\nC')
        wrapper.unmount()
    })

    it('copies symbols in descending order without GT lookup beyond the visible page', async () => {
        const wrapper = render()
        await flushPromises()
        wrapper.vm.sortOrder = 'desc'
        await flushPromises()
        const posts = api.post.mock.calls.length
        await button(wrapper).trigger('click')
        expect(writeText).toHaveBeenCalledWith([...genes].reverse().map(g => g.gene_symbol).join('\n'))
        expect(api.post).toHaveBeenCalledTimes(posts)
        wrapper.unmount()
    })

    it('disables copying for no matches and pending or failed membership refresh', async () => {
        const wrapper = render()
        await flushPromises()
        await wrapper.setProps({ membershipPending: true })
        expect(button(wrapper).attributes('disabled')).toBeDefined()
        await wrapper.vm.copyGenes()
        await wrapper.setProps({ membershipPending: false })
        wrapper.vm.search = 'NO MATCH'
        await flushPromises()
        expect(button(wrapper).attributes('disabled')).toBeDefined()
        await wrapper.vm.copyGenes()
        expect(writeText).not.toHaveBeenCalled()
        wrapper.unmount()
    })

    it('never copies a partial or stale global query while enrichment loads or fails', async () => {
        const wrapper = render()
        await flushPromises()
        let reject
        api.post.mockImplementation(() => new Promise((_, fail) => { reject = fail }))
        // One remaining batch makes the loading/failure transition deterministic.
        await wrapper.setProps({ genes: genes.slice(0, 100) })
        await flushPromises()
        wrapper.vm.search = 'Target disease'
        await flushPromises()
        expect(button(wrapper).attributes('disabled')).toBeDefined()
        await wrapper.vm.copyGenes()
        reject(new Error('GT unavailable'))
        await flushPromises()
        expect(wrapper.text()).toContain('Showing previous results')
        expect(button(wrapper).attributes('disabled')).toBeDefined()
        await wrapper.vm.copyGenes()
        expect(writeText).not.toHaveBeenCalled()
        wrapper.unmount()
    })

    it('reports clipboard failures without claiming success and permits retry', async () => {
        const wrapper = render()
        await flushPromises()
        writeText.mockRejectedValueOnce(new Error('denied'))
        await button(wrapper).trigger('click')
        await flushPromises()
        expect(wrapper.text()).toContain('Could not copy gene symbols')
        expect(wrapper.text()).not.toContain('Copied 1000')
        await button(wrapper).trigger('click')
        await flushPromises()
        expect(wrapper.text()).toContain('Copied 1000 gene symbols')
        wrapper.unmount()
    })

    it('keeps copying disabled for empty nonpaged consumers', async () => {
        const wrapper = render({ paged: false, genes: [] })
        await flushPromises()
        expect(button(wrapper).attributes('disabled')).toBeDefined()
        wrapper.unmount()
    })
})

it('renders saved records readonly and copies all filtered snapshot matches without GT', async () => {
    const saved = genes.map((gene, i) => ({ ...gene, curation_source: 'snapshot',
        snapshot_captured_at: ['2024-12-20T10:00:00.000000Z'],
        statuses: [i < 135 ? 'Published' : 'Uploaded'], expert_panels: ['Saved expert panel'],
        details: [{ curation_status: i < 135 ? 'Published' : 'Uploaded', expert_panel: 'Saved expert panel', disease_name: i < 200 ? 'Saved disease' : 'Other' }],
    }))
    saved[0] = { ...saved[0], statuses: ['No curation snapshot available'], details: [], snapshot_captured_at: [] }
    const wrapper = render({ genes: saved, paged: false, snapshotView: true, readonly: true, editing: false })
    await flushPromises()
    expect(wrapper.text()).toContain('Approved scope snapshot')
    expect(wrapper.text()).toContain('No curation snapshot available')
    expect(wrapper.text()).toContain('Snapshot: 2024-12-20')
    expect(wrapper.text()).not.toContain('Refresh curations')
    expect(wrapper.findAll('input[type="checkbox"]')).toHaveLength(0)
    const link = wrapper.find('a[href="https://gt.example.test/#/genes/GENE0002"]')
    expect(link.text()).toBe('View current curations in GeneTracker')
    expect(link.attributes('target')).toBe('_blank')
    expect(link.attributes('rel')).toBe('noopener noreferrer')
    const beforeClick = JSON.stringify(saved)
    await link.trigger('click')
    expect(JSON.stringify(saved)).toBe(beforeClick)
    await wrapper.findAll('button').find(b => b.text() === 'Show Details').trigger('click')
    expect(wrapper.text()).toContain('Saved disease')
    wrapper.vm.search = 'Saved disease'
    wrapper.vm.filterTier = '2'
    wrapper.vm.selectedStatuses = ['Uploaded']
    wrapper.vm.sortOrder = 'desc'
    await flushPromises()
    wrapper.vm.currentPage = 2
    await flushPromises()
    await button(wrapper).trigger('click')
    expect(writeText).toHaveBeenCalledWith(genes.slice(135, 200).reverse().map(g => g.gene_symbol).join('\n'))
    expect(wrapper.vm.currentPage).toBe(2)
    expect(api.get).not.toHaveBeenCalled()
    expect(api.post).not.toHaveBeenCalled()
    wrapper.unmount()
})

it('URL-encodes gene symbols in the configured frontend link', async () => {
    const wrapper = render({ paged: false, snapshotView: true, genes: [{ id: 1, gene_symbol: 'A/B + C', curation_source: 'snapshot', details: [], statuses: ['No curation snapshot available'] }] })
    await flushPromises()
    expect(wrapper.find('a').attributes('href')).toBe('https://gt.example.test/#/genes/A%2FB%20%2B%20C')
    wrapper.unmount()
})
