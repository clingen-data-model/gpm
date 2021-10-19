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