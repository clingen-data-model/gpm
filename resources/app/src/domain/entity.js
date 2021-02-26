class Entity {
    static defaults = {
        'created_at': null,
        'updated_at': null,
        'deleted_at': null,
    }
    static dates = [
        'updated_at',
        'created_at',
        'deleted_at'
    ]
    static self = Entity

    constructor(attributes = {}) {


        this.attributes = {...this.constructor.defaults, ...attributes}

        for (let attr in this.attributes) {
            this.defineAttributeGettersAndSetters(attr)
        }
    }

    defineAttributeGettersAndSetters(attr) 
    {
        let setter = (value) => {
            this.attributes[attr] = value
        }

        let getter = () => {
            return this.attributes[attr]
        }

        if (this.constructor.dates.includes(attr)) {
            getter = () => (this.attributes[attr]) ? new Date(Date.parse(this.attributes[attr])) : null;
            setter = (value) => {
                this.attributes[attr] = (value) ? new Date(Date.parse(value)) : null;
            }
        }

        Object.defineProperty(
            this, 
            attr, 
            { 
                get: getter, 
                set: setter
            }
        );
    }

    setAttribute(attr, value=null) {
        if (typeof Object.getOwnPropertyDescriptor(this, attr) == 'undefined') {
            this.defineAttributeGettersAndSetters(attr);
        }

        this[attr] = value;
    }

    clone(){
        return new (this.constructor.self)(this.attributes);
    }

}

export default Entity;