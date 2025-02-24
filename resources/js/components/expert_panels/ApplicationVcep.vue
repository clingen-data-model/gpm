<script>
import ApplicationSection from '@/components/expert_panels/ApplicationSection.vue'

import ApplicationStep from '@/components/expert_panels/ApplicationStep.vue'
import AttestationNhgri from '@/components/expert_panels/AttestationNhgri.vue'
import AttestationReanalysis from '@/components/expert_panels/AttestationReanalysis.vue'
import EvidenceSummaryList from '@/components/expert_panels/EvidenceSummaryList.vue'
import MemberDesignationForm from '@/components/expert_panels/MemberDesignationForm.vue';
import MembershipDescriptionForm from '@/components/expert_panels/MembershipDescriptionForm.vue';
import ScopeDescriptionForm from '@/components/expert_panels/ScopeDescriptionForm.vue';
import SpecificationsSection from '@/components/expert_panels/SpecificationsSection.vue'
import VcepGeneList from '@/components/expert_panels/VcepGeneList.vue';
import VcepOngoingPlansForm from '@/components/expert_panels/VcepOngoingPlansForm.vue';
import GroupForm from '@/components/groups/GroupForm.vue'
import MemberList from '@/components/groups/MemberList.vue';
import {errors} from '@/forms/form_factory'
import {isValidationError} from '@/http'
import {debounce} from 'lodash-es'

export default {
    name: 'ApplicationVcep',
    components: {
        AppSection: ApplicationSection,
        ApplicationStep,
        AttestationNhgri,
        AttestationReanalysis,
        SpecificationsSection,
        EvidenceSummaryList,
        GroupForm,
        MemberDesignationForm,
        MemberList,
        MembershipDescriptionForm,
        ScopeDescriptionForm,
        VcepGeneList,
        VcepOngoingPlansForm,
    },
    emits: [
        'autosaved',
        'saved',
        'saving',
    ],
    setup () {
        return {
            errors
        }
    },
    data () {
        return {
            genesChanged: false,
            saving: false,
        }
    },
    computed: {
        group: {
            get () {
                return this.$store.getters['groups/currentItemOrNew'];
            },
            set (value) {
                this.$store.commit('groups/addItem', value);
            }
        },
    },
    created() {
        this.debounceAutoSave = debounce(this.autosave, 2000)
    },
    methods: {
        async save() {
            this.$emit('saving');

            const promises = Object.keys(this.$refs)
                                .map(key => {
                                    if (this.$refs[key] && this.$refs[key].save) {
                                        return this.$refs[key].save();
                                    }
                                    return null;
                                });
            promises.push(this.saveUpdates());

            try {
                await Promise.all(promises);
                this.$emit('saved');
                this.genesChanged = false;
            } catch (error) {
                if (isValidationError(error)) {
                    this.errors = error.response.data.errors;
                    return;
                }
                throw error;
            }
        },
        saveUpdates () {
            if (this.applicationIsDirty()) {
                return this.$store.dispatch('groups/saveApplicationData', this.group)
                        .then(() => {
                            this.$emit('saved');
                        });
            }
        },
        async autosave () {
            if (this.applicationIsDirty()) {
                await this.save();
                this.$emit('autosaved');
            }
        },
        applicationIsDirty () {
            return  this.group.expert_panel.isDirty()
                || this.group.isDirty()
                || this.genesChanged
        },
        handleUpdate () {
            this.debounceAutoSave();
        }

    },
}
</script>
<template>
  <div>
    <ApplicationStep
      id="definition"
      title="Group Definition"
      :disabled="group.expert_panel.hasPendingSubmission"
    >
      <AppSection id="basicInfo" title="Basic Information">
        <GroupForm
          ref="groupForm" :group="group"
          @update="handleUpdate"
        />
      </AppSection>
      <AppSection v-if="group" id="membership" title="Membership">
        <p>
          Expert Panels are expected to broad representation of expertise and backgrounds in the field.
        </p>
        <MemberList :group="group" />
        <hr>
        <MembershipDescriptionForm :editing="true" @update="handleUpdate" />
      </AppSection>
      <AppSection id="scope" title="Scope of Work">
        <VcepGeneList ref="geneList" :group="group" @update="handleUpdate" />
        <hr>
        <ScopeDescriptionForm @update="handleUpdate" />
      </AppSection>
      <AppSection id="reanalysis" title="Reanalysis & Discrepancy Resolution">
        <AttestationReanalysis @update="handleUpdate" />
      </AppSection>
      <AppSection id="nhgri" title="NHGRI Data Availability">
        <AttestationNhgri @update="handleUpdate" />
      </AppSection>
    </ApplicationStep>

    <!-- <application-step
            id="specifications-development"
            title="Specifications Development"
            :disabled="group.expert_panel.current_step < 2 || group.expert_panel.hasPendingSubmission"
            :no-submit="true"
        >
            <app-section>
                <cspec-summary></cspec-summary>
            </app-section>
        </application-step>
        -->

    <ApplicationStep
      id="draft-specifications"
      title="Draft Specifications"
      :disabled="group.expert_panel.current_step < 2 || group.expert_panel.hasPendingSubmission"
      :no-submit="true"
    >
      <AppSection>
        <SpecificationsSection :doc-type-id="2" :step="2" />
      </AppSection>
    </ApplicationStep>

    <ApplicationStep
      id="pilot-specifications"
      title="Pilot Specifications"
      :disabled="group.expert_panel.current_step < 3 || group.expert_panel.hasPendingSubmission"
      :no-submit="true"
    >
      <AppSection>
        <SpecificationsSection :doc-type-id="[3,4,7]" :step="3" />
      </AppSection>
    </ApplicationStep>

    <ApplicationStep
      id="sustained-curation"
      title="Sustained Curation"
      :disabled="group.expert_panel.current_step < 4 || group.expert_panel.hasPendingSubmission"
    >
      <AppSection id="curationReviewProcess" title="Plans for Ongoing Review and Reanalysis and Discrepancy Resolution">
        <VcepOngoingPlansForm @update="handleUpdate" />
      </AppSection>

      <AppSection id="evidenceSummaries" title="Example Evidence Summaries">
        <EvidenceSummaryList />
      </AppSection>

      <AppSection id="designations" title="Member Designation">
        <MemberDesignationForm ref="designationForm" />
      </AppSection>
    </ApplicationStep>
  </div>
</template>
