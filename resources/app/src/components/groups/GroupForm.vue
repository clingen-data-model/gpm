<template>
    <div>
        <input-row 
            v-if="canSetType"
            v-model="group.group_type_id"
            :errors="errors.group_type_id"
            type="select"
            :options="typeOptions"
            label="Type" 
        />

        <dictionary-row label="Type" v-else>
            {{typeDisplayName}}
        </dictionary-row>
        
        <transition name="slide-fade-down" mode="out-in">
            <div v-if="group.group_type_id > 2 && group.expert_panel">
                <input-row 
                    label="Long Base Name" 
                    v-model="group.expert_panel.long_base_name"
                    @update:modelValue="emitUpdate"
                    placeholder="Long base name"
                    :errors="errors.long_base_name"
                    input-class="w-full"
                />
                <input-row 
                    label="Short Base Name" 
                    v-model="group.expert_panel.short_base_name" 
                    @update:modelValue="emitUpdate"
                    placeholder="Short base name"
                    :errors="errors.short_base_name"
                    input-class="w-full"
                />
                <div v-if="hasAnyPermission(['groups-manage'])">
                    <input-row 
                        label="Affiliation ID" 
                        v-model="group.expert_panel.affiliation_id" 
                        :placeholder="affiliationIdPlaceholder"
                        :errors="errors.affiliation_id"  
                        input-class="w-full"
                        @update:modelValue="emitUpdate"
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
                    @update:modelValue="emitUpdate"
                />
            </div>
        </transition>
        <div v-if="hasPermission('groups-manage')">
            <input-row 
                v-model="group.group_status_id"
                type="select"
                :options="statusOptions"
                :errors="errors.group_status_id"
                @update:modelValue="emitUpdate"
            >
                <template v-slot:label>
                    Status:
                    <note>admin-only</note>
                </template>
            </input-row>

            <input-row
                v-model="group.parent_id"
                type="select"
                :options="parentOptions"
                :errors="errors.parent_id"
                @update:modelValue="emitUpdate"
            >
                <template v-slot:label>
                    Parent group:
                    <note>admin-only</note>
                </template>
            </input-row>
        </div>
    </div>
</template>
<script>
import {sortBy} from 'lodash'
import {isValidationError} from '@/http'
import {api} from '@/http'
import Group from '@/domain/group'
import configs from '@/configs'
import formFactory from '@/forms/form_factory'

export default {
    name: 'GroupForm',
    emits: [
        'canceled',
        'saved',
        'update'
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
            parents: []
        }
    },
    computed: {
        group: {
            get() {
                const group = this.$store.getters['groups/currentItem'];
                if (group) {
                    return group;
                }
                return this.newGroup;
            },
            set (value) {
                try {
                    this.$store.commit('groups/addItem', value);
                } catch (e) {
                    this.newGroup = value;
                }
            }
        },
        statusOptions () {
            return Object.values(this.groupStatuses).map(status => ({value: status.id, label: this.titleCase(status.name)}))
        },
        typeOptions () {
            return this.groupTypes.map(type => ({value: type.id, label: type.fullname}));
        },
        canSetType() {
            return this.hasPermission('groups-manage') && !this.group.id 
        },
        typeDisplayName () {
            if (!this.group.type) {
                return "🐇🥚";
            }
            if (this.group.type.name) {
                return (this.group.type.id < 3) 
                    ? this.group.type.name.toUpperCase() 
                    : this.group.expert_panel.type.name.toUpperCase();
            }
            return null;
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
            console.log({
                new: this.group.expert_panel.attributes.affiliation_id,
                original: this.group.expert_panel.original.affiliation_id
            });
            return this.group.expert_panel.isDirty('affiliation_id');
        },
        parentOptions () {
            const options = [{value: 0, label: 'None'}];
            this.parents
                .filter(group => group.type.can_be_parent)
                .forEach(parent => {
                    options.push({value: parent.id, label: parent.displayName})
                })

            return sortBy(options, 'label');
        }
    },
    methods: {
        async save() {
            this.resetErrors();
            try {
                if (this.group.id) {
                    await this.updateGroup();
                    this.$emit('saved');

                    // this.$store.dispatch('groups/find', this.group.uuid);
                    // this.$store.commit('pushSuccess', 'Group info updated.');
                    return;
                } 

                const newGroup = await this.createGroup()
                                    .then(response => response.data.data);
                this.$emit('saved');
                this.$store.commit('pushSuccess', 'Group created.');
                this.$router.push({name: 'AddMember', params: {uuid: newGroup.uuid}});
            } catch (error) {
                if (isValidationError(error)) {
                    this.errors = error.response.data.errors;
                }
                throw error;
            }
        },
        createGroup () {
            let {
                name, 
                parent_id, 
                group_type_id, 
                group_status_id
            } = this.group.attributes;

            const {short_base_name} = this.group.expert_panel;

            if (name === null && this.group.expert_panel) {
                name = this.group.expert_panel.long_base_name;
            }

            return this.$store.dispatch(
                'groups/create', 
                {
                    name,
                    parent_id,
                    group_type_id,
                    group_status_id,
                    short_base_name
                }
            );
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
            if (this.group.isDirty('parent_id')) {
                promises.push(this.saveParent());
            }

            if (this.group.isDirty('name')) {
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
                    data: { affiliation_id: this.group.expert_panel.affiliation_id }
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
            if (this.group.uuid) {
                this.resetData();
            }
            this.$emit('canceled');
        },
        async getParentOptions () {
            this.parents = await api.get(`/api/groups`)
                        .then(response => {
                            return response.data
                                .filter(group => group.id != this.group.id)
                                .map(g => new Group(g))
                        });
        },
        emitUpdate () {
            this.$emit('update');
        }
    },
    beforeMount() {
        this.getParentOptions();
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