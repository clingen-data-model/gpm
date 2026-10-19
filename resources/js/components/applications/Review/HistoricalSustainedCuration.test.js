import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import ReviewRoundDetail from './ReviewRoundDetail.vue'
import InlineTextDiff from '@/components/groups/InlineTextDiff.vue'
import HistoricalSustainedCuration from './HistoricalSustainedCuration.vue'

const section = (label, value, available = true) => ({ label, value, available })
const render = sections => mount(ReviewRoundDetail, { props: { detail: {
  availability: 'available', step: 4, mode: 'submitted_state', submitted_state: sections,
} } })
const evidence = { gene: 'BRAF', disease: 'Captured disease', mondo_id: 'MONDO:123', moi: 'AD',
  variant: 'p.Val600Glu', summary: 'Frozen evidence summary' }

describe('frozen Step 4 presentation', () => {
  it.each([['VCEP', 'VCI'], ['SC-VCEP', 'CIViC']])('renders %s evidence and its captured URL', (type, source) => {
    const wrapper = render([
      section(`${type} Standardized Review Process`, 'Captured process'),
      section('Curation and Review Process Notes', 'Captured process notes'),
      section('Evidence Summaries', [{ ...evidence, [`${source} URL`]: 'https://example.org/frozen', ...(type === 'SC-VCEP' ? { assertion_id: 42 } : {}) }]),
    ])
    expect(wrapper.findAllComponents(HistoricalSustainedCuration)).toHaveLength(3)
    for (const text of ['Captured process', 'Captured process notes', 'BRAF', 'Captured disease', 'MONDO:123', 'AD', 'p.Val600Glu', 'Frozen evidence summary']) expect(wrapper.text()).toContain(text)
    expect(wrapper.get('a').text()).toBe(`View ${source}`)
    expect(wrapper.get('a').attributes('href')).toBe('https://example.org/frozen')
    expect(wrapper.text().includes('Assertion ID: 42')).toBe(type === 'SC-VCEP')
    expect(wrapper.text()).not.toContain('mondo_id')
  })
  it('renders captured member attestations, dates, responses and trainer names', () => {
    const wrapper = render([
      section('Core Approval Members', [{ name: 'Frozen member', attestation_completed: '1',
        attestation_completion_date: '2026-10-14T00:00:00Z', attestation_responses: ['Captured response'] },
        { name: 'Unsigned member', attestation_completed: 0 }, { name: 'Legacy member' }]),
      section('Biocurator Trainers', [{ name: 'Trainer One' }, { name: 'Trainer Two' }]),
      section('Trained Biocurators', [{ name: 'Trained Person' }]),
    ])
    for (const text of ['Frozen member', 'Attestation: Yes', 'Oct 14, 2026', 'Captured response', 'Attestation: No', 'Attestation: Not recorded', 'Trainer One, Trainer Two', 'Trained Person']) expect(wrapper.text().replace(/\s+/g, ' ')).toContain(text)
    expect(wrapper.text()).not.toContain('attestation_completed')
  })
  it.each(['Evidence Summaries', 'Core Approval Members', 'Biocurator Trainers', 'Trained Biocurators'])('distinguishes absent, null and empty %s', label => {
    expect(render([section(label, [], false)]).text()).toContain('Historical section unavailable.')
    expect(render([section(label, null)]).text()).toContain('Not recorded')
    expect(render([section(label, [])]).text()).toContain('None recorded.')
  })
  it('keeps captured URL text without making unsafe URLs clickable', () => {
    const wrapper = render([section('Evidence Summaries', [{ ...evidence, 'VCI URL': 'javascript:alert(1)' }])])
    expect(wrapper.find('a').exists()).toBe(false)
    expect(wrapper.text()).toContain('VCI: javascript:alert(1)')
  })
  it.each(['Evidence Summaries', 'Biocurator Trainers', 'Trained Biocurators', 'Core Approval Members'])('renders both frozen sides of %s without repeating headings', label => {
    const before = label === 'Evidence Summaries' ? [{ ...evidence, summary: 'Previous evidence' }] : [{ name: 'Previous person' }]
    const after = label === 'Evidence Summaries' ? [{ ...evidence, summary: 'Submitted evidence' }] : [{ name: 'Submitted person' }]
    const wrapper = mount(ReviewRoundDetail, { props: { detail: { availability: 'available', step: 4,
      mode: 'previous_review_round', comparison: { changes: [{ label, before, after }], rows: {} },
    } } })
    expect(wrapper.findAllComponents(HistoricalSustainedCuration)).toHaveLength(label === 'Evidence Summaries' ? 0 : 2)
    if (label === 'Evidence Summaries') {
      expect(wrapper.findComponent(InlineTextDiff).props()).toEqual({ before: 'Previous evidence', after: 'Submitted evidence' })
    } else {
      expect(wrapper.text()).toContain('Previous person')
      expect(wrapper.text()).toContain('Submitted person')
    }
    expect(wrapper.text().split(label)).toHaveLength(2)
  })
  it('recognizes stable section keys in later-round comparisons', () => {
    const wrapper = mount(ReviewRoundDetail, { props: { detail: { availability: 'available', comparison: {
      rows: {}, changes: [{ key: 'review_state.evidence_summaries', section: 'application.review_state.evidence_summaries', before: [], after: [evidence] }],
    } } } })
    expect(wrapper.findAllComponents(HistoricalSustainedCuration)).toHaveLength(1)
    expect(wrapper.text()).toContain('Frozen evidence summary')
  })
})
