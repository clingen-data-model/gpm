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
        this.original = {};

        for (let attr in this.attributes) {
            this.defineAttributeGettersAndSetters(attr)
        }
    }

    defineAttributeGettersAndSetters(attr) 
    {
        let setter = (value) => {
            this.original[attr] = this.attributes[attr];
            this.attributes[attr] = value
        }

        let getter = () => {
            return this.attributes[attr]
        }

        if (this.constructor.dates.includes(attr)) {
            getter = () => (this.attributes[attr]) ? new Date(Date.parse(this.attributes[attr])) : null;
            setter = (value) => {
                this.original[attr] = this.attributs[attr];
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

    isPersisted() {
        return Boolean(this.attributes.id)
    }

    clone(){
        return new (this.constructor.self)(this.attributes);
    }

    getAttributes() {
        return this.attributes;
    }

    getOriginal() {
        return this.original;
    }

    clearOriginal() {
        this.original = {};
    }

    isDirty (attribute = null) {
        if (attribute) {
            return Boolean(this.original[attribute]);
        }
        return Object.keys(this.original).length > 0;
    }

    getDirty (attribute = null) {
        if (!this.isDirty(attribute)) {
            return {};
        }
        let keys = Object.keys(this.original);
        if (attribute) {
            if (Array.isArray(attribute)) {
                keys = attribute
            }
            if (typeof attribute == 'string') {
                keys = [attribute];
            }
        }

        const dirty = {};
        keys.forEach(key => {
            dirty[key] = {original: this.original[key], new: this.attributes[key]}
        });

        return dirty;
    }

    revertDirty () {
        this.attributes = {...this.attributes, ...this.original};
        this.clearOriginal();
    }

}

export default Entity;