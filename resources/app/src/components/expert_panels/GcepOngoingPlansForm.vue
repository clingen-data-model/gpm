<template>
    <div>
        <h3>Plans for Ongoing Gene Review and Reanalysis and Discrepancy Resolution</h3>
        <p>Three examples of ClinGen-approved curation and review protocols are below (additional details may be requested from the CDWG Oversight Committee).  Check or describe the curation and review protocol that this Expert Panel will use.</p>
        <div class="mb-4">
            <input-row label="" :errors="errors.curation_review_protocol_id" vertical>
                <div>
                    <label class="block mt-2 flex items-top space-x-2">
                        <input type="radio" v-model="protocolId" value="1" class="mt-1">
                        <div>Single biocurator curation with comprehensive GCEP review (presentation of all data on calls with GCEP votes). Note: definitive genes may be expedited with brief summaries.</div>
                    </label>
                    <label class="block mt-2 flex items-top space-x-2">
                        <input type="radio" v-model="protocolId" value="2" class="mt-1">
                        <p>Paired review (biocurator &amp; protocolOther expert) with expedited GCEP review. Expert works closely with a curator on the initial summation of the information for expedited GCEP review (brief summary on a call with GCEP voting and/or electronic voting by GCEP). Definitive genes can move directly from biocurator to expedited GCEP review.</p>
                    </label>
                    <label class="block mt-2 flex items-top space-x-2">
                        <input type="radio" v-model="protocolId" value="3" class="mt-1">
                        <p>Dual biocurator review with expedited protocolOther review for concordant genes and full review for discordant genes.</p>
                    </label>
                    <div class="flex space-x-2 items-start mt-3">
                        <label class="block flex items-top space-x-2">
                            <input type="radio" v-model="protocolId" value="100" class="mt-1">
                            <p>Other</p>
                        </label>
                        <transition name="slide-fade-down">
                            <input-row class="flex-1 mt-0" v-if="protocolId == 100" label="Details" :label-width="0" v-model="protocolOther" :errors="errors.curation_review_protocol_other">
                                <textarea rows="2" v-model="protocolOther" class="w-full"></textarea>
                            </input-row>
                        </transition>

                    </div>
                </div>
            </input-row>
        </div>
        <button-row @submit="save" @cancel="cancel" submit-text="Save" v-if="!hideSubmission"></button-row>
    </div>
</template>
<script>
import {watch} from 'vue'
import {useStore} from 'vuex'
import {protocolId, protocolOther, errors, syncData, saveProtocolData} from '@/composables/curation_protocol_updates'

export default {
    name: 'GcepOngoingPlansForm',
    props: {
        group: {
            type: Object,
            required:true
        },
        hideSubmission: {
            type: Boolean,
            required: false,
            default: false
        },
    },
    setup(props) {
        const store = useStore();

        const save = async () => {
            await saveProtocolData(props.group);
            await store.dispatch('groups/find', props.group.uuid);
            store.commit('pushSuccess', 'Curation/Review protocol saved.')
        }

        watch(() => props.group, 
            () => syncData(props.group), 
            { immediate: true }
        );

        const cancel = () => {
            syncData(props.group);
        }

        return {
            protocolId,
            protocolOther,
            errors,
            save,
            cancel
        }
    }
}
</script> 