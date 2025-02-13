<template>
    <base-step
        :step="2"
        :document-type="2"
        document-name="Draft Specifications"
        :document-gets-reviewed="false"
        approve-button-label="Approve Draft Specifications"
        title="Draft ACMG Guideline Specifications"
        @updated="$emit('updated')"
    >
        <template v-slot:sections>
            <div class="application-section screen-block">
                <h2>Draft Specifications</h2>
                <specifications-section :doc-type-id="2" />
            </div>
        </template>
    </base-step>
</template>
<script>
import {mapGetters} from 'vuex'
import BaseStep from '@/components/applications/BaseStep.vue'
import SpecificationsSection from '@/components/expert_panels/SpecificationsSection.vue'

export default {
    name: 'StepTwo',
    components: {
        BaseStep,
        SpecificationsSection
    },
    props: {

    },
    emits: ['approved', 'updated'],
    data() {
        return {

        }
    },
    computed: {
        ...mapGetters({
            group: 'groups/currentItemOrNew'
        }),
        application () {
            return this.group.expert_panel;
        },
    },
    methods: {

    }
}
</script>
