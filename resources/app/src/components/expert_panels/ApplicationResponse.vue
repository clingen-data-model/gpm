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
            <h1>{{group.displayName}} - Group Definition</h1>
            <definition-review></definition-review>
        </div>
        <div class="step-break">
            End of Group Definition Application.
        </div>

        <div class="application-step print:hidden">
            <h1 v-if="expertPanel.definition_is_approved" class="print:hidden" >
                {{group.displayName}} - Draft and Pilot Specifications
            </h1>
            <section v-if="expertPanel.definition_is_approved" class="print:hidden" >
                <cspec-summary></cspec-summary>
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
import DefinitionReview from '@/components/expert_panels/DefinitionReview'
import SustainedCurationReview from '@/components/expert_panels/SustainedCurationReview'
import CspecSummary from '@/components/expert_panels/CspecSummary'
import ApplicationStepReview from '@/components/expert_panels/ApplicationStepReview'

export default {
    name: 'ApplicationResponse',
    extends: ApplicationStepReview,
    components: {
        DefinitionReview,
        CspecSummary,
        SustainedCurationReview
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
    }
}
</script>
<style lang="postcss">
    .application-review section {
        @apply mt-8 bg-white p-4 border-b border-gray-200 bg-white;
        @apply print:mt-0 print:px-0 print:border-b-0;
        max-width: 800px;
    }

    .application-review .step-break {
        @apply border-t border-b font-bold text-center py-4 my-4 bg-gray-100 print:hidden;
        max-width: 800px;
    }
    .application-review section:first-child {
        @apply mt-0
    }
    .application-review h2 {
        @apply mb-2 border-b pb-1;
        @apply print:border-b-0;
    }
    .application-review h3 {
        @apply mb-1;
    }
    @media print {
        .page-break {
            page-break-before: always;
        }
    }
</style>