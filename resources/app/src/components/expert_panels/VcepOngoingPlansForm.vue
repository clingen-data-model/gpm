<template>
    <div>
        <div class="mb-4">
            <input-row 
                v-model="group.expert_panel.meeting_frequency" 
                label="Meeting/call frequency" 
                :errors="errors.meeting_frequency"
                placeholder="Once per week"
                label-width-class="w-44"
                :disabled="!canEdit"
            >
            </input-row>
            <input-row label="VCEP Standardized Review Process" :errors="errors.curation_review_protocol_id" vertical>
                <div>
                    <label class="mt-2">
                        <input type="radio" 
                            v-model="group.expert_panel.curation_review_protocol_id" 
                            value="1" 
                            class="mt-1"
                            :disabled="!canEdit"
                        >
                        <div>Process #1: Biocurator review followed by VCEP discussion</div>
                    </label>
                    <label class="mt-2">
                        <input type="radio" 
                            v-model="group.expert_panel.curation_review_protocol_id" 
                            value="2" 
                            class="mt-1"
                            :disabled="!canEdit"
                        >
                       <div>Process #2: Paired biocurator/expert review followed by expedited VCEP approval</div>
                    </label>
                </div>
            </input-row>
        </div>
        <p class="text-sm mb-0">For all variants approved by either of the processes described above, a summary of approved variants should be sent to ensure that any members absent from a call have an opportunity to review each variant. The summary should be emailed to the full VCEP after the call and should summarize decisions that were made and invite feedback within a week.</p>

        <input-row label="Curation and Review Process Notes" :vertical="true" label-class="font-bold">
            <textarea 
                v-if="canEdit"
                class="w-full h-20" 
                v-model="group.expert_panel.curation_review_process_notes" 
            />
            <blockquote v-else>
                {{group.expert_panel.curation_review_process_notes}}
            </blockquote>
        </input-row>
    </div>
</template>
<script>

export default {
    name: 'VcepOngoingPlansForm',
    props: {
        errors: {
            type: Object,
            required: false,
            default: () => ({})
        },
        editing: {
            type: Boolean,
            required: false,
            default: true
        },
        readonly: {
            type: Boolean,
            required: false,
            default: false
        },
    },
    computed: {
        group: {
            get () {
                return this.$store.getters['groups/currentItemOrNew'];
            }, 
            set (value) {
                this.$store.commit('groups/addItem', value)
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