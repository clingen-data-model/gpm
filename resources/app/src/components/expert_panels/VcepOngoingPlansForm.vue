<template>
    <div>
        <h3>Plans for Ongoing Variant Review and Reanalysis and Discrepancy Resolution</h3>
        <div class="mb-4">
            <input-row 
                v-model="meetingFrequency" 
                label="Meeting/call frequency" 
                :errors="errors.meeting_frequency"
                placeholder="Once per week"
                :label-width="44"
            >
            </input-row>
            <input-row label="VCEP Standardized Review Process" :errors="errors.curation_review_protocol_id" vertical>
                <div>
                    <label class="block mt-2 flex items-top space-x-2">
                        <input type="radio" v-model="protocolId" value="1" class="mt-1">
                        <p>Process #1: Biocurator review followed by VCEP discussion</p>
                    </label>
                    <label class="block mt-2 flex items-top space-x-2">
                        <input type="radio" v-model="protocolId" value="2" class="mt-1">
                       <p>Process #2: Paired biocurator/expert review followed by expedited VCEP approval</p>
                    </label>
                </div>
            </input-row>
        </div>
        <p class="text-sm mb-0">For all variants approved by either of the processes described above, a summary of approved variants should be sent to ensure that any members absent from a call have an opportunity to review each variant. The summary should be emailed to the full VCEP after the call and should summarize decisions that were made and invite feedback within a week.</p>
    </div>
</template>
<script>
import {computed} from 'vue';

export default {
    name: 'VcepOngoingPlansForm',
    props: {
        group: {
            type: Object,
            required:true
        },
        errors: {
            type: Object,
            required: false,
            default: () => ({})
        },
        editing: {
            type: Boolean,
            required: false,
            default: true
        }
    },
     setup(props, context) {
        const protocolId = computed({
            get: function () {
                return props.group.expert_panel.curation_review_protocol_id;
            },
            set: function (value) {
                const groupClone = props.group.clone();
                groupClone.expert_panel.curation_review_protocol_id = value;

                context.emit('update:group', groupClone);
            }
        });
        const meetingFrequency = computed({
            get: function () {
                return props.group.expert_panel.meeting_frequency;
            },
            set: function (value) {
                const groupClone = props.group.clone();
                groupClone.expert_panel.meeting_frequency = value;
                
                context.emit('update:group', groupClone);
            }
        });

        return {
            protocolId,
            meetingFrequency,
        }
    }
}

</script> 