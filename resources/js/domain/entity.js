import {cloneDeep, isEqual} from 'lodash-es'
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

    constructor(attributes = {}) {

        this.attributes = {};
        this.setAttributes({...this.constructor.defaults, ...attributes});

        this.original = {};
        this.setOriginal(this.attributes);

        for (const attr in this.attributes) {
            this.defineAttributeGettersAndSetters(attr)
        }
    }

    setAttributes(attributes) {
        this.attributes = cloneDeep(attributes);
    }

    setOriginal(attributes) {
        this.original = cloneDeep(attributes);
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

    isPersisted() {
        return Boolean(this.attributes.id)
    }

    clone(){
        return new this.constructor(this.attributes);
    }

    getAttributes() {
        return this.attributes;
    }

    /**
     *
     * TODO: dirty & original need to support nested objects.  currently this is not the case.
     */
    getOriginal() {
        return this.original;
    }

    clearChanges() {
        this.original = {};
    }

    isDirty (attribute = null) {
        if (attribute) {
            return this.original[attribute] != this.attributes[attribute];
        }
        return Object.keys(this.original).some(key => {
            return !isEqual(this.original[key], this.attributes[key]);
        });
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
            if (this.isDirty(key)) {
                dirty[key] = {original: this.original[key], new: this.attributes[key]}
            }
        });

        return dirty;
    }

    revertDirty () {
        this.attributes = {...this.attributes, ...this.original};
        this.clearChanges();
    }

}

export default Entity;
