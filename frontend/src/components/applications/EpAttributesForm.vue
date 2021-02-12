<template>
    <div>
        <input-row label="Long Base Name" v-model="appClone.long_base_name" placeholder="Long base name"></input-row>
        <input-row label="Short Base Name" v-model="appClone.short_base_name" placeholder="Short base name"></input-row>
        <input-row label="Affiliation ID" v-model="appClone.affiliation_id" :placeholder="affiliationIdPlaceholder"></input-row>
        <input-row label="CDWG">
            <select name="" id="" v-model="appClone.cdwg_id">
                <option :value="cdwg.id" v-for="cdwg in cdwgs" :key="cdwg.id">{{cdwg.name}}</option>
            </select>
        </input-row>
        <input-row label="Contacts">
            <application-contact-form></application-contact-form>
        </input-row>
        <input-row label="Expert Panel URL">
            <div :class="{'text-gray-400': !hasAffiliationId}">
                {{affiliationUrl}}
            </div>
        </input-row>
        <div class="py-1 flex space-x-2">
            <button class="btn white" @click="resetClone">Reset</button>
            <button class="btn blue" @click="saveChanges">Save Changes</button>
        </div>
    </div>
</template>
<script>
import ApplicationContactForm from '../contacts/ApplicationContactForm'

export default {
    components: {
        ApplicationContactForm
    },
    props: {
        application: {
            required: true,
            type: Object
        }
    },
    data() {
        return {
            appClone: {...this.application}
        }
    },
    watch: {
        application: {
            immediate: true,
            handler() {
                this.appClone = {...this.application}
            }
        }
    },
    computed: {
        affiliationUrl() {
            return `https://clinicalgenome.org/affiliation/${this.appClone.affiliation_id || this.affiliationIdPlaceholder}`
        },
        affiliationIdPlaceholder() {
            if (this.application && this.application.ep_type_id == 1) {
                return '4000XX'
            }
            return '5000XX'
        },
        cdwgs() {
            return this.$store.state.cdwgs.items
        },
        hasAffiliationId() {
            return Boolean(this.appClone.affiliation_id);
        }
    },
    methods: {
        
    },
    mounted () {
        this.$store.dispatch('getCdwgs');
    }
}
</script>