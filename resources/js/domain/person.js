import Entity from './entity';
import GroupMember from './group_member';

class Person extends Entity {
    static defaults = {
        uuid: null,
        first_name: null,
        last_name: null,
        email: null,
        phone:null,
        institution_id: null,
        institution: {},
        credentials: [],
        expertises: [],
        primary_occupation_id: null,
        primary_occupation: {},
        profile_photo: null,
        race_id: null,
        race: {},
        race_other: null,
        ethnicity_id: null,
        ethnicity: {},
        ethnicity_other: null,
        gender_id: null,
        gender: {},
        gender_other: null,
        created_at: null,
        updated_at: null,
        deleted_at: null
    }

    static dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ]

    constructor(attributes = {}) {
        if (attributes instanceof Person) {
            attributes = {
                ...attributes.attributes,
                memberships: [attributes.memberships.map(m => m.attributes)],
            };
        }

        const memberships = attributes.memberships || [];
        delete(attributes.memberships);

        const mailLog = attributes.mailLog || [];
        delete(attributes.mailLog);

        super({...attributes});

        this.memberships = memberships.map(m => new GroupMember(m));
        this.mailLog = mailLog;
    }

    get institutionName () {
        return this.institution ? this.institution.name : null;
    }

    get credentialsString () {
        return this.credentials ? this.credentials.map(c => c.name).join(', ') : null;
    }

    get primaryOccupationName () {
        if (this.primary_occupation_id == 100) {
            return this.primary_occupation_other;
        }
        return this.primary_occupation ? this.primary_occupation.name : null
    }

    get raceName () {
        if (this.race_id == 100) {
            return this.race_other || 'Other';
        }
        return this.race ? this.race.name : null
    }

    get ethnicityName () {
        return this.ethnicity ? this.ethnicity.name : null
    }

    get genderName () {
        if (this.gender_id == 100) {
            return this.gender_other || 'Not specified.';
        }
        return this.gender ? this.gender.name : null
    }

    get membershipsWithPendingCois () {
        return this.membershipsWithCoiRequirement.filter(m => m.coi_needed);
    }

    get membershipsWithCompletedCois () {
        return this.membershipsWithCoiRequirement.filter(m => !m.coi_needed);
    }

    get membershipsWithOutdatedCois () {
        return this.membershipsWithCoiRequirement.filter(m => m.coi_needed && m.cois.length > 0);
    }

    get completedCois () {
        return this.membershipsWithCompletedCois.map(m => m.cois).flat();
    }

    get hasPendingCois () {
        return this.membershipsWithPendingCois.length  > 0;
    }

    get hasCompletedCois () {
        return this.membershipsWithCompletedCois.length > 0;
    }

    get hasOutdatedCois () {
        return this.membershipsWithOutdatedCois.length > 0;
    }

    get expertPanelMemberships () {
        return this.memberships.filter(m => [3,4].includes(m.group.group_type_id) )
    }

    get membershipsWithCoiRequirement () {
        return this.memberships.filter(m => m.has_coi_requirement)
    }

    get needsCredentials () {
        return !this.credentials || this.credentials.length == 0
    }

    get hasExpertise () {
        return this.expertises && this.expertises.length > 0;
    }

    get needsExpertise () {
        return !this.hasExpertise;
    }

    matchesKeyword(keyword) {

        const {first_name, last_name, email} = this.attributes;
        const pattern = new RegExp(keyword, 'i');

        if (first_name && first_name.toLowerCase().match(pattern)) {
            return true;
        }
        if (last_name && last_name.toLowerCase().match(pattern)) {
            return true;
        }
        if (email && email.toLowerCase().match(pattern)) {
            return true;
        }
        if (first_name && last_name) {
            const fullname = `${first_name} ${last_name}`;
            if (fullname && fullname.toLowerCase().match(pattern)) {
                return true;
            }
        }

        return false;
    }

}

export default Person
