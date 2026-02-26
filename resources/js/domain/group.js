import Entity from '@/domain/entity'
import GroupMember from '@/domain/group_member'
import ExpertPanel from '@/domain/expert_panel'
import configs from '@/configs.json'

class Group extends Entity {
    static dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    static defaults = {
        id: null,
        uuid: null,
        name: null,
        parent_id: null,
        group_type_id: null,
        group_status_id: null,
        type: {},
        parent: {},
    };

    constructor(attributes = {}) {
        if (attributes instanceof Group) {
            attributes = {
                ...attributes.attributes,
                members: [attributes.members.map(m => m.attributes)],
                expert_panel: attributes.expert_panel.attributes
            };
        }

        const members = attributes.members ? [...attributes.members] : [];
        delete(attributes.members);

        const documents = attributes.documents ? [...attributes.documents] : [];
        delete(attributes.documents);

        const expertPanel = attributes.expert_panel ? {...attributes.expert_panel} : {};
        delete(attributes.expert_panel);

        super(attributes);

        this.members = [];
        if (members) {
            members.forEach(m => this.addMember(m))
        }
        this.expert_panel = new ExpertPanel(expertPanel)
        this.documents = documents;

    }

    get coordinators () {
        return this.findMembersWithRole('coordinator')
                .filter(m => m.isActive)
    }

    get chairs () {
        return this.findMembersWithRole('chair')
                .filter(m => m.isActive)
    }

    get biocurators () {
        return this.findMembersWithRole('biocurator')
                .filter(m => m.isActive)
    }

    get trainedBiocurators () {
        return this.findMembersWithRole('biocurator')
                .filter(m => {
                    return m.isActive
                        && m.training_level_1
                        && m.training_level_2
                })
    }

    get biocuratorTrainers () {
        return this.findMembersWithRole('biocurator-trainer')
                .filter(m => m.isActive)
    }

    get coreApprovalMembers () {
        return this.findMembersWithRole('core-approval-member')
                .filter(m => m.isActive)
    }

    get activeMembers () {
        return this.members.filter(m => m.isActive)
    }

    get typeName () {
        return this.expert_panel.type.display_name || this.type.name;
    }

    get displayName () {
        return this.expert_panel.display_name || this.name;
    }

    get displayStatus () {
        return this.expertPanelLoaded()
            ? this.status.name
            : this.status.name
    }

    get statusColor () {
        return configs.groups.statusColors[this.group_status_id];
    }

    get contacts () {
        return this.members.filter(m => Boolean(m.is_contact))
    }

    get hasContacts() {
        return (this.contacts.length > 0);
    }

    get isApplying () {
        return this.expert_panel.isApplying
    }

    get hasChildren() {
        return this.children && this.children.length > 0;
    }


    addMembers (members) {
        members.forEach(m => {
            this.addMember(m);
        })
    }

    addMember(member) {
        const idx = this.members.findIndex(m => m && Number.parseInt(m.id) === Number.parseInt(member.id));
        if (idx > -1) {
            this.members.splice(idx, 1, new GroupMember({...member}))
            return;
        }

        this.members.push(new GroupMember({...member}));
    }

    removeMember(member) {
        const idx = this.members.findIndex(m => Number.parseInt(m.id) === Number.parseInt(member.id));
        if (idx > -1) {
            this.members.splice(idx, 1);
        }
    }

    findMember(memberId) {
        const member = this.members.find(m => Number.parseInt(m.id) === Number.parseInt(memberId));
        if (member) {
            return member;
        }
        throw new Error(`Member with id ${memberId} not found in group.`);
    }

    findMembersWithRole(role) {
        let roleId = role;
        if (typeof role == 'object') {
            roleId = role.id
        }
        if (typeof role == 'string') {
            const matchingRole = configs.groups.roles[role];
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
        const groupTypeID = Number.parseInt(this.attributes.group_type_id);
        const epIds = [ Number.parseInt(configs.groups.types.gcep.id), 
                        Number.parseInt(configs.groups.types.vcep.id), 
                        Number.parseInt(configs.groups.types.scvcep.id),
        ];
        return epIds.includes(groupTypeID);
    }

    isWorkingGroup() {
        return Number.parseInt(this.attributes.group_type_id) === Number.parseInt(configs.groups.types.wg.id);
    }

    isWg() {
        return this.isWorkingGroup();
    }

    isCdwg() {
        return Number.parseInt(this.attributes.group_type_id) === Number.parseInt(configs.groups.types.cdwg.id);
    }

    isScCdwg() {
        return Number.parseInt(this.attributes.group_type_id) === Number.parseInt(configs.groups.types.sccdwg.id);
    }

    documentsOfType(docTypeId) {
        return this.documents.filter(d =>  d.document_type_id === docTypeId)
    }

    hasDocumentsOfType(docTypeId) {
        return this.documentsOfType(docTypeId).length
    }

    clone(){
        const attributes = {
            ...this.attributes,
            members: [this.members.map(m => m.attributes)],
            expert_panel: this.expert_panel.attributes
        };

        const clone = super.clone(attributes);

        return clone;
    }
}

export default Group;
