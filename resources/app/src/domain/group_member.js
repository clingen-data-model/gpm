import Entity from './entity'
import Person from './person'

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

    matchesKeyword (keyword) {
        if (this.person.matchesKeyword(keyword)) {
            return true;
        }

        return false;
    }

    hasRole (role) {
        let roleId = role;
        if (typeof role == 'object') {
            roleId = role.id
        }

        return this.attributes.roles 
                && this.attributes.roles.findIndex(r => r.id == roleId) > -1;
    }

    hasPermission (permission) {
        let permissionId = permission;
        if (typeof permission == 'object') {
            permissionId = permission.id
        }

        return this.attributes.permissions 
                && this.attributes.permissions.findIndex(r => r.id == permissionId) > -1;
    }

    coiUpToDate () {
        console.log('member.coiUpToDate', this.coi_last_completed);
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
}

export default GroupMember;