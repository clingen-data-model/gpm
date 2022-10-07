import Entity from '@/domain/entity';
import Person from '@/domain/person';
import GroupMember from '@/domain/group_member';
import { arrayContains } from '@/utils';
class User extends Entity {
    static defaults = {
        id: null,
        email: null,
        name: null,
        roles: [],
        permissions: [],
        preferences: [],
        person: new Person(),
        memberships: [],
    }

    constructor(attributes = {}) {
        const {person = {}, memberships = []} = attributes;
        delete(attributes.person);
        delete(attributes.memberships);

        super(attributes);

        this.person = new Person(person);
        this.memberships = memberships.map(m => new GroupMember(m));
    }

    get rolePermissions () {
        return this.attributes.roles.map(r => r.permissions).flat();
    }

    get allPermissions () {
        return [...this.permissions, ...this.rolePermissions]
    }

    get needsCredentials () {
        return this.person.needsCredentials;
    }

    get needsExpertise () {
        return this.person.needsExpertise
    }

    get profileIncomplete () {
        return !this.person.country_id
            || !this.person.timezone
            || !this.person.institution_id
    }

    get hasPendingCois () {
        return this.person.hasPendingCois;
    }

    hasRole (role, group = null)
    {
        return arrayContains(role, this.roles)
            || this.hasGroupRole(role, group);
    }

    hasNoRole()
    {
        return this.roles.length == 0;
    }

    hasGroupRole(role, group) {
        if (!group) {
            return false;
        }
        const membership = this.memberships.find(m => m.group_id == group.id);
        if (membership && membership.hasRole(role)) {
            return true;
        }
        return false;
    }

    hasRoleForAnyGroup(role, groups) {
       return groups.some(g => this.hasGroupRole(role, g));
    }

    hasAnyPermission (permissions)
    {
        if (!Array.isArray(permissions)) {
            throw new Error('user.hasAnyArray expected array got '+(typeof permissions));
        }

        return permissions.map(p => {
            let perm = p;
            let group = null;
            if (Array.isArray(p)) {
                perm = p[0];
                group = p[1];
            }
            // check all possible permission sets
            return this.hasDirectPermission(perm)
                || this.hasPermissionThroughRole(perm)
                || this.hasGroupPermission(perm, group);
            })
            .filter(r => r) //filter for true values only
            .length > 0; // check we have at least one true result
    }

    hasPermission (permission, group = null)
    {
        return this.hasDirectPermission(permission)
            || this.hasPermissionThroughRole(permission)
            || this.hasGroupPermission(permission, group);
    }

    hasDirectPermission(permission) {
        return arrayContains(permission, this.permissions);
    }

    hasPermissionThroughRole (permission) {
        return arrayContains(permission, this.roles.map(m => m.permissions).flat());
    }

    hasGroupPermission (permission, group) {
        if (!group) {
            return false;
        }
        let membership = this.memberships.find(m => m.group_id == group.id);
        if (!(membership instanceof GroupMember)) {
            membership = new GroupMember(membership)
        }
        if (membership && membership.hasPermission(permission)) {
            return true;
        }
        return false;
    }

    coordinatesPerson (person) {
        return this.hasRoleForAnyGroup('coordinator', person.memberships.map(m => m.group));
    }
}

export default User;
