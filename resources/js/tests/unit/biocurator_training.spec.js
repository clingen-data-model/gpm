import { describe, it, expect } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import { createStore } from 'vuex'
import Group from '@/domain/group'
import config from '@/configs.json'
import { biocuratorTrainingCategories } from '@/domain/biocurator_training'
import { minimumBiocurators } from '@/domain/ep_requirements'
import SustainedCurationReview from '@/components/expert_panels/SustainedCurationReview.vue'

const member = (id, level1, level2, overrides = {}) => ({
    id, person_id: id, group_id: 10, end_date: null,
    training_level_1: level1, training_level_2: level2,
    roles: [config.groups.roles.biocurator],
    person: { id, name: `Person${id} Member`, first_name: `Person${id}`, last_name: 'Member' },
    ...overrides,
})
const group = (members, type = 'vcep') => new Group({ id: 10, group_type_id: config.groups.types[type].id, members })
const categorize = members => biocuratorTrainingCategories(group(members))

describe('Step 4 biocurator training display', () => {
    it.each([
        [0, 0, 'untrained'], [1, 0, 'level1'], [1, 1, 'level2'],
        [0, 1, 'verification'], [null, null, 'untrained'],
        [0, null, 'untrained'], [1, null, 'level1'],
        [null, 0, 'untrained'], [null, 1, 'verification'],
        [false, false, 'untrained'], [true, false, 'level1'], [true, true, 'level2'],
        ['0', '0', 'untrained'], ['1', '0', 'level1'], ['1', '1', 'level2'],
        ['false', 'true', 'verification'], [undefined, true, 'verification'],
    ])('classifies %s / %s as %s', (first, second, category) => {
        const result = categorize([member(1, first, second)])
        expect(result.find(row => row.key === category).members).toHaveLength(1)
        expect(result.flatMap(row => row.members)).toHaveLength(1)
    })

    it('uses only active, non-deleted biocurator memberships in the current group', () => {
        const result = categorize([
            member(1, 0, 0),
            member(2, 1, 1, { end_date: '2026-01-01' }),
            member(3, 1, 1, { deleted_at: '2026-01-01' }),
            member(4, 1, 1, { roles: [config.groups.roles['biocurator-trainer']] }),
            member(5, 1, 1, { group_id: 99, person_id: 1 }),
        ])
        expect(result[0].members.map(entry => entry.member.id)).toEqual([1])
        expect(result.flatMap(row => row.members)).toHaveLength(1)
    })

    it('deduplicates the same person and exposes conflicting membership records', () => {
        const consistent = categorize([member(1, 1, 0), member(2, 1, 0, { person_id: 1 })])
        expect(consistent[1].members).toHaveLength(1)
        const conflicting = categorize([member(1, 1, 0), member(2, 1, 1, { person_id: 1 })])
        expect(conflicting.flatMap(row => row.members)).toHaveLength(1)
        expect(conflicting[3].members[0].details).toContain('Conflicting')
    })

    it('treats NULL and zero duplicate flags as equivalent without modifying memberships', () => {
        const ep = group([
            member(1, 1, null), member(2, 1, 0, { person_id: 1 }),
            member(3, null, null), member(4, 0, 0, { person_id: 3 }),
        ])
        const result = biocuratorTrainingCategories(ep)
        expect(result[0].members).toHaveLength(1)
        expect(result[1].members).toHaveLength(1)
        expect(result[3].members).toHaveLength(0)
        expect(ep.members[0].training_level_2).toBeNull()
        expect(ep.members[2].training_level_1).toBeNull()
        expect(ep.members[2].training_level_2).toBeNull()
    })

    it('classifies Samarth’s API true/null membership as Level 1 and labels NULL as not complete', () => {
        // MemberResource preserves SQL NULL while casting 1 to true.
        const ep = group([member(6842, true, null, {
            person_id: 3509,
            person: { id: 3509, name: 'Samarth Kulshrestha' },
        }), member(2, null, null)])
        const categories = biocuratorTrainingCategories(ep)
        expect(categories[1].members[0].member.person.name).toBe('Samarth Kulshrestha')
        expect(categories[1].members[0].details).toBe('Level 1: Complete; Level 2: Not complete.')
        expect(categories[0].members).toHaveLength(1)
        expect(categories[0].members[0].details).toBe('Level 1: Not complete; Level 2: Not complete.')
        expect(categories[3].members).toHaveLength(0)
        expect(ep.members[0].training_level_2).toBeNull()
    })

    it('retains verification for conflicting NULL-as-zero and completed flags', () => {
        const result = categorize([member(1, 1, null), member(2, 1, 1, { person_id: 1 })])
        expect(result.flatMap(row => row.members)).toHaveLength(1)
        expect(result[3].members[0].details).toContain('Conflicting')
        const inconsistent = categorize([member(1, null, 1)])
        expect(inconsistent[3].members[0].details).toBe('Level 1: Not complete; Level 2: Complete.')
    })

    it('keeps Biocurator overlap with Core Approval Member and Trainer roles', () => {
        const ep = group([member(1, 1, 1, { roles: [config.groups.roles.biocurator, config.groups.roles['biocurator-trainer'], config.groups.roles['core-approval-member']] })])
        expect(biocuratorTrainingCategories(ep)[2].members).toHaveLength(1)
        expect(ep.coreApprovalMembers).toHaveLength(1)
        expect(ep.biocuratorTrainers).toHaveLength(1)
        expect(ep.trainedBiocurators).toHaveLength(1)
    })

    it('does not change the three fully trained biocurators submission requirement', () => {
        const ep = group([member(1, 1, 1), member(2, 1, 1), member(3, 1, 0), member(4, 1, null)])
        biocuratorTrainingCategories(ep)
        expect(minimumBiocurators.isMet(ep)).toBe(false)
        ep.addMember(member(5, 1, 1))
        expect(minimumBiocurators.isMet(ep)).toBe(true)
    })

    it.each(['vcep', 'scvcep'])('renders the readonly categories and existing sections for %s', type => {
        const ep = group([
            member(1, null, null), member(2, 1, 0), member(3, 1, 1),
            member(4, true, null, { person: { id: 4, name: 'Samarth Kulshrestha' } }),
            member(5, 0, 1),
            member(6, 1, 1, { roles: [config.groups.roles['biocurator-trainer'], config.groups.roles['core-approval-member']] }),
        ], type)
        ep.expert_panel = { has_approved_pilot: true }
        const wrapper = shallowMount(SustainedCurationReview, {
            global: {
                plugins: [createStore({ getters: { 'groups/currentItemOrNew': () => ep } })],
                stubs: {
                    ReviewSection: { template: '<section><slot /></section>' },
                    'dictionary-row': { props: ['label'], template: '<div><strong>{{ label }}</strong><slot /></div>' },
                    'markdown-block': true,
                },
            },
        })
        expect(wrapper.text()).toContain('Untrained (1)')
        expect(wrapper.text()).toContain('Level 1 Trained (2)')
        expect(wrapper.text()).toContain('Level 2 Trained (1)')
        expect(wrapper.text()).toContain('Training needs verification (1)')
        const level1Row = wrapper.findAll('div').find(row => row.find('strong').exists() && row.find('strong').text() === 'Level 1 Trained (2)')
        expect(level1Row.text()).toContain('Samarth Kulshrestha')
        const verificationRow = wrapper.findAll('div').find(row => row.find('strong').exists() && row.find('strong').text() === 'Training needs verification (1)')
        expect(verificationRow.text()).not.toContain('Samarth Kulshrestha')
        expect(wrapper.text()).not.toContain('Unknown')
        expect(wrapper.text()).toContain('Level 1: Not complete; Level 2: Complete.')
        expect(wrapper.text()).toContain('Core Approval Members')
        expect(wrapper.text()).toContain('Biocurator Trainers')
        expect(wrapper.text()).toContain('Person6 Member')
        expect(wrapper.findAll('input, select, textarea')).toHaveLength(0)
        wrapper.unmount()
    })
})
