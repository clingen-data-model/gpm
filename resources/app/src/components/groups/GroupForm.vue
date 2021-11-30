<template>
    <div>
        <input-row label="Type" v-if="canSetType">
            <select v-model="group.group_type_id" class="w-full">
                <option :value="null">Select&hellip;</option>
                <option v-for="type in groupTypes" :key="type.id" :value="type.id">
                    {{type.fullname}}
                </option>
            </select>
        </input-row>
        <dictionary-row label="Type" v-else>
            {{typeDisplayName}}
        </dictionary-row>
        
        <transition name="slide-fade-down" mode="out-in">
            <div v-if="group.group_type_id > 2 && group.expert_panel">
                <input-row 
                    label="Long Base Name" 
                    v-model="group.expert_panel.long_base_name" 
                    placeholder="Long base name"
                    :errors="errors.long_base_name"
                    input-class="w-full"
                ></input-row>
                <input-row 
                    label="Short Base Name" 
                    v-model="group.expert_panel.short_base_name" 
                    placeholder="Short base name"
                    :errors="errors.short_base_name"
                    input-class="w-full"
                ></input-row>
                <div v-if="hasAnyPermission(['groups-manage'])">
                    <input-row 
                        label="Affiliation ID" 
                        v-model="group.expert_panel.affiliation_id" 
                        :placeholder="affiliationIdPlaceholder"
                        :errors="errors.affiliation_id"  
                        input-class="w-full"
                    >
                        <template v-slot:label>
                            Affiliation ID
                            <note>admin-only</note>
                        </template>
                    </input-row>
                </div>
                <dictionary-row label="Affiliation ID" v-else>
                    <span v-if="group.expert_panel.affiliation_id">{{group.expert_panel.affiliation_id}}</span>
                    <span v-else class="text-gray-400">{{'Not yet assigend'}}</span>
                </dictionary-row>
            </div>
            <div v-else>
                <input-row 
                    v-model="group.name" 
                    placeholder="Name" 
                    label="Name" 
                    input-class="w-full"
                    :errors="errors.name"
                />
            </div>
        </transition>
        <div v-if="hasPermission('groups-manage')">
            <input-row :errors="errors.group_status_id">
                <template v-slot:label>
                    Status:
                    <note>admin-only</note>
                </template>
                <select v-model="group.group_status_id" class="w-full"
                    
                >
                    <option :value="null"></option>
                    <option v-for="status in groupStatuses" :key="status.id" :value="status.id">
                        {{titleCase(status.name)}}
                    </option>
                </select>
            </input-row>

            <input-row :errors="errors.parent_id"  >
                <template v-slot:label>
                    Parent group:
                    <note>admin-only</note>
                </template>
                <select v-model="group.parent_id" class="w-full">
                    <option :value="null">Select...</option>
                    <option :value="0">None</option>
                    <option v-for="parent in groups" :key="parent.id" :value="parent.id">
                        {{parent.displayName}}
                    </option>
                </select>
            </input-row>
        </div>
    </div>
</template>
<script>
import is_validation_error from '@/http/is_validation_error'
import Group from '@/domain/group'
import configs from '@/configs'
import formFactory from '@/forms/form_factory'

