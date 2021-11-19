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
        expert_panel: {
            type: {},
        },
        type: {},
    };

    constructor(attributes = {}) {
        const members = attributes.members ? [...attributes.members] : [];
        delete(attributes.members);

        super(attributes);

        this.members = [];
        if (members) {
            members.forEach(m => this.addMember(m))
        }
    }

    get coordinators () {
        return this.findMembersWithRole('coordinator')
                .filter(m => m.isActive)
    }

    get chairs () {
        return this.findMembersWithRole('chair')
                .filter(m => m.isActive)
    }

    get activeMembers () {
        return this.members.filter(m => m.isActive)
    }

    get typeName () {
        return this.expert_panel && this.expert_panel.id ? this.expert_panel.type.display_name : this.type.name;
    }

    get displayName () {
        return this.expert_panel && this.expert_panel.id 
            ? this.expert_panel.full_long_base_name
            : this.name;
    }

    get displayStatus () {
        return this.expertPanelLoaded()
            ? this.status.name
            : this.status.name
    }

    addMembers (members) {
        members.forEach(m => {
            this.addMember(m);
        })
    }

    addMember(member) {
        const idx = this.members.findIndex(m => m.id == member.id);
        if (idx > -1) {
            this.members.splice(idx, 1, new GroupMember(member))
            return;
        }

        this.members.push(new GroupMember(member));
    }

    removeMember(member) {
        const idx = this.members.findIndex(m => m.id == member.id);
        if (idx > -1) {
            delete(this.members[idx]);
            return;
        }
    }

    findMember(memberId) {
        const member = this.members.find(m => m.id == memberId);
        if (member) {
            return member;
        }
        throw Error(`Member with id ${memberId} not found in group.`);
    }

    findMembersWithRole(role) {
        let roleId = role;
        if (typeof role == 'object') {
            roleId = role.id
        }
        if (typeof role == 'string') {
            const matchingRole = config.groups.roles[role];
            if (!matchingRole) {
                throw new Error(`No role matching ${role}`);
            }
            roleId = matchingRole.id
        }
        return this.members.filter(m => m.hasRole(roleId));
    }

    expertPanelLoaded () {
        return this.isEp() && this.expert_panel.id;
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

    isVcep() {
        return this.isEp() && this.expert_panel && this.expert_panel.expert_panel_type_id == 2;
    }

    isGcep() {
        return this.isEp() && this.expert_panel && this.expert_panel.expert_panel_type_id == 1;
    }

    clone(){
        const members = this.members;
        return new (this.constructor.self)({...this.attributes, members});
    }
}

export default Group;