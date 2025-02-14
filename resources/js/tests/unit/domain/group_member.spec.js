import { expect } from 'chai';
import GroupMember from '@/domain/group_member'
import configs from '@/configs.json'

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
                {name: 'coordinator', id: 1}
            ]
        });

        expect(member.hasPermissionThroughRole(configs.groups.permissions['info-edit']))
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

    it('can add a role by object to the roles list', () => {
        const member  = new GroupMember();
        member.addRole(configs.groups.roles.biocurator);

        expect(member.roles.length).to.equal(1);
        expect(member.roles[0]).to.eq(configs.groups.roles.biocurator)
    });

    it('can add a role by name to the roles list', () => {
        const member  = new GroupMember();
        member.addRole('biocurator');

        expect(member.roles.length).to.equal(1);
        expect(member.roles[0]).to.eq(configs.groups.roles.biocurator)
    });

    it('can add a role by id to the roles list', () => {
        const member  = new GroupMember();
        member.addRole(configs.groups.roles.biocurator.id);

        expect(member.roles.length).to.equal(1);
        expect(member.roles[0]).to.eq(configs.groups.roles.biocurator)
    });

    it('can remove a role by object', () => {
        const member  = new GroupMember();
        member.roles = [configs.groups.roles.biocurator];
        expect(member.roles.length).to.equal(1);

        member.removeRole(configs.groups.roles.biocurator);

        expect(member.roles.length).to.equal(0);
    })

    it('can remove a role by id', () => {
        const member  = new GroupMember();
        member.roles = [configs.groups.roles.biocurator];
        expect(member.roles.length).to.equal(1);

        member.removeRole(configs.groups.roles.biocurator.id);

        expect(member.roles.length).to.equal(0);
    })

    it('can remove a role by name', () => {
        const member  = new GroupMember();
        member.roles = [configs.groups.roles.biocurator];
        expect(member.roles.length).to.equal(1);
        expect(member.roles[0].name).to.equal('biocurator');

        member.removeRole('biocurator');

        expect(member.roles.length).to.equal(0);
    })

    it('can get newly assigned roles', () => {
        const member = new GroupMember({roles: [
            configs.groups.roles.biocurator
        ]});

        member.addRole(configs.groups.roles.chair);

        expect(member.addedRoles.length).to.equal(1);
        expect(member.addedRoles.map(r => r.id)[0]).to.equal(configs.groups.roles.chair.id);
    })
    
    it('can get newly removed roles', () => {
        const member = new GroupMember({roles: [
            configs.groups.roles.biocurator,
            configs.groups.roles.chair
        ]});
        
        member.removeRole(configs.groups.roles.chair);
        
        expect(member.removedRoles.length).to.equal(1);
        expect(member.removedRoles.map(r => r.id)[0]).to.equal(configs.groups.roles.chair.id);
    })

    it('knows it needs a COI if coi_last_completed is not set', () => {
        const member = new GroupMember();
        expect(member.needsCoi).to.be.true;
    });

    it('knows it needs a COI if coi_last_completed is more than 1 year old', () => {
        const member = new GroupMember();

        const today = new Date();
        const lastYear = new Date();
        lastYear.setFullYear(today.getFullYear()-1);
        member.coi_last_completed = lastYear;

        expect(member.needsCoi).to.be.true;
    });

    it('knows it does not need a COI if coi_last_completed_is less than 1 year old', () => {
        const member = new GroupMember();

        const today = new Date();
        const yesterday = new Date();
        yesterday.setDate(today.getDate()-364)
        member.coi_last_completed = yesterday;

        expect(member.needsCoi).to.be.false;
    });

})