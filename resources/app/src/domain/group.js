import Entity from '@/domain/entity'
import GroupMember from '@/domain/group_member'
import config from '@/configs.json'

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
        this.members = members ? members.forEach(m => this.addMember(m)) : [];
    }

    addMember(member) {
        const idx = this.members.findIndex(m => m.id == member.id);
        if (idx > -1) {
            this.members.splice(idx, 1, new GroupMember(member))
            return;
        }

        this.members.push(new GroupMember(member));
    }
    
    isEp() {
        return this.attributes.group_type_id === config.groups.types.ep.id;
    }

    isWorkingGroup() {
        return this.attributes.group_type_id === config.groups.types.wg.id;
    }

    isCdwg() {
        return this.attributes.group_type_id === config.groups.types.cdwg.id;
    }

    clone(){
        const members = this.members;
        return new (this.constructor.self)({...this.attributes, members});
    }

}

export default Group;