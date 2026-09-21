import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import MemberSuggestions from '@/components/groups/MemberSuggestions.vue'

const rows = {
    person: {
        kind: 'person', person_id: 1, uuid: 'p-1', first_name: 'Cheryl', last_name: 'Tunt', name: 'Cheryl Tunt',
        email: 'cheryl@example.com', institution: 'ISIS', has_account: true, has_idp_identity: false, idp_id: null, already_member: false,
    },
    unregistered: {
        kind: 'person', person_id: 2, uuid: 'p-2', first_name: 'Pam', last_name: 'Poovey', name: 'Pam Poovey',
        email: 'pam@example.com', institution: null, has_account: false, has_idp_identity: false, idp_id: null, already_member: false,
    },
    linkable: {
        kind: 'person', person_id: 3, uuid: 'p-3', first_name: 'Ray', last_name: 'Gillette', name: 'Ray Gillette',
        email: 'ray@example.com', institution: null, has_account: false, has_idp_identity: true, idp_id: 'user_ray', already_member: false,
    },
    idp: {
        kind: 'idp', person_id: null, uuid: null, first_name: 'Zed', last_name: 'Zardoz', name: 'Zed Zardoz',
        email: 'zed@example.com', institution: null, has_account: false, has_idp_identity: true, idp_id: 'user_zed', already_member: false,
    },
    member: {
        kind: 'person', person_id: 4, uuid: 'p-4', first_name: 'Sterling', last_name: 'Archer', name: 'Sterling Archer',
        email: 'archer@example.com', institution: null, has_account: true, has_idp_identity: false, idp_id: null, already_member: true,
    },
}

const badgeStub = { props: ['color'], template: '<span class="badge"><slot /></span>' }

function mountWith(suggestions) {
    return mount(MemberSuggestions, {
        props: { suggestions },
        global: { stubs: { badge: badgeStub } },
    })
}

describe('MemberSuggestions', () => {
    it('renders name, email and institution for a GPM person with an account, without a badge', () => {
        const wrapper = mountWith([rows.person])
        const item = wrapper.find('li')

        expect(item.text()).toContain('Cheryl Tunt')
        expect(item.text()).toContain('cheryl@example.com')
        expect(item.text()).toContain('ISIS')
        expect(item.find('.badge').exists()).toBe(false)
        expect(item.find('button').text()).toBe('Add as member')
    })

    it('flags a person without a GPM account', () => {
        const wrapper = mountWith([rows.unregistered])

        expect(wrapper.find('.badge').text()).toBe('No GPM account yet')
        expect(wrapper.find('button').text()).toBe('Add as member')
    })

    it('offers to add a person whose address has a ClinGen account through that account', () => {
        const wrapper = mountWith([rows.linkable])

        expect(wrapper.find('.badge').text()).toBe('Has ClinGen account')
        expect(wrapper.find('button').text()).toBe('Add using ClinGen account')
    })

    it('renders an identity not yet in the GPM', () => {
        const wrapper = mountWith([rows.idp])

        expect(wrapper.find('.badge').text()).toBe('ClinGen account, not yet in GPM')
        expect(wrapper.find('button').text()).toBe('Add using ClinGen account')
    })

    it('shows existing members without a button', () => {
        const wrapper = mountWith([rows.member])

        expect(wrapper.text()).toContain('Already a member')
        expect(wrapper.find('button').exists()).toBe(false)
    })

    it('emits the selected row and keys rows per identity address', async () => {
        const second = { ...rows.idp, email: 'z.zardoz@other.org' }
        const wrapper = mountWith([rows.idp, second])

        const items = wrapper.findAll('li')
        expect(items).toHaveLength(2)

        await items[1].find('button').trigger('click')
        expect(wrapper.emitted('selected')[0][0]).toEqual(second)
    })
})
