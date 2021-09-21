import { expect } from 'chai';
import { mutations, actions } from '@/store/groups'
import Group from '@/domain/group'
import api from '@/http/api'
import { testAction } from '../test_utils'

const groupData = {
    id: 1,
    name: 'Bob\'s Burgers',
    uuid: 'abcdefg',
    parent_id: null
}

describe('groups mutations', () => {    
    it('adds new group to items', () => {
        const state = {
            items: []
        };

        mutations.addItem(state, groupData);

        expect(state.items.length).to.equal(1);
        expect(state.items[0]).to.be.instanceOf(Group);
        expect(state.items[0].name).to.equal('Bob\'s Burgers');
    });

    it('updates an item in items', () => {
        const state = {
            items: [
                new Group(groupData)
            ]
        }

        const updatedGroupData  = {...groupData};
        updatedGroupData.parent_id = 2;

        mutations.addItem(state, updatedGroupData);

        expect(state.items.length).to.equal(1);
        expect(state.items[0].name).to.equal('Bob\'s Burgers');
        expect(state.items[0].parent_id).to.equal(2);
    });

    it('adds a member to a group', () => {
        const state = {
            items: [new Group({...groupData, ...{members: []}})]
        };

        const personData = {person_id: 1, person: { first_name: 'tammy' }};
        mutations.addMemberToGroup(state, groupData.uuid, personData);
        
        console.log('members', state.items[0].members);
        // expect(state.items[0].members).to.eql([personData]);
    })
});

describe('groups actions', () => {
    let mock;
    beforeEach(() => {
        const MockAdapter = require('axios-mock-adapter');
        mock = new MockAdapter(api);
    });

    it('gets all groups and adds them to items', () => {
        const groups = [
            groupData,
            {id: 2, name: 'big bob\'s diner', uuid: '123-123-123'}
        ];

        mock.onGet("/api/groups").reply(200, groups);

        testAction(
            actions.getItems, 
            {},
            {items: []}, 
            [
                { type: 'addItem', payload: groups[0]},
                { type: 'addItem', payload: groups[1]}
            ]
        );
    });

    it('gets a single group by uuid and commits addItem', () => {
        mock.onGet("/api/groups/abcdefg").reply(200, groupData);

        testAction(
            actions.find,
            'abcdefg',
            {items: []},
            [
                {type: 'addItem', payload: groupData}
            ]
        );
    });

    it('can add an existing person as a member', () => {
        const responseData = {
            group_id: groupData.id,
            person_id: 1,
            person: {
                first_name: 'Tammy',
                email: 'tammy@tamtam.com'
            },
            roles: [
                {
                    id: 1,
                    name: 'role name'
                }
            ]
        };

        mock.onPost("/api/groups/abcdefg/members").reply(200, responseData);

        testAction(
            actions.memberAdd,
            {
                uuid: 'abcdefg',
                personId: 1,
                roleIds: [1]
            },
            {items: [
                {...groupData, ...{members: []}}
            ]},
            [
                {type: 'addMemberToGroup', payload: responseData}
            ]
        );
    });

    it('can invite a new person to be a member', () => {
        const responseData = {
            group_id: groupData.id,
            person_id: 1,
            person: {
                first_name: 'Tammy',
                email: 'tammy@tamtam.com'
            },
            roles: [
                {
                    id: 1,
                    name: 'role name'
                }
            ]
        };

        mock.onPost("/api/groups/"+groupData.uuid+"/invites").reply(200, responseData);

        testAction(
            actions.memberInvite,
            {
                uuid: 'abcdefg',
                first_name: 'bob',
                last_name: 'belcher',
                email: 'bob@bobsburgers.com',
            },
            {items: [
                {...groupData, ...{members: []}}
            ]},
            [
                {type: 'addMemberToGroup', payload: responseData}
            ]
        );
    });

    it('can assign roles to a member', () => {
        const responseData = {
            id: 666,
            group_id: groupData.id,
            person_id: 1,
            person: {
                first_name: 'Tammy',
                email: 'tammy@tamtam.com'
            },
            roles: [
                {
                    id: 1,
                    name: 'role name'
                }
            ]
        };
        mock.onPost(`/api/groups/${groupData.uuid}/members/${responseData.id}/roles`).reply(201, responseData);

        testAction(
            actions.memberAssignRole, 
            {uuid: groupData.uuid, memberId: responseData.id, roleIds: [1]},
            {},
            [
                {type: 'addMemberToGroup', payload: responseData}
            ]
        );
    });

    it('removes a role from a member', () => {
        const role = {
            id: 1,
            name: 'role name'
        };
        const member = {
            id: 666,
            roles: []
        };
        mock.onDelete(`api/groups/${groupData.uuid}/members/${member.id}/roles/${role.id}`)
            .reply(200, member);

        testAction(
            actions.memberRemoveRole,
            {uuid: groupData.uuid, memberId: member.id, roleId: role.id},
            {},
            [{type: 'addMemberToGroup', payload: member}]
        )
    });

    it('grants permissions to members', () => {
        const perm = { id: 1, name: 'perm 1' };
        const member = { id: 1, permissions: [perm]};

        mock.onPost(`api/groups/${groupData.uuid}/members/${member.id}/permissions`)
            .reply(201, member);

        testAction(
            actions.memberGrantPermission, 
            {uuid: groupData.uuid, memberId: member.id, permissionId: [perm.id]},
            {},
            [{type: 'addMemberToGroup', payload: member}]
        );

    });

    it('revokes a permission from a member', () => {
        const perm = { id: 1, name: 'perm 1' };
        const member = { id: 1, permissions: []};

        mock.onDelete(`api/groups/${groupData.uuid}/members/${member.id}/permissions/${perm.id}`)
            .reply(201, member);

        testAction(
            actions.memberRevokePermission, 
            {uuid: groupData.uuid, memberId: member.id, permissionId: perm.id},
            {},
            [{type: 'addMemberToGroup', payload: member}]
        );
    });

    it('retires a member from a group', () => {
        const member = { 
            id: 1,
            start_date: '2021-01-01T00:00:00Z',
            end_date: '2021-09-16T00:00:00Z'
        }

        mock.onPost(`api/groups/${groupData.uuid}/members/${member.id}/retire`)
            .reply(200, member);

        testAction(
            actions.memberRetire,
            {uuid: groupData.uuid, memberId: member.id, startDate: member.start_date, endData: member.end_date},
            {},
            [{type: 'addMemberToGroup', payload: member}]
        );
    });

    it('removes a member from a group', () => {
        const member = { 
            id: 1,
            start_date: '2021-01-01T00:00:00Z',
            end_date: '2021-09-16T00:00:00Z'
        }

        mock.onDelete(`api/groups/${groupData.uuid}/members/${member.id}`)
            .reply(200, member);

        testAction(
            actions.memberRemove,
            {uuid: groupData.uuid, memberId: member.id, endData: member.end_date},
            {},
            [{type: 'addMemberToGroup', payload: member}]
        );
    });
});