import Entity from './entity'
import Person from './person'
import configs from '@/configs.json'
import { arrayContains } from '@/utils';

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
        permissions: []
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


    matchesKeyword (keyword) {
        if (this.person.matchesKeyword(keyword)) {
            return true;
        }

        return false;
    }

    hasRole (role) {
        return arrayContains(role, this.roles);
    }

    hasPermission (permission) {
        return this.hasDirectPermission(permission) || this.hasPermissionThroughRole(permission);
    }

    hasDirectPermission (permission) {
        let permissionId = permission;
        if (typeof permission == 'object') {
            permissionId = permission.id
        }

        return arrayContains(permission, this.attributes.permissions);
    }

    hasPermissionThroughRole (permission) {
        let permId = permission;
        if (typeof permId == 'object') {
            permId = permission.id
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