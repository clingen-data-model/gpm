<template>
    <div class="application-review">
        <div class="print:hidden">
            <router-link :to="{name: 'GroupList'}" class="note">Groups</router-link>
            <span class="note"> &gt; </span>
            <router-link v-if="group.uuid" :to="{name: 'GroupDetail', params: {uuid: group.uuid}}" class="note">
                {{group.displayName}}
            </router-link>
        </div>
        <div class="application-step">
            <h1 class="flex items-center justify-between">
                {{group.displayName}} - Group Definition
                <button class="btn btn-sm print:hidden" @click="print">
                    <strong>Print</strong>
                    &nbsp;
                    <icon-printer class="inline-block"></icon-printer>
                </button>
            </h1>
            <definition-review></definition-review>
        </div>
        <div class="step-break">
            End of Group Definition Application.
        </div>

        <div class="application-step print:hidden">
            <h1 v-if="expertPanel.definition_is_approved" class="print:hidden" >
                {{group.displayName}} - Specifications and Pilot
            </h1>
            <section v-if="expertPanel.definition_is_approved" class="print:hidden" >
                <specifications-section :doc-type-id="[3,4,7]" :readonly="true" />
            </section>
        </div>

        <div class="step-break">
            End of Specification Draft and Pilot.
        </div>

        <div class="application-step  page-break">
            <h1 v-if="expertPanel.has_approved_pilot">
                {{group.displayName}} - Sustained Curation
            </h1>

            <sustained-curation-review />
        </div>
    </div>
</template>
<script>
import DefinitionReview from '@/components/expert_panels/DefinitionReview.vue'
import SustainedCurationReview from '@/components/expert_panels/SustainedCurationReview.vue'
import ApplicationStepReview from '@/components/expert_panels/ApplicationStepReview.vue'
import SpecificationsSection from '@/components/expert_panels/SpecificationsSection'

export default {
    name: 'ApplicationResponse',
    extends: ApplicationStepReview,
    components: {
        DefinitionReview,
        SustainedCurationReview,
        SpecificationsSection
    },
    props: {
        uuid: {
            type: String,
            required: true
        }
    },
    watch: {
        uuid: {
            immediate: true,
            handler: async function (to) {
                await this.$store.dispatch('groups/findAndSetCurrent', to);
                this.$store.dispatch('groups/getMembers', this.group);
            }
        }
    },
    methods: {
        print () {
            window.print();
        }
    }
}
</script>
<style lang="postcss">
    .application-review {
        max-width: 800px;
    }

</style>