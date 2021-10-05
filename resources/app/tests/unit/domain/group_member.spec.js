import { expect } from 'chai';
import GroupMember from '@/domain/group_member'

describe('GroupMember entity', () => {
    it('knows_if_it_has_a_role', () => {
        const member = new GroupMember({
            roles: [
                {
                    id: 1,
                    name: 'Role 1'
                }
            ]
        });

        expect(member.hasRole(1)).to.be.true;
        expect(member.hasRole({id: 1})).to.be.true;
        
        expect(member.hasRole(2)).to.be.false;
        expect(member.hasRole({id: 2})).to.be.false;
    });

    it('knows_if_it_has_a_permission', () => {
        const member = new GroupMember({
            permissions: [
                {
                    id: 1,
                    name: 'Permission 1'
                }
            ]
        });

        expect(member.hasPermission(1)).to.be.true;
        expect(member.hasPermission({id: 1})).to.be.true;

        expect(member.hasPermission(2)).to.be.false;
        expect(member.hasPermission({id: 2})).to.be.false;
    });

    it('knows if the member has a permission through a role', () => {
        const member = new GroupMember({
            roles: [
                {name: 'Coordinator', id: 1}
            ]
        });

        expect(member.hasPermissionThroughRole({name: 'info-edit', id: 1}))
            .to.be.true;

            expect(member.hasPermissionThroughRole({name: 'farting-inpublic', id: 666}))
            .to.be.false;
    })

    it('knows if the member needs to complete coi', () => {
        const yesterday = new Date();
        yesterday.setFullYear(yesterday.getFullYear()-1);
        const member1 = new GroupMember({
            coi_last_completed: yesterday.toISOString()
        })
        expect(member1.coiUpToDate()).to.be.true

        const lastYear = new Date();
        lastYear.setFullYear(lastYear.getFullYear()-1);
        lastYear.setDate(lastYear.getDate()-1);
        const member2 = new GroupMember({
            coi_last_completed: lastYear.toISOString()
        })
        expect(member2.coiUpToDate()).to.be.false
    });

    it('knows if the member has completed required training', () => {
        const chair  = new GroupMember({
            needs_training: false,
            training_completed_at: null
        });
        expect(chair.trainingComplete()).to.be.true;

        const curator1  = new GroupMember({
            needs_training: true,
            training_completed_at: null
        });
        expect(curator1.trainingComplete()).to.be.false;

        const curator2  = new GroupMember({
            needs_training: true,
            training_completed_at: '2020-01-01T00:00:00Z'
        });
        expect(curator2.trainingComplete()).to.be.true;
    })
})