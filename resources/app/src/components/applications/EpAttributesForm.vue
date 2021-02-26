<template>
    <div>
        <input-row 
            label="Long Base Name" 
            v-model="appClone.long_base_name" 
            placeholder="Long base name"
            :errors="errors.long_base_name"
        ></input-row>
        <input-row 
            label="Short Base Name" 
            v-model="appClone.short_base_name" 
            placeholder="Short base name"
            :errors="errors.short_base_name"
        ></input-row>
        <input-row 
            label="Affiliation ID" 
            v-model="appClone.affiliation_id" 
            :placeholder="affiliationIdPlaceholder"
            :errors="errors.affiliation_id"
        ></input-row>
        <input-row label="CDWG" :errors="errors.cdwg_id">
            <select name="" id="" v-model="appClone.cdwg_id">
                <option :value="cdwg.id" v-for="cdwg in cdwgs" :key="cdwg.id">{{cdwg.name}}</option>
            </select>
        </input-row>
        <input-row label="Contacts">
            <application-contacts></application-contacts>
        </input-row>
        <input-row label="Expert Panel URL">
            <div :class="{'text-gray-400': !hasAffiliationId}">
                {{affiliationUrl}}
            </div>
        </input-row>
        <div class="py-1 flex space-x-2">
            <button class="btn white btn-xs" @click="resetClone" :disabled="isClean">Reset</button>
            <button class="btn blue btn-xs" @click="saveChanges" :disabled="isClean">Save Changes</button>
        </div>
    </div>
</template>
<script>
import ApplicationContacts from '../contacts/ApplicationContacts'

export default {
    components: {
        ApplicationContacts
    },
    props: {
        application: {
            required: true,
            type: Object
        }
    },
    data() {
        return {
            appClone: this.application.clone(),
            errors: {}
        }
    },
    watch: {
        application: {
            immediate: true,
            handler() {
                this.appClone = this.application.clone()
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
        },
        hasChanges() {
            const epAttrs = ['long_base_name', 'short_base_name', 'cdwg_id', 'affiliation_id'];
            for (let idx in epAttrs) {
                const attr = epAttrs[idx]
                if (this.application[attr] == null && this.appClone[attr] == '') {
                    continue;
                }
                if (this.application[attr] != this.appClone[attr]) {
                    return true;
                }
            }
            return false;
        },
        isClean() {
            return !this.hasChanges;
        }
    },
    methods: {
        async saveChanges() {
            try {
                this.clearErrors();
                await this.$store.dispatch('applications/updateEpAttributes', this.appClone);
                this.resetClone();
            } catch (error) {
                if (error.response && error.response.status == 422 && error.response.data.errors) {
                    this.errors = error.response.data.errors
                    return;
                }
                console.error(error)
            }
            
        },
        resetClone() {
            this.appClone = this.application.clone()
        },
        clearErrors() {
            this.errors = {};
        }
    },
    mounted () {
        this.$store.dispatch('cdwgs/getCdwgs');
    }
}
</script>