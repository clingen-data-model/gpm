<template>
    <div>
        <input-row 
            label="Long Base Name" 
            v-model="longBaseName" 
            placeholder="Long base name"
            :errors="errors.long_base_name"
             input-class="w-full"
        ></input-row>
        <input-row 
            label="Short Base Name" 
            v-model="shortBaseName" 
            placeholder="Short base name"
            :errors="errors.short_base_name"
             input-class="w-full"
        ></input-row>
        <input-row 
            label="Affiliation ID" 
            v-model="affiliationId" 
            :placeholder="affiliationIdPlaceholder"
            :errors="errors.affiliation_id"
            v-if="hasAnyPermission(['groups-manage'])"
             input-class="w-full"
        />

    </div>
</template>
<script>
import formFactory from '@/forms/form_factory'

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
            longBaseName: null,
            shortBaseName: null,
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
        affiliationIdDirty () {
            return this.affiliationId != this.group.expert_panel.affiliation_id;
        }
    },
    watch: {
        group: {
            immediate: true,
            handler (to) {
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
                promises.push(this.submitFormData(
                    `/api/groups/${this.group.uuid}/expert-panel/name`, 
                    { long_base_name: this.longBaseName, short_base_name: this.shortBaseName}
                ));
            }

            if (this.affiliationIdDirty) {
                promises.push(this.submitFormData(
                    `/api/groups/${this.group.uuid}/expert-panel/affiliation-id`, 
                    { affiliation_id: this.affiliationId }
                ));
            }

            await Promise.all(promises);
            this.$store.dispatch('groups/find', this.group.uuid);
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
    },
    setup (props, context) {
        const {errors, submitFormData} = formFactory(props, context)

        return {
            errors,
            submitFormData
        }
    }
}
</script>