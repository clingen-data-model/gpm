import Entity from '@/domain/entity'

class Group extends Entity {
    static dates = [
        'date_completed',
        'date_initiated',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    static defaults = {
        id: null,
        uuid: null,
        name: null,
        parent_id: null,
    };

    constructor(attributes = {}) {
        const members = attributes.members;
        delete(attributes.members);
        super(attributes);
        this.members = members || [];
    }

    addMember(member) {
        const idx = this.members.findIndex(m => m.id == member.id);
        if (idx > -1) {
            this.members.splice(idx, 1, member)
            return;
        }

        this.members.push(member);
    }

    clone(){
        const members = this.members;
        return new (this.constructor.self)({...this.attributes, members});
    }

}

export default Group;