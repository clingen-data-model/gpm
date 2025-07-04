<script>
import api from '@/http/api'
import is_validation_error from '../../http/is_validation_error'

export default {
    name: 'ReanalysisForm',
    props: {
        disabled: {
            type: Boolean,
            required: false,
            default: false
        }
    },
    emits: [
        'update'
    ],
    data() {
        return {
            errors: {},
            otherCheckbox: false
        }
    },
    computed: {
        group: {
            get () {
                return this.$store.getters['groups/currentItemOrNew'];
            }, 
            set (value) {
                this.$store.commit('groups/addItem', value)
            }
        }
    },
    watch: {
        otherCheckbox (to) {
            if (!to) {
                this.group.expert_panel.reanalysis_other = null;
            }
        }
    },
    methods: {
        async save () {
            try {
                this.errors = {};
                await api.post(`/api/groups/${this.group.uuid}/application/attestations/reanalysis`, this.group.expert_panel)
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors;
                }
            }
        },
        checkCompleteness () {
			const checkedStandard =
				this.group.expert_panel.reanalysis_conflicting &&
				this.group.expert_panel.reanalysis_review_lp &&
				this.group.expert_panel.reanalysis_review_lb;

			const hasOtherExplanation =
				this.otherCheckbox &&				
				this.group.expert_panel.reanalysis_other.trim().length >= 5;

			if (
				(checkedStandard || hasOtherExplanation) &&
				this.group.expert_panel.gcep_attestation_date === null
			) {
				this.group.expert_panel.reanalysis_attestation_date = new Date();
				return;
			}

			this.group.expert_panel.reanalysis_attestation_date = null;
		}
    }
}
</script>
<template>
	<div>
		<p>
			Expert Panels are expected to keep their variant interpretations up-to-date and to expedite the re-review of variants that have a conflicting assertion submitted to ClinVar after the Expert Panel submission. Please check all 3 boxes below to attest that the VCEP will follow the ClinGen-approved schedule described below <strong><em>or</em></strong> describe other plans at the bottom of the section.
		</p>
		<ul class="ml-4 mt-2">
			<li>
				<input-row :errors="errors.reanalysis_conflicting" :hide-label="true">
					<checkbox v-model="group.expert_panel.reanalysis_conflicting" :disabled="disabled" @update:model-value="$emit('update'), checkCompleteness()">
						VCEPs are expected to reassess any newly submitted conflicting assertion in ClinVar from a one star submitter or above and attempt to resolve or address the conflict within 6 months of being notified about the conflict from ClinGen. Please reach out to the submitter if you need additional information about the conflicting assertion.
					</checkbox>
				</input-row>
			</li>
			<li>
				<input-row :errors="errors.reanalysis_review_lp" :hide-label="true">
					<checkbox v-model="group.expert_panel.reanalysis_review_lp" :disabled="disabled" @update:model-value="$emit('update'), checkCompleteness()">
						VCEPs are expected to re-review all LP and VUS classifications made by the EP at least every 2 years to see if new evidence has emerged to re-classify the variants
					</checkbox>
				</input-row>
			</li>
			<li>
				<input-row :errors="errors.reanalysis_review_lb" :hide-label="true">
					<checkbox v-model="group.expert_panel.reanalysis_review_lb" :disabled="disabled" @update:model-value="$emit('update'), checkCompleteness()">
						VCEPs are expected to re-review any LB classifications when new evidence is available or when requested by the public via the ClinGen website.
					</checkbox>
				</input-row>
			</li>
			<li>
				<input-row :errors="errors.reanalysis_other" :hide-label="true">
					<checkbox v-model="otherCheckbox" :disabled="disabled" @update:model-value="$emit('update'), checkCompleteness()">
						Plans differ from the expectations above.
					</checkbox>
					<transition name="slide-fade-down">
						<div v-if="otherCheckbox" class="ml-4">
						<label for="reanalysis-other-textarea">Explain differences:</label>
						<textarea 
							id="reanalysis-other-textarea" 
							v-model="group.expert_panel.reanalysis_other"
							class="w-full"
							:disabled="disabled"
							@update:model-value="$emit('update'), checkCompleteness()"
						></textarea>
						</div>
					</transition>
				</input-row>
			</li>
		</ul>
	</div>
</template>