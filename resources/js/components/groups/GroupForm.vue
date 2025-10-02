<script>
import configs from '@/configs'
import Group from '@/domain/group'
import formFactory from '@/forms/form_factory'
import { api, isValidationError } from '@/http'
import {sortBy} from 'lodash-es'

export default {
    name: 'GroupForm',
    emits: [
        'canceled',
        'saved',
        'update'
    ],
    setup (props, context) {
        const {errors, submitFormData, resetErrors} = formFactory(props, context)

        return {
            errors,
            submitFormData,
            resetErrors
        }
    },
    data() {
        return {
            groupTypes: configs.groups.types,
            groupStatuses: configs.groups.statuses,
            newGroup: new Group(),
            parents: [],
            creatingAffil: false,
            affilError: null,
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
                    this.$store.commit("groups/addItem", value);
                } catch (e) {
                    // eslint-disable-next-line no-console
                    console.log(`Error setting group: ${e}`);
                    this.newGroup = value;
                }
            }
        },
        statusOptions () {
            return Object.values(this.groupStatuses).map(status => ({value: status.id, label: this.titleCase(status.name)}))
        },
        typeOptions () {
            return Object.values(this.groupTypes).map(type => ({value: type.id, label: type.display_name}));
        },
        canSetType() {
            return this.hasPermission('groups-manage') && !this.group.id
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
        },
        parentOptions () {
            const options = [{value: 0, label: 'None'}];
            const parentFilter = this.group.isEp() ? g => g.isCdwg() : g => g.type.can_be_parent;
            this.parents
                .filter(parentFilter)
                .sort((a, b) => a.displayName.localeCompare(b.displayName))
                .forEach(parent => {
                    options.push({value: parent.id, label: parent.displayName})
                })
            return options;
        },
        canRequestAffil () {
            return this.hasAnyPermission(['groups-manage']) && this.group?.isEp?.() && this.group?.expert_panel?.uuid && !this.group?.expert_panel?.affiliation_id
        },
    },
    beforeMount() {
        this.getParentOptions();
        this.$store.dispatch('cdwgs/getAll');
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
            // eslint-disable-next-line no-console
            console.log('Not sure isDirty is supposed to be called here...')
            return this.group.isDirty(attribute)
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
            const params = {
                'where[group_type_id]': [1,2],
                without: ['coordinators', 'expertPanel']
            };

            this.parents = await api.get(`/api/groups`, {params})
                        .then(response => {
                            return response.data
                                .filter(group => group.id !== this.group.id)
                                .map(g => new Group(g))
                        });
        },
        emitUpdate () {
            this.$emit('update');
        },

        async createAffiliationId () {
            if (this.creatingAffil) return
                this.creatingAffil = true
                this.affilError = null
            try {
                const epUuid = this.group?.expert_panel?.uuid
                const id = await this.$store.dispatch('groups/createAffiliationId', { epUuid })
                this.$store.commit('pushSuccess', `Affiliation ID set to ${id}.`)
            } catch (e) {
                const msg = e?.message || 'Request failed'
                this.affilError = msg
                this.$store.commit('pushError', msg)
            } finally {
                this.creatingAffil = false
            }
        },

    }
}
</script>
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

    <dictionary-row v-else label="Type">
      {{ group?.type?.display_name }}
    </dictionary-row>

    <transition name="slide-fade-down" mode="out-in">
      <div v-if="group.group_type_id > 2 && group.expert_panel">
        <input-row
          v-model="group.expert_panel.long_base_name"
          label="Long Base Name"
          placeholder="Long base name"
          :errors="errors.long_base_name"
          input-class="w-full"
          @update:model-value="emitUpdate"
        />
        <input-row
          v-model="group.expert_panel.short_base_name"
          label="Short Base Name"
          placeholder="Short base name"
          :errors="errors.short_base_name"
          input-class="w-full"
          @update:model-value="emitUpdate"
        />
        

            <div class="flex items-end gap-2" v-if="hasAnyPermission(['groups-manage'])">
                <div class="flex-1">
                    <input-row
                        :errors="errors.affiliation_id"
                        label="Affiliation ID"
                        >
                        <template #label>
                            Affiliation ID
                            <note>admin-only</note>
                        </template>

                        <!-- Custom field content (inline) -->
                        <div class="flex items-center gap-2">
                            <input
                                type="text"
                                class="w-full"
                                :placeholder="affiliationIdPlaceholder"
                                :value="group.expert_panel.affiliation_id"
                                disabled="true"
                                @input="group.expert_panel.affiliation_id = $event.target.value; emitUpdate()"
                                @change="emitUpdate"
                            />

                            <button
                                v-if="canRequestAffil"
                                class="btn"
                                :disabled="creatingAffil"
                                @click="createAffiliationId"
                            >
                            <span v-if="creatingAffil">Creating…</span>
                            <span v-else>Create</span>
                            </button>
                        </div>
                    </input-row>
                </div>
            </div>
          
            
        <dictionary-row v-else label="Affiliation ID">
          <span v-if="group.expert_panel.affiliation_id">{{ group.expert_panel.affiliation_id }}</span>
          <span v-else class="text-gray-400">{{ 'Not yet assigend' }}</span>
        </dictionary-row>
      </div>
      <div v-else>
        <input-row
          v-model="group.name"
          placeholder="Name"
          label="Name"
          input-class="w-full"
          :errors="errors.name"
          @update:model-value="emitUpdate"
        />
      </div>
    </transition>
    <div v-if="hasPermission('groups-manage')">
      <input-row
        v-model="group.group_status_id"
        type="select"
        :options="statusOptions"
        :errors="errors.group_status_id"
        @update:model-value="emitUpdate"
      >
        <template #label>
          Status:
          <note>admin-only</note>
        </template>
      </input-row>

      <input-row
        v-model="group.parent_id"
        type="select"
        :options="parentOptions"
        :errors="errors.parent_id"
        @update:model-value="emitUpdate"
      >
        <template #label>
          Parent group:
          <note>admin-only</note>
        </template>
      </input-row>
    </div>
  </div>
</template>
