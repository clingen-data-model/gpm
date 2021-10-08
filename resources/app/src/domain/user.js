import Entity from '@/domain/entity';
import Person from '@/domain/person';
import { ArrayContains } from '../utils';
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

    get rolePermissions () {
        return this.attributes.roles.map(r => r.permissions).flat();
    }

    get allPermissions () {
        return [...this.permissions, ...this.rolePermissions]
    }

    hasRole (role, group = null) 
    {
        return ArrayContains(role, this.roles)
            || this.hasGroupRole(role, group);
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

    hasPermission (permission, group = null)
    {
        return this.hasDirectPermission(permission)
            || this.hasPermissionThroughRole(permission)
            || this.hasGroupPermission(permission, group);
    }

    hasDirectPermission(permission) {
        return ArrayContains(permission, this.permissions);
    }

    hasPermissionThroughRole (permission) {
        return ArrayContains(permission, this.roles.map(m => m.permissions).flat());
    }

    hasGroupPermission (permission, group) {
        if (!group) {
            return false;
        }
        const membership = this.memberships.find(m => m.group_id == group.id);
        if (membership && membership.hasPermission(permission)) {
            return true;
        }
        return false;
    }
}

export default User;