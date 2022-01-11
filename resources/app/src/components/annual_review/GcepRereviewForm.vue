<template>
    <div>
        <p>Gene Curation Expert Panels are expected to review current clinical validity classifications for their approved genes based on the guidance provided in the <a href="https://clinicalgenome.org/site/assets/files/2164/clingen_standard_gene-disease_validity_recuration_procedures_v1.pdf">Gene-Disease Validity Recuration Process document</a>. Please indicate the following:</p>

        <input-row label="The GCEP recuration designee(s) is designated to monitor for recuration updates, on a yearly basis, according to the Gene-Disease Validity Recuration protocol (link above). Please list the name(s) of your GCEP recuration designee(s)." :errors="errors.recuration_designees" vertical>
            <textarea class="w-full" rows="5" v-model="workingCopy.recuration_designees"></textarea>
        </input-row>

        <input-row label="Have you begun recuration?" :errors="errors.recuration_begun" vertical>
            <radio-button v-model="workingCopy.recuration_begun" :value="1" label="Yes"></radio-button>
            <radio-button v-model="workingCopy.recuration_begun" :value="0" label="No"></radio-button>
        </input-row>
    </div>
</template>
<script>
import setupWorkingMirror from '@/composables/setup_working_mirror';

export default {
    name: 'GcepRereviewForm',
    props: {
        modelValue: {
            type: Object,
            required: true
        },
        errors: {
            type: Object,
            required: true
        }
    },
    emits: [
        'update:modelValue'
    ],
    setup(props, context) {
        const {workingCopy} = setupWorkingMirror(props, context);
        return {
            workingCopy
        };
    }
}
</script>