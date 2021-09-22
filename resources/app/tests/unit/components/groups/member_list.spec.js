import { mount } from '@vue/test-utils'
import { expect } from 'chai'
import MemberList from '@/components/groups/MemberList'
import Group from '@/domain/group'
import config from '@/configs.json'

describe ('MemberList Component', () => {
    let group;
    let component;

    beforeEach(() => {
        group = new Group({name: 'Bob\'s Burgers', uuid: 'abcd-1234', id: 1, group_type_id: config.groups.types.ep.id});
        group.addMember({
            id: 1,
            person_id: 1,
            person: {
                first_name: 'Bob',
                last_name: 'Belcher',
                email: 'bob@bobsburgers.com',
            },
            start_date: '2021-01-01T00:00:00Z',
            end_date: null,
            v1_contact: 1,
            roles: [
                { name: 'chair', id: 1 },
                { name: 'coordinator', id: 2 },
                { name: 'parent', id: 3 }
            ]
        });
        group.addMember({
            id: 2,
            person_id: 2,
            person: {
                first_name: 'Linda',
                last_name: 'Belcher',
                email: 'linda@bobsburgers.com',
            },
            start_date: '2021-01-01T00:00:00Z',
            end_date: null,
            v1_contact: 1,
            roles: [
                { name: 'coordinator', id: 2 },
                { name: 'parent', id: 3 }
            ]
        });
        group.addMember({
            id: 3,
            person_id: 3,
            person: {
                first_name: 'Tina',
                last_name: 'Belcher',
                email: 'tina@bobsburgers.com',
            },
            start_date: '2021-01-01T00:00:00Z',
            end_date: null,
            v1_contact: 1,
            roles: [
                { name: 'kid', id: 4 },
            ]
        });
        component = mount(MemberList, {
            prosData: {
                group: group
            }
        });
    });

    it('it has a title "Members"', async () => {
        const heading = component.find('head').find('h2');

        expect(heading.text()).to.equal('Members');
    })

    it('launches AddMember dialog when Add Member button clicked', () => {
        const addMemberBtn = component.find({ref: 'new-member-button'});

        await addMemberBtn.trigger('click');
    });
    
});