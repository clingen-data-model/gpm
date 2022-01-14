<template>
    <div>
        <input-row :errors="errors.specification_progress" vertical>
            <template v-slot:label>
                Please indicate whether you have made any changes or additions to your ACMG/AMP specifications for the gene(s) of interest, including evidence and rationale to support the rule specifications.
            </template>
            <div class="ml-4">
                <radio-button v-model="workingCopy.specification_progress" value="yes">Yes</radio-button>
                <transition name="slide-fade-down">
                    <input-row 
                        label="Link to approved specification in Cspec" 
                        :errors="errors.specification_url" 
                        vertical
                        class="ml-4"
                        v-if="workingCopy.specification_progress == 'yes'"
                        v-model="workingCopy.specification_url"
                    >
                    </input-row>
                </transition>
                
                <radio-button v-model="workingCopy.specification_progress" value="no">No</radio-button>
            </div>
        </input-row>
    </div>
</template>
<script>
import setupWorkingMirror from '@/composables/setup_working_mirror'

export default {
    name: 'ComponentName',
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
    data() {
        return {
        }
    },
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew'];
        },
    },
    setup(props, context) {
        const {workingCopy} = setupWorkingMirror(props, context);
        console.log(workingCopy)
        return {
            workingCopy
        }
    }
}
</script>