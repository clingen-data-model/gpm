<script>
import {debounce} from 'lodash-es'

import {errors} from '@/forms/form_factory'
import {isValidationError} from '@/http'

import ApplicationSection from '@/components/expert_panels/ApplicationSection.vue'
import ApplicationStep from '@/components/expert_panels/ApplicationStep.vue'
import AttestationGcep from '@/components/expert_panels/AttestationGcep.vue'
import AttestationNhgri from '@/components/expert_panels/AttestationNhgri.vue'
import GcepGeneList from '@/components/expert_panels/GcepGeneList.vue';
import GroupForm from '@/components/groups/GroupForm.vue'
import MemberList from '@/components/groups/MemberList.vue';
import GroupDescriptionForm from '@/components/groups/GroupDescriptionForm.vue';
import ScopeDescriptionForm from '@/components/expert_panels/ScopeDescriptionForm.vue';
import GcepOngoingPlansForm from '@/components/expert_panels/GcepOngoingPlansForm.vue';

export default {
    name: 'ApplicationGcep',
    components: {
        AppSection: ApplicationSection,
        ApplicationStep,
        AttestationGcep,
        AttestationNhgri,
        GcepGeneList,
        GroupForm,
        GcepOngoingPlansForm,
        MemberList,
        GroupDescriptionForm,
        ScopeDescriptionForm,
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
            promises.push(this.saveWebDescription());
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
        saveWebDescription() {
            if (this.webDescriptionIsDirty()) {
              return this.$store.dispatch('groups/descriptionUpdate', {
                uuid: this.group.uuid,
                description: this.group.description
              })
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
        webDescriptionIsDirty () {
            return  this.group.isDirty('description')
        },
        handleUpdate () {
            this.debounceAutoSave();
        }

    },
}
</script>
<template>
  <div class="relative">
    <ApplicationStep id="definition" :disabled="group.expert_panel.hasPendingSubmission">
      <AppSection id="basicInfo" title="Basic Information">
        <GroupForm
          ref="groupForm" :group="group"
          @update="handleUpdate"
        />
      </AppSection>
      <AppSection v-if="group" id="membership" title="Membership">
        <p>
          Expert Panels are expected to have broad representation of expertise in the field, including all major areas of expertise (clinical, diagnostic laboratory, and basic research).  Membership should include representation from three or more institutions and will encompass disease/gene expert members as well as biocurators. Biocurators do not have to be gene/disease experts and will be primarily responsible for assembling the available evidence for subsequent expert member review. For role, suggested examples include: primary biocurator, expert reviewer, etc.
        </p>
        <MemberList :group="group" />
      </AppSection>
      <AppSection id="websiteDescription" title="Website Description">
        <GroupDescriptionForm @update="handleUpdate" />
      </AppSection>
      <AppSection id="scope" title="Scope of Work">
        <p>
          It is expected that the expert panel will utilize
          <lumping-and-splitting-link />
          during pre-curation and should use the
          <gene-tracker-link />
          to enter their precuration information. Focus should be on the canonical disease, and splitting into multiple phenotypes should be avoided. The precurations will be published to
          <website-link />.
        </p>

        <GcepGeneList
          ref="geneList"
          :group="group"
          @geneschanged="genesChanged = true"
          @update="handleUpdate"
        />

        <hr>

        <ScopeDescriptionForm @update="handleUpdate" />
      </AppSection>

      <AppSection id="attestations" title="Attestations">
        <AttestationGcep @update="handleUpdate" />
      </AppSection>

      <AppSection id="curationReviewProcess" title="Plans for Ongoing Gene Review and Reanalysis and Discrepancy Resolution">
        <GcepOngoingPlansForm @update="handleUpdate" />
      </AppSection>

      <AppSection id="nhgri" title="NHGRI Data Availability">
        <AttestationNhgri @update="handleUpdate" />
      </AppSection>
    </ApplicationStep>
  </div>
</template>
