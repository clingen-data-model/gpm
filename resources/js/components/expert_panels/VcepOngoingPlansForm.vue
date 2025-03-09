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
    emits: [
        'update'
    ],
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
                @update:modelValue="$emit('update')"
            >
            </input-row>
            <input-row
                v-model="group.expert_panel.curation_review_protocol_id"
                :options="[
                    {value: 1, label: 'Process #1: Biocurator review followed by VCEP discussion'},
                    {value: 2, label: 'Process #2: Paired biocurator/expert review followed by expedited VCEP approval'}
                ]"
                type="radio-group"
                :errors="errors.curation_review_protocol_id"
                :disabled="!canEdit"
                label="VCEP Standardized Review Process"
                vertical
                @update:modelValue="$emit('update')"
            />
        </div>
        <p class="text-sm mb-0">Note: For all variants approved by either of the processes described above, a summary of approved variants should be sent to ensure that any members absent from a call have an opportunity to review each variant. The summary should be emailed to the full VCEP after the call and should summarize decisions that were made and invite feedback within a week.</p>

        <input-row v-if="canEdit"
            v-model="group.expert_panel.curation_review_process_notes"
            type="large-text"
            label="Curation and Review Process Notes"
            :vertical="true"
            label-class="font-bold"
            @update:modelValue="$emit('update')"
        />
        <blockquote v-else>
            {{group.expert_panel.curation_review_process_notes}}
        </blockquote>
    </div>
</template>
