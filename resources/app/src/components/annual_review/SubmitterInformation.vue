<style lang="postcss" scoped>
    .csv-item:after {
        content: ', ';
    }
    .csv-item:last-child:after {
        content: '';
    }
</style>
<template>
    <div>
        <dictionary-row label="Expert Panel">{{group.displayName}}</dictionary-row>
        <dictionary-row label="Affilation ID">{{group.expert_panel.affiliation_id}}</dictionary-row>

        <input-row label="Submitting member">
            <select v-model="workingCopy.submitter_id">
                <option :value="null">Select...</option>
                <option v-for="member in members" :key="member.id" :value="member.id">{{member.person.name}}</option>
            </select>
        </input-row>
        <dictionary-row label="EP Coordinator(s)">
            <span class="csv-item" 
                v-for="coordinator in group.coordinators" :key="coordinator.id"
            >{{coordinator.person.name}}</span>
            <span v-if="group.coordinators.length == 0" class="text-red-600">No coordinators on file for this expert panel.</span>
        </dictionary-row>

        <input-row label="Liaising ClinGen grant?">
            <select v-model="workingCopy.grant">
                <option :value="null">Select...</option>
                <option v-for="grant in grants" :key="grant" :value="grant">{{grant}}</option>
            </select>
        </input-row>
        
        <input-row v-if="this.group.isGcep()"
            label="What is the current activity status of the EP?"
            vertical
        >
            <div class="ml-4">
                <radio-button v-model="workingCopy.ep_activity" value="active">
                    Active - Group meets on a routine basis and/or is on hiatus but planning to reconvene to recurate.
                </radio-button>
                <radio-button v-model="workingCopy.ep_activity" value="inactive">
                    Inactive - The group is no longer routinely meeting, and plans to, or has, transferred the responsibility of re-curating has been to a different GCEP.
                </radio-button>
            </div>
        </input-row>

        <transition name="slide-fade-down">
            <input-row v-if="workingCopy.ep_activity == 'inactive'"
                label="Have you submitted an Inactive GCEP form to the CDWG Oversight Committee?"
                vertical
            >
                <div class="ml-4">
                    <radio-button v-model="workingCopy.submitted_inactive_form" :value="1">Yes</radio-button>
                    <radio-button v-model="workingCopy.submitted_inactive_form" :value="0">No</radio-button>
                </div>
            </input-row>
        </transition>
        <transition name="slide-fade-down">
            <p v-if="workingCopy.submitted_inactive_form == 0">
                You must complete and submit the <a href="https://docs.google.com/document/d/13m4xeuh-GDHbYciQYHu1CiE_6HI-Xz_6_-yp8q2Ybp4/edit?usp=sharing">ClinGene Inactive GCEP Form</a>
            </p>        
        </transition>
    </div>
</template>
<style lang="postcss" scoped>
    .input-row .label-container label {
        font-weight: bold;
    }
</style>
<script>
import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'SubmitterInformation',
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
        };
    },
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew']
        },
        members () {
            return this.group.members.filter(m => m !== null);
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