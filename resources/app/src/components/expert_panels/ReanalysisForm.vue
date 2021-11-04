<template>
    <div>
        <h3>Reanalysis &amp; Discrepency Resolution</h3>
        <input-row :vertical="true">
            <p  slot:label>
                Expert Panels are expected to keep their variant interpretations up-to-date and to expedite the re-review of variants that have a conflicting assertion submitted to ClinVar after the Expert Panel submission. Please check all 3 boxes below to attest that the VCEP will follow the ClinGen-approved schedule described below <strong><em>or</em></strong> describe other plans at the bottom of the section.
            </p>
            <ul class="ml-8 mt-2">
                <li class="flex space-x-2 mb-2">
                    <div class="mt-1"><input type="checkbox" v-model="attestationCheckboxes.att1" value="" id="att-1"></div>
                    <label for="att-1">
                        VCEPs are expected to reassess any newly submitted conflicting assertion in ClinVar from a one star submitter or above and attempt to resolve or address the conflict within 6 months of being notified about the conflict from ClinGen. Please reach out to the submitter if you need additional information about the conflicting assertion.
                    </label>
                </li>
                <li class="flex space-x-2 mb-2">
                    <div class="mt-1"><input type="checkbox" v-model="attestationCheckboxes.att2" value="" id="att-2"></div>
                    <label for="att-2">
                        VCEPs are expected to re-review all LP and VUS classifications made by the EP at least every 2 years to see if new evidence has emerged to re-classify the variants
                    </label>
                </li>
                <li class="flex space-x-2 mb-2">
                    <div class="mt-1"><input type="checkbox" v-model="attestationCheckboxes.att3" value="" id="att-3"></div>
                    <label for="att-3">
                        VCEPs are expected to re-review any LB classifications when new evidence is available or when requested by the public via the ClinGen website.
                    </label>
                </li>
                <li>
                    <div class="flex space-x-2 mb-2">
                        <div class="mt-1"><input type="checkbox" v-model="attestationCheckboxes.att4" value="" id="att-4"></div>
                        <label for="att-4">
                            Check box if plans differ from the expectations above.
                        </label>
                    </div>
                    <transition name="slide-fade-down">
                        <input-row label="Describe differences" :vertical="true" v-if="attestationCheckboxes.att4">
                            <textarea v-model="differences" class="w-full"></textarea>
                        </input-row>
                    </transition>
                </li>
            </ul>
        </input-row>
    </div>
</template>
<script>
export default {
    name: 'ReanalysisForm',
    props: {
        
    },
    data() {
        return {
            attestationCheckboxes: {
                att1: null,
                att2: null,
                att3: null,
                att4: null
            },
            differences: null
        }
    },
    computed: {

    },
    methods: {

    }
}
</script>