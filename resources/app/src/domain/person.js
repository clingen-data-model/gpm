import Entity from './entity';

class Person extends Entity {
    static default = {
        'uuid': '',
        'first_name': '',
        'last_name': '',
        'email': '',
        'phone':'',
        'created_at': null,
        'updated_at': null,
        'deleted_at': null
    }

    static dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ]

    matchesKeyword(keyword) {
        const {first_name, last_name, email} = this.attributes;
        const pattern = new RegExp(keyword);
        if (first_name && first_name.match(pattern)) {
            return true;
        }
        if (last_name && last_name.match(pattern)) {
            return true;
        }
        if (email && email.match(pattern)) {
            return true;
        }
        if (first_name && last_name) {
            const fullname = `${first_name} ${last_name}`;
            if (fullname && fullname.match(pattern)) {
                return true;
            }
        }
        
        return false;
    }

}

export default Person