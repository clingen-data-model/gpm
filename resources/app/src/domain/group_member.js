import Entity from './entity'
import Person from './person'
import configs from '@/configs.json'
import { arrayContains } from '@/utils';

const groupRoles = configs.groups.roles;

const matchRole = (role) => {
    let matchedRole = role;
    if (typeof matchedRole == 'string') {
        matchedRole = Object.values(groupRoles)
                        .find(r => r.name == role);
    }
    if (typeof matchedRole == 'number') {
        matchedRole = Object.values(groupRoles)
                        .find(r => r.id == role);
    }

    if (typeof matchedRole != 'object' || !matchedRole || !Object.values(groupRoles).includes(matchedRole)) {
        let errorRole = role;
        if (typeof role == 'object') {
            errorRole = JSON.stringify(role);
        }
        throw new Error(`Role not found for identifier ${errorRole}`);
    }

    return matchedRole;
}

class GroupMember extends Entity {
    static dates = [
        'start_date',
        'end_date',
        'coi_last_completed'
    ];

    static defaults = {
        person_id: null,
        group_id: null,
        start_date: null,
        end_date: null,
        v1_contact: false,
        roles: [],
        permissions: [],
        coi_last_completed: null,
        coi_needed: null,
        expertise: null,
        notes: null,
        training_level_1: null,
        training_level_2: null
    }

    constructor(attributes = {}) {
        if (attributes instanceof GroupMember) {
            attributes = {...attributes.attributes, person: attributes.person.attributes}
        }
        const person = attributes.person;
        delete(attributes.person);
        super(attributes);
        this.person = person ? new Person(person) : new Person();
    }

    get latestCoiDate () {
        if (this.attributes.coi_last_completed) {
            return this.attributes.coi_last_completed;
        }

        return null;
    }

    get first_name () {
        return this.person.first_name
    }

    set first_name (value) {
        this.person.first_name = value;
    }

    get last_name () {
        return this.person.last_name
    }

    set last_name (value) {
        this.person.last_name = value;
    }

    get email () {
        return this.person.email
    }

    set email (value) {
        this.person.email = value;
    }

    get isActive () {
        return this.attributes.end_date === null;
    }

    get isRetired () {
        return !this.isActive;
    }

    get roleIds () {
        return this.roles.map(r => r.id)
    }

    get roleNames () {
        return this.roles.map(r => r.name)
    }

    get addedRoles () {
        const originalRoles = this.original.roles;
        return this.roles.filter(r => !originalRoles.map(or => or.id).includes(r.id));
    }

    get removedRoles () {
        const originalRoles = this.original.roles;
        return originalRoles.filter(or => !this.roles.map(r => r.id).includes(or.id));
    }

    get needsCoi () {
        if (!this.latestCoiDate) {
            return true;
        }

        const today = new Date();
        const yearAgo = new Date();
        yearAgo.setFullYear(today.getFullYear()-1);

        if (this.latestCoiDate.valueOf() <= yearAgo.valueOf()) {
            return true;
        }

        return false;
    }

    matchesKeyword (keyword) {
        if (this.person.matchesKeyword(keyword)) {
            return true;
        }

        return false;
    }

    addRole (role) {
        if (!this.hasRole(role)) {
            this.roles.push(matchRole(role))
        }
    }

    removeRole (role) {
        if (this.hasRole(role)) {
            const matchedRole = matchRole(role);
            const roleIdx = this.roles.findIndex(r => r.id == matchedRole.id);
            this.roles.splice(roleIdx, 1);
        }
    }

    hasRole (role) {
        return arrayContains(role, this.roles);
    }

    hasPermission (permission) {
        return this.hasDirectPermission(permission) || this.hasPermissionThroughRole(permission);
    }

    hasDirectPermission (permission) {
        return arrayContains(permission, this.attributes.permissions);
    }

    hasPermissionThroughRole (permission) {
        let permId = permission;
        if (typeof permId == 'object') {
            permId = permission.id
        }
        if (typeof permId == 'string') {
            if (!configs.groups.permissions[permission]) {
                throw new Error(`Can not find permission ${permission} in configs`)
            }
            permId = configs.groups.permissions[permission].id
        }

        const memberRoleSlugs = this.roles.map(r => {
            return r.name.toLowerCase()
        })

        for (let i in configs.groups.rolePermissions) {
            if (!memberRoleSlugs.includes(i)) {
                continue;
            }

            if (configs.groups.rolePermissions[i].includes(permId)) {
                return true;
            }
        }

        return false;
    }

    coiUpToDate () {
        if (!this.coi_last_completed) {
            return false;
        }
        const cuttoff = new Date();
        cuttoff.setFullYear(cuttoff.getFullYear()-1);

        if (Date.parse(cuttoff) <= this.coi_last_completed) {
            return true;
        }

        return false;
    }

    trainingComplete () {
        if (!this.attributes.needs_training) {
            return true;
        }

        return Boolean(this.attributes.training_completed_at)
    }

    clone(){
        const person = this.person;
        const clone = new (this.constructor)({...this.attributes, person: person.attributes});
        return clone;
    }
}

export default GroupMember;