<style scoped>
    .csv-item:after {
        content: ', ';
    }
    .csv-item:last-child:after {
        content: '';
    }
</style>
<template>
    <application-section title="Submitter Information">
        <dictionary-row label="Expert Panel">{{workingCopy.expert_panel.display_name}}</dictionary-row>
        <dictionary-row label="Affilation ID">{{workingCopy.expert_panel.affiliation_id}}</dictionary-row>

        <input-row
            label="Submitting member"
            v-model="workingCopy.submitter_id"
            type="select"
            :options="members.map(m => ({value: m.id, label:m.person.name}))"
            :disabled="isComplete"
        />

        <dictionary-row label="EP Coordinator(s)">
            <span class="csv-item"
                v-for="coordinator in coordinators" :key="coordinator.id"
            >{{coordinator.person.name}}</span>
            <span v-if="coordinators.length == 0" class="text-red-600">No coordinators on file for this expert panel.</span>
        </dictionary-row>

        <input-row
            label="Liaising ClinGen grant"
            v-model="workingCopy.data.grant"
            type="select"
            :options="grants.map(g => ({value: g}))"
            :errors="errors.grant"
            :disabled="isComplete"
        />

        <input-row v-if="this.workingCopy.is_gcep"
            label="What is the current activity status of the EP?"
            v-model="workingCopy.data.ep_activity"
            type="radio-group"
            :options="activityOptions"
            vertical
            :errors="errors.ep_activity"
            :disabled="isComplete"
        />

        <transition name="slide-fade-down">
            <input-row v-if="workingCopy.data.ep_activity == 'inactive'"
                label="Have you submitted an Inactive GCEP form to the CDWG Oversight Committee?"
                v-model="workingCopy.data.submitted_inactive_form"
                type="radio-group"
                :options="[{value: 'yes'}, {value: 'no'}]"
                :disabled="isComplete"
                vertical
            />
        </transition>

        <transition name="slide-fade-down">
            <p v-if="workingCopy.data.submitted_inactive_form == 'no'" class="ml-4 alert-warning p-2 rounded-lg">
                You must complete and submit the <a href="https://docs.google.com/document/d/13m4xeuh-GDHbYciQYHu1CiE_6HI-Xz_6_-yp8q2Ybp4/edit?usp=sharing" target="inacive-form">ClinGen Inactive GCEP Form</a>
            </p>
        </transition>

    </application-section>
</template>
<style lang="postcss" scoped>
    .input-row .label-container label {
        font-weight: bold;
    }
</style>
<script>
import mirror from '@/composables/setup_working_mirror'
import ApplicationSection from '@/components/expert_panels/ApplicationSection.vue'

export default {
    name: 'SubmitterInformation',
    components: {
        ApplicationSection
    },
    props: {
        ...mirror.props,
        errors: {
            type: Object,
            required: true
        },
    },
    emits: [ ...mirror.emits, ],
    data () {
        return {
            grants: ['Broad/Geisinger', 'Stanford/Baylor', 'UNC', 'Unsure'],
            activityOptions: [
                {value: 'active', label: 'Active - Group meets on a routine basis and/or is on hiatus but planning to reconvene to recurate.'},
                {value: 'inactive', label: 'Inactive - The group is no longer routinely meeting, and plans to, or has, transferred the responsibility of re-curating has been to a different GCEP.'}
            ]
        };
    },
    computed: {
        members () {
            return this.workingCopy.expert_panel.group.members.filter(m => m !== null);
        },
        coordinators () {
            return this.workingCopy.expert_panel.group.coordinators;
        },
        isComplete () {
            return Boolean(this.modelValue.completed_at);
        }
    },
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);

        return {
            workingCopy
        }
    }
}
</script>
