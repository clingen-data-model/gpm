import { beforeEach, describe, expect, it, vi } from 'vitest'
import { shallowMount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import GcepGeneList from '@/components/expert_panels/GcepGeneList.vue'
import api from '@/http/api'

vi.mock('@/http/api', () => ({ default: { post: vi.fn() } }))

function render() {
    const group = { uuid: 'gcep', expert_panel: { genes: [{ id: 1, gene_symbol: 'A' }] } }
    const membership = vi.fn(() => group.expert_panel.genes)
    const store = createStore({
        getters: { 'groups/currentItemOrNew': () => group },
        actions: { 'groups/getGeneMembership': membership },
        mutations: { pushSuccess() {}, pushError() {} },
    })
    const wrapper = shallowMount(GcepGeneList, { global: { plugins: [store], mocks: { hasAnyPermission: () => true }, stubs: { 'edit-icon-button': true } } })
    return { wrapper, membership }
}
beforeEach(() => vi.resetAllMocks())

describe('GCEP mutation lifecycle', () => {
    it('prevents duplicate additions and distinguishes saving from membership refresh', async () => {
        let finish
        api.post.mockImplementation(url => url.includes('check-gcep') ? Promise.resolve({ data: { data: [] } }) : new Promise(resolve => { finish = resolve }))
        const { wrapper, membership } = render()
        await flushPromises()
        wrapper.vm.selectedGene = { hgnc_id: 2, gene_symbol: 'B' }
        await flushPromises()
        const saving = wrapper.vm.addGene()
        await flushPromises()
        expect(wrapper.text()).toContain('Adding gene...')
        await wrapper.vm.addGene()
        expect(api.post.mock.calls.filter(([url]) => url.endsWith('/expert-panel/genes'))).toHaveLength(1)
        finish({ data: {} })
        await saving
        await flushPromises()
        expect(membership).toHaveBeenCalledTimes(2)
        expect(wrapper.vm.adding).toBe(false)
        wrapper.unmount()
    })

    it('retains the selected gene and presents Retry after a failed add', async () => {
        api.post.mockImplementation(url => url.includes('check-gcep') ? Promise.resolve({ data: { data: [] } }) : Promise.reject(new Error('failed')))
        const { wrapper } = render()
        await flushPromises()
        wrapper.vm.selectedGene = { hgnc_id: 2, gene_symbol: 'B' }
        await flushPromises()
        await wrapper.vm.addGene()
        await flushPromises()
        expect(wrapper.vm.selectedGene.gene_symbol).toBe('B')
        expect(wrapper.text()).toContain('Failed to add B')
        expect(wrapper.findAll('button').some(button => button.text() === 'Retry')).toBe(true)
        expect(wrapper.vm.adding).toBe(false)
        wrapper.unmount()
    })

    it('prevents duplicate bulk additions and refreshes only membership after saving', async () => {
        let finish
        api.post.mockImplementation(url => url.endsWith('/availability')
            ? Promise.resolve({ data: [{ hgnc_id: 2, gene_symbol: 'B' }, { hgnc_id: 3, gene_symbol: 'C' }] })
            : new Promise(resolve => { finish = resolve }))
        const { wrapper, membership } = render()
        await flushPromises()
        wrapper.vm.pasteText = 'B C'
        await wrapper.vm.onReviewClick()
        const saving = wrapper.vm.submitBulkFromReviewUI()
        await flushPromises()
        expect(wrapper.text()).toContain('Adding genes...')
        await wrapper.vm.submitBulkFromReviewUI()
        expect(api.post.mock.calls.filter(([url]) => url.endsWith('/expert-panel/genes'))).toHaveLength(1)
        finish({ data: {} })
        await saving
        await flushPromises()
        expect(membership).toHaveBeenCalledTimes(2)
        expect(wrapper.vm.submittingBulk).toBe(false)
        wrapper.unmount()
    })
})
