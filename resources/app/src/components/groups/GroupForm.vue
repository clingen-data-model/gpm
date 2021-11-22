<template>
    <div>
        <input-row label="Type" v-if="canSetType">
            <select v-model="workingCopy.group_type_id" class="w-full">
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
            <basic-info-form 
                :group="group" ref="infoForm"
                v-if="workingCopy.group_type_id > 2 && group.expert_panel"
            />
            <div v-else>
                <input-row v-model="workingCopy.name" placeholder="Name" label="Name" input-class="w-full"/>
                <input-row label="Status">
                    <select v-model="workingCopy.group_status_id" class="w-full">
                        <option :value="null"></option>
                        <option v-for="status in groupStatuses" :key="status.id" :value="status.id">
                            {{titleCase(status.name)}}
                        </option>
                    </select>
                </input-row>
            </div>
        </transition>
        <input-row label="Parent group" :errors="errors.parent_id">
            <select v-model="workingCopy.parent_id" class="w-full">
                <option :value="null">Select...</option>
                <option :value="0">None</option>
                <option v-for="group in groups" :key="group.id" :value="group.id">
                    {{group.displayName}}
                </option>
            </select>
        </input-row>
        <button-row submit-text="Save" @submitted="save" @canceled="cancel"></button-row>
    </div>
</template>
<script>
import is_validation_error from '@/http/is_validation_error'
import Group from '@/domain/group'
import BasicInfoForm from '@/components/expert_panels/BasicInfoForm'
import configs from '@/configs'
import formFactory from '@/forms/form_factory'

export default {
    name: 'GroupForm',
    components: {
        BasicInfoForm,
    },
    props: {
        group: {
            type: Object,
            required: false,
            default: () => ({})
        }
    },
    data() {
        return {
            workingCopy: {},
            groupTypes: [
                {id: 1, fullname: 'Working Group'},
                {id: 2, fullname: 'Clinical Domain Working Group'},
                {id: 3, fullname: 'GCEP'},
                {id: 4, fullname: 'VCEP'},
            ],
            groupStatuses: configs.groups.statuses
        }
    },
    computed: {
        groups () {
            return this.$store.getters['groups/all'];
        },
        canSetType() {
            return this.hasPermission('groups-manage') && !this.group.id 
        },
        typeDisplayName () {
            if (!this.workingCopy.type) {
                return 1;
            }
            return (this.workingCopy.type.id < 3) 
                ? this.workingCopy.type.name.toUpperCase() 
                : this.workingCopy.expert_panel.type.name.toUpperCase();
        }
    },
    watch: {
        group: function (to) {
            this.syncWorkingCopy(to);
        }
    },
    methods: {
        initWorkingCopy () {
            this.workingCopy = new Group();
        },
        syncWorkingCopy (group) {
            if (Object.prototype.hasOwnProperty.call(group, 'attributes')) {
                this.workingCopy = group.clone();
            } else {
               this.workingCopy = new Group(group)
            }
            this.workingCopy.group_type_id = group.expertPanel ? group.expertPanel.expert_panel_type_id+2 : group.group_type_id;
        },
        async save() {
            try {
                this.resetErrors();
                if (this.workingCopy.id) {
                    await this.updateGroup();
                    this.$emit('saved');

                    this.$store.dispatch('groups/find', this.workingCopy.uuid);
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
            return this.$store.dispatch('groups/create', this.workingCopy);
        },
        updateGroup () {
            const promises = [];
            promises.push(this.saveGroupData());
            if (this.workingCopy.expert_panel) {
                promises.push(this.saveEpData());
            }
            
            return Promise.all(promises);
        },
        saveGroupData () {
            const promises = [];
            if (this.isDirty('parent_id')) {
                promises.push(this.saveParent());
            }

            if (this.isDirty('name')) {
                promises.push(this.saveName())
            }

            if (this.isDirty('group_status_id')) {
                promises.push(this.saveStatus())
            }

            return Promise.all(promises);
        },
        async saveEpData() {
            await this.$refs.infoForm.save();
        },

        isDirty (attribute) {
            return this.group[attribute] != this.workingCopy[attribute]
        },
        
        saveParent () {
            return this.submitFormData({
                method: 'put',
                url: `/api/groups/${this.group.uuid}/parent`, 
                data: { parent_id: this.workingCopy.parent_id }
            })
        },
        saveName () {
            return this.submitFormData({
                method: 'put',
                url: `/api/groups/${this.group.uuid}/name`,
                data: {name: this.workingCopy.name}
            })
        },
        saveStatus () {
            return this.submitFormData({
                method: 'put',
                url: `/api/groups/${this.group.uuid}/status`,
                data: {status_id: this.workingCopy.group_status_id}
            })
        },
        cancel() {
            this.syncWorkingCopy(this.group);
            this.$refs.infoForm.syncData(this.group);
            this.$emit('canceled');
        }
    },
    beforeMount() {
        this.$store.dispatch('groups/getItems')
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