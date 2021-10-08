import {expect} from 'chai';
import User from '@/domain/user';
import Group from '@/domain/group';
import GroupMember from '@/domain/group_member';

describe('User entity', () => {
    it('knows if it has a direct permission', () => {
        const user = new User({permissions: [{name: 'test-perm', id: 1}]})
    
        const trueCases = [1, {id: 1}, 'test-perm'];
        trueCases.forEach(i => {
            expect(user.hasDirectPermission(i)).to.be.true;
        });
        
        const falseCases = [2, {id: 2}, 'fart-perm']
        falseCases.forEach(i => {
            expect(user.hasDirectPermission(i)).to.be.false;
        })
    });

    it('knows if it has a permission through a role', () => {
        const user = new User({
            roles: [
                {name: 'beans', permissions: [{name: 'test-perm', id: 1}]}
            ]
        });
    
        const trueCases = [1, {id: 1}, 'test-perm'];
        trueCases.forEach(i => {
            expect(user.hasPermissionThroughRole(i)).to.be.true;
        });
        
        const falseCases = [2, {id: 2}, 'fart-perm']
        falseCases.forEach(i => {
            expect(user.hasPermissionThroughRole(i)).to.be.false;
        })
    });

    it('knows if it has a permission for a group', () => {
        const group = new Group({id: 1, uuid: 'bob', name: 'test group'});
        const group2 = new Group({id: 2, uuid: 'early', name: 'test group 2'});
        const group3 = new Group({id: 3});
        const user = new User({
            memberships: [
                new GroupMember({
                    group_id: group.id, 
                    roles: [{name: 'coordinator', id: 1}],
                    permissions: [{name: 'test-perm', id: 1}]}),
                new GroupMember({group_id: group2.id, permissions: [{name: 'test-perm', id: 2}]})
            ]
        });

        expect(user.hasGroupPermission(1, group)).to.be.true
        expect(user.hasGroupPermission(1002, group)).to.be.true
        expect(user.hasGroupPermission(1, group2)).to.be.false
        expect(user.hasGroupPermission(1, group3)).to.be.false
    })
});