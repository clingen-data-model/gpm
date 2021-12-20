<template>
    <div>
        <p>Three examples of ClinGen-approved curation and review protocols are below (additional details may be requested from the CDWG Oversight Committee).  Check or describe the curation and review protocol that this Expert Panel will use.</p>
        <div class="mb-4">
            <input-row label="" :errors="errors.curation_review_protocol_id" vertical>
                <div>
                    <label class="mt-2">
                        <input type="radio" 
                            v-model="group.expert_panel.curation_review_protocol_id" 
                            value="1" 
                            :disabled="!canEdit"
                        >
                        <div>Single biocurator curation with comprehensive GCEP review (presentation of all data on calls with GCEP votes). Note: definitive genes may be expedited with brief summaries.</div>
                    </label>
                    <label class="mt-2 items-top">
                        <input type="radio" 
                            v-model="group.expert_panel.curation_review_protocol_id" 
                            value="2" 
                            :disabled="!canEdit"
                        >
                        <p>Paired review (biocurator &amp; domain expert) with expedited GCEP review. Expert works closely with a curator on the initial summation of the information for expedited GCEP review (brief summary on a call with GCEP voting and/or electronic voting by GCEP). Definitive genes can move directly from biocurator to expedited GCEP review.</p>
                    </label>
                    <label class="mt-2">
                        <input type="radio" 
                            v-model="group.expert_panel.curation_review_protocol_id" 
                            value="3" 
                            :disabled="!canEdit"
                        >
                        <p>Dual biocurator review with expedited GCEP review for concordant genes and full review for discordant genes.</p>
                    </label>
                    <div class="flex space-x-2 items-start mt-3">
                        <label>
                            <input type="radio" 
                                v-model="group.expert_panel.curation_review_protocol_id" 
                                value="100" 
                                    :disabled="!canEdit"
                            >
                            <p>Other</p>
                        </label>
                        <transition name="slide-fade-down">
                            <input-row class="flex-1 mt-0" v-if="group.expert_panel.curation_review_protocol_id == 100" label="Details" :label-width="0" v-model="group.expert_panel.curation_review_protocol_other" :errors="errors.curation_review_protocol_other">
                                <textarea rows="2" v-model="group.expert_panel.curation_review_protocol_other" class="w-full"></textarea>
                            </input-row>
                        </transition>
                    </div>
                </div>
            </input-row>
        </div>
    </div>
</template>
<script>
import Group from '@/domain/group'

const attributes = [
    'curation_review_protocol_id',
    'curation_review_protocol_other',
    'meeting_frequency',
    'expert_panel_type_id'
];

export default {
    name: 'GcepOngoingPlansForm',
    props: {
        errors: {
            type: Object,
            required: false,
            default: () => ({})
        },
        readonly: {
            type: Boolean,
            required: false,
            default: false
        }
    },
    computed: {
        group: {
            get () {
                return this.$store.getters['groups/currentItemOrNew'];
            },
            set (value) {
                this.$store.commit('groups/addItem', value);
            }
        },
        canEdit () {
            return this.hasAnyPermission([
                        'ep-applications-manage',
                        ['application-edit', this.group]
                    ]) 
                    && !this.readonly;
        }
    }
    
}
</script> 