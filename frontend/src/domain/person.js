import Entity from './entity';

class Person extends Entity {
    static default = {
        'uuid': null,
        'first_name': null,
        'last_name': null,
        'email': null,
        'phone': null,
        'created_at': null,
        'updated_at': null,
        'deleted_at': null
    }

    static dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ]

}

export default Person