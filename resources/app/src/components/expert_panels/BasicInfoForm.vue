<template>
    <div>
        <input-row 
            label="Long Base Name" 
            v-model="longBaseName" 
            placeholder="Long base name"
            :errors="errors.long_base_name"
        ></input-row>
        <input-row 
            label="Short Base Name" 
            v-model="shortBaseName" 
            placeholder="Short base name"
            :errors="errors.short_base_name"
        ></input-row>
        <input-row 
            label="CDWG" :errors="errors.cdwg_id"
            v-if="hasAnyPermission(['groups-manage'])"
        >
            <select name="" id="" v-model="cdwgId">
                <option :value="cdwg.id" v-for="cdwg in cdwgs" :key="cdwg.id">{{cdwg.name}}</option>
            </select>
        </input-row>
        <input-row 
            label="Affiliation ID" 
            v-model="affiliationId" 
            :placeholder="affiliationIdPlaceholder"
            :errors="errors.affiliation_id"
            v-if="hasAnyPermission(['groups-manage'])"
        />

    </div>
</template>
<script>
import api from '@/http/api'
import is_validation_error from '@/http/is_validation_error'

export default {
    name: 'BasicInfoForm',
    props: {
        group: {
            type: Object,
            required: true
        },
    },
    data() {
        return {
            errors: {},
            longBaseName: null,
            shortBaseName: null,
            cdwgId: null,
            affiliationId: null,
        }
    },
    computed: {
        affiliationIdPlaceholder () {
            return 50000
        },
        cdwgs () {
            return this.$store.getters['cdwgs/all']
        },
        namesDirty () {
            return this.longBaseName != this.group.expert_panel.long_base_name
                || this.shortBaseName != this.group.expert_panel.short_base_name;
        },
        cdwgDirty () {
            return this.cdwgId != this.group.parent_id;
        },
        affiliationIdDirty () {
            return this.affiliationId != this.group.expert_panel.affiliation_id;
        }
    },
    watch: {
        group: {
            immediate: true,
            handler (to) {
                console.log({group: to});
                if (to.id) {
                    this.syncData(to);
                    return
                }

                this.initData()
            }
        }
    },
    methods: {
        async save(){
            const promises = []
            if (this.namesDirty) {
                promises.push(this.saveAttributes(
                    `/api/groups/${this.group.uuid}/expert-panel/name`, 
                    { long_base_name: this.longBaseName, short_base_name: this.shortBaseName}
                ));
            }

            if (this.cdwgDirty) {
                promises.push(this.saveAttributes(
                    `/api/groups/${this.group.uuid}/parent`, 
                    { parent_id: this.cdwgId }
                ));
            }

            if (this.affiliationIdDirty) {
                promises.push(this.saveAttributes(
                    `/api/groups/${this.group.uuid}/expert-panel/affiliation-id`, 
                    { affiliation_id: this.affiliationId }
                ));
            }

            await Promise.all(promises);
            this.$store.dispatch('groups/find', this.group.uuid);
        },

        async saveAttributes(url, data) {
            try {
                return await api.put(
                    url, 
                    data
                ).then(response => response.data.data)
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = {...this.errors, ...error.response.data.errors}
                }
            }
        },

        initData(){
            this.longBaseName = null;
            this.shortBaseName = null;
            this.cdwgId = null;
            this.affiliationId = null;
        },
        syncData(group) {
            this.longBaseName = group.expert_panel.long_base_name;
            this.shortBaseName = group.expert_panel.short_base_name;
            this.cdwgId = group.expert_panel.cdwg_id;
            this.affiliationId = group.expert_panel.affiliation_id;
        }
    },
    beforeMount () {
        this.$store.dispatch('cdwgs/getAll');
    }
}
</script>