export default {
    name: 'GroupForm',
    emits: [
        'canceled',
        'saved'
    ],
    data() {
        return {
            groupTypes: [
                {id: 1, fullname: 'Working Group'},
                {id: 2, fullname: 'Clinical Domain Working Group'},
                {id: 3, fullname: 'GCEP'},
                {id: 4, fullname: 'VCEP'},
            ],
            groupStatuses: configs.groups.statuses,
            newGroup: new Group(),
        }
    },
    computed: {
        group: {
            get() {
                const group = this.$store.getters['groups/currentItem'];
                if (group) {
                    console.log('got a group from the store')
                    return group;
                }
                console.log('use this.newGroup b/c no group in the store.')
                return this.newGroup;
            },
            set (value) {
                console.log('set', value)
                try {
                    this.$store.commit('groups/addItem', value);
                } catch (e) {
                    console.log('no id so update newgroup')
                    this.newGroup = value;
                }
            }
        },
        groups () {
            return this.$store.getters['groups/all'];
        },
        canSetType() {
            return this.hasPermission('groups-manage') && !this.group.id 
        },
        typeDisplayName () {
            if (!this.group.type) {
                return "🐇🥚";
            }
            return (this.group.type.id < 3) 
                ? this.group.type.name.toUpperCase() 
                : this.group.expert_panel.type.name.toUpperCase();
        },
        affiliationIdPlaceholder () {
            return 50000
        },
        cdwgs () {
            return this.$store.getters['cdwgs/all']
        },
        namesDirty () {
            return this.group.expert_panel.isDirty('long_base_name')
                || this.group.expert_panel.isDirty('short_base_name');
        },
        affiliationIdDirty () {
            return this.group.expert_panel.isDirty('affiliation_id');
        }
    },
    methods: {
        async save() {
            try {
                this.resetErrors();
                if (this.group.id) {
                    await this.updateGroup();
                    this.$emit('saved');

                    this.$store.dispatch('groups/find', this.group.uuid);
                    this.$store.commit('pushSuccess', 'Group info updated.');
                    return;
                } 

                const newGroup = await this.createGroup()
                                    .then(response => response.data.data);
                this.$emit('saved');
                this.$store.commit('pushSuccess', 'Group created.');
                this.$router.push({name: 'AddMember', params: {uuid: newGroup.uuid}});
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors;
                }
            }
        },
        createGroup () {
            let {name, parent_id, group_type_id, group_status_id} = this.group.attributes;
            if (name === null && this.group.expert_panel) {
                name = this.group.expert_panel.long_base_name;
            }
            return this.$store.dispatch('groups/create', {name, parent_id, group_type_id, group_status_id});
        },
        updateGroup () {
            const promises = [];
            promises.push(this.saveGroupData());
            if (this.group.expert_panel) {
                promises.push(this.saveEpData());
            }
            
            return Promise.all(promises);
        },
        saveGroupData () {
            const promises = [];
            console.log(this.group.isDirty)
            if (this.group.isDirty('parent_id')) {
                promises.push(this.saveParent());
            }

            if (this.group.isDirty('name')) {
                console.log('name is dirty.  save new name.')
                promises.push(this.saveName())
            }

            if (this.group.isDirty('group_status_id')) {
                promises.push(this.saveStatus())
            }

            return Promise.all(promises);
        },
        async saveEpData() {
            const promises = []
            if (this.namesDirty) {
                console.log('update names')
                const {long_base_name, short_base_name} = this.group.expert_panel;
                promises.push(this.submitFormData({
                    method: 'put',
                    url: `/api/groups/${this.group.uuid}/expert-panel/name`, 
                    data: { long_base_name, short_base_name }
                }));
            }

            if (this.affiliationIdDirty) {
                promises.push(this.submitFormData({
                    method: 'put',
                    url: `/api/groups/${this.group.uuid}/expert-panel/affiliation-id`, 
                    data: { affiliation_id: this.affiliationId }
                }));
            }

            return await Promise.all(promises);
        },

        isDirty (attribute) {
            return this.group[attribute] != this.group[attribute]
        },
        
        saveParent () {
            return this.submitFormData({
                method: 'put',
                url: `/api/groups/${this.group.uuid}/parent`, 
                data: { parent_id: this.group.parent_id }
            })
        },
        saveName () {
            console.log('saveName', this.group.uuid, this.grou);
            return this.submitFormData({
                method: 'put',
                url: `/api/groups/${this.group.uuid}/name`,
                data: {name: this.group.name}
            })
        },
        saveStatus () {
            return this.submitFormData({
                method: 'put',
                url: `/api/groups/${this.group.uuid}/status`,
                data: {status_id: this.group.group_status_id}
            })
        },
        resetData () {
            if (this.group.uuid) {
                this.$store.dispatch('groups/find', this.group.uuid);
            }
        },
        cancel() {
            console.log('cancel!');
            if (this.group.uuid) {
                this.resetData();
            }
            this.$emit('canceled');
        }
    },
    beforeMount() {
        this.$store.dispatch('groups/getItems');
        this.$store.dispatch('cdwgs/getAll');
    },
    setup (props, context) {
        const {errors, submitFormData, resetErrors} = formFactory(props, context)

        return {
            errors,
            submitFormData,
            resetErrors
        }
    }
}
</script>