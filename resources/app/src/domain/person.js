import Entity from './entity';

class Person extends Entity {
    static defaults = {
        uuid: '',
        first_name: '',
        last_name: '',
        email: '',
        phone:'',
        institution_id: null,
        institution: {},
        primary_occupation_id: null,
        primary_occupation: {},
        credentials: '',
        race_id: null,
        race: {},
        race_other: '',
        ethnicity_id: null,
        ethnicity: {},
        ethnicity_other: '',
        gender_id: null,
        gender: {},
        gender_other: '',
        created_at: null,
        updated_at: null,
        deleted_at: null
    }

    static dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ]

    get institutionName () {
        return this.institution ? this.institution.name : null;
    }

    get primaryOccupationName () {
        if (this.primary_occupation_id == 100) {
            return this.primary_occupation_other;
        }
        return this.primary_occupation ? this.primary_occupation.name : null
    }

    get raceName () {
        if (this.race_id == 100) {
            return this.race_other;
        }
        return this.race ? this.race.name : null
    }

    get ethnicityName () {
        return this.ethnicity ? this.ethnicity.name : null
    }

    get genderName () {
        if (this.gender_id == 100) {
            return this.gender_other;
        }
        return this.gender ? this.gender.name : null
    }

    matchesKeyword(keyword) {
        if (!keyword || keyword.length < 3) {
            return false;
        }
        const {first_name, last_name, email} = this.attributes;
        const pattern = new RegExp(keyword.toLowerCase());

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