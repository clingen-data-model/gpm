<template>
    <base-step
        :step="3"
        approve-button-label="Approve Pilot and Specifications"
        title="Pilot ACMG Guideline Specificiations"
        @updated="$emit('updated')"
    >
        <template v-slot:sections>
            <div class="application-section screen-block">
                <h2>Pilot Specifications</h2>
                <specifications-section :doc-type-id="[3,4,7]" />
            </div>
        </template>
    </base-step>
</template>
<script>
import BaseStep from '@/components/applications/BaseStep.vue'
import {formatDate} from '@/date_utils'
import {mapGetters} from 'vuex'
import SpecificationsSection from '../expert_panels/SpecificationsSection.vue'

export default {
    name: 'StepThree',
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
        formatDate(dateString) {
            return formatDate(dateString)
        }
    }
}
</script>
