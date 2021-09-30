import { expect } from 'chai';
import Group from '@/domain/group'
import GroupMember from '@/domain/group_member'
import config from '@/configs.json'


describe('group entity', () => {
    it('can tell if it is an expert panel', () => {
        const group = new Group({'group_type_id': config.groups.types.ep.id})
        expect(group.isEp()).to.be.true;
    });

    it('can tell if it is an working group', () => {
        const group = new Group({'group_type_id': config.groups.types.wg.id})
        expect(group.isWorkingGroup()).to.be.true;
    });

    it('can tell if it is an cdwg', () => {
        const group = new Group({'group_type_id': config.groups.types.cdwg.id})
        expect(group.isCdwg()).to.be.true;
    });

    it('can add/or update members', () => {
        const group = new Group();
        expect(group.members).to.be.eql([]);

        const louise = {id: 1, uuid: 'abcd', email: 'louise@bobsburgers.com'};
        group.addMember(louise);

        expect(group.members).to.be.eql([new GroupMember(louise)]);

        const roles = ['boss', 'kid'];
        const louise2 = {...louise, roles};
        group.addMember(louise2);

        expect(group.members).to.be.eql([new GroupMember(louise2)])
    });

    it('includes members in clode', () => {
        const group = new Group();
        const louise = {id: 1, uuid: 'abcd', email: 'louise@bobsburgers.com'};
        group.addMember(louise);

        const groupClone = group.clone();
        expect(groupClone.members).to.eql([new GroupMember(louise)])
    })
});