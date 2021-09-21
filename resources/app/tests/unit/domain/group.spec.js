import { expect } from 'chai';
import Group from '@/domain/group'

describe('group entity', () => {
    it('can add/or update members', () => {
        const group = new Group();
        expect(group.members).to.be.eql([]);

        const louise = {id: 1, uuid: 'abcd', email: 'louise@bobsburgers.com'};
        group.addMember(louise);

        expect(group.members).to.be.eql([louise]);

        const roles = ['boss', 'kid'];
        const louise2 = {...louise, roles};
        group.addMember(louise2);

        expect(group.members).to.be.eql([louise2])
    });

    it('includes members in clode', () => {
        const group = new Group();
        const louise = {id: 1, uuid: 'abcd', email: 'louise@bobsburgers.com'};
        group.addMember(louise);

        const groupClone = group.clone();
        expect(groupClone.members).to.eql([louise])
    })
});