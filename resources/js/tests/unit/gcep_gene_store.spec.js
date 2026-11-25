import { describe, it, expect, vi, beforeEach } from 'vitest'
import { actions } from '@/store/groups'
import api from '@/http/api'

vi.mock('@/http/api', () => ({ default: { get: vi.fn(), post: vi.fn() } }))

let nextID = 0
function context(type = 'gcep') {
    const group = { uuid: `scope-${++nextID}`, is_gcep: type === 'gcep', is_vcep_or_scvcep: type !== 'gcep', expert_panel: { genes: [] } }
    const ctx = { commit: vi.fn(), getters: { getItemByUuid: () => group } }
    ctx.dispatch = (name, input) => actions[name](ctx, input)
    return { group, ctx }
}
beforeEach(() => vi.resetAllMocks())

describe('complete gene-list contracts', () => {
    it('keeps complete review data and deduplicates concurrent GCEP loads', async () => {
        const { group, ctx } = context()
        const genes = Array.from({ length: 367 }, (_, i) => ({ id: i, gene_symbol: `GENE${i}` }))
        api.get.mockResolvedValue({ data: genes })
        api.post.mockImplementation(async (_, { gene_symbol }) => ({ data: { data: gene_symbol.split(', ').flatMap(symbol => [
            { gene_symbol: symbol, curation_id: symbol + '-1', curation_status: 'Published', expert_panel: 'Panel A' },
            { gene_symbol: symbol, curation_id: symbol + '-2', curation_status: 'Precuration', expert_panel: 'Panel B' },
        ]) } }))
        const [result] = await Promise.all([actions.getGenes(ctx, group), actions.getGenes(ctx, group)])
        expect(api.get).toHaveBeenCalledTimes(1)
        expect(api.post).toHaveBeenCalledTimes(4)
        expect(result).toHaveLength(367)
        expect(group.expert_panel.genes).toHaveLength(367)
        expect(group.expert_panel.genes[366].details).toHaveLength(2)
        expect(group.expert_panel.genes[366].statuses).toEqual(['Published', 'Precuration'])
    })

    it('keeps failure distinct from no curations after a partially failed lookup', async () => {
        const { group, ctx } = context()
        const genes = Array.from({ length: 101 }, (_, i) => ({ id: i, gene_symbol: `FAIL${i}` }))
        api.get.mockResolvedValue({ data: genes })
        api.post.mockResolvedValueOnce({ data: { data: [] } }).mockRejectedValueOnce(new Error('timeout'))
        const log = vi.spyOn(console, 'error').mockImplementation(() => {})
        await actions.getGenes(ctx, group)
        expect(group.expert_panel.genes.every(g => g.enrichment_error)).toBe(true)
        expect(group.expert_panel.genes.some(g => g.statuses.includes('Not Curated'))).toBe(false)
        api.post.mockResolvedValue({ data: { data: [] } })
        await actions.getGenes(ctx, group)
        expect(group.expert_panel.genes.every(g => g.statuses.includes('Not Curated'))).toBe(true)
        log.mockRestore()
    })

    it.each(['vcep', 'scvcep'])('retains the %s curation-ID path and snapshot comparison', async type => {
        const { group, ctx } = context(type)
        api.get.mockResolvedValue({ data: [{ id: 1, gene_symbol: 'A', plan: { curation_id: 'curation-a', checkKey: 'old' } }] })
        api.post.mockResolvedValue({ data: { data: [{ curation_id: 'curation-a', checkKey: 'new' }] } })
        await actions.getGenes(ctx, group)
        expect(api.post).toHaveBeenCalledExactlyOnceWith('/api/curationids', { curation_ids: 'curation-a' })
        expect(group.expert_panel.genes[0].is_outdated).toBe(true)
    })

    it('loads complete membership without GT for summaries and duplicate checking', async () => {
        const { group, ctx } = context()
        const genes = Array.from({ length: 1001 }, (_, i) => ({ id: i, gene_symbol: `GENE${i}` }))
        api.get.mockResolvedValue({ data: genes })
        const result = await actions.getGeneMembership(ctx, group)
        expect(result).toHaveLength(1001)
        expect(group.expert_panel.genes).toHaveLength(1001)
        expect(api.post).not.toHaveBeenCalled()
    })

    it('retains valid review details with an error flag when refresh fails', async () => {
        const { group, ctx } = context()
        group.expert_panel.genes = [{ id: 1, gene_symbol: 'A', statuses: ['Published'], details: [{ curation_id: 'old' }], expert_panels: ['Panel'] }]
        api.get.mockResolvedValue({ data: [{ id: 1, gene_symbol: 'A', tier: 2 }] })
        api.post.mockRejectedValue(new Error('timeout'))
        const log = vi.spyOn(console, 'error').mockImplementation(() => {})
        await actions.getGenes(ctx, group)
        expect(group.expert_panel.genes[0]).toMatchObject({ tier: 2, statuses: ['Published'], details: [{ curation_id: 'old' }], enrichment_error: true })
        log.mockRestore()
    })
})

it('uses approved GCEP API snapshots without requesting live GT enrichment', async () => {
    const { group, ctx } = context()
    group.expert_panel.step_1_approval_date = '2025-01-01'
    const rows = [{ id: 1, gene_symbol: 'A', curation_source: 'snapshot', details: [{ curation_status: 'Published' }], statuses: ['Published'] },
        { id: 2, gene_symbol: 'B', curation_source: 'snapshot', details: [], statuses: ['No curation snapshot available'] }]
    api.get.mockResolvedValue({ data: rows })
    await actions.getGenes(ctx, group)
    expect(group.expert_panel.genes).toEqual(rows)
    expect(api.post).not.toHaveBeenCalled()
})
